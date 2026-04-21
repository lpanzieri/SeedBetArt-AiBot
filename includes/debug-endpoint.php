<?php
/**
 * Debug Endpoint for SeedBetArt Ai Bot
 * 
 * Provides detailed diagnostics for API connectivity and configuration.
 * Only accessible to admin users.
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Debug {
    
    /**
     * Initialize debug endpoints
     */
    public static function init() {
        add_action('wp_ajax_bsp_v2_debug_test_apis', [__CLASS__, 'ajax_test_all_apis']);
        add_action('wp_ajax_bsp_v2_debug_test_odds', [__CLASS__, 'ajax_test_odds_api']);
        add_action('wp_ajax_bsp_v2_debug_test_football', [__CLASS__, 'ajax_test_football_api']);
        add_action('wp_ajax_bsp_v2_debug_test_openai', [__CLASS__, 'ajax_test_openai_api']);
    }
    
    /**
     * Test all APIs and return diagnostics
     */
    public static function ajax_test_all_apis() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $results = [
            'timestamp' => current_time('mysql'),
            'server_info' => self::get_server_info(),
            'config' => self::get_config_info(),
            'apis' => [
                'odds' => self::test_odds_api(),
                'football' => self::test_football_api(),
                'openai' => self::test_openai_api(),
            ],
            'logs' => self::get_recent_logs(),
        ];
        
        ob_end_clean();
        wp_send_json_success($results);
    }
    
    /**
     * Test Odds-API only
     */
    public static function ajax_test_odds_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        ob_end_clean();
        wp_send_json_success(self::test_odds_api());
    }
    
    /**
     * Test Football-API only
     */
    public static function ajax_test_football_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        ob_end_clean();
        wp_send_json_success(self::test_football_api());
    }
    
    /**
     * Test OpenAI API only
     */
    public static function ajax_test_openai_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        ob_end_clean();
        wp_send_json_success(self::test_openai_api());
    }
    
    /**
     * Get server information
     */
    private static function get_server_info() {
        return [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'debug_mode' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'ssl_verify' => apply_filters('https_local_ssl_verify', true),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];
    }
    
    /**
     * Get API configuration info
     */
    private static function get_config_info() {
        return [
            'odds_api_key' => [
                'configured' => !empty(bsp_v2_option('api_key_odds')),
                'key_length' => strlen(bsp_v2_option('api_key_odds') ?? ''),
                'first_chars' => substr(bsp_v2_option('api_key_odds') ?? '', 0, 5) . '...',
            ],
            'football_api_key' => [
                'configured' => !empty(bsp_v2_option('api_key_football')),
                'key_length' => strlen(bsp_v2_option('api_key_football') ?? ''),
                'first_chars' => substr(bsp_v2_option('api_key_football') ?? '', 0, 5) . '...',
            ],
            'openai_api_key' => [
                'configured' => !empty(bsp_v2_option('api_key_openai')),
                'key_length' => strlen(bsp_v2_option('api_key_openai') ?? ''),
                'first_chars' => substr(bsp_v2_option('api_key_openai') ?? '', 0, 5) . '...',
            ],
            'openai_model' => bsp_v2_normalize_openai_model(bsp_v2_option('openai_model')),
        ];
    }
    
    /**
     * Test Odds-API connectivity
     */
    private static function test_odds_api() {
        $api_key = bsp_v2_option('api_key_odds');
        
        if (empty($api_key)) {
            return [
                'status' => 'error',
                'message' => 'API key not configured',
                'endpoint' => BSP_V2_ODDS_API_BASE,
            ];
        }
        
        $url = BSP_V2_ODDS_API_BASE . '/events';
        $start_time = microtime(true);
        
        $response = wp_remote_get(add_query_arg([
            'apiKey' => $api_key,
            'sport' => 'football',
            'limit' => 1,
        ], $url), [
            'timeout' => 15,
            'sslverify' => apply_filters('https_local_ssl_verify', true),
            'headers' => [
                'User-Agent' => 'SeedBetArt-Ai-Bot/0.2',
                'Accept' => 'application/json'
            ],
        ]);
        
        $elapsed = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'endpoint' => $url,
                'message' => $response->get_error_message(),
                'response_time' => round($elapsed * 1000, 2) . 'ms',
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        return [
            'status' => $status_code === 200 ? 'success' : 'error',
            'endpoint' => $url,
            'http_code' => $status_code,
            'response_time' => round($elapsed * 1000, 2) . 'ms',
            'response_size' => strlen($body) . ' bytes',
            'content_type' => $headers['content-type'] ?? 'unknown',
            'body_preview' => substr($body, 0, 200),
            'is_json' => self::is_valid_json($body),
        ];
    }
    
    /**
     * Test Football-API connectivity
     */
    private static function test_football_api() {
        $api_key = bsp_v2_option('api_key_football');
        
        if (empty($api_key)) {
            return [
                'status' => 'error',
                'message' => 'API key not configured',
                'endpoint' => BSP_V2_FOOTBALL_API_BASE,
            ];
        }
        
        $url = BSP_V2_FOOTBALL_API_BASE . '/status';
        $start_time = microtime(true);
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'sslverify' => apply_filters('https_local_ssl_verify', true),
            'headers' => [
                'User-Agent' => 'SeedBetArt-Ai-Bot/0.2',
                'Accept' => 'application/json',
                'x-apisports-key' => $api_key,
            ],
        ]);
        
        $elapsed = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'endpoint' => $url,
                'message' => $response->get_error_message(),
                'response_time' => round($elapsed * 1000, 2) . 'ms',
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        return [
            'status' => $status_code === 200 ? 'success' : 'error',
            'endpoint' => $url,
            'http_code' => $status_code,
            'response_time' => round($elapsed * 1000, 2) . 'ms',
            'response_size' => strlen($body) . ' bytes',
            'content_type' => $headers['content-type'] ?? 'unknown',
            'body_preview' => substr($body, 0, 200),
            'is_json' => self::is_valid_json($body),
        ];
    }
    
    /**
     * Test OpenAI API connectivity
     */
    private static function test_openai_api() {
        $api_key = bsp_v2_option('api_key_openai');
        $model = bsp_v2_normalize_openai_model(bsp_v2_option('openai_model'));
        
        if (empty($api_key)) {
            return [
                'status' => 'error',
                'message' => 'API key not configured',
                'endpoint' => BSP_V2_OPENAI_API_BASE,
            ];
        }
        
        $url = BSP_V2_OPENAI_API_BASE . '/responses';
        $body_data = [
            'model' => $model,
            'instructions' => 'Reply with OK only.',
            'input' => 'Say OK',
            'max_output_tokens' => 16,
            'store' => false,
        ];
        
        $start_time = microtime(true);
        
        $response = wp_remote_post($url, [
            'timeout' => 15,
            'sslverify' => apply_filters('https_local_ssl_verify', true),
            'headers' => [
                'User-Agent' => 'SeedBetArt-Ai-Bot/0.2',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body_data),
        ]);
        
        $elapsed = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'endpoint' => $url,
                'model' => $model,
                'message' => $response->get_error_message(),
                'response_time' => round($elapsed * 1000, 2) . 'ms',
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        return [
            'status' => $status_code === 200 ? 'success' : 'error',
            'endpoint' => $url,
            'model' => $model,
            'http_code' => $status_code,
            'response_time' => round($elapsed * 1000, 2) . 'ms',
            'response_size' => strlen($body) . ' bytes',
            'content_type' => $headers['content-type'] ?? 'unknown',
            'body_preview' => substr($body, 0, 200),
            'is_json' => self::is_valid_json($body),
        ];
    }
    
    /**
     * Get recent log entries
     */
    private static function get_recent_logs() {
        $log_file = bsp_v2_get_log_file();
        
        if (!file_exists($log_file)) {
            return [
                'count' => 0,
                'message' => 'No log file created yet',
                'path' => $log_file,
            ];
        }
        
        $lines = file($log_file);
        $recent = array_slice($lines, -20);
        
        return [
            'count' => count($lines),
            'recent_entries' => count($recent),
            'path' => $log_file,
            'file_size' => filesize($log_file) . ' bytes',
            'last_entries' => $recent,
        ];
    }
    
    /**
     * Check if string is valid JSON
     */
    private static function is_valid_json($string) {
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

// Initialize on admin_init
add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        BSP_V2_Debug::init();
    }
});
