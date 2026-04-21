<?php
/**
 * Cache management
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Cache {

    /**
     * Build the transient key used for cache metadata.
     */
    private static function get_updated_at_key($key) {
        return 'bsp_v2_' . $key . '_updated_at';
    }

    /**
     * Get option names that match a given prefix.
     */
    private static function get_option_names_by_prefix($prefix) {
        global $wpdb;

        $like = $wpdb->esc_like($prefix) . '%';
        $option_names = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));

        return is_array($option_names) ? $option_names : [];
    }

    /**
     * Get plugin transient keys without the wp_options storage prefixes.
     */
    private static function get_plugin_transient_keys() {
        $value_option_names = self::get_option_names_by_prefix('_transient_bsp_v2_');
        $timeout_option_names = self::get_option_names_by_prefix('_transient_timeout_bsp_v2_');
        $transient_keys = [];

        foreach (array_merge($value_option_names, $timeout_option_names) as $option_name) {
            if (str_starts_with($option_name, '_transient_timeout_')) {
                $transient_keys[] = substr($option_name, strlen('_transient_timeout_'));
                continue;
            }

            if (str_starts_with($option_name, '_transient_')) {
                $transient_keys[] = substr($option_name, strlen('_transient_'));
            }
        }

        return array_values(array_unique(array_filter($transient_keys)));
    }
    
    /**
     * Get cached value
     */
    public static function get($key) {
        return get_transient('bsp_v2_' . $key);
    }
    
    /**
     * Set cached value
     */
    public static function set($key, $value, $ttl = 3600) {
        set_transient('bsp_v2_' . $key, $value, $ttl);
        set_transient(self::get_updated_at_key($key), time(), $ttl);
    }
    
    /**
     * Delete cached value
     */
    public static function delete($key) {
        delete_transient('bsp_v2_' . $key);
        delete_transient(self::get_updated_at_key($key));
    }

    /**
     * Get the last successful update timestamp for a cache key.
     */
    public static function get_updated_at($key) {
        return intval(get_transient(self::get_updated_at_key($key)));
    }

    /**
     * Get the most recent update timestamp across multiple cache keys.
     */
    public static function get_most_recent_update($keys) {
        if (empty($keys) || !is_array($keys)) {
            return 0;
        }

        $timestamps = array_filter(array_map(function($key) {
            return self::get_updated_at($key);
        }, $keys));

        return !empty($timestamps) ? max($timestamps) : 0;
    }

    /**
     * Count currently stored plugin transients.
     */
    public static function count_cached_entries() {
        global $wpdb;

        $like = $wpdb->esc_like('_transient_bsp_v2_') . '%';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        )));
    }

    /**
     * Delete all plugin options stored in wp_options.
     */
    public static function delete_plugin_options() {
        $deleted = 0;

        foreach (self::get_option_names_by_prefix('bsp_v2_') as $option_name) {
            if (delete_option($option_name)) {
                $deleted++;
            }
        }

        return $deleted;
    }
    
    /**
     * Flush all cache
     */
    public static function flush() {
        $deleted = 0;

        foreach (self::get_plugin_transient_keys() as $transient_key) {
            $value_deleted = delete_transient($transient_key);
            $orphan_value_deleted = delete_option('_transient_' . $transient_key);
            $timeout_deleted = delete_option('_transient_timeout_' . $transient_key);

            if ($value_deleted || $orphan_value_deleted || $timeout_deleted) {
                $deleted++;
            }
        }

        return $deleted;
    }
    
    /**
     * Install database tables
     */
    public static function install_tables() {
        global $wpdb;
        
        ob_start();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        ob_end_clean();
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Insights table
        $table_insights = $wpdb->prefix . 'bsp_v2_insights';
        $sql_insights = "CREATE TABLE $table_insights (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            insight_type varchar(50),
            match_info longtext,
            market varchar(50),
            side varchar(50),
            odds decimal(10,2),
            confidence int(3),
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY insight_type (insight_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        ob_start();
        dbDelta($sql_insights);
        ob_end_clean();
        
        bsp_v2_log('✓ Database tables created/verified');
    }
    
    /**
     * Uninstall tables
     */
    public static function uninstall_tables() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bsp_v2_insights';
        $wpdb->query("DROP TABLE IF EXISTS $table");
        
        bsp_v2_log('✓ Database tables removed');
    }
}
