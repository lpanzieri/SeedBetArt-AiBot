<?php
/**
 * AI Lay The Draw Analysis template - Enhanced UI
 */

if (!defined('ABSPATH')) exit;
?>

<div class="bsp-v2-ai-widget">
    <h3>🤖 AI Lay The Draw Analysis</h3>
    
    <?php if (!bsp_v2_ai_enabled()): ?>
        <p style="color: #ff9800; padding: 15px; background: #fff3e0; border-radius: 6px;">
            <strong>⚠️ AI features require OpenAI API key configuration</strong>
        </p>
    <?php elseif (empty($analysis)): ?>
        <div class="bsp-v2-empty-state">
            <p>🤔 No AI analysis available at this time</p>
        </div>
    <?php else: ?>
        <div class="bsp-v2-analysis-content">
            <?php foreach ($analysis as $item): ?>
                <div class="bsp-v2-analysis-item">
                    <h4><?php echo esc_html($item['match'] ?? 'Unknown Match'); ?></h4>
                    <p><?php echo wp_kses_post($item['analysis'] ?? 'No analysis available'); ?></p>
                    <div class="bsp-v2-analysis-meta">
                        <span><strong>Confidence:</strong> <?php echo esc_html(number_format($item['confidence'] ?? 0, 0)); ?>%</span>
                        <span><strong>Draw Odds:</strong> <?php echo esc_html(number_format($item['odds'] ?? 1, 2)); ?></span>
                        <span><strong>League:</strong> <?php echo esc_html($item['league'] ?? 'Unknown'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
