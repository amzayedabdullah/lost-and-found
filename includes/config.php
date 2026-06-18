<?php
/**
 * Database Configuration
 */

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lost_and_found_db');

/* Attempt to connect to MySQL database */
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Global functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function createNotification($pdo, $user_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
        return $stmt->execute(['user_id' => $user_id, 'message' => $message]);
    } catch (PDOException $e) {
        return false;
    }
}

session_start();

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

require_once __DIR__ . '/lang.php';

function __($key) {
    global $translations;
    $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}
?>
