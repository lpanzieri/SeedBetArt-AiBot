<?php
/**
 * Admin main orchestrator with enhanced dashboard
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Admin {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_init', [__CLASS__, 'handle_database_cleanup']);
        add_action('admin_notices', [__CLASS__, 'show_activation_notice']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_bsp_v2_validate_odds_api', [__CLASS__, 'ajax_validate_odds_api']);
        add_action('wp_ajax_bsp_v2_validate_football_api', [__CLASS__, 'ajax_validate_football_api']);
        add_action('wp_ajax_bsp_v2_validate_openai_api', [__CLASS__, 'ajax_validate_openai_api']);
        add_action('wp_ajax_bsp_v2_change_theme', [__CLASS__, 'ajax_change_theme']);
        add_action('wp_ajax_bsp_v2_unlink_api', [__CLASS__, 'ajax_unlink_api']);
        BSP_V2_Search_Params::init();
        BSP_V2_Debug::init();
    }
    
    public static function enqueue_assets($hook) {
        // Don't enqueue during AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        if (strpos($hook, 'bsp-v2') === false) return;
        
        // Get theme assets
        $admin_assets = bsp_v2_get_admin_assets();
        
        // Enqueue Chart.js for visualizations
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
        wp_enqueue_script('bsp-v2-admin', $admin_assets['scripts']['admin-enhanced'], ['jquery', 'chart-js'], '0.2', true);
        wp_enqueue_style('bsp-v2-admin', $admin_assets['styles']['admin-enhanced'], [], '0.2');
        
        wp_localize_script('bsp-v2-admin', 'bspV2Data', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bsp_v2_admin_nonce')
        ]);
    }
    
    public static function show_activation_notice() {
        // Check if activation notice transient exists
        if (get_transient('bsp_v2_activation_notice')) {
            delete_transient('bsp_v2_activation_notice');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✓ SeedBetArt Ai Bot activated successfully!</strong><br>
                    Database tables have been created and the plugin is ready to use. 
                    <a href="<?php echo admin_url('admin.php?page=bsp-v2-settings'); ?>">Configure API settings →</a>
                </p>
            </div>
            <?php
        }
    }
    
    public static function register_menu() {
        add_menu_page(
            'SeedBetArt Ai Bot',
            'SeedBetArt Ai Bot',
            'manage_options',
            'bsp-v2-dashboard',
            [__CLASS__, 'render_dashboard'],
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'bsp-v2-dashboard',
            [__CLASS__, 'render_dashboard']
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Settings',
            '⚙️ Settings',
            'manage_options',
            'bsp-v2-settings',
            [__CLASS__, 'render_settings']
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Activity Log',
            '📋 Activity Log',
            'manage_options',
            'bsp-v2-activity',
            [__CLASS__, 'render_activity']
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Search Parameters',
            '🎯 Search Params',
            'manage_options',
            'bsp-v2-search-params',
            [__CLASS__, 'render_search_params']
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Database',
            '🗄️ Database',
            'manage_options',
            'bsp-v2-database',
            [__CLASS__, 'render_database']
        );
        
        add_submenu_page(
            'bsp-v2-dashboard',
            'Themes',
            '🎨 Themes',
            'manage_options',
            'bsp-v2-themes',
            [__CLASS__, 'render_themes']
        );
    }
    
    public static function register_settings() {
        register_setting('bsp_v2_settings', 'bsp_v2_api_key_odds');
        register_setting('bsp_v2_settings', 'bsp_v2_api_key_football');
        register_setting('bsp_v2_settings', 'bsp_v2_api_key_openai');
        register_setting('bsp_v2_settings', 'bsp_v2_openai_model');
        register_setting('bsp_v2_settings', 'bsp_v2_debug_enabled');
        register_setting('bsp_v2_settings', 'bsp_v2_auto_refresh');
        register_setting('bsp_v2_settings', 'bsp_v2_limit_openai_tokens');
        register_setting('bsp_v2_settings', 'bsp_v2_limit_odds_api_calls');
        register_setting('bsp_v2_settings', 'bsp_v2_limit_football_api_calls');
        
        add_settings_section(
            'bsp_v2_api_section',
            'API Configuration',
            [__CLASS__, 'render_api_section'],
            'bsp_v2_settings'
        );
    }
    
    public static function render_stats() {
        $value_bets = BSP_V2_Cache::get('latest_value_bets') ?: [];
        $ltd = BSP_V2_Cache::get('latest_ltd') ?: [];
        $under25 = BSP_V2_Cache::get('latest_under25') ?: [];
        $last_updated = BSP_V2_Cache::get_most_recent_update([
            'latest_value_bets',
            'latest_ltd',
            'latest_under25',
        ]);
        $last_updated_time = $last_updated ? wp_date(get_option('time_format'), $last_updated) : '—';
        $last_updated_date = $last_updated ? wp_date(get_option('date_format'), $last_updated) : 'No successful refresh';
        
        // Handle WP_Error responses
        if (is_wp_error($value_bets)) $value_bets = [];
        if (is_wp_error($ltd)) $ltd = [];
        if (is_wp_error($under25)) $under25 = [];
        
        ?>
        <div class="bsp-v2-stats-grid">
            <div class="bsp-v2-stat-card">
                <div class="bsp-v2-stat-icon">💰</div>
                <h3>Value Bets</h3>
                <p class="bsp-v2-stat-number"><?php echo count($value_bets); ?></p>
                <p class="bsp-v2-stat-label">Active Opportunities</p>
            </div>
            
            <div class="bsp-v2-stat-card">
                <div class="bsp-v2-stat-icon">🎯</div>
                <h3>Lay The Draw</h3>
                <p class="bsp-v2-stat-number"><?php echo count($ltd); ?></p>
                <p class="bsp-v2-stat-label">Current Picks</p>
            </div>
            
            <div class="bsp-v2-stat-card">
                <div class="bsp-v2-stat-icon">⚽</div>
                <h3>Under 2.5</h3>
                <p class="bsp-v2-stat-number"><?php echo count($under25); ?></p>
                <p class="bsp-v2-stat-label">Predicted Matches</p>
            </div>
            
            <div class="bsp-v2-stat-card">
                <div class="bsp-v2-stat-icon">🔄</div>
                <h3>Last Refresh</h3>
                <p class="bsp-v2-stat-number"><?php echo esc_html($last_updated_time); ?></p>
                <p class="bsp-v2-stat-label"><?php echo esc_html($last_updated_date); ?></p>
            </div>
        </div>
        <?php
    }
    
    public static function render_api_usage() {
        ?>
        <div class="bsp-v2-settings-section">
            <h2>📡 API Usage & Limits</h2>
            
            <div class="bsp-v2-api-usage-grid">
                <?php
                // OpenAI Usage
                $openai_stats = bsp_v2_get_openai_usage_stats();
                if ($openai_stats['limit'] > 0):
                    $bar_width = min(100, $openai_stats['percentage']);
                    $bar_class = $bar_width > 80 ? 'danger' : ($bar_width > 50 ? 'warning' : '');
                ?>
                <div class="bsp-v2-api-usage-card">
                    <h4>🤖 OpenAI API</h4>
                    <p class="bsp-v2-usage-label">Tokens used this month</p>
                    <div class="bsp-v2-usage-bar-track">
                        <div class="bsp-v2-usage-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                    <p class="bsp-v2-usage-text">
                        <strong><?php echo number_format($openai_stats['used']); ?></strong> / 
                        <?php echo number_format($openai_stats['limit']); ?> tokens
                        (<?php echo $openai_stats['percentage']; ?>%)
                    </p>
                </div>
                <?php endif; ?>
                
                <?php
                // Odds-API Usage
                $odds_stats = bsp_v2_get_api_usage_stats('odds_api');
                if ($odds_stats['limit'] > 0):
                    $bar_width = min(100, $odds_stats['percentage']);
                    $bar_class = $bar_width > 80 ? 'danger' : ($bar_width > 50 ? 'warning' : '');
                ?>
                <div class="bsp-v2-api-usage-card">
                    <h4>📊 Odds-API</h4>
                    <p class="bsp-v2-usage-label">Calls used today</p>
                    <div class="bsp-v2-usage-bar-track">
                        <div class="bsp-v2-usage-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                    <p class="bsp-v2-usage-text">
                        <strong><?php echo $odds_stats['used']; ?></strong> / 
                        <?php echo $odds_stats['limit']; ?> calls
                        (<?php echo $odds_stats['percentage']; ?>%)
                    </p>
                </div>
                <?php endif; ?>
                
                <?php
                // Football-API Usage
                $football_stats = bsp_v2_get_api_usage_stats('football_api');
                if ($football_stats['limit'] > 0):
                    $bar_width = min(100, $football_stats['percentage']);
                    $bar_class = $bar_width > 80 ? 'danger' : ($bar_width > 50 ? 'warning' : '');
                ?>
                <div class="bsp-v2-api-usage-card">
                    <h4>⚽ Football-API</h4>
                    <p class="bsp-v2-usage-label">Calls used today</p>
                    <div class="bsp-v2-usage-bar-track">
                        <div class="bsp-v2-usage-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                    <p class="bsp-v2-usage-text">
                        <strong><?php echo $football_stats['used']; ?></strong> / 
                        <?php echo $football_stats['limit']; ?> calls
                        (<?php echo $football_stats['percentage']; ?>%)
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <p class="bsp-v2-tip-box">
                💡 <strong>Tip:</strong> Configure API usage limits in <a href="<?php echo admin_url('admin.php?page=bsp-v2-settings'); ?>">Settings</a> 
                to manage costs and prevent unexpected overages.
            </p>
        </div>
        <?php
    }
    
    public static function render_dashboard() {
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>📊 SeedBetArt Ai Bot Dashboard</h1>
                <p class="bsp-v2-subtitle">Real-time betting analytics and recommendations</p>
            </div>
            
            <?php self::render_stats(); ?>
            <?php self::render_api_usage(); ?>
            <?php self::render_charts(); ?>
            <?php self::render_recent_bets(); ?>
        </div>
        <?php
    }
    
    public static function render_charts() {
        ?>
        <div class="bsp-v2-charts-container">
            <div class="bsp-v2-chart-card">
                <h3>📈 EV Distribution</h3>
                <canvas id="bsp-v2-chart-ev"></canvas>
            </div>
            
            <div class="bsp-v2-chart-card">
                <h3>📊 Bets by Type</h3>
                <canvas id="bsp-v2-chart-types"></canvas>
            </div>
        </div>
        
        <script>
        (function($) {
            $(function() {
                // EV Distribution Chart
                var ctxEV = document.getElementById('bsp-v2-chart-ev').getContext('2d');
                new Chart(ctxEV, {
                    type: 'bar',
                    data: {
                        labels: ['0-2%', '2-5%', '5-10%', '10%+'],
                        datasets: [{
                            label: 'Number of Bets',
                            data: [<?php echo rand(2, 10); ?>, <?php echo rand(5, 15); ?>, <?php echo rand(3, 12); ?>, <?php echo rand(1, 5); ?>],
                            backgroundColor: [
                                'rgba(255, 193, 7, 0.8)',
                                'rgba(33, 150, 243, 0.8)',
                                'rgba(76, 175, 80, 0.8)',
                                'rgba(76, 175, 80, 1)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
                
                // Bets by Type Chart
                var ctxTypes = document.getElementById('bsp-v2-chart-types').getContext('2d');
                new Chart(ctxTypes, {
                    type: 'doughnut',
                    data: {
                        labels: ['Value Bets', 'Lay The Draw', 'Under 2.5'],
                        datasets: [{
                            data: [<?php echo rand(5, 20); ?>, <?php echo rand(5, 20); ?>, <?php echo rand(5, 20); ?>],
                            backgroundColor: [
                                '#667eea',
                                '#764ba2',
                                '#f093fb'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
    
    public static function render_recent_bets() {
        $value_bets = BSP_V2_Cache::get('latest_value_bets') ?: [];
        $recent = array_slice($value_bets, 0, 5);
        
        ?>
        <div class="bsp-v2-recent-bets">
            <h3>🎯 Recent Value Bets</h3>
            <?php if (empty($recent)): ?>
                <p class="bsp-v2-empty-state">No recent bets yet. Check back soon!</p>
            <?php else: ?>
                <table class="bsp-v2-data-table">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Odds</th>
                            <th>EV %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $bet): ?>
                        <tr>
                            <td><strong><?php echo esc_html($bet['home'] ?? 'N/A'); ?></strong> vs <strong><?php echo esc_html($bet['away'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo esc_html(number_format($bet['odds'] ?? 0, 2)); ?></td>
                            <td><span class="bsp-v2-badge-ev"><?php echo esc_html(number_format($bet['ev'] ?? 0, 1)); ?>%</span></td>
                            <td><span class="bsp-v2-badge-active">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public static function render_activity() {
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>📋 Activity Log</h1>
                <p class="bsp-v2-subtitle">Recent plugin events and debug output</p>
            </div>
            <div class="bsp-v2-activity-log">
                <p>Debug logs from: <code><?php echo bsp_v2_get_log_file(); ?></code></p>
                <?php
                $log_file = bsp_v2_get_log_file();
                if (file_exists($log_file)) {
                    $lines = array_slice(file($log_file), -50);
                    echo '<pre class="bsp-v2-log-pre">';
                    echo esc_html(implode('', $lines));
                    echo '</pre>';
                } else {
                    echo '<p><em>No log file yet. Logs will appear after first run.</em></p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    public static function render_settings() {
        $validation_status = bsp_v2_get_api_validation_status();
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>⚙️ Settings</h1>
                <p class="bsp-v2-subtitle">Configure API keys, usage limits, and plugin behaviour</p>
            </div>
            
            <form method="post" action="options.php" class="bsp-v2-settings-form">
                <?php settings_fields('bsp_v2_settings'); ?>
                
                <div class="bsp-v2-settings-section">
                    <h2>🔑 API Configuration</h2>
                    <p class="description">Enter your API keys from the respective service providers and validate them.</p>
                    
                    <div class="bsp-v2-form-group">
                        <div class="bsp-v2-api-form-row">
                            <div class="bsp-v2-api-form-row-input">
                                <label for="bsp_v2_api_key_odds">Odds-API Key</label>
                                <input type="password" id="bsp_v2_api_key_odds" name="bsp_v2_api_key_odds" 
                                       value="<?php echo esc_attr(bsp_v2_option('api_key_odds')); ?>" 
                                       placeholder="sk_live_..." class="bsp-v2-input"
                                       <?php if ($validation_status['odds']) echo 'readonly style="cursor:not-allowed;opacity:0.7;"'; ?>>
                                <p class="description">Get it from <a href="https://odds-api.io/" target="_blank">odds-api.io</a></p>
                            </div>
                            <div class="bsp-v2-api-actions">
                                <button type="button" class="bsp-v2-validate-btn <?php echo $validation_status['odds'] ? 'validated' : ''; ?>" data-api="odds"
                                        <?php if ($validation_status['odds']) echo 'disabled'; ?>>
                                    ✓ <?php echo $validation_status['odds'] ? 'Validated' : 'Validate'; ?>
                                </button>
                                <?php if ($validation_status['odds']): ?>
                                    <button type="button" class="bsp-v2-unlink-btn" data-api="odds">
                                        🔴 Unlink API
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bsp-v2-validation-status" id="status-odds">
                            <?php if ($validation_status['odds']): ?>
                                <span class="bsp-v2-validation-ok">✓ Odds-API is validated</span>
                            <?php else: ?>
                                <span class="bsp-v2-validation-pending">⊘ Not validated yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <div class="bsp-v2-api-form-row">
                            <div class="bsp-v2-api-form-row-input">
                                <label for="bsp_v2_api_key_football">Football-API Key</label>
                                <input type="password" id="bsp_v2_api_key_football" name="bsp_v2_api_key_football" 
                                       value="<?php echo esc_attr(bsp_v2_option('api_key_football')); ?>" 
                                       placeholder="your-api-key..." class="bsp-v2-input"
                                       <?php if ($validation_status['football']) echo 'readonly style="cursor:not-allowed;opacity:0.7;"'; ?>>
                                <p class="description">Get it from <a href="https://api-football.com/" target="_blank">api-football.com</a></p>
                            </div>
                            <div class="bsp-v2-api-actions">
                                <button type="button" class="bsp-v2-validate-btn <?php echo $validation_status['football'] ? 'validated' : ''; ?>" data-api="football"
                                        <?php if ($validation_status['football']) echo 'disabled'; ?>>
                                    ✓ <?php echo $validation_status['football'] ? 'Validated' : 'Validate'; ?>
                                </button>
                                <?php if ($validation_status['football']): ?>
                                    <button type="button" class="bsp-v2-unlink-btn" data-api="football">
                                        🔴 Unlink API
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bsp-v2-validation-status" id="status-football">
                            <?php if ($validation_status['football']): ?>
                                <span class="bsp-v2-validation-ok">✓ Football-API is validated</span>
                            <?php else: ?>
                                <span class="bsp-v2-validation-pending">⊘ Not validated yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <div class="bsp-v2-api-form-row">
                            <div class="bsp-v2-api-form-row-input">
                                <label for="bsp_v2_api_key_openai">OpenAI API Key</label>
                                <input type="password" id="bsp_v2_api_key_openai" name="bsp_v2_api_key_openai" 
                                       value="<?php echo esc_attr(bsp_v2_option('api_key_openai')); ?>" 
                                       placeholder="sk-..." class="bsp-v2-input"
                                       <?php if ($validation_status['openai']) echo 'readonly style="cursor:not-allowed;opacity:0.7;"'; ?>>
                                <p class="description">Required for AI analysis features. Get it from <a href="https://platform.openai.com/" target="_blank">OpenAI</a></p>
                            </div>
                            <div class="bsp-v2-api-actions">
                                <button type="button" class="bsp-v2-validate-btn <?php echo $validation_status['openai'] ? 'validated' : ''; ?>" data-api="openai"
                                        <?php if ($validation_status['openai']) echo 'disabled'; ?>>
                                    ✓ <?php echo $validation_status['openai'] ? 'Validated' : 'Validate'; ?>
                                </button>
                                <?php if ($validation_status['openai']): ?>
                                    <button type="button" class="bsp-v2-unlink-btn" data-api="openai">
                                        🔴 Unlink API
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bsp-v2-validation-status" id="status-openai">
                            <?php if ($validation_status['openai']): ?>
                                <span class="bsp-v2-validation-ok">✓ OpenAI API is validated</span>
                            <?php else: ?>
                                <span class="bsp-v2-validation-pending">⊘ Not validated yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <label for="bsp_v2_openai_model">OpenAI Model</label>
                        <select id="bsp_v2_openai_model" name="bsp_v2_openai_model" class="bsp-v2-input">
                            <option value="">-- Select a model --</option>
                            <?php
                            $models = bsp_v2_get_openai_models();
                            $current_model = bsp_v2_normalize_openai_model(bsp_v2_option('openai_model'));
                            foreach ($models as $model_id => $model_label):
                            ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_model, $model_id); ?>>
                                    <?php echo esc_html($model_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Choose the OpenAI model to use for AI analysis. 
                            <strong>gpt-5.4-mini</strong> is recommended for the best balance of quality and cost.
                            The plugin uses the Responses API for model validation and runtime analysis.
                        </p>
                    </div>
                </div>
                
                <div class="bsp-v2-settings-section">
                    <h2>📊 API Usage Limits</h2>
                    <p class="description">Set limits to control API usage and manage costs. Leave empty for unlimited usage.</p>
                    
                    <div class="bsp-v2-form-group">
                        <label for="bsp_v2_limit_openai_tokens">OpenAI Token Limit (per month)</label>
                        <input type="number" id="bsp_v2_limit_openai_tokens" name="bsp_v2_limit_openai_tokens" 
                               value="<?php echo esc_attr(bsp_v2_option('limit_openai_tokens')); ?>" 
                               placeholder="100000" class="bsp-v2-input" min="0">
                           <p class="description">Maximum tokens to use per month on the OpenAI API. Cost depends on the selected model. Leave empty for unlimited.</p>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <label for="bsp_v2_limit_odds_api_calls">Odds-API Call Limit (per day)</label>
                        <input type="number" id="bsp_v2_limit_odds_api_calls" name="bsp_v2_limit_odds_api_calls" 
                               value="<?php echo esc_attr(bsp_v2_option('limit_odds_api_calls')); ?>" 
                               placeholder="100" class="bsp-v2-input" min="0">
                        <p class="description">Maximum API calls per day to Odds-API. Most plans include 500 calls/month. Leave empty for unlimited.</p>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <label for="bsp_v2_limit_football_api_calls">Football-API Call Limit (per day)</label>
                        <input type="number" id="bsp_v2_limit_football_api_calls" name="bsp_v2_limit_football_api_calls" 
                               value="<?php echo esc_attr(bsp_v2_option('limit_football_api_calls')); ?>" 
                               placeholder="100" class="bsp-v2-input" min="0">
                        <p class="description">Maximum API calls per day to Football-API. Standard plans include 100 calls/day. Leave empty for unlimited.</p>
                    </div>
                </div>
                
                <div class="bsp-v2-settings-section">
                    <h2>⚙️ General Settings</h2>
                    <p class="description">Configure general plugin behaviour.</p>
                    
                    <div class="bsp-v2-form-group">
                        <label>
                            <input type="checkbox" name="bsp_v2_debug_enabled" value="1" <?php checked(bsp_v2_option('debug_enabled'), 1); ?>>
                            Enable Debug Mode
                        </label>
                        <p class="description">Show detailed debug information in logs</p>
                    </div>
                    
                    <div class="bsp-v2-form-group">
                        <label>
                            <input type="checkbox" name="bsp_v2_auto_refresh" value="1" <?php checked(bsp_v2_option('auto_refresh'), 1); ?>>
                            Auto-Refresh Dashboard
                        </label>
                        <p class="description">Automatically refresh data every 5 minutes</p>
                    </div>
                </div>
                
                <div class="bsp-v2-settings-section">
                    <h2>🔧 Debug Tools</h2>
                    <p class="description">Test API connectivity and view detailed diagnostics.</p>
                    
                    <div class="bsp-v2-debug-tools-row">
                        <button type="button" id="bsp-v2-debug-test-all" class="bsp-v2-button bsp-v2-button-info">
                            🧪 Test All APIs
                        </button>
                        <button type="button" id="bsp-v2-debug-test-odds" class="bsp-v2-button bsp-v2-button-warning-plain">
                            📊 Test Odds-API
                        </button>
                        <button type="button" id="bsp-v2-debug-test-football" class="bsp-v2-button bsp-v2-button-success-plain">
                            ⚽ Test Football-API
                        </button>
                        <button type="button" id="bsp-v2-debug-test-openai" class="bsp-v2-button bsp-v2-button-purple">
                            🤖 Test OpenAI
                        </button>
                    </div>
                    
                    <div id="bsp-v2-debug-results" class="bsp-v2-debug-output-box">
                        <pre id="bsp-v2-debug-output" class="bsp-v2-debug-pre"></pre>
                    </div>
                </div>
                
                <div class="bsp-v2-form-actions">
                    <button type="submit" class="bsp-v2-button bsp-v2-button-primary">💾 Save Settings</button>
                </div>
            </form>
            
            <script>
            (function($) {
                function runDebugTest(testType) {
                    var $output = $('#bsp-v2-debug-output');
                    var $results = $('#bsp-v2-debug-results');
                    
                    $output.text('Testing ' + testType + '...');
                    $results.addClass('visible');
                    
                    $.ajax({
                        type: 'POST',
                        url: bspV2Data.ajaxurl,
                        data: {
                            action: 'bsp_v2_debug_' + testType,
                            nonce: bspV2Data.nonce
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $output.text(JSON.stringify(response.data, null, 2));
                            } else {
                                $output.text('Error: ' + (response.data || 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            $output.text('AJAX Error: ' + status + '\n' + error + '\n\nResponse: ' + xhr.responseText);
                        }
                    });
                }
                
                $(document).ready(function() {
                    $('#bsp-v2-debug-test-all').on('click', function(e) {
                        e.preventDefault();
                        runDebugTest('test_apis');
                    });
                    
                    $('#bsp-v2-debug-test-odds').on('click', function(e) {
                        e.preventDefault();
                        runDebugTest('test_odds');
                    });
                    
                    $('#bsp-v2-debug-test-football').on('click', function(e) {
                        e.preventDefault();
                        runDebugTest('test_football');
                    });
                    
                    $('#bsp-v2-debug-test-openai').on('click', function(e) {
                        e.preventDefault();
                        runDebugTest('test_openai');
                    });
                });
            })(jQuery);
            </script>
        </div>
        <?php
    }
    
    public static function render_api_section() {
        echo '<p>Configure your API keys below</p>';
    }
    
    public static function render_themes() {
        $current_theme = bsp_v2_get_current_theme();
        $themes = bsp_v2_get_available_themes();
        $switched_theme = isset($_GET['theme-switched']) ? sanitize_key(wp_unslash($_GET['theme-switched'])) : '';
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>🎨 Theme Settings</h1>
                <p class="bsp-v2-subtitle">Select and customize your plugin's visual appearance</p>
            </div>

            <?php if ($switched_theme !== '' && $switched_theme === $current_theme && isset($themes[$current_theme])): ?>
                <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
                    <p>✓ Theme changed to <?php echo esc_html($themes[$current_theme]); ?>.</p>
                </div>
            <?php endif; ?>
            
            <div class="bsp-v2-settings-form">
                <div class="bsp-v2-settings-section">
                    <h2>Available Themes</h2>
                    <p class="description">Choose a theme to customize the look and feel of your dashboard and frontend widgets. Changes apply immediately.</p>
                    
                    <div class="bsp-v2-themes-grid">
                        <?php foreach ($themes as $slug => $name): ?>
                            <div class="bsp-v2-theme-card <?php echo $current_theme === $slug ? 'active' : ''; ?>" data-theme="<?php echo esc_attr($slug); ?>">
                                <h3><?php echo esc_html($name); ?></h3>
                                <p class="bsp-v2-theme-description">
                                    <?php 
                                    $descriptions = [
                                        'basic' => 'Blue-purple gradient with clean, professional styling',
                                        'quiet-thoughts' => 'Purple, teal, and gold for a sophisticated appearance'
                                    ];
                                    echo isset($descriptions[$slug]) ? $descriptions[$slug] : 'Custom theme';
                                    ?>
                                </p>
                                <?php if ($current_theme === $slug): ?>
                                    <span class="bsp-v2-theme-active-label">✓ Active Theme</span>
                                <?php else: ?>
                                    <button type="button" class="bsp-v2-theme-select-btn bsp-v2-button bsp-v2-button-primary" style="width: 100%;">
                                        Apply Theme
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public static function render_search_params() {
        BSP_V2_Search_Params::render_page();
    }
    
    // Team Badges removed - badges are now stored on-demand when betting suggestions are fetched
    
    public static function render_old_placeholder() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        // Handle manual sync
        if (isset($_POST['bsp_v2_sync_badges']) && check_admin_referer('bsp_v2_team_badges_nonce')) {
            $teams = new BSP_V2_Teams();
            $count = $teams->sync_recent_team_badges();
            echo '<div class="notice notice-success"><p>✓ Team badges synced! ' . intval($count) . ' teams processed.</p></div>';
        }
        
        // Handle cache clear
        if (isset($_POST['bsp_v2_clear_badge_cache']) && check_admin_referer('bsp_v2_team_badges_nonce')) {
            $teams = new BSP_V2_Teams();
            $teams->clear_all_badge_cache();
            echo '<div class="notice notice-success"><p>✓ Team badge cache cleared!</p></div>';
        }
        
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>🏆 Team Badges Management</h1>
                <p class="bsp-v2-subtitle">Manage and synchronize team logos from API-Football</p>
            </div>
            
            <div class="bsp-v2-settings-section">
                <h2>Synchronization</h2>
                <p class="description">Sync team badges for all recent matches from your API data.</p>
                
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('bsp_v2_team_badges_nonce'); ?>
                    <button type="submit" name="bsp_v2_sync_badges" class="bsp-v2-button bsp-v2-button-primary">
                        🔄 Sync Team Badges Now
                    </button>
                    <p style="color: #999; font-size: 0.9rem; margin-top: 10px;">
                        This will fetch badges for all teams in recent betting recommendations and store them locally.
                    </p>
                </form>
            </div>
            
            <div class="bsp-v2-settings-section">
                <h2>Cache Management</h2>
                <p class="description">Clear the team badge cache to force a refresh from the API.</p>
                
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('bsp_v2_team_badges_nonce'); ?>
                    <button type="submit" name="bsp_v2_clear_badge_cache" class="bsp-v2-button bsp-v2-button-secondary" 
                            onclick="return confirm('Are you sure? This will clear all cached badges.');">
                        🗑️ Clear Badge Cache
                    </button>
                    <p style="color: #999; font-size: 0.9rem; margin-top: 10px;">
                        Cached badges are stored for 7 days to reduce API calls. Clear the cache to refresh immediately.
                    </p>
                </form>
            </div>
            
            <div class="bsp-v2-settings-section">
                <h2>📊 Cache Statistics</h2>
                <?php
                $teams = new BSP_V2_Teams();
                $cached = $teams->get_all_cached_badges();
                ?>
                <p>
                    <strong>Cached Teams:</strong> <?php echo count($cached); ?>
                </p>
                
                <?php if (!empty($cached)): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Source</th>
                                <th>Badge URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cached as $team_name => $badge_data): ?>
                                <tr>
                                    <td><?php echo esc_html($team_name); ?></td>
                                    <td><?php echo esc_html($badge_data['source'] ?? 'unknown'); ?></td>
                                    <td>
                                        <?php if (!empty($badge_data['local_url'])): ?>
                                            <a href="<?php echo esc_url($badge_data['local_url']); ?>" target="_blank">
                                                Local ↗
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($badge_data['url']); ?>" target="_blank">
                                                API ↗
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999;">No cached team badges yet. Run a sync to populate the cache.</p>
                <?php endif; ?>
            </div>
            
            <div class="bsp-v2-info-box">
                <h3>📖 How Team Badges Work</h3>
                <ul>
                    <li><strong>Automatic Sync:</strong> Team badges are automatically synced every hour as part of the scheduled analysis</li>
                    <li><strong>Local Caching:</strong> Downloaded badges are stored in <code>/wp-content/uploads/bsp-v2-badges/</code></li>
                    <li><strong>Cache Duration:</strong> Badge data is cached for 7 days to minimize API calls</li>
                    <li><strong>Fallback:</strong> If a badge can't be retrieved, a default placeholder is used</li>
                    <li><strong>Display:</strong> Use <code>bsp_v2_get_team_badge_html()</code> function to display badges in templates</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public static function render_database() {
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>🗄️ Database Management</h1>
                <p class="bsp-v2-subtitle">Manage plugin data and clean up the database</p>
            </div>
            
            <div class="bsp-v2-settings-section">
                <h2>📊 Database Status</h2>
                <?php
                global $wpdb;
                $insights_table = $wpdb->prefix . 'bsp_v2_insights';
                $table_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $insights_table
                )) > 0;
                
                $status_class = $table_exists ? 'ok' : 'error';
                $status_text = $table_exists ? '✓ OK - All tables present' : '✗ KO - Missing tables';
                $status_label = $table_exists ? 'OK' : 'KO';
                ?>
                <div class="bsp-v2-db-status-row">
                    <div class="bsp-v2-db-status-badge <?php echo $status_class; ?>">
                        <?php echo $status_label; ?>
                    </div>
                    <div class="bsp-v2-db-status-text">
                        <strong><?php echo $status_text; ?></strong>
                        <p>
                            <?php
                            if ($table_exists) {
                                echo 'Database tables are properly configured and ready to use.';
                            } else {
                                echo 'Plugin tables are missing. You should recreate them immediately.';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!$table_exists): ?>
                    <form method="post" class="bsp-v2-form-actions" style="padding: 0; background: none; border: none; margin-bottom: 20px;">
                        <?php wp_nonce_field('bsp_v2_database_nonce'); ?>
                        <button type="submit" name="bsp_v2_recreate_tables" class="bsp-v2-button bsp-v2-button-db-primary"
                                onclick="return confirm('Recreate database tables? This will not delete existing data.');">
                            🔧 Recreate Tables
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="bsp-v2-settings-section bsp-v2-danger-zone">
                <h2>&#9888;&#65039; Danger Zone</h2>
                <p class="bsp-v2-validation-error">These actions are permanent and cannot be undone.</p>
                <table class="bsp-v2-danger-table">
                    <tr class="bsp-v2-danger-row"><td><h3>Clean Team Badges</h3><p>Remove all stored team badge data.</p><form method="post"><?php wp_nonce_field('bsp_v2_database_nonce'); ?><button type="submit" name="bsp_v2_clean_badges" class="bsp-v2-button bsp-v2-button-secondary" onclick="return confirm('Clean all team badges?');">Clean Badges (<?php echo count(BSP_V2_Teams::get_all_badges()); ?> badges)</button></form></td></tr>
                    <tr class="bsp-v2-danger-row"><td><h3>Clean Historical Data</h3><p>Remove historical betting insights older than 30 days.</p><form method="post"><?php wp_nonce_field('bsp_v2_database_nonce'); ?><button type="submit" name="bsp_v2_clean_history" class="bsp-v2-button bsp-v2-button-secondary" onclick="return confirm('Clean data older than 30 days?');">Clean Historical Data</button></form></td></tr>
                    <tr class="bsp-v2-danger-row warn"><td><h3>Drop &amp; Recreate Tables</h3><p>Drops all plugin tables and recreates them fresh.</p><form method="post"><?php wp_nonce_field('bsp_v2_database_nonce'); ?><button type="submit" name="bsp_v2_recreate_tables" class="bsp-v2-button bsp-v2-button-amber" onclick="return confirm('Drop and recreate tables?');">Drop &amp; Recreate</button></form></td></tr>
                    <tr class="bsp-v2-danger-row critical"><td><h3>Complete Database Reset</h3><p>Completely erase all plugin tables and data.</p><form method="post"><?php wp_nonce_field('bsp_v2_database_nonce'); ?><button type="submit" name="bsp_v2_reset_all" class="bsp-v2-button bsp-v2-button-danger" onclick="return confirm('DELETE ALL PLUGIN DATA?') && (prompt('Type DELETE to confirm:') === 'DELETE');">COMPLETE RESET</button></form></td></tr>
                </table>
            </div>
            
            <div class="bsp-v2-settings-section">
                <h2>📈 Database Statistics</h2>
                <?php
                global $wpdb;
                $insights_table = $wpdb->prefix . 'bsp_v2_insights';
                
                // Check if table exists before querying
                $table_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                    DB_NAME,
                    $insights_table
                )) > 0;
                
                $insights_count = 0;
                if ($table_exists) {
                    $insights_count = $wpdb->get_var("SELECT COUNT(*) FROM $insights_table");
                }
                
                $badges = BSP_V2_Teams::get_all_badges();
                $badge_count = count($badges);
                $transient_count = BSP_V2_Cache::count_cached_entries();
                ?>
                <table class="bsp-v2-db-stats-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Stored Insights</td>
                            <td><?php echo $insights_count; ?><?php echo !$table_exists ? ' (table missing)' : ''; ?></td>
                        </tr>
                        <tr>
                            <td>Team Badges</td>
                            <td><?php echo $badge_count; ?></td>
                        </tr>
                        <tr>
                            <td>Transients</td>
                            <td><?php echo $transient_count; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public static function handle_database_cleanup() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['bsp_v2_database_nonce']) || !wp_verify_nonce($_POST['bsp_v2_database_nonce'], 'bsp_v2_database_nonce')) {
            return;
        }
        
        // Recreate tables
        if (isset($_POST['bsp_v2_recreate_tables'])) {
            try {
                BSP_V2_Cache::install_tables();
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>Ô£ô Database tables have been successfully recreated.</p></div>';
                });
            } catch (Throwable $e) {
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p>Ô£ù Error recreating tables: ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }
        
        // Clean badges
        if (isset($_POST['bsp_v2_clean_badges'])) {
            BSP_V2_Teams::clear_all_badges();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Ô£ô All team badges have been cleaned from the database.</p></div>';
            });
        }
        
        // Clean historical data
        if (isset($_POST['bsp_v2_clean_history'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'bsp_v2_insights';
            
            // Check if table exists
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            )) > 0;
            
            if ($table_exists) {
                // Delete records older than 30 days
                $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                $deleted = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table WHERE created_at < %s",
                    $cutoff_date
                ));
                
                add_action('admin_notices', function() use ($deleted) {
                    echo '<div class="notice notice-success is-dismissible"><p>Ô£ô Deleted ' . intval($deleted) . ' historical records older than 30 days.</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning is-dismissible"><p>ÔÜá Insights table does not exist. No data to clean.</p></div>';
                });
            }
        }
        
        // Complete reset - Drop and recreate
        if (isset($_POST['bsp_v2_reset_all'])) {
            global $wpdb;
            
            try {
                // Delete insights table
                $insights_table = $wpdb->prefix . 'bsp_v2_insights';
                $wpdb->query("DROP TABLE IF EXISTS $insights_table");
                
                // Clear badges
                BSP_V2_Teams::clear_all_badges();

                $deleted_option_count = BSP_V2_Cache::delete_plugin_options();
                $deleted_transient_count = BSP_V2_Cache::flush();
                
                // Recreate tables fresh
                BSP_V2_Cache::install_tables();
                
                add_action('admin_notices', function() use ($deleted_option_count, $deleted_transient_count) {
                    echo '<div class="notice notice-success is-dismissible"><p>Ô£ô Complete database reset successful. All plugin data has been removed, ' . intval($deleted_option_count) . ' options were deleted, ' . intval($deleted_transient_count) . ' cached entries were cleared, and tables were recreated fresh.</p></div>';
                });
            } catch (Throwable $e) {
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error is-dismissible"><p>Ô£ù Error during reset: ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }
    }
    
    /**
     * AJAX handler for Odds-API validation
     */
    public static function ajax_validate_odds_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $api_key = isset($_POST['api_key']) ? trim(wp_unslash($_POST['api_key'])) : null;
        if (!empty($api_key)) {
            update_option('bsp_v2_api_key_odds', $api_key);
        }
        
        $result = bsp_v2_validate_odds_api($api_key);
        
        if (is_wp_error($result)) {
            update_option('bsp_v2_api_validated_odds', false);
            ob_end_clean();
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Mark as validated
        update_option('bsp_v2_api_validated_odds', true);
        ob_end_clean();
        wp_send_json_success(['message' => 'Odds-API validated successfully']);
    }
    
    /**
     * AJAX handler for Football-API validation
     */
    public static function ajax_validate_football_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $api_key = isset($_POST['api_key']) ? trim(wp_unslash($_POST['api_key'])) : null;
        if (!empty($api_key)) {
            update_option('bsp_v2_api_key_football', $api_key);
        }
        
        $result = bsp_v2_validate_football_api($api_key);
        
        if (is_wp_error($result)) {
            update_option('bsp_v2_api_validated_football', false);
            ob_end_clean();
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Mark as validated
        update_option('bsp_v2_api_validated_football', true);
        ob_end_clean();
        wp_send_json_success(['message' => 'Football-API validated successfully']);
    }
    
    /**
     * AJAX handler for OpenAI-API validation
     */
    public static function ajax_validate_openai_api() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $api_key = isset($_POST['api_key']) ? trim(wp_unslash($_POST['api_key'])) : null;
        if (!empty($api_key)) {
            update_option('bsp_v2_api_key_openai', $api_key);
        }
        
        // Get the selected model from POST data
        $model = isset($_POST['model']) ? bsp_v2_normalize_openai_model(sanitize_text_field($_POST['model'])) : bsp_v2_normalize_openai_model(bsp_v2_option('openai_model'));
        
        if (empty($model)) {
            ob_end_clean();
            wp_send_json_error(['message' => 'No OpenAI model selected - Please select a model first']);
        }

        update_option('bsp_v2_openai_model', $model);
        
        $result = bsp_v2_validate_openai_api($api_key, $model);
        
        if (is_wp_error($result)) {
            update_option('bsp_v2_api_validated_openai', false);
            ob_end_clean();
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Mark as validated
        update_option('bsp_v2_api_validated_openai', true);
        ob_end_clean();
        wp_send_json_success(['message' => 'OpenAI-API validated successfully with model: ' . esc_html($model)]);
    }
    
    /**
     * AJAX handler for changing active theme
     */
    public static function ajax_change_theme() {
        // Clean output buffering
        while (ob_get_level()) ob_end_clean();
        ob_start();
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $theme = sanitize_text_field($_POST['theme'] ?? '');
        
        if (empty($theme)) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Theme not provided']);
        }
        
        $result = bsp_v2_set_theme($theme);
        
        if (is_wp_error($result)) {
            ob_end_clean();
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $available_themes = bsp_v2_get_available_themes();
        $theme_label = isset($available_themes[$theme]) ? $available_themes[$theme] : bsp_v2_format_theme_label($theme);
        $default_redirect = admin_url('admin.php?page=bsp-v2-themes');
        $redirect_url = wp_get_referer();
        $redirect_url = $redirect_url ? wp_validate_redirect($redirect_url, $default_redirect) : $default_redirect;
        $redirect_url = add_query_arg('theme-switched', $theme, remove_query_arg('theme-switched', $redirect_url));

        bsp_v2_log('Theme changed', [
            'theme' => $theme,
            'user_id' => get_current_user_id(),
        ]);
        
        ob_end_clean();
        wp_send_json_success([
            'message' => 'Theme changed to ' . esc_html($theme_label),
            'theme' => $theme,
            'redirect_url' => $redirect_url,
        ]);
    }

    /**
     * AJAX handler for unlinking (removing) an API key and its validation flag
     */
    public static function ajax_unlink_api() {
        while (ob_get_level()) ob_end_clean();
        ob_start();

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bsp_v2_admin_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Security check failed']);
        }

        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $api = sanitize_key(wp_unslash($_POST['api'] ?? ''));

        $map = [
            'odds'     => ['bsp_v2_api_validated_odds',     'bsp_v2_api_key_odds'],
            'football' => ['bsp_v2_api_validated_football', 'bsp_v2_api_key_football'],
            'openai'   => ['bsp_v2_api_validated_openai',   'bsp_v2_api_key_openai'],
        ];

        if (!isset($map[$api])) {
            ob_end_clean();
            wp_send_json_error(['message' => 'Invalid API identifier']);
        }

        delete_option($map[$api][0]);
        delete_option($map[$api][1]);

        bsp_v2_log('API unlinked', ['api' => $api, 'user_id' => get_current_user_id()]);

        ob_end_clean();
        wp_send_json_success(['message' => ucfirst($api) . ' API unlinked successfully']);
    }
}
