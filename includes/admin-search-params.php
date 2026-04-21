<?php
/**
 * Search Parameters Configuration Page
 * Advanced filtering options for each betting strategy
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Search_Params {
    
    public static function init() {
        add_action('admin_init', [__CLASS__, 'register_params']);
    }
    
    public static function register_params() {
        // Value Bets Parameters
        register_setting('bsp_v2_search_params', 'bsp_v2_vb_min_ev');
        register_setting('bsp_v2_search_params', 'bsp_v2_vb_min_odds');
        register_setting('bsp_v2_search_params', 'bsp_v2_vb_max_odds');
        register_setting('bsp_v2_search_params', 'bsp_v2_vb_min_confidence');
        
        // Lay The Draw Parameters
        register_setting('bsp_v2_search_params', 'bsp_v2_ltd_max_draw_prob');
        register_setting('bsp_v2_search_params', 'bsp_v2_ltd_min_draw_odds');
        register_setting('bsp_v2_search_params', 'bsp_v2_ltd_form_weight');
        register_setting('bsp_v2_search_params', 'bsp_v2_ltd_home_advantage');
        
        // Under 2.5 Goals Parameters
        register_setting('bsp_v2_search_params', 'bsp_v2_under_max_xg');
        register_setting('bsp_v2_search_params', 'bsp_v2_under_form_weight');
        register_setting('bsp_v2_search_params', 'bsp_v2_under_min_odds');
        register_setting('bsp_v2_search_params', 'bsp_v2_under_min_confidence');
    }
    
    public static function get_defaults() {
        return [
            // Value Bets
            'vb_min_ev' => 5,
            'vb_min_odds' => 1.5,
            'vb_max_odds' => 50,
            'vb_min_confidence' => 60,
            
            // Lay The Draw
            'ltd_max_draw_prob' => 25,
            'ltd_min_draw_odds' => 2.5,
            'ltd_form_weight' => 75,
            'ltd_home_advantage' => 1,
            
            // Under 2.5
            'under_max_xg' => 2.3,
            'under_form_weight' => 70,
            'under_min_odds' => 1.5,
            'under_min_confidence' => 60,
        ];
    }
    
    public static function get_param($key, $type = 'vb') {
        $defaults = self::get_defaults();
        $option_key = 'bsp_v2_' . $type . '_' . $key;
        $default_key = $type . '_' . $key;
        return get_option($option_key, $defaults[$default_key] ?? 0);
    }
    
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>🎯 Search Parameters Configuration</h1>
                <p class="bsp-v2-subtitle">Fine-tune betting strategy parameters with sliders</p>
            </div>

            <form method="post" action="options.php" class="bsp-v2-settings-form">
                <?php settings_fields('bsp_v2_search_params'); ?>

                <!-- VALUE BETS SECTION -->
                <div class="bsp-v2-collapsible-section" id="section-value-bets">
                    <button type="button" class="bsp-v2-section-toggle" data-target="section-value-bets">
                        <span class="bsp-v2-section-toggle-title">💰 Value Bets Parameters</span>
                        <span class="bsp-v2-section-toggle-meta">Configure thresholds for identifying value betting opportunities</span>
                        <span class="bsp-v2-section-chevron">▾</span>
                    </button>
                    <div class="bsp-v2-section-body">
                        <div class="bsp-v2-params-grid">
                            <?php self::render_slider('bsp_v2_vb_min_ev',        'Minimum Expected Value (EV)', 'bsp_v2_vb_min_ev',        5,   20,  1,   '%', 'Minimum EV percentage to consider a bet as value'); ?>
                            <?php self::render_slider('bsp_v2_vb_min_odds',       'Minimum Odds',               'bsp_v2_vb_min_odds',       1.1, 5,   0.1, 'x', 'Lowest odds accepted for value bet analysis'); ?>
                            <?php self::render_slider('bsp_v2_vb_max_odds',       'Maximum Odds',               'bsp_v2_vb_max_odds',       10,  100, 5,   'x', 'Highest odds accepted for value bet analysis'); ?>
                            <?php self::render_slider('bsp_v2_vb_min_confidence', 'Minimum Confidence Level',   'bsp_v2_vb_min_confidence', 40,  95,  5,   '%', 'Minimum confidence score required for recommendation'); ?>
                        </div>
                    </div>
                </div>

                <!-- LAY THE DRAW SECTION -->
                <div class="bsp-v2-collapsible-section" id="section-ltd">
                    <button type="button" class="bsp-v2-section-toggle" data-target="section-ltd">
                        <span class="bsp-v2-section-toggle-title">🎯 Lay The Draw (LTD) Parameters</span>
                        <span class="bsp-v2-section-toggle-meta">Configure parameters for draw laying strategy</span>
                        <span class="bsp-v2-section-chevron">▾</span>
                    </button>
                    <div class="bsp-v2-section-body">
                        <div class="bsp-v2-params-grid">
                            <?php self::render_slider('bsp_v2_ltd_max_draw_prob', 'Maximum Draw Probability', 'bsp_v2_ltd_max_draw_prob', 15,  35,  1,   '%', 'Maximum probability for the draw to lay it'); ?>
                            <?php self::render_slider('bsp_v2_ltd_min_draw_odds', 'Minimum Draw Odds',        'bsp_v2_ltd_min_draw_odds', 2.0, 4.5, 0.1, 'x', 'Minimum odds required to lay the draw'); ?>
                            <?php self::render_slider('bsp_v2_ltd_form_weight',   'Team Form Weight',         'bsp_v2_ltd_form_weight',   40,  100, 5,   '%', 'How much to consider recent team performance'); ?>
                            <div class="bsp-v2-param-card bsp-v2-param-card-check">
                                <div class="bsp-v2-param-card-inner">
                                    <label class="bsp-v2-checkbox-label">
                                        <input type="checkbox" name="bsp_v2_ltd_home_advantage" value="1"
                                               <?php checked(get_option('bsp_v2_ltd_home_advantage'), 1); ?>>
                                        <span class="bsp-v2-checkbox-text">Consider Home/Away Advantage</span>
                                    </label>
                                    <p class="description">Account for home team advantage when analyzing draws</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- UNDER 2.5 GOALS SECTION -->
                <div class="bsp-v2-collapsible-section" id="section-under25">
                    <button type="button" class="bsp-v2-section-toggle" data-target="section-under25">
                        <span class="bsp-v2-section-toggle-title">⚽ Under 2.5 Goals Parameters</span>
                        <span class="bsp-v2-section-toggle-meta">Configure parameters for low-scoring match strategy</span>
                        <span class="bsp-v2-section-chevron">▾</span>
                    </button>
                    <div class="bsp-v2-section-body">
                        <div class="bsp-v2-params-grid">
                            <?php self::render_slider('bsp_v2_under_max_xg',         'Maximum Expected Goals (xG)', 'bsp_v2_under_max_xg',         1.8, 3.0, 0.1, '',  'Maximum combined expected goals to consider under'); ?>
                            <?php self::render_slider('bsp_v2_under_form_weight',     'Recent Form Weight',          'bsp_v2_under_form_weight',     50,  100, 5,   '%', 'How much to consider goals from recent matches'); ?>
                            <?php self::render_slider('bsp_v2_under_min_odds',        'Minimum Under Odds',          'bsp_v2_under_min_odds',        1.5, 2.5, 0.1, 'x', 'Minimum odds required for under bet recommendation'); ?>
                            <?php self::render_slider('bsp_v2_under_min_confidence',  'Minimum Confidence Level',    'bsp_v2_under_min_confidence',  40,  95,  5,   '%', 'Minimum confidence score for under recommendation'); ?>
                        </div>
                    </div>
                </div>

                <div class="bsp-v2-form-actions" style="margin-top: 24px;">
                    <button type="submit" class="bsp-v2-button bsp-v2-button-primary">💾 Save Parameters</button>
                    <button type="button" class="bsp-v2-button bsp-v2-button-secondary" onclick="bspResetToDefaults()">↻ Reset to Defaults</button>
                    <button type="button" class="bsp-v2-button bsp-v2-button-secondary" onclick="bspToggleAll(true)" style="margin-left: 6px;">▾ Expand All</button>
                    <button type="button" class="bsp-v2-button bsp-v2-button-secondary" onclick="bspToggleAll(false)" style="margin-left: 6px;">▸ Collapse All</button>
                </div>
            </form>

            <div class="bsp-v2-info-box" style="margin-top: 28px;">
                <h3>📖 Parameter Guidelines</h3>
                <ul>
                    <li><strong>Expected Value (EV):</strong> Higher thresholds = stricter but higher quality bets</li>
                    <li><strong>Confidence Level:</strong> % certainty the model has in the recommendation</li>
                    <li><strong>Form Weight:</strong> % influence of recent performance vs historical data</li>
                    <li><strong>Odds:</strong> Adjust min/max to focus on specific betting markets</li>
                </ul>
            </div>
        </div>

        <script>
        (function() {
            // Collapse/expand sections
            document.querySelectorAll('.bsp-v2-section-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id     = btn.dataset.target;
                    var section = document.getElementById(id);
                    var body   = section.querySelector('.bsp-v2-section-body');
                    var chev   = btn.querySelector('.bsp-v2-section-chevron');
                    var open   = section.classList.toggle('bsp-v2-section-open');
                    chev.textContent = open ? '▾' : '▸';
                    // persist state
                    try { localStorage.setItem('bsp_sp_' + id, open ? '1' : '0'); } catch(e){}
                });
            });

            // Restore persisted state (default: all open)
            document.querySelectorAll('.bsp-v2-collapsible-section').forEach(function(section) {
                var stored = null;
                try { stored = localStorage.getItem('bsp_sp_' + section.id); } catch(e){}
                var open = stored === null ? true : stored === '1';
                var chev = section.querySelector('.bsp-v2-section-chevron');
                if (open) {
                    section.classList.add('bsp-v2-section-open');
                    if (chev) chev.textContent = '▾';
                } else {
                    if (chev) chev.textContent = '▸';
                }
            });

            // Live slider value update
            function updateSliderValue(slider) {
                var card = slider.closest('.bsp-v2-param-card');
                if (!card) return;
                var span = card.querySelector('.bsp-v2-slider-val');
                if (span) span.textContent = slider.value;
            }
            document.querySelectorAll('.bsp-v2-slider').forEach(function(s) {
                updateSliderValue(s);
                s.addEventListener('input', function() { updateSliderValue(this); });
            });
            window.updateSliderValue = updateSliderValue;
        })();

        function bspToggleAll(open) {
            document.querySelectorAll('.bsp-v2-collapsible-section').forEach(function(section) {
                var chev = section.querySelector('.bsp-v2-section-chevron');
                if (open) { section.classList.add('bsp-v2-section-open'); if (chev) chev.textContent = '▾'; }
                else      { section.classList.remove('bsp-v2-section-open'); if (chev) chev.textContent = '▸'; }
                try { localStorage.setItem('bsp_sp_' + section.id, open ? '1' : '0'); } catch(e){}
            });
        }

        function bspResetToDefaults() {
            if (!confirm('Reset all parameters to default values?')) return;
            var defaults = <?php echo wp_json_encode(self::get_defaults()); ?>;
            for (var key in defaults) {
                var field = document.querySelector('[name="bsp_v2_' + key + '"]');
                if (!field) continue;
                if (field.type === 'checkbox') {
                    field.checked = defaults[key] == 1;
                } else {
                    field.value = defaults[key];
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            alert('Parameters reset to defaults. Remember to save!');
        }
        </script>

        <style>
        /* ── Collapsible section shell ───────────────────────────────── */
        .bsp-v2-collapsible-section {
            background: var(--card-bg, #1e2035);
            border: 1px solid var(--border-color, #2d3150);
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .bsp-v2-collapsible-section:hover {
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
        }

        /* ── Toggle header button ────────────────────────────────────── */
        .bsp-v2-section-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
            color: inherit;
        }
        .bsp-v2-section-toggle-title {
            font-size: 1.05rem;
            font-weight: 700;
            flex: 1;
            color: var(--text-dark, #e0e6f0);
        }
        .bsp-v2-section-toggle-meta {
            font-size: 0.82rem;
            color: var(--text-muted, #8899aa);
            flex: 2;
        }
        .bsp-v2-section-chevron {
            font-size: 1.1rem;
            color: var(--primary-color, #667eea);
            transition: transform 0.25s;
            min-width: 18px;
            text-align: right;
        }

        /* ── Body (hidden by default, shown when .bsp-v2-section-open) ─ */
        .bsp-v2-section-body {
            display: none;
            padding: 0 22px 22px;
            border-top: 1px solid var(--border-color, #2d3150);
        }
        .bsp-v2-collapsible-section.bsp-v2-section-open .bsp-v2-section-body {
            display: block;
        }

        /* ── 2-column parameter grid ─────────────────────────────────── */
        .bsp-v2-params-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 18px;
        }
        @media (max-width: 900px) {
            .bsp-v2-params-grid { grid-template-columns: 1fr; }
            .bsp-v2-section-toggle-meta { display: none; }
        }

        /* ── Individual parameter card ───────────────────────────────── */
        .bsp-v2-param-card {
            background: var(--input-bg, #252840);
            border: 1px solid var(--border-color, #2d3150);
            border-radius: 10px;
            padding: 16px 18px 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .bsp-v2-param-card:hover {
            border-color: var(--primary-color, #667eea);
            box-shadow: 0 2px 10px rgba(102,126,234,0.12);
        }
        .bsp-v2-param-card-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* ── Card header: label + value badge ───────────────────────── */
        .bsp-v2-param-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .bsp-v2-param-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-dark, #e0e6f0);
        }
        .bsp-v2-slider-badge {
            background: linear-gradient(135deg, var(--primary-color, #667eea), var(--secondary-color, #764ba2));
            color: #fff;
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            min-width: 46px;
            text-align: center;
        }

        /* ── Slider ──────────────────────────────────────────────────── */
        .bsp-v2-slider {
            width: 100%;
            height: 6px;
            border-radius: 5px;
            background: linear-gradient(to right,
                var(--primary-color, #667eea) 0%,
                var(--secondary-color, #764ba2) 50%,
                var(--accent-color, #f093fb) 100%);
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
            margin: 6px 0 4px;
        }
        .bsp-v2-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: #fff;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102,126,234,0.4);
            border: 3px solid var(--primary-color, #667eea);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .bsp-v2-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 14px rgba(102,126,234,0.6);
        }
        .bsp-v2-slider::-moz-range-thumb {
            width: 20px; height: 20px;
            border-radius: 50%;
            background: #fff;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102,126,234,0.4);
            border: 3px solid var(--primary-color, #667eea);
        }
        .bsp-v2-param-desc {
            font-size: 0.78rem;
            color: var(--text-muted, #8899aa);
            margin: 6px 0 0;
            line-height: 1.4;
        }

        /* ── Checkbox card variant ───────────────────────────────────── */
        .bsp-v2-param-card-check {
            display: flex;
            align-items: center;
        }
        .bsp-v2-checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-dark, #e0e6f0);
            margin-bottom: 6px;
        }
        .bsp-v2-checkbox-label input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--primary-color, #667eea);
            cursor: pointer;
        }

        /* ── Info box ────────────────────────────────────────────────── */
        .bsp-v2-info-box {
            background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.08) 100%);
            border-left: 4px solid var(--primary-color, #667eea);
            padding: 20px 24px;
            border-radius: 8px;
        }
        .bsp-v2-info-box h3 { margin-top: 0; color: var(--primary-color, #667eea); }
        .bsp-v2-info-box ul { margin: 0; padding-left: 20px; }
        .bsp-v2-info-box li { margin: 8px 0; line-height: 1.6; }

        /* ── Secondary button ────────────────────────────────────────── */
        .bsp-v2-button-secondary {
            background: var(--light-gray, #2a2d45);
            color: var(--text-dark, #e0e6f0);
            border: 2px solid var(--border-color, #2d3150);
            margin-left: 8px;
        }
        .bsp-v2-button-secondary:hover {
            border-color: var(--primary-color, #667eea);
        }
        </style>
        <?php
    }
    
    private static function render_slider($input_name, $label, $option_name, $min, $max, $step, $unit, $description) {
        $value = get_option($option_name, 0);
        if (!$value) {
            $defaults = self::get_defaults();
            $key      = str_replace('bsp_v2_', '', $option_name);
            $value    = $defaults[$key] ?? $min;
        }
        ?>
        <div class="bsp-v2-param-card">
            <div class="bsp-v2-param-header">
                <span class="bsp-v2-param-label"><?php echo esc_html($label); ?></span>
                <span class="bsp-v2-slider-badge">
                    <span class="bsp-v2-slider-val"><?php echo esc_html($value); ?></span><?php echo esc_html($unit); ?>
                </span>
            </div>
            <input
                type="range"
                id="<?php echo esc_attr($input_name); ?>"
                name="<?php echo esc_attr($input_name); ?>"
                min="<?php echo esc_attr($min); ?>"
                max="<?php echo esc_attr($max); ?>"
                step="<?php echo esc_attr($step); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="bsp-v2-slider"
            >
            <p class="bsp-v2-param-desc"><?php echo esc_html($description); ?></p>
        </div>
        <?php
    }
}

// Initialize
BSP_V2_Search_Params::init();
