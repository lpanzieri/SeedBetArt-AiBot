<?php
/**
 * AI-powered shortcodes
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_ShortcodesAI {

    private static $registered = false;
    
    public static function register() {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        add_shortcode('bsp_v2_ai_analysis', [__CLASS__, 'ai_analysis']);
        add_shortcode('bsp_v2_ai_explain', [__CLASS__, 'ai_explain']);
    }
    
    /**
     * AI match analysis
     * Uses the OpenAI model configured in Settings
     */
    public static function ai_analysis($atts) {
        if (!bsp_v2_is_openai_api_validated()) {
            return '<p class="bsp-v2-alert bsp-v2-alert-error"><strong>AI features disabled:</strong> Validate the OpenAI API key in plugin settings.</p>';
        }
        
        if (!bsp_v2_ai_enabled()) {
            return '<p class="bsp-v2-alert bsp-v2-alert-warning">AI features not available. Please configure OpenAI API key.</p>';
        }
        
        try {
            $openai = new BSP_V2_OpenAI();
            
            // Get match data from shortcode attributes or query
            $match_info = [
                'home' => $atts['home'] ?? 'Team A',
                'away' => $atts['away'] ?? 'Team B',
                'league' => $atts['league'] ?? 'Premier League',
            ];
            
            $analysis = $openai->analyze_match($match_info);
            
            if (is_wp_error($analysis)) {
                return '<p class="bsp-v2-alert bsp-v2-alert-error">Analysis failed: ' . esc_html($analysis->get_error_message()) . '</p>';
            }
            
            return '<div class="bsp-v2-ai-widget">' .
                   '<h3>' . esc_html($match_info['home']) . ' vs ' . esc_html($match_info['away']) . '</h3>' .
                   '<div class="bsp-v2-analysis-item"><p>' . wp_kses_post($analysis) . '</p></div>' .
                   '</div>';
            
        } catch (Throwable $e) {
            bsp_v2_log_error('AI analysis error: ' . $e->getMessage());
            return '<p class="bsp-v2-alert bsp-v2-alert-error">Analysis error occurred</p>';
        }
    }
    
    /**
     * AI bet explanation
     * Uses the OpenAI model configured in Settings
     */
    public static function ai_explain($atts) {
        if (!bsp_v2_is_openai_api_validated()) {
            return '<p class="bsp-v2-alert bsp-v2-alert-error"><strong>AI features disabled:</strong> Validate the OpenAI API key in plugin settings.</p>';
        }
        
        if (!bsp_v2_ai_enabled()) {
            return '<p class="bsp-v2-alert bsp-v2-alert-warning">AI features not available</p>';
        }
        
        try {
            $openai = new BSP_V2_OpenAI();
            
            $match_info = [
                'home' => $atts['home'] ?? 'Team A',
                'away' => $atts['away'] ?? 'Team B',
            ];
            
            $explanation = $openai->explain_bet($match_info, $atts['type'] ?? 'value', $atts['odds'] ?? 1.5);
            
            if (is_wp_error($explanation)) {
                return '<p class="bsp-v2-alert bsp-v2-alert-error">' . esc_html($explanation->get_error_message()) . '</p>';
            }
            
            return '<div class="bsp-v2-ai-widget"><div class="bsp-v2-analysis-item">' . wp_kses_post($explanation) . '</div></div>';
            
        } catch (Throwable $e) {
            bsp_v2_log_error('Bet explanation error: ' . $e->getMessage());
            return '<p class="bsp-v2-alert bsp-v2-alert-error">Error generating explanation</p>';
        }
    }
}
