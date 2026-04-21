<?php
/**
 * SeedBetArt Ai Bot Uninstall
 * 
 * Handles plugin removal and cleanup
 */

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Define constants needed for cleanup
define('BSP_V2_DIR', plugin_dir_path(__FILE__));

// Load all required classes for cleanup
require_once BSP_V2_DIR . 'includes/helpers.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-teams.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-cache.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-cron.php';

// Drop all database tables
if (class_exists('BSP_V2_Cache')) {
    BSP_V2_Cache::uninstall_tables();
    BSP_V2_Cache::flush();
    BSP_V2_Cache::delete_plugin_options();
}

// Remove team badges from database
if (class_exists('BSP_V2_Teams')) {
    BSP_V2_Teams::clear_all_badges();
}

// Remove plugin log files
$log_dir = BSP_V2_DIR . 'logs';
if (is_dir($log_dir)) {
    $files = scandir($log_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'README.md') {
            $file_path = $log_dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($file_path)) {
                @unlink($file_path);
            }
        }
    }
}

// Remove scheduled events
if (class_exists('BSP_V2_Cron')) {
    foreach (BSP_V2_Cron::get_hook_names() as $hook_name) {
        wp_clear_scheduled_hook($hook_name);
    }
}
