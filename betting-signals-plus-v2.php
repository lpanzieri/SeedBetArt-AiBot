<?php
/**
 * Plugin Name: SeedBetArt Ai Bot
 * Plugin URI: https://github.com/lpanzieri/BettingSignalsPlus
 * Description: Advanced betting analysis tool with value bets, LTD, Under2.5, and AI-powered insights. Clean rebuild from scratch.
 * Version: 0.2
 * Requires PHP: 8.2
 * Requires at least: 6.4
 * Author: Lello Panzieri
 * Author URI: https://github.com/lpanzieri
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bsp-v2
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('BSP_V2_VERSION', '0.2');
define('BSP_V2_DIR', plugin_dir_path(__FILE__));
define('BSP_V2_URL', plugin_dir_url(__FILE__));

// Debug mode
if (!defined('BSP_V2_DEBUG')) {
    define('BSP_V2_DEBUG', (defined('WP_DEBUG') && WP_DEBUG));
}

// Load helpers FIRST
require_once BSP_V2_DIR . 'includes/helpers.php';

// Load configuration
require_once BSP_V2_DIR . 'includes/config-constants.php';

// Load services
if (file_exists(BSP_V2_DIR . 'includes/services-settings-manager.php')) {
    require_once BSP_V2_DIR . 'includes/services-settings-manager.php';
}

// Load core classes
require_once BSP_V2_DIR . 'includes/class-bsp-v2-cache.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-client.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-logic.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-insights.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-openai.php';
require_once BSP_V2_DIR . 'includes/class-bsp-v2-teams.php';

bsp_v2_log('Core classes loaded');

// Load admin
require_once BSP_V2_DIR . 'includes/admin-main.php';
require_once BSP_V2_DIR . 'includes/admin-menu.php';
require_once BSP_V2_DIR . 'includes/admin-settings.php';
require_once BSP_V2_DIR . 'includes/admin-search-params.php';
require_once BSP_V2_DIR . 'includes/debug-endpoint.php';

// Load shortcodes
require_once BSP_V2_DIR . 'includes/shortcodes-bets.php';
require_once BSP_V2_DIR . 'includes/shortcodes-ai.php';
require_once BSP_V2_DIR . 'includes/widgets-bets.php';

// Load cron
require_once BSP_V2_DIR . 'includes/class-bsp-v2-cron.php';

// Initialize shortcodes and widgets
add_action('init', function() {
    BSP_V2_Shortcodes::register();
    BSP_V2_ShortcodesAI::register();
});

// Initialize on plugins_loaded hook
add_action('plugins_loaded', function() {
    try {
        BSP_V2_Admin::init();
    } catch (Throwable $e) {
        // Silently fail - admin is not critical for frontend
    }
    
    try {
        BSP_V2_Cron::init();
    } catch (Throwable $e) {
        // Silently fail - cron is not critical for initialization
    }
});

// Activation
register_activation_hook(__FILE__, function() {
    // Suppress any output during activation
    ob_start();
    
    if (version_compare(PHP_VERSION, '8.2', '<')) {
        ob_end_clean();
        wp_die('SeedBetArt Ai Bot requires PHP 8.2+. Current: ' . PHP_VERSION);
    }
    
    if (version_compare(get_bloginfo('version'), '6.4', '<')) {
        ob_end_clean();
        wp_die('SeedBetArt Ai Bot requires WordPress 6.4+. Current: ' . get_bloginfo('version'));
    }
    
    try {
        BSP_V2_Cache::install_tables();
        // Set transient to show activation notice
        set_transient('bsp_v2_activation_notice', true, HOUR_IN_SECONDS);
    } catch (Throwable $e) {
        ob_end_clean();
        wp_die('Database setup failed: ' . $e->getMessage());
    }
    
    try {
        BSP_V2_Cron::activate();
    } catch (Throwable $e) {
        // Silently fail - cron activation is not critical
    }
    
    ob_end_clean();
});

// Deactivation
register_deactivation_hook(__FILE__, function() {
    try {
        BSP_V2_Cron::deactivate();
    } catch (Throwable $e) {
        // Silently fail - deactivation is not critical
    }
});
