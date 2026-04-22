<?php
/**
 * SeedBetArt Ai Bot Widgets
 * Custom widgets for displaying betting recommendations in sidebars and widget areas
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Widget_Value_Bets extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'bsp_v2_value_bets_widget',
            '💰 Value Bets Widget',
            [
                'description' => 'Display value betting opportunities with configurable parameters',
                'classname' => 'bsp-v2-value-bets-widget',
            ]
        );
    }
    
    public function widget($args, $instance) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                echo wp_kses_post($args['before_widget']);
                if (!empty($instance['title'])) {
                    echo wp_kses_post($args['before_title']);
                    echo esc_html(apply_filters('widget_title', $instance['title']));
                    echo wp_kses_post($args['after_title']);
                }
                echo '<p class="bsp-v2-widget-error"><strong>Configuration Required:</strong> Please validate Odds-API and Football-API in plugin settings.</p>';
                echo wp_kses_post($args['after_widget']);
                return;
            }

            echo wp_kses_post($args['before_widget']);

            if (!empty($instance['title'])) {
                echo wp_kses_post($args['before_title']);
                echo esc_html(apply_filters('widget_title', $instance['title']));
                echo wp_kses_post($args['after_title']);
            }

            $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
            $show_confidence = !empty($instance['show_confidence']) ? 1 : 0;
            $show_ev = !empty($instance['show_ev']) ? 1 : 0;
            $display_style = !empty($instance['display_style']) ? $instance['display_style'] : 'list';

            $logic = new BSP_V2_Logic();
            $bets = $logic->get_value_bets('football', $limit);

            if (is_wp_error($bets)) {
                echo '<p class="bsp-v2-widget-error">' . esc_html($bets->get_error_message()) . '</p>';
            } elseif (empty($bets)) {
                echo '<p class="bsp-v2-widget-muted">No value bets available at the moment.</p>';
            } else {
                $this->render_bets($bets, $display_style, $show_confidence, $show_ev);
            }

            echo wp_kses_post($args['after_widget']);

        } catch (Throwable $e) {
            bsp_v2_log_error('Value Bets Widget Error: ' . $e->getMessage());
            echo '<p class="bsp-v2-widget-error">Widget error occurred</p>';
        }
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Value Bets';
        $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
        $show_confidence = !empty($instance['show_confidence']) ? 1 : 0;
        $show_ev = !empty($instance['show_ev']) ? 1 : 0;
        $display_style = !empty($instance['display_style']) ? $instance['display_style'] : 'list';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Number of Bets to Display:</label>
            <input id="<?php echo esc_attr($this->get_field_id('limit')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" 
                   type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>" style="width: 60px;">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_style')); ?>">Display Style:</label>
            <select id="<?php echo esc_attr($this->get_field_id('display_style')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('display_style')); ?>" class="widefat">
                <option value="list" <?php selected($display_style, 'list'); ?>>List</option>
                <option value="cards" <?php selected($display_style, 'cards'); ?>>Cards</option>
                <option value="compact" <?php selected($display_style, 'compact'); ?>>Compact</option>
            </select>
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_confidence')); ?>" 
                       value="1" <?php checked($show_confidence, 1); ?>>
                Show Confidence Score
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_ev')); ?>" 
                       value="1" <?php checked($show_ev, 1); ?>>
                Show Expected Value (EV)
            </label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = !empty($new_instance['limit']) ? (int)$new_instance['limit'] : 5;
        $instance['show_confidence'] = !empty($new_instance['show_confidence']) ? 1 : 0;
        $instance['show_ev'] = !empty($new_instance['show_ev']) ? 1 : 0;
        $instance['display_style'] = !empty($new_instance['display_style']) ? $new_instance['display_style'] : 'list';
        return $instance;
    }
    
    private function render_bets($bets, $style, $show_confidence, $show_ev) {
        if ($style === 'cards') {
            echo '<div class="bsp-v2-widget-cards">';
            foreach ($bets as $bet) {
                echo $this->render_bet_card($bet, $show_confidence, $show_ev);
            }
            echo '</div>';
        } elseif ($style === 'compact') {
            echo '<ul class="bsp-v2-widget-compact-list">';
            foreach ($bets as $bet) {
                echo $this->render_bet_compact($bet, $show_confidence, $show_ev);
            }
            echo '</ul>';
        } else {
            echo '<ul class="bsp-v2-widget-list">';
            foreach ($bets as $bet) {
                echo $this->render_bet_item($bet, $show_confidence, $show_ev);
            }
            echo '</ul>';
        }
    }
    
    private function render_bet_item($bet, $show_confidence, $show_ev) {
        ?>
        <li class="bsp-v2-widget-item">
            <strong><?php echo esc_html($bet['home']); ?></strong> vs <strong><?php echo esc_html($bet['away']); ?></strong><br>
            <small class="bsp-v2-widget-meta">
                Odds: <?php echo esc_html(number_format($bet['odds'], 2)); ?>
                <?php if ($show_ev && isset($bet['ev'])): ?>
                    | EV: <?php echo esc_html(number_format($bet['ev'], 2)); ?>%
                <?php endif; ?>
                <?php if ($show_confidence && isset($bet['confidence'])): ?>
                    | Conf: <?php echo esc_html(number_format($bet['confidence'], 0)); ?>%
                <?php endif; ?>
            </small>
        </li>
        <?php
    }
    
    private function render_bet_card($bet, $show_confidence, $show_ev) {
        $confidence = $bet['confidence'] ?? 0;
        $confidence_class = $confidence >= 75 ? 'high' : ($confidence >= 50 ? 'medium' : 'low');
        ?>
        <div class="bsp-v2-widget-card bsp-v2-confidence-<?php echo esc_attr($confidence_class); ?>">
            <div class="bsp-v2-card-title">
                <?php echo esc_html($bet['home']); ?><br>vs<br><?php echo esc_html($bet['away']); ?>
            </div>
            <div class="bsp-v2-card-odds">
                <strong><?php echo esc_html(number_format($bet['odds'], 2)); ?>x</strong>
            </div>
            <?php if ($show_ev && isset($bet['ev'])): ?>
                <div class="bsp-v2-card-ev">EV: <?php echo esc_html(number_format($bet['ev'], 2)); ?>%</div>
            <?php endif; ?>
            <?php if ($show_confidence && isset($bet['confidence'])): ?>
                <div class="bsp-v2-card-confidence">
                    <div class="bsp-v2-confidence-bar">
                        <div class="bsp-v2-confidence-fill" style="width: <?php echo esc_attr($bet['confidence']); ?>%"></div>
                    </div>
                    <small><?php echo esc_html(number_format($bet['confidence'], 0)); ?>%</small>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_bet_compact($bet, $show_confidence, $show_ev) {
        ?>
        <li class="bsp-v2-widget-compact">
            <span class="bsp-v2-match"><?php echo esc_html($bet['home'] . ' vs ' . $bet['away']); ?></span>
            <span class="bsp-v2-odds"><?php echo esc_html(number_format($bet['odds'], 2)); ?>x</span>
        </li>
        <?php
    }
}

class BSP_V2_Widget_LTD extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'bsp_v2_ltd_widget',
            '🎯 Lay The Draw Widget',
            [
                'description' => 'Display Lay The Draw recommendations',
                'classname' => 'bsp-v2-ltd-widget',
            ]
        );
    }
    
    public function widget($args, $instance) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                echo wp_kses_post($args['before_widget']);
                if (!empty($instance['title'])) {
                    echo wp_kses_post($args['before_title']);
                    echo esc_html(apply_filters('widget_title', $instance['title']));
                    echo wp_kses_post($args['after_title']);
                }
            echo '<p class="bsp-v2-widget-error"><strong>Configuration Required:</strong> Please validate Odds-API and Football-API in plugin settings.</p>';
            echo wp_kses_post($args['after_widget']);
            return;
        }
        
        echo wp_kses_post($args['before_widget']);
        
        if (!empty($instance['title'])) {
            echo wp_kses_post($args['before_title']);
            echo esc_html(apply_filters('widget_title', $instance['title']));
            echo wp_kses_post($args['after_title']);
        }
        
        $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
        $show_probability = !empty($instance['show_probability']) ? 1 : 0;
        
        $logic = new BSP_V2_Logic();
        $suggestions = $logic->get_ltd_suggestions('football', $limit);
        
        if (is_wp_error($suggestions)) {
            echo '<p class="bsp-v2-widget-error">' . esc_html($suggestions->get_error_message()) . '</p>';
        } elseif (empty($suggestions)) {
            echo '<p class="bsp-v2-widget-muted">No LTD suggestions available.</p>';
        } else {
            echo '<ul class="bsp-v2-ltd-widget-list">';
            foreach ($suggestions as $item) {
                echo '<li class="bsp-v2-ltd-item">';
                echo esc_html($item['home']) . ' vs ' . esc_html($item['away']) . '<br>';
                echo '<small class="bsp-v2-widget-meta">';
                if ($show_probability && isset($item['draw_probability'])) {
                    echo 'Draw Prob: ' . esc_html(number_format($item['draw_probability'], 1)) . '%';
                }
                echo '</small>';
                echo '</li>';
            }
            echo '</ul>';
        }

        echo wp_kses_post($args['after_widget']);

        } catch (Throwable $e) {
            bsp_v2_log_error('LTD Widget Error: ' . $e->getMessage());
            echo '<p class="bsp-v2-widget-error">Widget error occurred</p>';
        }
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Lay The Draw';
        $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
        $show_probability = !empty($instance['show_probability']) ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Number of Recommendations:</label>
            <input id="<?php echo esc_attr($this->get_field_id('limit')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" 
                   type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>" style="width: 60px;">
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_probability')); ?>" 
                       value="1" <?php checked($show_probability, 1); ?>>
                Show Draw Probability
            </label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = !empty($new_instance['limit']) ? (int)$new_instance['limit'] : 5;
        $instance['show_probability'] = !empty($new_instance['show_probability']) ? 1 : 0;
        return $instance;
    }
}

class BSP_V2_Widget_Under25 extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'bsp_v2_under25_widget',
            '⚽ Under 2.5 Goals Widget',
            [
                'description' => 'Display Under 2.5 Goals recommendations',
                'classname' => 'bsp-v2-under25-widget',
            ]
        );
    }
    
    public function widget($args, $instance) {
        try {
            if (!bsp_v2_are_betting_apis_validated()) {
                echo wp_kses_post($args['before_widget']);
                if (!empty($instance['title'])) {
                    echo wp_kses_post($args['before_title']);
                    echo esc_html(apply_filters('widget_title', $instance['title']));
                    echo wp_kses_post($args['after_title']);
                }
                echo '<p class="bsp-v2-widget-error"><strong>Configuration Required:</strong> Please validate Odds-API and Football-API in plugin settings.</p>';
                echo wp_kses_post($args['after_widget']);
                return;
            }
            
            echo wp_kses_post($args['before_widget']);
            
            if (!empty($instance['title'])) {
                echo wp_kses_post($args['before_title']);
                echo esc_html(apply_filters('widget_title', $instance['title']));
                echo wp_kses_post($args['after_title']);
            }
            
            $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
            $show_xg = !empty($instance['show_xg']) ? 1 : 0;
            $show_confidence = !empty($instance['show_confidence']) ? 1 : 0;
            
            $logic = new BSP_V2_Logic();
            $suggestions = $logic->get_under_25_suggestions('football', $limit);
            
            if (is_wp_error($suggestions)) {
                echo '<p class="bsp-v2-widget-error">' . esc_html($suggestions->get_error_message()) . '</p>';
            } elseif (empty($suggestions)) {
                echo '<p class="bsp-v2-widget-muted">No Under 2.5 suggestions available.</p>';
            } else {
                echo '<ul class="bsp-v2-under25-widget-list">';
                foreach ($suggestions as $item) {
                    echo '<li class="bsp-v2-under25-item">';
                    echo esc_html($item['home']) . ' vs ' . esc_html($item['away']) . '<br>';
                    echo '<small class="bsp-v2-widget-meta">';
                    echo 'Odds: ' . esc_html(number_format($item['odds'], 2)) . 'x';
                    if ($show_xg && isset($item['estimated_xg'])) {
                        echo ' | xG: ' . esc_html(number_format($item['estimated_xg'], 2));
                    }
                    if ($show_confidence && isset($item['confidence'])) {
                        echo ' | Conf: ' . esc_html(number_format($item['confidence'], 0)) . '%';
                    }
                    echo '</small>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            
            echo wp_kses_post($args['after_widget']);
            
        } catch (Throwable $e) {
            bsp_v2_log_error('Under 2.5 Widget Error: ' . $e->getMessage());
            echo '<p class="bsp-v2-widget-error">Widget error occurred</p>';
        }
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Under 2.5 Goals';
        $limit = !empty($instance['limit']) ? (int)$instance['limit'] : 5;
        $show_xg = !empty($instance['show_xg']) ? 1 : 0;
        $show_confidence = !empty($instance['show_confidence']) ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Number of Recommendations:</label>
            <input id="<?php echo esc_attr($this->get_field_id('limit')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" 
                   type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>" style="width: 60px;">
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_xg')); ?>" 
                       value="1" <?php checked($show_xg, 1); ?>>
                Show Expected Goals (xG)
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('show_confidence')); ?>" 
                       value="1" <?php checked($show_confidence, 1); ?>>
                Show Confidence Score
            </label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = !empty($new_instance['limit']) ? (int)$new_instance['limit'] : 5;
        $instance['show_xg'] = !empty($new_instance['show_xg']) ? 1 : 0;
        $instance['show_confidence'] = !empty($new_instance['show_confidence']) ? 1 : 0;
        return $instance;
    }
}

// Register widgets
function bsp_v2_register_widgets() {
    register_widget('BSP_V2_Widget_Value_Bets');
    register_widget('BSP_V2_Widget_LTD');
    register_widget('BSP_V2_Widget_Under25');
}

function bsp_v2_enqueue_widget_assets() {
    if (
        !is_active_widget(false, false, 'bsp_v2_value_bets_widget', true) &&
        !is_active_widget(false, false, 'bsp_v2_ltd_widget', true) &&
        !is_active_widget(false, false, 'bsp_v2_under25_widget', true)
    ) {
        return;
    }

    bsp_v2_enqueue_widget_styles();
}

add_action('widgets_init', 'bsp_v2_register_widgets');
add_action('wp_enqueue_scripts', 'bsp_v2_enqueue_widget_assets');
