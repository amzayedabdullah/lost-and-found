<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero animate-fade">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="badge-hero">
                    <span>🇧🇩</span> <?php echo __('hero_badge'); ?>
                </div>
                <h1><?php echo __('hero_title'); ?> <br><span><?php echo __('hero_title_span'); ?></span></h1>
                <p><?php echo __('hero_subtitle'); ?></p>
                
                <div class="hero-btns">
                    <a href="post-item.php?type=lost" class="btn btn-primary"><?php echo __('btn_report_lost'); ?> <i class="fas fa-arrow-right"></i></a>
                    <a href="post-item.php?type=found" class="btn btn-outline" style="background: white; border-color: #e2e8f0; color: #1e293b;"><?php echo __('btn_report_found'); ?></a>
                </div>

                <div class="hero-checks">
                    <span><i class="fas fa-check-circle"></i> <?php echo __('check_free'); ?></span>
                    <span><i class="fas fa-check-circle"></i> <?php echo __('check_no_money'); ?></span>
                </div>
            </div>
            <div class="hero-image-container">
                <img src="assets/images/hero_image.png" alt="Bangladesh Hope" class="hero-main-img">
                <div class="floating-stat">
                    <div class="floating-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h4>87%</h4>
                        <p><?php echo __('stat_success_rate'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="container">
    <div class="stats-grid">
        <div class="stat-card animate-fade">
            <h2>1,234+</h2>
            <p><?php echo __('stat_reunited'); ?></p>
        </div>
        <div class="stat-card animate-fade">
            <h2>5,678+</h2>
            <p><?php echo __('stat_users'); ?></p>
        </div>
        <div class="stat-card animate-fade">
            <h2>87%</h2>
            <p><?php echo __('stat_success_rate'); ?></p>
        </div>
        <div class="stat-card animate-fade">
            <h2><?php echo __('stat_days'); ?></h2>
            <p><?php echo __('stat_time'); ?></p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="container mt-100">
    <div class="section-title">
        <h2><?php echo __('how_title'); ?></h2>
        <p style="color: #64748b; max-width: 600px; margin: 0 auto;"><?php echo __('how_subtitle'); ?></p>
    </div>

    <div class="steps-container">
        <div class="step-card animate-fade">
            <div class="step-number">1</div>
            <h3><?php echo __('nav_post'); ?></h3>
            <p><?php echo __('step1_desc'); ?></p>
        </div>
        <div class="step-card animate-fade">
            <div class="step-number">2</div>
            <h3><?php echo __('feat3_title'); ?></h3>
            <p><?php echo __('step2_desc'); ?></p>
        </div>
        <div class="step-card animate-fade">
            <div class="step-number">3</div>
            <h3><?php echo __('step3_title'); ?></h3>
            <p><?php echo __('step3_desc'); ?></p>
        </div>
        <div class="step-card animate-fade">
            <div class="step-number">4</div>
            <h3><?php echo __('step4_title'); ?></h3>
            <p><?php echo __('step4_desc'); ?></p>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="container mt-100">
    <div class="section-title">
        <h2><?php echo __('why_title'); ?></h2>
        <p style="color: #64748b; max-width: 600px; margin: 0 auto;"><?php echo __('why_subtitle'); ?></p>
    </div>

    <div class="feature-grid">
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-box"><i class="fas fa-box"></i></div>
            <h3><?php echo __('feat1_title'); ?></h3>
            <p><?php echo __('feat1_desc'); ?></p>
        </div>
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-search"><i class="fas fa-search"></i></div>
            <h3><?php echo __('feat2_title'); ?></h3>
            <p><?php echo __('feat2_desc'); ?></p>
        </div>
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-bolt"><i class="fas fa-bolt"></i></div>
            <h3><?php echo __('feat3_title'); ?></h3>
            <p><?php echo __('feat3_desc'); ?></p>
        </div>
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-bell"><i class="fas fa-bell"></i></div>
            <h3><?php echo __('feat4_title'); ?></h3>
            <p><?php echo __('feat4_desc'); ?></p>
        </div>
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-shield"><i class="fas fa-shield-alt"></i></div>
            <h3><?php echo __('feat5_title'); ?></h3>
            <p><?php echo __('feat5_desc'); ?></p>
        </div>
        <div class="feature-card animate-fade">
            <div class="feature-icon icon-users"><i class="fas fa-users"></i></div>
            <h3><?php echo __('feat6_title'); ?></h3>
            <p><?php echo __('feat6_desc'); ?></p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="story-section">
    <div class="container">
        <div class="story-grid">
            <div class="story-image animate-fade">
                <img src="assets/images/story_image.png" alt="Bangladesh Culture">
            </div>
            <div class="story-content animate-fade">
                <h2><?php echo __('story1_title'); ?></h2>
                <p><?php echo __('story1_desc'); ?></p>
                
                <ul class="check-list">
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story1_li1'); ?></li>
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story1_li2'); ?></li>
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story1_li3'); ?></li>
                </ul>
            </div>
        </div>

        <div class="story-grid mt-100">
            <div class="story-content animate-fade">
                <h2><?php echo __('story2_title'); ?></h2>
                <p><?php echo __('story2_desc'); ?></p>
                
                <ul class="check-list">
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story2_li1'); ?></li>
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story2_li2'); ?></li>
                    <li><i class="fas fa-check-circle"></i> <?php echo __('story2_li3'); ?></li>
                </ul>
            </div>
            <div class="story-image animate-fade">
                <img src="assets/images/tech_image.png" alt="Smart Technology" style="box-shadow: 0 40px 80px rgba(0,0,0,0.08);">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section animate-fade">
    <div class="container">
        <h2><?php echo __('cta_title'); ?></h2>
        <p><?php echo __('cta_desc'); ?></p>
        <div class="hero-btns" style="justify-content: center;">
            <a href="register.php" class="btn btn-primary"><?php echo __('cta_btn1'); ?> <i class="fas fa-arrow-right"></i></a>
            <a href="browse.php" class="btn btn-outline" style="background: white; border-color: #e2e8f0; color: #1e293b;"><?php echo __('cta_btn2'); ?></a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
