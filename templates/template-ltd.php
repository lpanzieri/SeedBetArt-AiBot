<?php
/**
 * Lay The Draw template - Enhanced UI with multiple display options
 */

if (!defined('ABSPATH')) exit;
?>

<div class="bsp-v2-widget bsp-v2-ltd-widget" data-bet-type="ltd">
    <div class="bsp-v2-widget-header">
        <h3>🎯 Lay The Draw</h3>
        <div class="bsp-v2-layout-toggle">
            <button class="bsp-v2-layout-btn bsp-v2-layout-table active" data-layout="table" title="Table View">📊</button>
            <button class="bsp-v2-layout-btn bsp-v2-layout-grid" data-layout="grid" title="Card View">🎯</button>
        </div>
    </div>
    
    <div class="bsp-v2-widget-controls">
        <div class="bsp-v2-search-box">
            <input type="text" class="bsp-v2-table-search" placeholder="Search by match or league..." data-target=".bsp-v2-ltd-table">
        </div>
        <button class="bsp-v2-refresh-btn" data-refresh="ltd" title="Refresh">🔄</button>
    </div>
    
    <?php if (empty($bets)): ?>
        <div class="bsp-v2-empty-state">
            <p>📭 No Lay The Draw suggestions currently available</p>
        </div>
    <?php else: ?>
        <!-- Stats Panel -->
        <?php echo bsp_v2_generate_stats_panel($bets); ?>
        
        <!-- Table View -->
        <table class="bsp-v2-data-table bsp-v2-ltd-table bsp-v2-layout-active">
            <thead>
                <tr>
                    <th data-sortable="true">⚽ Match</th>
                    <th data-sortable="true" data-type="numeric">Draw Odds</th>
                    <th data-sortable="true">Confidence</th>
                    <th data-sortable="true">📅 Date</th>
                    <th data-sortable="false">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bets as $index => $bet): ?>
                    <tr class="bsp-v2-data-row" data-row-index="<?php echo $index; ?>" style="animation-delay: <?php echo ($index * 50); ?>ms;">
                        <td>
                            <?php echo bsp_v2_format_match($bet['home'] ?? 'N/A', $bet['away'] ?? 'N/A'); ?>
                        </td>
                        <td>
                            <?php echo bsp_v2_format_odds($bet['draw_odds'] ?? 1.0); ?>
                        </td>
                        <td>
                            <?php echo bsp_v2_format_confidence($bet['confidence'] ?? 0); ?>
                        </td>
                        <td><?php echo bsp_v2_format_event_datetime($bet['date'] ?? date('Y-m-d H:i')); ?></td>
                        <td>
                            <div class="bsp-v2-action-buttons">
                                <button class="bsp-v2-action-btn bsp-v2-copy-btn" title="Copy details" data-details="<?php echo esc_attr(wp_json_encode($bet)); ?>">📋</button>
                                <button class="bsp-v2-action-btn bsp-v2-expand-btn" title="Details">→</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="bsp-v2-details-row" style="display:none;">
                        <td colspan="5">
                            <div class="bsp-v2-expanded-details">
                                <div class="bsp-v2-detail-grid">
                                    <div class="bsp-v2-detail-item">
                                        <label>Draw Probability:</label>
                                        <span><?php echo is_numeric($bet['draw_odds']) ? number_format((1 / $bet['draw_odds']) * 100, 1) : 'N/A'; ?>%</span>
                                    </div>
                                    <div class="bsp-v2-detail-item">
                                        <label>Confidence:</label>
                                        <span><?php echo number_format($bet['confidence'] ?? 0, 0); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Grid View -->
        <div class="bsp-v2-bets-grid" style="display: none;">
            <?php foreach ($bets as $index => $bet): ?>
                <div class="bsp-v2-bet-card bsp-v2-card-ltd" style="animation-delay: <?php echo ($index * 50); ?>ms;">
                    <div class="bsp-v2-card-header">
                        <div class="bsp-v2-card-match">
                            <strong><?php echo esc_html($bet['home'] ?? 'N/A'); ?></strong>
                            <span class="bsp-v2-vs">vs</span>
                            <strong><?php echo esc_html($bet['away'] ?? 'N/A'); ?></strong>
                        </div>
                        <button class="bsp-v2-card-action" title="More options">⋯</button>
                    </div>
                    <div class="bsp-v2-card-body">
                        <div class="bsp-v2-card-stat">
                            <label>Draw Odds</label>
                            <div class="bsp-v2-card-value-large"><?php echo number_format($bet['draw_odds'] ?? 1.0, 2); ?></div>
                        </div>
                        <div class="bsp-v2-card-stat">
                            <label>Draw Prob</label>
                            <div class="bsp-v2-card-value-large" style="color: #764ba2;"><?php echo is_numeric($bet['draw_odds']) ? number_format((1 / $bet['draw_odds']) * 100, 1) : 'N/A'; ?>%</div>
                        </div>
                        <div class="bsp-v2-card-stat">
                            <label>Confidence</label>
                            <?php echo bsp_v2_format_confidence_radial($bet['confidence'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="bsp-v2-card-footer">
                        <small><?php echo bsp_v2_format_event_datetime($bet['date'] ?? date('Y-m-d H:i')); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
