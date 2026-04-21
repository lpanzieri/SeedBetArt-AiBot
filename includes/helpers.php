<?php
/**
 * Global helper functions for SeedBetArt Ai Bot
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if debug mode is enabled
 */
function bsp_v2_is_debug() {
    return defined('BSP_V2_DEBUG') && BSP_V2_DEBUG;
}

/**
 * Get log file path
 */
function bsp_v2_get_log_file() {
    $log_dir = BSP_V2_DIR . 'logs';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        chmod($log_dir, 0755);
    }
    return $log_dir . '/bsp-v2.log';
}

/**
 * Main logging function
 */
function bsp_v2_log($message, $context = []) {
    $log_file = bsp_v2_get_log_file();
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [INFO] {$message}";
    
    if (!empty($context)) {
        $log_message .= " | " . wp_json_encode($context);
    }
    
    error_log($log_message . "\n", 3, $log_file);
}

/**
 * Debug logging
 */
function bsp_v2_log_debug($message, $context = []) {
    if (!bsp_v2_is_debug()) {
        return;
    }
    
    $log_file = bsp_v2_get_log_file();
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1] ?? [];
    $file = basename($caller['file'] ?? 'unknown');
    $line = $caller['line'] ?? '?';
    
    $log_message = "[{$timestamp}] [DEBUG] {$message} (from {$file}:{$line})";
    
    if (!empty($context)) {
        $log_message .= " | " . wp_json_encode($context);
    }
    
    error_log($log_message . "\n", 3, $log_file);
}

/**
 * Error logging
 */
function bsp_v2_log_error($message, $context = []) {
    $log_file = bsp_v2_get_log_file();
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [ERROR] {$message}";
    
    if (!empty($context)) {
        $log_message .= " | " . wp_json_encode($context);
    }
    
    error_log($log_message . "\n", 3, $log_file);
}

/**
 * Format bytes
 */
function bsp_v2_format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get option
 */
function bsp_v2_option($key, $default = '') {
    return get_option('bsp_v2_' . $key, $default);
}

/**
 * Set option
 */
function bsp_v2_set_option($key, $value) {
    return update_option('bsp_v2_' . $key, $value);
}

/**
 * Format event datetime
 */
function bsp_v2_format_event_datetime($datetime) {
    if (empty($datetime)) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    if (!$timestamp) return $datetime;
    
    return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
}

/**
 * Get team badge URL by team ID
 */
function bsp_v2_get_team_badge_url($team_id) {
    if (empty($team_id)) {
        return '';
    }
    return BSP_V2_Teams::get_stored_badge($team_id);
}

/**
 * Get team badge HTML by team ID
 */
function bsp_v2_get_team_badge_html($team_id, $team_name = '', $class = '') {
    if (empty($team_id)) {
        return '';
    }
    
    $url = BSP_V2_Teams::get_stored_badge($team_id);
    $alt_text = !empty($team_name) ? sanitize_text_field($team_name) : 'Team badge';
    $classes = 'bsp-v2-team-badge';
    if (!empty($class)) {
        $classes .= ' ' . esc_attr($class);
    }
    
    return sprintf(
        '<img src="%s" alt="%s" class="%s" loading="lazy">',
        esc_url($url),
        esc_attr($alt_text),
        $classes
    );
}

/**
 * Format match with team names and badges (placeholder for team IDs)
 * Note: Odds API doesn't include team IDs, so we store badges by name lookup
 */
function bsp_v2_format_match($home_team, $away_team, $with_badges = true) {
    if (empty($home_team) || empty($away_team)) {
        return 'N/A';
    }
    
    $home = esc_html($home_team);
    $away = esc_html($away_team);
    
    if ($with_badges) {
        return sprintf(
            '<div class="bsp-v2-match-display">%s <span class="bsp-v2-vs">vs</span> %s</div>',
            $home,
            $away
        );
    }
    
    return "{$home} vs {$away}";
}

/**
 * Format odds with proper styling
 */
function bsp_v2_format_odds($odds) {
    if (empty($odds) || !is_numeric($odds)) {
        return '—';
    }
    
    $odds = floatval($odds);
    $class = 'bsp-v2-odds';
    
    if ($odds > 3.5) {
        $class .= ' bsp-v2-odds-high';
    } elseif ($odds > 2.0) {
        $class .= ' bsp-v2-odds-medium';
    } else {
        $class .= ' bsp-v2-odds-low';
    }
    
    return sprintf(
        '<span class="%s">%.2f</span>',
        $class,
        $odds
    );
}

/**
 * Format confidence as radial progress HTML
 */
function bsp_v2_format_confidence_radial($confidence) {
    if (!is_numeric($confidence)) {
        $confidence = 0;
    }
    
    $confidence = max(0, min(100, intval($confidence)));
    
    // Calculate circumference for SVG circle animation
    $radius = 35;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($confidence / 100) * $circumference;
    
    // Determine color based on confidence
    $color = '#f44336';
    if ($confidence >= 70) {
        $color = '#4caf50';
    } elseif ($confidence >= 50) {
        $color = '#ff9800';
    }
    
    ob_start();
    ?>
    <div class="bsp-v2-confidence-radial">
        <svg width="80" height="80" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="<?php echo $radius; ?>" fill="none" stroke="#e0e0e0" stroke-width="4"></circle>
            <circle cx="40" cy="40" r="<?php echo $radius; ?>" fill="none" stroke="<?php echo $color; ?>" stroke-width="4" 
                    stroke-dasharray="<?php echo $circumference; ?>" 
                    stroke-dashoffset="<?php echo $offset; ?>" 
                    stroke-linecap="round"
                    class="bsp-v2-confidence-fill"
                    style="transform: rotate(-90deg); transform-origin: 40px 40px;"></circle>
        </svg>
        <div class="bsp-v2-confidence-text"><?php echo $confidence; ?>%</div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Format confidence as simple badge
 */
function bsp_v2_format_confidence($confidence) {
    if (!is_numeric($confidence)) {
        $confidence = 0;
    }
    
    $confidence = max(0, min(100, intval($confidence)));
    $class = 'bsp-v2-badge bsp-v2-badge-confidence';
    
    if ($confidence >= 70) {
        $class .= ' bsp-v2-confidence-high';
    } elseif ($confidence >= 50) {
        $class .= ' bsp-v2-confidence-medium';
    } else {
        $class .= ' bsp-v2-confidence-low';
    }
    
    return sprintf(
        '<span class="%s">%d%%</span>',
        $class,
        $confidence
    );
}

/**
 * Format match status badge
 */
function bsp_v2_format_status_badge($status) {
    $status_map = [
        'live' => ['icon' => '🔴', 'label' => 'LIVE', 'class' => 'bsp-v2-status-live'],
        'upcoming' => ['icon' => '⏰', 'label' => 'UPCOMING', 'class' => 'bsp-v2-status-upcoming'],
        'finished' => ['icon' => '✓', 'label' => 'FINISHED', 'class' => 'bsp-v2-status-finished'],
        'void' => ['icon' => '⚠️', 'label' => 'VOID', 'class' => 'bsp-v2-status-void'],
        'active' => ['icon' => '✓', 'label' => 'ACTIVE', 'class' => 'bsp-v2-status-active'],
    ];
    
    $status_info = $status_map[$status] ?? $status_map['upcoming'];
    
    return sprintf(
        '<span class="bsp-v2-badge bsp-v2-status-badge %s">%s %s</span>',
        $status_info['class'],
        $status_info['icon'],
        $status_info['label']
    );
}

/**
 * Format EV badge
 */
function bsp_v2_format_ev_badge($ev) {
    if (!is_numeric($ev)) {
        $ev = 0;
    }
    
    $class = 'bsp-v2-badge bsp-v2-badge-ev';
    
    if ($ev >= 10) {
        $class .= ' bsp-v2-ev-high';
    } elseif ($ev >= 5) {
        $class .= ' bsp-v2-ev-medium';
    } else {
        $class .= ' bsp-v2-ev-low';
    }
    
    return $class;
}

/**
 * Generate quick stats HTML
 */
function bsp_v2_generate_stats_panel($bets) {
    if (empty($bets) || !is_array($bets)) {
        return '';
    }
    
    $total = count($bets);
    $avg_odds = array_sum(array_column($bets, 'odds')) / $total;
    $avg_confidence = array_sum(array_column($bets, 'confidence')) / $total;
    $avg_ev = array_sum(array_column($bets, 'ev')) / $total;
    
    ob_start();
    ?>
    <div class="bsp-v2-stats-panel">
        <div class="bsp-v2-stat-item">
            <div class="bsp-v2-stat-value"><?php echo $total; ?></div>
            <div class="bsp-v2-stat-label">💰 Total Bets</div>
        </div>
        <div class="bsp-v2-stat-item">
            <div class="bsp-v2-stat-value"><?php echo number_format($avg_odds, 2); ?></div>
            <div class="bsp-v2-stat-label">📊 Avg Odds</div>
        </div>
        <div class="bsp-v2-stat-item">
            <div class="bsp-v2-stat-value"><?php echo round($avg_confidence, 0); ?>%</div>
            <div class="bsp-v2-stat-label">⭐ Avg Confidence</div>
        </div>
        <div class="bsp-v2-stat-item">
            <div class="bsp-v2-stat-value"><?php echo number_format($avg_ev, 1); ?>%</div>
            <div class="bsp-v2-stat-label">📈 Avg EV</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Format bet badge
 */
function bsp_v2_format_bet_badge($market, $side) {
    $badges = [
        'ltd' => 'LTD',
        'value' => 'VALUE',
        'under25' => 'U2.5',
    ];
    
    $market_key = strtolower($market);
    $badge_text = $badges[$market_key] ?? ucfirst($market);
    
    return '<span class="bsp-v2-badge badge-' . esc_attr($market_key) . '">' . esc_html($badge_text) . ' - ' . esc_html($side) . '</span>';
}

/**
 * Get table CSS class
 */
function bsp_v2_table_class() {
    return 'bsp-v2-table table table-striped';
}

/**
 * Format EV class
 */
function bsp_v2_format_ev_class($ev) {
    $ev = (float)$ev;
    
    if ($ev >= 15) return 'ev-excellent';
    if ($ev >= 10) return 'ev-very-good';
    if ($ev >= 5) return 'ev-good';
    if ($ev >= 0) return 'ev-fair';
    return 'ev-poor';
}

/**
 * Get missing APIs
 */
function bsp_v2_check_required_apis($feature = 'general') {
    $missing = [];
    
    $odds_key = bsp_v2_option('api_key_odds');
    $football_key = bsp_v2_option('api_key_football');
    $openai_key = bsp_v2_option('api_key_openai');
    
    if (in_array($feature, ['value', 'ltd', 'under25'])) {
        if (empty($odds_key)) $missing[] = 'Odds-API.io';
        if (empty($football_key)) $missing[] = 'API-Football.com';
    }
    
    if (in_array($feature, ['ai'])) {
        if (empty($openai_key)) $missing[] = 'OpenAI';
    }
    
    return $missing;
}

/**
 * Check if AI is enabled
 */
function bsp_v2_ai_enabled() {
    $openai_key = bsp_v2_option('api_key_openai');
    return !empty($openai_key);
}

/**
 * Memory logging
 */
function bsp_v2_log_memory($label = 'Memory') {
    $memory = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    bsp_v2_log_debug("{$label} usage", [
        'current' => bsp_v2_format_bytes($memory),
        'peak' => bsp_v2_format_bytes($peak),
    ]);
}


/**
 * Enqueue frontend interactive scripts
 */
function bsp_v2_enqueue_frontend_assets($include_scripts = true) {
    $frontend_assets = bsp_v2_get_frontend_assets();

    wp_enqueue_style('bsp-v2-frontend', $frontend_assets['styles']['frontend'], [], BSP_V2_VERSION);

    if ($include_scripts) {
        wp_enqueue_script('bsp-v2-frontend', $frontend_assets['scripts']['frontend-interactive'], ['jquery'], BSP_V2_VERSION, true);
    }
}

/**
 * Enqueue frontend widget styles.
 */
function bsp_v2_enqueue_widget_styles() {
    wp_enqueue_style('bsp-v2-widgets', BSP_V2_URL . 'assets/css/widgets.css', [], BSP_V2_VERSION);
}

/**
 * Get theme accent color (compatible with any theme)
 */
function bsp_v2_get_theme_accent_color() {
    $color = get_theme_mod('accent_color', '#667eea');
    return is_string($color) ? $color : '#667eea';
}

/**
 * Check if dark mode is preferred
 */
function bsp_v2_is_dark_mode() {
    return isset($_SERVER['HTTP_PREFERS_COLOR_SCHEME']) && 
           $_SERVER['HTTP_PREFERS_COLOR_SCHEME'] === 'dark';
}

/**
 * Format league name with flag emoji
 */
function bsp_v2_format_league($league) {
    $flags = [
        'Premier League' => '🇬🇧',
        'La Liga' => '🇪🇸',
        'Serie A' => '🇮🇹',
        'Bundesliga' => '🇩🇪',
        'Ligue 1' => '🇫🇷',
        'Champions League' => '🏆',
        'Europa League' => '🏆',
    ];
    
    $flag = $flags[$league] ?? '⚽';
    return sprintf('%s %s', $flag, esc_html($league));
}

/**
 * Get data table attributes for sorting
 */
function bsp_v2_get_table_header($label, $sortable = true, $type = 'string') {
    if (!$sortable) {
        return sprintf('<th>%s</th>', esc_html($label));
    }
    return sprintf(
        '<th data-sortable="true" data-type="%s">%s</th>',
        esc_attr($type),
        esc_html($label)
    );
}

/**
 * Track OpenAI token usage
 */
function bsp_v2_track_openai_tokens($tokens_used) {
    $current_month = date('Y-m');
    $option_key = 'bsp_v2_openai_tokens_' . $current_month;
    $current_usage = intval(get_option($option_key, 0));
    $new_usage = $current_usage + intval($tokens_used);
    update_option($option_key, $new_usage);
    bsp_v2_log('OpenAI tokens tracked', ['tokens' => $tokens_used, 'total_month' => $new_usage]);
}

/**
 * Check if OpenAI token limit exceeded
 */
function bsp_v2_check_openai_limit() {
    $limit = intval(bsp_v2_option('limit_openai_tokens'));
    if (!$limit) {
        return true; // No limit set, allow
    }
    
    $current_month = date('Y-m');
    $option_key = 'bsp_v2_openai_tokens_' . $current_month;
    $current_usage = intval(get_option($option_key, 0));
    
    if ($current_usage >= $limit) {
        bsp_v2_log_error('OpenAI token limit exceeded', ['limit' => $limit, 'used' => $current_usage]);
        return false;
    }
    
    return true;
}

/**
 * Get OpenAI usage stats for current month
 */
function bsp_v2_get_openai_usage_stats() {
    $limit = intval(bsp_v2_option('limit_openai_tokens'));
    $current_month = date('Y-m');
    $option_key = 'bsp_v2_openai_tokens_' . $current_month;
    $current_usage = intval(get_option($option_key, 0));
    
    return [
        'limit' => $limit,
        'used' => $current_usage,
        'remaining' => $limit > 0 ? max(0, $limit - $current_usage) : null,
        'percentage' => $limit > 0 ? round(($current_usage / $limit) * 100, 1) : 0,
        'month' => $current_month
    ];
}

/**
 * Track API call usage
 */
function bsp_v2_track_api_call($api_name) {
    $today = date('Y-m-d');
    $option_key = 'bsp_v2_' . strtolower($api_name) . '_calls_' . $today;
    $current_count = intval(get_option($option_key, 0));
    $new_count = $current_count + 1;
    update_option($option_key, $new_count);
    bsp_v2_log('API call tracked', ['api' => $api_name, 'daily_total' => $new_count]);
}

/**
 * Check if API call limit exceeded
 */
function bsp_v2_check_api_limit($api_name) {
    $api_lower = strtolower($api_name);
    $limit_key = 'limit_' . $api_lower . '_calls';
    $limit = intval(bsp_v2_option($limit_key));
    
    if (!$limit) {
        return true; // No limit set, allow
    }
    
    $today = date('Y-m-d');
    $option_key = 'bsp_v2_' . $api_lower . '_calls_' . $today;
    $current_count = intval(get_option($option_key, 0));
    
    if ($current_count >= $limit) {
        bsp_v2_log_error('API call limit exceeded', ['api' => $api_name, 'limit' => $limit, 'used' => $current_count]);
        return false;
    }
    
    return true;
}

/**
 * Get API usage stats for today
 */
function bsp_v2_get_api_usage_stats($api_name) {
    $api_lower = strtolower($api_name);
    $limit_key = 'limit_' . $api_lower . '_calls';
    $limit = intval(bsp_v2_option($limit_key));
    $today = date('Y-m-d');
    $option_key = 'bsp_v2_' . $api_lower . '_calls_' . $today;
    $current_count = intval(get_option($option_key, 0));
    
    return [
        'api' => $api_name,
        'limit' => $limit,
        'used' => $current_count,
        'remaining' => $limit > 0 ? max(0, $limit - $current_count) : null,
        'percentage' => $limit > 0 ? round(($current_count / $limit) * 100, 1) : 0,
        'date' => $today
    ];
}

/**
 * Get the default OpenAI model for this plugin.
 */
function bsp_v2_get_default_openai_model() {
    return 'gpt-5.4-mini';
}

/**
 * Get available OpenAI models
 */
function bsp_v2_get_openai_models() {
    return [
        'gpt-5.4' => 'GPT-5.4 - Highest quality reasoning',
        'gpt-5.4-mini' => 'GPT-5.4 Mini - Recommended balance of quality and cost',
        'gpt-5.4-nano' => 'GPT-5.4 Nano - Lowest cost and fastest responses',
    ];
}

/**
 * Normalize a stored or user-provided OpenAI model to a supported current model.
 */
function bsp_v2_normalize_openai_model($model = null) {
    $model = is_string($model) ? trim($model) : '';

    if ($model === '') {
        return bsp_v2_get_default_openai_model();
    }

    $legacy_map = [
        'gpt-5' => 'gpt-5.4',
        'gpt-5-turbo' => 'gpt-5.4-mini',
        'gpt-4o' => 'gpt-5.4-mini',
        'gpt-4o-mini' => 'gpt-5.4-mini',
        'gpt-4-turbo' => 'gpt-5.4-mini',
        'gpt-4' => 'gpt-5.4',
        'gpt-3.5-turbo' => 'gpt-5.4-nano',
    ];

    return $legacy_map[$model] ?? $model;
}

/**
 * Extract text output from an OpenAI Responses API payload.
 */
function bsp_v2_extract_openai_response_text($response_data) {
    if (!is_array($response_data)) {
        return '';
    }

    if (!empty($response_data['output_text']) && is_string($response_data['output_text'])) {
        return trim($response_data['output_text']);
    }

    if (empty($response_data['output']) || !is_array($response_data['output'])) {
        return '';
    }

    $text_parts = [];

    foreach ($response_data['output'] as $output_item) {
        if (!is_array($output_item) || empty($output_item['content']) || !is_array($output_item['content'])) {
            continue;
        }

        foreach ($output_item['content'] as $content_item) {
            if (!is_array($content_item)) {
                continue;
            }

            if (!empty($content_item['text']) && is_string($content_item['text'])) {
                $text_parts[] = trim($content_item['text']);
            }
        }
    }

    return trim(implode("\n\n", array_filter($text_parts)));
}

/**
 * Extract total token usage from an OpenAI API payload.
 */
function bsp_v2_extract_openai_total_tokens($response_data) {
    if (!is_array($response_data) || empty($response_data['usage']) || !is_array($response_data['usage'])) {
        return 0;
    }

    if (!empty($response_data['usage']['total_tokens'])) {
        return intval($response_data['usage']['total_tokens']);
    }

    $input_tokens = !empty($response_data['usage']['input_tokens']) ? intval($response_data['usage']['input_tokens']) : 0;
    $output_tokens = !empty($response_data['usage']['output_tokens']) ? intval($response_data['usage']['output_tokens']) : 0;

    return $input_tokens + $output_tokens;
}

/**
 * Build a user-facing error message for OpenAI API failures.
 */
function bsp_v2_get_openai_error_message($status, $body, $model = '') {
    $error_detail = substr((string) $body, 0, 160);
    $json_error = json_decode((string) $body, true);

    if (!empty($json_error['error']['message']) && is_string($json_error['error']['message'])) {
        $error_detail = $json_error['error']['message'];
    }

    if ($status === 401 || $status === 403) {
        return 'Invalid API key - Check your key is correct, active, and has billing enabled';
    }

    if ($status === 404) {
        return 'Model "' . $model . '" not found - This model may not be available to your account';
    }

    if ($status === 429) {
        return 'Rate limit exceeded - Upgrade your OpenAI plan or wait for limit reset';
    }

    if ($status === 503) {
        return 'OpenAI service temporarily unavailable - Try again in a few moments';
    }

    return 'API error (Status ' . $status . '): ' . $error_detail;
}

/**
 * Validate Odds-API key
 */
function bsp_v2_validate_odds_api($api_key = null) {
    bsp_v2_log_debug('validate_odds_api called', [
        'api_key param' => $api_key ? 'provided (length: ' . strlen($api_key) . ')' : 'null/empty',
    ]);
    
    if (!$api_key) {
        $api_key = bsp_v2_option('api_key_odds');
        bsp_v2_log_debug('No key provided, checking options', [
            'option value' => $api_key ? 'found (length: ' . strlen($api_key) . ')' : 'not found',
        ]);
    }
    
    if (empty($api_key)) {
        bsp_v2_log_error('Odds-API validation failed - no key available', [
            'param received' => isset($api_key) ? 'yes' : 'no',
            'param value' => $api_key,
        ]);
        return new WP_Error('missing_key', 'Odds-API key not configured');
    }
    
    // Use an authenticated endpoint so key validation does not succeed on a public route.
    $url = BSP_V2_ODDS_API_BASE . '/events';
    bsp_v2_log_debug('Odds-API validation started', ['endpoint' => $url]);
    
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
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        bsp_v2_log_error('Odds-API request failed', ['error' => $error_message]);
        // Check if it's an SSL issue
        if (strpos(strtolower($error_message), 'ssl') !== false || strpos(strtolower($error_message), 'certificate') !== false) {
            return new WP_Error('ssl_error', 'SSL Certificate Error: Your server may have SSL verification issues. Try disabling SSL verification if on local environment.');
        }
        return new WP_Error('request_failed', 'Cannot reach API endpoint: ' . $error_message);
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status === 401 || $status === 403) {
        return new WP_Error('unauthorized', 'Invalid API key - Check your key is correct and has appropriate permissions');
    } elseif ($status === 429) {
        return new WP_Error('rate_limit', 'Rate limit exceeded - Upgrade your API plan or wait for limit reset');
    } elseif ($status === 503) {
        return new WP_Error('service_unavailable', 'Odds-API service temporarily unavailable - Try again in a few moments');
    } elseif ($status !== 200) {
        return new WP_Error('api_error', 'API error (Status ' . $status . '): ' . substr($body, 0, 100));
    }
    
    // Try to decode JSON to ensure valid response
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_response', 'API returned invalid JSON - The API may be having issues. Response: ' . substr($body, 0, 100));
    }
    
    return true;
}

/**
 * Validate Football-API key
 */
function bsp_v2_validate_football_api($api_key = null) {
    bsp_v2_log_debug('validate_football_api called', [
        'api_key param' => $api_key ? 'provided (length: ' . strlen($api_key) . ')' : 'null/empty',
    ]);
    
    if (!$api_key) {
        $api_key = bsp_v2_option('api_key_football');
        bsp_v2_log_debug('No key provided, checking options', [
            'option value' => $api_key ? 'found (length: ' . strlen($api_key) . ')' : 'not found',
        ]);
    }
    
    if (empty($api_key)) {
        bsp_v2_log_error('Football-API validation failed - no key available', [
            'param received' => isset($api_key) ? 'yes' : 'no',
            'param value' => $api_key,
        ]);
        return new WP_Error('missing_key', 'Football-API key not configured');
    }
    
    // Use the /status endpoint which doesn't count against daily quota
    $url = BSP_V2_FOOTBALL_API_BASE . '/status';
    bsp_v2_log_debug('Football-API validation started', ['endpoint' => $url]);
    
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => apply_filters('https_local_ssl_verify', true),
        'headers' => [
            'User-Agent' => 'SeedBetArt-Ai-Bot/0.2',
            'Accept' => 'application/json',
            'x-apisports-key' => $api_key,
        ],
    ]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        bsp_v2_log_error('Football-API request failed', ['error' => $error_message]);
        if (strpos(strtolower($error_message), 'ssl') !== false || strpos(strtolower($error_message), 'certificate') !== false) {
            return new WP_Error('ssl_error', 'SSL Certificate Error: Your server may have SSL verification issues.');
        }
        return new WP_Error('request_failed', 'Cannot reach API endpoint: ' . $error_message);
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    bsp_v2_log_debug('Football-API response received', ['status_code' => $status, 'response_length' => strlen($body)]);
    
    if ($status === 401 || $status === 403) {
        return new WP_Error('unauthorized', 'Invalid API key - Check your key is correct and matches your subscription');
    } elseif ($status === 429) {
        return new WP_Error('rate_limit', 'Rate limit exceeded - Upgrade your API plan or wait for limit reset');
    } elseif ($status === 503) {
        return new WP_Error('service_unavailable', 'API-Football service temporarily unavailable - Try again in a few moments');
    } elseif ($status !== 200) {
        return new WP_Error('api_error', 'API error (Status ' . $status . '): ' . substr($body, 0, 100));
    }
    
    // Try to decode JSON to ensure valid response
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_response', 'API returned invalid JSON - The API may be having issues');
    }
    
    return true;
}

/**
 * Validate OpenAI API key
 */
function bsp_v2_validate_openai_api($api_key = null, $model = null) {
    bsp_v2_log_debug('validate_openai_api called', [
        'api_key param' => $api_key ? 'provided (length: ' . strlen($api_key) . ')' : 'null/empty',
        'model param' => $model ? 'provided' : 'null/empty',
    ]);
    
    if (!$api_key) {
        $api_key = bsp_v2_option('api_key_openai');
        bsp_v2_log_debug('No API key provided, checking options', [
            'option value' => $api_key ? 'found (length: ' . strlen($api_key) . ')' : 'not found',
        ]);
    }
    
    if (empty($api_key)) {
        bsp_v2_log_error('OpenAI-API validation failed - no key available', [
            'param received' => isset($api_key) ? 'yes' : 'no',
            'param value' => $api_key,
        ]);
        return new WP_Error('missing_key', 'OpenAI API key not configured');
    }
    
    if (empty($model)) {
        $model = bsp_v2_option('openai_model');
    }

    $model = bsp_v2_normalize_openai_model($model);
    
    if (empty($model)) {
        return new WP_Error('no_model', 'No OpenAI model selected - Please select a model in settings');
    }
    
    // Use the Responses API so validation matches the runtime integration.
    $url = BSP_V2_OPENAI_API_BASE . '/responses';
    $body_data = [
        'model' => $model,
        'instructions' => 'Reply with OK only.',
        'input' => 'Say OK',
        'max_output_tokens' => 5,
        'store' => false,
    ];
    
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
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        bsp_v2_log_error('OpenAI request failed', ['error' => $error_message]);
        if (strpos(strtolower($error_message), 'ssl') !== false || strpos(strtolower($error_message), 'certificate') !== false) {
            return new WP_Error('ssl_error', 'SSL Certificate Error: Your server may have SSL verification issues.');
        }
        return new WP_Error('request_failed', 'Cannot reach API endpoint: ' . $error_message);
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    bsp_v2_log_debug('OpenAI response received', ['status_code' => $status, 'response_length' => strlen($body)]);
    
    if (!in_array($status, [200, 201], true)) {
        return new WP_Error('api_error', bsp_v2_get_openai_error_message($status, $body, $model));
    }
    
    // Try to decode JSON to ensure valid response
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_response', 'API returned invalid JSON - The API may be having issues');
    }
    
    if (bsp_v2_extract_openai_response_text($json) === '') {
        return new WP_Error('invalid_response', 'API response missing text output - Unexpected API format');
    }
    
    return true;
}

/**
 * Check if the betting APIs are validated
 */
function bsp_v2_are_betting_apis_validated() {
    $odds_valid = get_option('bsp_v2_api_validated_odds', false);
    $football_valid = get_option('bsp_v2_api_validated_football', false);

    return $odds_valid && $football_valid;
}

/**
 * Check if the OpenAI API is validated
 */
function bsp_v2_is_openai_api_validated() {
    return (bool) get_option('bsp_v2_api_validated_openai', false);
}

/**
 * Check if all APIs are validated
 */
function bsp_v2_are_all_apis_validated() {
    return bsp_v2_are_betting_apis_validated() && bsp_v2_is_openai_api_validated();
}

/**
 * Get API validation status
 */
function bsp_v2_get_api_validation_status() {
    return [
        'odds' => get_option('bsp_v2_api_validated_odds', false),
        'football' => get_option('bsp_v2_api_validated_football', false),
        'openai' => get_option('bsp_v2_api_validated_openai', false),
    ];
}

/**
 * Theme Management Functions
 */

/**
 * Get the default theme slug.
 */
function bsp_v2_get_default_theme_slug() {
    $available = bsp_v2_get_available_themes();

    if (isset($available['basic'])) {
        return 'basic';
    }

    $first_theme = array_key_first($available);

    return is_string($first_theme) && $first_theme !== '' ? $first_theme : 'basic';
}

/**
 * Format a theme slug for display.
 */
function bsp_v2_format_theme_label($theme) {
    $theme = str_replace(['-', '_'], ' ', (string) $theme);

    return ucwords($theme);
}

/**
 * Get the required files for a complete theme bundle.
 */
function bsp_v2_get_theme_required_files() {
    return [
        'admin/css/admin-enhanced.css',
        'admin/css/admin.css',
        'admin/js/admin-enhanced.js',
        'frontend/css/frontend.css',
        'frontend/js/frontend-interactive.js',
    ];
}

/**
 * Check whether a theme contains the full required asset bundle.
 */
function bsp_v2_theme_has_required_files($theme) {
    $theme = sanitize_key((string) $theme);

    if ($theme === '') {
        return false;
    }

    $theme_dir = trailingslashit(BSP_V2_DIR . 'assets/themes/' . $theme);

    if (!is_dir($theme_dir)) {
        return false;
    }

    foreach (bsp_v2_get_theme_required_files() as $relative_path) {
        if (!file_exists($theme_dir . $relative_path)) {
            return false;
        }
    }

    return true;
}

/**
 * Get the asset map for a given theme area.
 */
function bsp_v2_get_theme_asset_map($type = 'admin') {
    if ($type === 'frontend') {
        return [
            'styles' => [
                'frontend' => 'css/frontend.css',
            ],
            'scripts' => [
                'frontend-interactive' => 'js/frontend-interactive.js',
            ],
        ];
    }

    return [
        'styles' => [
            'admin-enhanced' => 'css/admin-enhanced.css',
            'admin' => 'css/admin.css',
        ],
        'scripts' => [
            'admin-enhanced' => 'js/admin-enhanced.js',
        ],
    ];
}

/**
 * Resolve theme asset URLs, falling back to the legacy root assets when needed.
 */
function bsp_v2_get_theme_assets($type = 'admin') {
    $theme = bsp_v2_get_current_theme();
    $theme_url = trailingslashit(BSP_V2_URL . 'assets/themes/' . $theme . '/' . $type);
    $theme_dir = trailingslashit(BSP_V2_DIR . 'assets/themes/' . $theme . '/' . $type);
    $fallback_url = trailingslashit(BSP_V2_URL . 'assets');
    $fallback_dir = trailingslashit(BSP_V2_DIR . 'assets');
    $assets = [
        'styles' => [],
        'scripts' => [],
    ];

    foreach (bsp_v2_get_theme_asset_map($type) as $group => $definitions) {
        foreach ($definitions as $handle => $relative_path) {
            $relative_path = ltrim($relative_path, '/');
            $theme_asset_path = $theme_dir . $relative_path;
            $fallback_asset_path = $fallback_dir . $relative_path;

            if (file_exists($theme_asset_path)) {
                $assets[$group][$handle] = $theme_url . $relative_path;
                continue;
            }

            if (file_exists($fallback_asset_path)) {
                $assets[$group][$handle] = $fallback_url . $relative_path;
                continue;
            }

            $assets[$group][$handle] = $theme_url . $relative_path;
        }
    }

    return $assets;
}

/**
 * Get available themes
 */
function bsp_v2_get_available_themes() {
    $themes_dir = BSP_V2_DIR . 'assets/themes';
    $themes = [];
    
    if (is_dir($themes_dir)) {
        $folders = scandir($themes_dir);
        foreach ($folders as $folder) {
            if ($folder !== '.' && $folder !== '..' && is_dir($themes_dir . '/' . $folder) && bsp_v2_theme_has_required_files($folder)) {
                $themes[$folder] = bsp_v2_format_theme_label($folder);
            }
        }
    }

    ksort($themes);
    
    return !empty($themes) ? $themes : ['basic' => 'Basic'];
}

/**
 * Get current theme
 */
function bsp_v2_get_current_theme() {
    $theme = sanitize_key((string) get_option('bsp_v2_theme', bsp_v2_get_default_theme_slug()));
    $default_theme = bsp_v2_get_default_theme_slug();
    
    // Validate theme exists
    $available = bsp_v2_get_available_themes();
    if (!isset($available[$theme])) {
        $theme = $default_theme;
        update_option('bsp_v2_theme', $theme);
    }
    
    return $theme;
}

/**
 * Set current theme
 */
function bsp_v2_set_theme($theme) {
    $theme = sanitize_key((string) $theme);
    $available = bsp_v2_get_available_themes();
    
    if (!isset($available[$theme])) {
        return new WP_Error('invalid_theme', 'Theme does not exist');
    }
    
    update_option('bsp_v2_theme', $theme);
    return true;
}

/**
 * Get theme URL
 */
function bsp_v2_get_theme_url($type = 'admin') {
    // $type can be 'admin' or 'frontend'
    $theme = bsp_v2_get_current_theme();
    return BSP_V2_URL . 'assets/themes/' . $theme . '/' . $type . '/';
}

/**
 * Get theme directory
 */
function bsp_v2_get_theme_dir($type = 'admin') {
    // $type can be 'admin' or 'frontend'
    $theme = bsp_v2_get_current_theme();
    return BSP_V2_DIR . 'assets/themes/' . $theme . '/' . $type . '/';
}

/**
 * Get admin theme CSS and JS files
 */
function bsp_v2_get_admin_assets() {
    return bsp_v2_get_theme_assets('admin');
}

/**
 * Get frontend theme CSS and JS files
 */
function bsp_v2_get_frontend_assets() {
    return bsp_v2_get_theme_assets('frontend');
}
