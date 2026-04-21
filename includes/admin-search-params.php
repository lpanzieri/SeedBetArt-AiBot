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
        
        $defaults = self::get_defaults();
        ?>
        <div class="bsp-v2-admin-wrapper">
            <div class="bsp-v2-header">
                <h1>🎯 Search Parameters Configuration</h1>
                <p class="bsp-v2-subtitle">Fine-tune betting strategy parameters with sliders</p>
            </div>
            
            <form method="post" action="options.php" class="bsp-v2-settings-form">
                <?php settings_fields('bsp_v2_search_params'); ?>
                
                <!-- VALUE BETS SECTION -->
                <div class="bsp-v2-settings-section">
                    <h2>💰 Value Bets Parameters</h2>
                    <p class="description">Configure thresholds for identifying value betting opportunities</p>
                    
                    <?php self::render_slider(
                        'bsp_v2_vb_min_ev',
                        'Minimum Expected Value (EV)',
                        'bsp_v2_vb_min_ev',
                        5, 20, 1,
                        '%',
                        'Minimum EV percentage to consider a bet as value'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_vb_min_odds',
                        'Minimum Odds (Quote minime)',
                        'bsp_v2_vb_min_odds',
                        1.1, 5, 0.1,
                        'x',
                        'Lowest odds accepted for value bet analysis'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_vb_max_odds',
                        'Maximum Odds (Quote massime)',
                        'bsp_v2_vb_max_odds',
                        10, 100, 5,
                        'x',
                        'Highest odds accepted for value bet analysis'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_vb_min_confidence',
                        'Minimum Confidence Level',
                        'bsp_v2_vb_min_confidence',
                        40, 95, 5,
                        '%',
                        'Minimum confidence score required for recommendation'
                    ); ?>
                </div>
                
                <!-- LAY THE DRAW SECTION -->
                <div class="bsp-v2-settings-section">
                    <h2>🎯 Lay The Draw (LTD) Parameters</h2>
                    <p class="description">Configure parameters for draw laying strategy</p>
                    
                    <?php self::render_slider(
                        'bsp_v2_ltd_max_draw_prob',
                        'Maximum Draw Probability',
                        'bsp_v2_ltd_max_draw_prob',
                        15, 35, 1,
                        '%',
                        'Maximum probability for the draw to lay it'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_ltd_min_draw_odds',
                        'Minimum Draw Odds',
                        'bsp_v2_ltd_min_draw_odds',
                        2.0, 4.5, 0.1,
                        'x',
                        'Minimum odds required to lay the draw'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_ltd_form_weight',
                        'Team Form Weight',
                        'bsp_v2_ltd_form_weight',
                        40, 100, 5,
                        '%',
                        'How much to consider recent team performance'
                    ); ?>
                    
                    <div class="bsp-v2-form-group">
                        <label>
                            <input type="checkbox" 
                                   name="bsp_v2_ltd_home_advantage" 
                                   value="1" 
                                   <?php checked(get_option('bsp_v2_ltd_home_advantage'), 1); ?>>
                            Consider Home/Away Advantage
                        </label>
                        <p class="description">Account for home team advantage when analyzing draws</p>
                    </div>
                </div>
                
                <!-- UNDER 2.5 GOALS SECTION -->
                <div class="bsp-v2-settings-section">
                    <h2>⚽ Under 2.5 Goals Parameters</h2>
                    <p class="description">Configure parameters for low-scoring match strategy</p>
                    
                    <?php self::render_slider(
                        'bsp_v2_under_max_xg',
                        'Maximum Expected Goals (xG)',
                        'bsp_v2_under_max_xg',
                        1.8, 3.0, 0.1,
                        '',
                        'Maximum combined expected goals to consider under'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_under_form_weight',
                        'Recent Form Weight',
                        'bsp_v2_under_form_weight',
                        50, 100, 5,
                        '%',
                        'How much to consider goals from recent matches'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_under_min_odds',
                        'Minimum Under Odds',
                        'bsp_v2_under_min_odds',
                        1.5, 2.5, 0.1,
                        'x',
                        'Minimum odds required for under bet recommendation'
                    ); ?>
                    
                    <?php self::render_slider(
                        'bsp_v2_under_min_confidence',
                        'Minimum Confidence Level',
                        'bsp_v2_under_min_confidence',
                        40, 95, 5,
                        '%',
                        'Minimum confidence score for under recommendation'
                    ); ?>
                </div>
                
                <div class="bsp-v2-form-actions">
                    <button type="submit" class="bsp-v2-button bsp-v2-button-primary">💾 Save Parameters</button>
                    <button type="button" class="bsp-v2-button bsp-v2-button-secondary" onclick="resetToDefaults()">↻ Reset to Defaults</button>
                </div>
            </form>
            
            <!-- Info Box -->
            <div class="bsp-v2-info-box">
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
        function updateSliderValue(slider) {
            const parent = slider.closest('.bsp-v2-slider-group');
            if (!parent) {
                return;
            }

            const valueSpan = parent.querySelector('.value');
            if (valueSpan) {
                valueSpan.textContent = slider.value;
            }
        }

        function resetToDefaults() {
            if (confirm('Are you sure? This will reset all parameters to default values.')) {
                const defaults = <?php echo wp_json_encode(self::get_defaults()); ?>;

                for (const [key, value] of Object.entries(defaults)) {
                    const fieldName = 'bsp_v2_' + key;
                    const field = document.querySelector('[name="' + fieldName + '"]');
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = value == 1;
                        } else {
                            field.value = value;
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                }
                alert('Parameters reset to defaults. Remember to save!');
            }
        }

        document.querySelectorAll('.bsp-v2-slider').forEach(function(slider) {
            updateSliderValue(slider);
        });
        </script>
        
        <style>
        .bsp-v2-info-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #f5f0ff 100%);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-top: 30px;
            border-radius: 8px;
        }
        
        .bsp-v2-info-box h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .bsp-v2-info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .bsp-v2-slider-group {
            margin: 25px 0;
        }

        .bsp-v2-slider-group label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .bsp-v2-slider-value {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .bsp-v2-slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: linear-gradient(to right,
                var(--primary-color) 0%,
                var(--secondary-color) 50%,
                var(--accent-color) 100%);
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
        }

        .bsp-v2-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
            border: 3px solid var(--primary-color);
            transition: all 0.2s;
        }

        .bsp-v2-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.6);
        }

        .bsp-v2-slider::-moz-range-thumb {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
            border: 3px solid var(--primary-color);
            transition: all 0.2s;
        }

        .bsp-v2-slider::-moz-range-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.6);
        }

        .bsp-v2-button-secondary {
            background: var(--light-gray);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            margin-left: 10px;
        }

        .bsp-v2-button-secondary:hover {
            background: var(--border-color);
            border-color: var(--primary-color);
        }
        
        .bsp-v2-info-box li {
            margin: 10px 0;
            line-height: 1.6;
        }
        </style>
        <?php
    }
    
    private static function render_slider($input_name, $label, $option_name, $min, $max, $step, $unit, $description) {
        $value = get_option($option_name, 0);
        if (!$value) {
            $defaults = self::get_defaults();
            $key = str_replace('bsp_v2_', '', $option_name);
            $value = $defaults[$key] ?? $min;
        }
        
        ?>
        <div class="bsp-v2-form-group bsp-v2-slider-group">
            <label for="<?php echo esc_attr($input_name); ?>">
                <?php echo esc_html($label); ?>
                <span class="bsp-v2-slider-value">
                    <span class="value"><?php echo esc_html($value); ?></span><?php echo esc_html($unit); ?>
                </span>
            </label>
            
            <input 
                type="range" 
                id="<?php echo esc_attr($input_name); ?>"
                name="<?php echo esc_attr($input_name); ?>"
                min="<?php echo esc_attr($min); ?>"
                max="<?php echo esc_attr($max); ?>"
                step="<?php echo esc_attr($step); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="bsp-v2-slider"
                onchange="updateSliderValue(this)"
                oninput="updateSliderValue(this)"
            >
            
            <p class="description"><?php echo esc_html($description); ?></p>
        </div>
        <?php
    }
}

// Initialize
BSP_V2_Search_Params::init();
