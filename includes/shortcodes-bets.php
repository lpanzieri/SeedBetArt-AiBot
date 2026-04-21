<?php
/**
 * Betting shortcodes with enhanced UI
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Shortcodes {

    private static $registered = false;

    private static function get_shortcode_tags() {
        return ['bsp_v2_value_bets', 'bsp_v2_ltd', 'bsp_v2_under25'];
    }

    private static function query_contains_shortcode() {
        global $post, $wp_query;

        $posts = [];

        if (isset($wp_query->posts) && is_array($wp_query->posts)) {
            $posts = $wp_query->posts;
        }

        if (empty($posts) && isset($post)) {
            $posts = [$post];
        }

        foreach ($posts as $candidate) {
            if (!is_object($candidate) || empty($candidate->post_content)) {
                continue;
            }

            $content = (string) $candidate->post_content;

            foreach (self::get_shortcode_tags() as $shortcode_tag) {
                if (has_shortcode($content, $shortcode_tag)) {
                    return true;
                }
            }
        }

        return false;
    }
    
    public static function register() {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        add_shortcode('bsp_v2_value_bets', [__CLASS__, 'value_bets']);
        add_shortcode('bsp_v2_ltd', [__CLASS__, 'ltd']);
        add_shortcode('bsp_v2_under25', [__CLASS__, 'under25']);
        
        // Load shared frontend styles early; interactive JS is enqueued only when a shortcode renders.
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        if (!self::query_contains_shortcode()) {
            return;
        }

        bsp_v2_enqueue_frontend_assets(false);
    }

    private static function enqueue_interactive_assets() {
        bsp_v2_enqueue_frontend_assets(true);
    }
    
    /**
     * Value bets shortcode
     */
    public static function value_bets($atts) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                return self::render_error('Value Bets', 'Odds-API and Football-API must be validated in plugin settings before using this feature.');
            }
            
            bsp_v2_log('Value bets shortcode called');
            
            $logic = new BSP_V2_Logic();
            $bets = $logic->get_value_bets('football', 10);
            
            if (is_wp_error($bets)) {
                return self::render_error('Value bets', $bets->get_error_message());
            }
            
            if (empty($bets)) {
                return self::render_empty_state('Value Bets');
            }

            self::enqueue_interactive_assets();
            
            ob_start();
            include BSP_V2_DIR . 'templates/template-value-bets.php';
            return ob_get_clean();
            
        } catch (Throwable $e) {
            bsp_v2_log_error('Value bets shortcode error: ' . $e->getMessage());
            return self::render_error('Value bets', $e->getMessage());
        }
    }
    
    /**
     * LTD shortcode
     */
    public static function ltd($atts) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                return self::render_error('Lay The Draw', 'Odds-API and Football-API must be validated in plugin settings before using this feature.');
            }
            
            bsp_v2_log('LTD shortcode called');
            
            $logic = new BSP_V2_Logic();
            $suggestions = $logic->get_ltd_suggestions('football', 10);
            
            if (is_wp_error($suggestions)) {
                return self::render_error('Lay The Draw', $suggestions->get_error_message());
            }
            
            if (empty($suggestions)) {
                return self::render_empty_state('Lay The Draw');
            }

            self::enqueue_interactive_assets();
            
            $bets = $suggestions;
            ob_start();
            include BSP_V2_DIR . 'templates/template-ltd.php';
            return ob_get_clean();
            
        } catch (Throwable $e) {
            bsp_v2_log_error('LTD shortcode error: ' . $e->getMessage());
            return self::render_error('Lay The Draw', $e->getMessage());
        }
    }
    
    /**
     * Under 2.5 shortcode
     */
    public static function under25($atts) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                return self::render_error('Under 2.5', 'Odds-API and Football-API must be validated in plugin settings before using this feature.');
            }
            
            bsp_v2_log('Under 2.5 shortcode called');
            
            $logic = new BSP_V2_Logic();
            $suggestions = $logic->get_under_25_suggestions('football', 10);
            
            if (is_wp_error($suggestions)) {
                return self::render_error('Under 2.5', $suggestions->get_error_message());
            }
            
            if (empty($suggestions)) {
                return self::render_empty_state('Under 2.5 Goals');
            }

            self::enqueue_interactive_assets();
            
            $bets = $suggestions;
            ob_start();
            include BSP_V2_DIR . 'templates/template-under25.php';
            return ob_get_clean();
            
        } catch (Throwable $e) {
            bsp_v2_log_error('Under 2.5 shortcode error: ' . $e->getMessage());
            return self::render_error('Under 2.5', $e->getMessage());
        }
    }
    
    /**
     * Render error state
     */
    private static function render_error($title, $message) {
        return sprintf(
            '<div class="bsp-v2-widget"><p class="bsp-v2-alert bsp-v2-alert-error"><strong>⚠️ %s Error:</strong> %s</p></div>',
            esc_html($title),
            esc_html($message)
        );
    }
    
    /**
     * Render empty state
     */
    private static function render_empty_state($title) {
        return sprintf(
            '<div class="bsp-v2-widget">
                <div class="bsp-v2-empty-state">
                    <p>📭 No %s currently available</p>
                    <p class="bsp-v2-text-muted">Check back later for updated recommendations</p>
                </div>
            </div>',
            esc_html(strtolower($title))
        );
    }
}
