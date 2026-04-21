<?php
/**
 * OpenAI integration
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_OpenAI {
    
    private $api_key;
    private $model;
    
    public function __construct($user_id = null) {
        $this->api_key = bsp_v2_option('api_key_openai');
        
        if (empty($this->api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $this->model = bsp_v2_normalize_openai_model(bsp_v2_option('openai_model'));
    }
    
    /**
     * Generate bet explanation
     */
    public function explain_bet($match_info, $bet_type, $odds) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_key', 'OpenAI API key not configured');
        }
        
        $prompt = $this->build_explanation_prompt($match_info, $bet_type, $odds);
        
        return $this->call_api($prompt);
    }
    
    /**
     * Generate match analysis
     */
    public function analyze_match($match_info) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_key', 'OpenAI API key not configured');
        }
        
        $prompt = $this->build_analysis_prompt($match_info);
        
        return $this->call_api($prompt);
    }
    
    /**
     * Call OpenAI API
     */
    private function call_api($prompt) {
        // Check token limit before making request
        if (!bsp_v2_check_openai_limit()) {
            return new WP_Error('openai_limit_exceeded', 'OpenAI monthly token limit exceeded');
        }
        
        $url = BSP_V2_OPENAI_API_BASE . '/responses';
        
        $body = wp_json_encode([
            'model' => $this->model,
            'instructions' => 'You are an expert sports betting analyst. Provide clear, concise analysis.',
            'input' => $prompt,
            'max_output_tokens' => 500,
            'store' => false,
        ]);
        
        $response = wp_remote_post($url, [
            'headers' => [
                'User-Agent' => 'SeedBetArt-Ai-Bot/0.2',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => $body,
            'timeout' => 15,
        ]);
        
        if (is_wp_error($response)) {
            bsp_v2_log_error('OpenAI API error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if (!in_array($status, [200, 201], true)) {
            bsp_v2_log_error('OpenAI API returned non-200 response', ['status' => $status, 'body' => substr($response_body, 0, 200)]);
            return new WP_Error('api_error', bsp_v2_get_openai_error_message($status, $response_body, $this->model));
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            bsp_v2_log_error('OpenAI returned invalid JSON', ['error' => json_last_error_msg()]);
            return new WP_Error('invalid_response', 'Invalid JSON returned from OpenAI');
        }
        
        $response_text = bsp_v2_extract_openai_response_text($data);

        if ($response_text === '') {
            bsp_v2_log_error('Empty response from OpenAI');
            return new WP_Error('empty_response', 'Empty response from OpenAI');
        }
        
        // Track token usage
        $tokens_used = bsp_v2_extract_openai_total_tokens($data);
        if ($tokens_used > 0) {
            bsp_v2_track_openai_tokens($tokens_used);
        }
        
        bsp_v2_log_debug('OpenAI analysis generated', ['tokens' => $tokens_used]);
        
        return $response_text;
    }
    
    /**
     * Build explanation prompt
     */
    private function build_explanation_prompt($match_info, $bet_type, $odds) {
        $match_str = wp_json_encode($match_info);
        
        return "Explain why this is a good $bet_type bet with odds $odds for: $match_str";
    }
    
    /**
     * Build analysis prompt
     */
    private function build_analysis_prompt($match_info) {
        $match_str = wp_json_encode($match_info);
        
        return "Analyze this football match and provide key betting insights: $match_str";
    }
    
    /**
     * Get the current model being used
     */
    public function get_model() {
        return $this->model;
    }
}
