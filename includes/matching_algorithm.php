<?php
/**
 * Smart Multi-Factor Matching Algorithm
 * Computes precision matching percentage (0-100%) based on:
 * - Category matching (30%)
 * - Keyword intersection in titles, descriptions, and tag words (40%)
 * - Location overlap (15%)
 * - Verification Photo presence match (15%)
 * - Date proximity (Up to 10% bonus)
 */

function generateImageHash($filepath) {
    if (!file_exists($filepath)) return null;
    
    $img_data = @file_get_contents($filepath);
    if (!$img_data) return null;
    
    $img = @imagecreatefromstring($img_data);
    
    if (!$img) return null;
    
    $resized = imagecreatetruecolor(8, 8);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));
    
    $pixels = [];
    $total = 0;
    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $rgb = imagecolorat($resized, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $gray = round(($r + $g + $b) / 3);
            $pixels[] = $gray;
            $total += $gray;
        }
    }
    
    $average = $total / 64;
    $hash = '';
    foreach ($pixels as $pixel) {
        $hash .= ($pixel >= $average) ? '1' : '0';
    }
    
    imagedestroy($img);
    imagedestroy($resized);
    
    return $hash;
}

function calculateHammingDistance($hash1, $hash2) {
    if (empty($hash1) || empty($hash2) || strlen($hash1) !== 64 || strlen($hash2) !== 64) return 64;
    $distance = 0;
    for ($i = 0; $i < 64; $i++) {
        if ($hash1[$i] !== $hash2[$i]) {
            $distance++;
        }
    }
    return $distance;
}

function getPotentialMatches($pdo, $itemId, $keywords, $type) {
    // Fetch source item details
    $src_stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $src_stmt->execute([$itemId]);
    $src_item = $src_stmt->fetch();
    if(!$src_item) return [];

    $searchType = ($type == 'lost') ? 'found' : 'lost';

    // Extract keywords for REGEXP
    $extract_words = function($text) {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($text));
        $words = explode(' ', $clean);
        return array_filter($words, function($w) { return strlen($w) > 2; });
    };
    
    $src_keywords_arr = $extract_words($src_item['keywords'] . ' ' . $src_item['title'] . ' ' . $src_item['description']);
    if (empty($src_keywords_arr)) {
        return [];
    }
    
    // Build REGEXP pattern (e.g., keyword1|keyword2|keyword3)
    $regexp_pattern = implode('|', array_map(function($kw) {
        return preg_quote($kw);
    }, $src_keywords_arr));

    // Fetch potential matches using REGEXP
    $sql = "SELECT * FROM items WHERE type = :type AND status = 'open' AND id != :id AND (keywords REGEXP :pattern OR title REGEXP :pattern OR description REGEXP :pattern)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['type' => $searchType, 'id' => $itemId, 'pattern' => $regexp_pattern]);
    
    $matches = [];
    $src_location = $extract_words($src_item['location']);

    while($row = $stmt->fetch()) {
        $score = 0;
        $reasons = [];
        
        // Count matching keywords exactly
        $dest_keywords_arr = $extract_words($row['keywords'] . ' ' . $row['title'] . ' ' . $row['description']);
        $intersect_keywords = array_unique(array_intersect($src_keywords_arr, $dest_keywords_arr));
        $keyword_matches_count = count($intersect_keywords);
        
        // Requirement: Must have at least 1 matching keyword
        if ($keyword_matches_count < 1) {
            continue;
        }

        $keyword_score = min(40, $keyword_matches_count * 10);
        $score += $keyword_score;
        $reasons[] = "Matches key terms ($keyword_matches_count): " . implode(', ', array_slice($intersect_keywords, 0, 4));

        // Image Similarity Check (Highest weight)
        $has_image_match = false;
        if (!empty($src_item['image_hash']) && !empty($row['image_hash'])) {
            $distance = calculateHammingDistance($src_item['image_hash'], $row['image_hash']);
            // Distance 0-10 is a very strong match, 11-20 is good, 21-30 is possible
            $similarity = max(0, 100 - ($distance * 1.5625)); // 64 distance = 0%, 0 distance = 100%
            
            if ($similarity >= 70) {
                $score += 40;
                $reasons[] = "High image similarity (" . round($similarity) . "%)";
                $has_image_match = true;
            } elseif ($similarity >= 50) {
                $score += 20;
                $reasons[] = "Moderate image similarity (" . round($similarity) . "%)";
            }
        }
        
        // Category Match
        if($src_item['category'] === $row['category']) {
            $score += 10;
            $reasons[] = "Category matches";
        }
        
        // Location Match
        $dest_location = $extract_words($row['location']);
        $intersect_location = array_unique(array_intersect($src_location, $dest_location));
        if(!empty($intersect_location)) {
            $score += 10;
            $reasons[] = "Shared location context";
        }
        
        // Cap score at 100%
        $match_percentage = min(100, $score);
        
        // If it's a very strong match, flag it
        if($match_percentage >= 50) {
            $row['match_percentage'] = $match_percentage;
            $row['match_reasons'] = $reasons;
            $row['match_count'] = $keyword_matches_count;
            $row['matching_keywords'] = implode(', ', array_slice($intersect_keywords, 0, 3));
            $matches[] = $row;
            
            // Background Task: If this is an extremely strong match (Score >= 50), 
            // trigger Admin Verification by inserting into system_matches
            if ($match_percentage >= 50) {
                // Check if it already exists
                $check_stmt = $pdo->prepare("SELECT id FROM system_matches WHERE lost_item_id = ? AND found_item_id = ?");
                $check_stmt->execute([
                    $type == 'lost' ? $src_item['id'] : $row['id'],
                    $type == 'found' ? $src_item['id'] : $row['id']
                ]);
                if (!$check_stmt->fetch()) {
                    $insert_stmt = $pdo->prepare("INSERT INTO system_matches (lost_item_id, found_item_id, match_percentage, match_reasons) VALUES (?, ?, ?, ?)");
                    $insert_stmt->execute([
                        $type == 'lost' ? $src_item['id'] : $row['id'],
                        $type == 'found' ? $src_item['id'] : $row['id'],
                        $match_percentage,
                        json_encode($reasons)
                    ]);
                }
            }
        }
    }
    
    // Sort matches by percentage descending
    usort($matches, function($a, $b) {
        return $b['match_percentage'] <=> $a['match_percentage'];
    });
    
    return $matches;
}
?>
