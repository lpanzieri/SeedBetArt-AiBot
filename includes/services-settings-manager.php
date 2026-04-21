<?php
/**
 * Settings Manager Service
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_SettingsManager {
    
    public static function get_setting($key, $default = '') {
        return bsp_v2_option($key, $default);
    }
    
    public static function set_setting($key, $value) {
        return bsp_v2_set_option($key, $value);
    }
    
    public static function get_all_settings() {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            'bsp_v2_%'
        ));
        
        $settings = [];
        foreach ($results as $row) {
            $key = str_replace('bsp_v2_', '', $row->option_name);
            $settings[$key] = maybe_unserialize($row->option_value);
        }
        
        return $settings;
    }
}
