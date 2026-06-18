    </main>
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <img src="assets/images/logo.png" alt="Lost & Found" class="footer-logo">
                    <p style="color: #94a3b8; line-height: 1.8;"><?php echo __('footer_desc'); ?></p>
                </div>
                <div class="footer-section">
                    <h4><?php echo __('platform'); ?></h4>
                    <ul class="footer-links">
                        <li><a href="post-item.php?type=lost"><?php echo __('nav_report_lost'); ?></a></li>
                        <li><a href="post-item.php?type=found"><?php echo __('nav_report_found'); ?></a></li>
                        <li><a href="browse.php"><?php echo __('nav_browse'); ?></a></li>
                        <li><a href="dashboard.php"><?php echo __('nav_dashboard'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php echo __('company'); ?></h4>
                    <ul class="footer-links">
                        <li><a href="#"><?php echo __('about_us'); ?></a></li>
                        <li><a href="#"><?php echo __('contact'); ?></a></li>
                        <li><a href="#"><?php echo __('privacy_policy'); ?></a></li>
                        <li><a href="#"><?php echo __('terms_of_service'); ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php echo __('contact_us'); ?></h4>
                    <p style="color: #94a3b8; font-size: 0.95rem; margin-bottom: 10px;"><i class="fas fa-envelope"></i> support@lostfound.com</p>
                    <p style="color: #94a3b8; font-size: 0.95rem;"><i class="fas fa-phone"></i> +880 1234 567890</p>
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <a href="#" style="color: #94a3b8;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: #94a3b8;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: #94a3b8;"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Lost & Found UIU Project. All rights reserved.</p>
                <div style="display: flex; gap: 20px;">
                    <a href="#" style="color: #64748b; text-decoration: none;"><?php echo __('privacy'); ?></a>
                    <a href="#" style="color: #64748b; text-decoration: none;"><?php echo __('terms'); ?></a>
                    <a href="#" style="color: #64748b; text-decoration: none;"><?php echo __('cookies'); ?></a>
                </div>
            </div>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>
