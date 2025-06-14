<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$settings = [
    'site_name' => 'Studio',
    'admin_email' => 'admin@example.com',
    'timezone' => 'UTC',
    'debug_mode' => true, 
];

if (in_array($settings['timezone'], timezone_identifiers_list())) {
    date_default_timezone_set($settings['timezone']);
} else {
    date_default_timezone_set('UTC'); 
}
function get_setting($key, $settings) {
    return isset($settings[$key]) ? $settings[$key] : null;
}
if ($settings['debug_mode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
if ($settings['debug_mode'] && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    echo '<pre>';
    print_r($settings);
    echo '</pre>';
}
?>