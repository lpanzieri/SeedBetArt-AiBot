<?php
/**
 * AI Insights management
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Insights {
    
    /**
     * Get user insights
     */
    public static function get_user($user_id, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bsp_v2_insights';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results ?: [];
    }
    
    /**
     * Create insight
     */
    public static function create($user_id, $data = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bsp_v2_insights';
        
        $insert = [
            'user_id' => $user_id,
            'insight_type' => $data['type'] ?? 'general',
            'match_info' => wp_json_encode($data['match_info'] ?? []),
            'market' => $data['market'] ?? '',
            'side' => $data['side'] ?? '',
            'odds' => $data['odds'] ?? 0,
            'confidence' => $data['confidence'] ?? 50,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ];
        
        $inserted = $wpdb->insert($table, $insert);
        
        if ($inserted) {
            bsp_v2_log('✓ Insight created', ['insight_id' => $wpdb->insert_id, 'user_id' => $user_id]);
            return $wpdb->insert_id;
        }
        
        bsp_v2_log_error('Failed to create insight', ['user_id' => $user_id]);
        return false;
    }
    
    /**
     * Update insight
     */
    public static function update($insight_id, $data = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bsp_v2_insights';
        
        $update = [];
        if (isset($data['status'])) $update['status'] = $data['status'];
        if (isset($data['confidence'])) $update['confidence'] = $data['confidence'];
        
        return $wpdb->update($table, $update, ['id' => $insight_id]);
    }
    
    /**
     * Delete insight
     */
    public static function delete($insight_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bsp_v2_insights';
        
        return $wpdb->delete($table, ['id' => $insight_id]);
    }
}
