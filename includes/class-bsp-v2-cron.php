<?php
/**
 * Cron job management
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Cron {
    
    private static $hook = 'bsp_v2_analysis_event';
    private static $recurrence = 'hourly';
    private static $cache_ttl = 3600;

    /**
     * Return all cron hooks owned by the plugin, including legacy names.
     */
    public static function get_hook_names() {
        return [
            self::$hook,
            'bsp_v2_hourly_analysis',
        ];
    }
    
    /**
     * Initialize cron
     */
    public static function init() {
        add_action(self::$hook, [__CLASS__, 'run_analysis']);
    }
    
    /**
     * Activate cron on plugin activation
     */
    public static function activate() {
        if (!wp_next_scheduled(self::$hook)) {
            wp_schedule_event(time(), self::$recurrence, self::$hook);
        }
    }
    
    /**
     * Deactivate cron on plugin deactivation
     */
    public static function deactivate() {
        foreach (self::get_hook_names() as $hook_name) {
            wp_clear_scheduled_hook($hook_name);
        }
    }

    /**
     * Persist a strategy result only when the analysis completed successfully.
     */
    private static function store_analysis_result($cache_key, $label, $result) {
        if (is_wp_error($result)) {
            $cached = BSP_V2_Cache::get($cache_key);
            $preserved_count = is_array($cached) ? count($cached) : 0;
            $status = is_array($cached) ? 'preserved' : 'unavailable';

            bsp_v2_log_error('Scheduled analysis failed for ' . $label . ': ' . $result->get_error_message(), [
                'cache_key' => $cache_key,
                'status' => $status,
                'preserved_count' => $preserved_count,
            ]);

            return [
                'status' => $status,
                'count' => $preserved_count,
            ];
        }

        if (!is_array($result)) {
            $cached = BSP_V2_Cache::get($cache_key);
            $preserved_count = is_array($cached) ? count($cached) : 0;
            $status = is_array($cached) ? 'preserved' : 'unavailable';

            bsp_v2_log_error('Scheduled analysis returned invalid data for ' . $label, [
                'cache_key' => $cache_key,
                'status' => $status,
                'result_type' => gettype($result),
                'preserved_count' => $preserved_count,
            ]);

            return [
                'status' => $status,
                'count' => $preserved_count,
            ];
        }

        BSP_V2_Cache::set($cache_key, $result, self::$cache_ttl);

        return [
            'status' => 'updated',
            'count' => count($result),
        ];
    }
    
    /**
     * Run analysis routine
     */
    public static function run_analysis() {
        bsp_v2_log_debug('Running scheduled analysis...');
        
        try {
            // Fetch latest odds
            $logic = new BSP_V2_Logic();
            
            $value_bets = $logic->get_value_bets('football', 5);
            $ltd = $logic->get_ltd_suggestions('football', 5);
            $under25 = $logic->get_under_25_suggestions('football', 5);

            $value_bets_summary = self::store_analysis_result('latest_value_bets', 'value bets', $value_bets);
            $ltd_summary = self::store_analysis_result('latest_ltd', 'Lay The Draw', $ltd);
            $under25_summary = self::store_analysis_result('latest_under25', 'Under 2.5', $under25);
            
            bsp_v2_log('✓ Scheduled analysis complete', [
                'value_bets' => $value_bets_summary['count'],
                'value_bets_status' => $value_bets_summary['status'],
                'ltd' => $ltd_summary['count'],
                'ltd_status' => $ltd_summary['status'],
                'under25' => $under25_summary['count'],
                'under25_status' => $under25_summary['status'],
            ]);
            
        } catch (Throwable $e) {
            bsp_v2_log_error('Scheduled analysis failed: ' . $e->getMessage());
        }
    }
}
