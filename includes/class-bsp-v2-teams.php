<?php
/**
 * Team Badges Management
 * Stores and retrieves team badge URLs from API-Football
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Teams {
    
    /**
     * Build badge URL from team ID
     */
    public static function get_badge_url($team_id) {
        if (empty($team_id)) {
            return '';
        }
        
        return 'https://media.api-sports.io/football/teams/' . intval($team_id) . '.png';
    }
    
    /**
     * Save team badge URL to database
     */
    public static function save_badge($team_id, $team_name = '') {
        if (empty($team_id)) {
            return false;
        }
        
        $badge_url = self::get_badge_url($team_id);
        
        // Store in options with team_id as key for easy retrieval
        $badges = get_option('bsp_v2_team_badges', []);
        $badges[$team_id] = [
            'url' => $badge_url,
            'name' => sanitize_text_field($team_name),
            'saved_at' => current_time('mysql')
        ];
        
        update_option('bsp_v2_team_badges', $badges);
        bsp_v2_log_debug('Badge saved to database', ['team_id' => $team_id, 'name' => $team_name]);
        
        return $badge_url;
    }
    
    /**
     * Get badge URL from database
     */
    public static function get_stored_badge($team_id) {
        if (empty($team_id)) {
            return '';
        }
        
        $badges = get_option('bsp_v2_team_badges', []);
        
        if (isset($badges[$team_id])) {
            return $badges[$team_id]['url'];
        }
        
        // Not in database, construct it
        return self::get_badge_url($team_id);
    }
    
    /**
     * Get all stored badges
     */
    public static function get_all_badges() {
        return get_option('bsp_v2_team_badges', []);
    }
    
    /**
     * Clear all stored badges (for uninstall)
     */
    public static function clear_all_badges() {
        delete_option('bsp_v2_team_badges');
        bsp_v2_log('All team badges cleared from database');
    }
}
