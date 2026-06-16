<?php
require_once 'includes/config.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

if (in_array($lang, ['en', 'bn'])) {
    $_SESSION['lang'] = $lang;
}

$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: " . $referrer);
exit;
