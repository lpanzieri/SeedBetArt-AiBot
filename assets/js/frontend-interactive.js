/**
 * SeedBetArt Ai Bot - Frontend Interactive Tables
 */

(function($) {
    'use strict';

    var BSP_V2_Frontend = {
        init: function() {
            this.initTableSorting();
            this.initTableSearch();
            this.initLiveRefresh();
            this.initTooltips();
        },

        // Table sorting functionality
        initTableSorting: function() {
            $(document).on('click', '.bsp-v2-data-table th[data-sortable]', function() {
                var $table = $(this).closest('.bsp-v2-data-table');
                var $tbody = $table.find('tbody');
                var rows = $tbody.find('tr').get();
                var columnIndex = $(this).index();
                var dataType = $(this).data('type') || 'string';
                var currentSort = $(this).data('sort') || 'asc';
                var newSort = currentSort === 'asc' ? 'desc' : 'asc';

                // Update sort indicators
                $table.find('th[data-sortable]').removeData('sort').html(function() {
                    return $(this).text().replace(/\s*[\u2191\u2193]$/, '');
                });
                var arrow = newSort === 'asc' ? ' ↑' : ' ↓';
                $(this).data('sort', newSort).append(arrow);

                // Sort rows
                rows.sort(function(a, b) {
                    var valA = $(a).find('td').eq(columnIndex).text().trim();
                    var valB = $(b).find('td').eq(columnIndex).text().trim();

                    // Remove currency symbols and percentages for numeric comparison
                    if (dataType === 'numeric') {
                        valA = parseFloat(valA.replace(/[^0-9.-]/g, '')) || 0;
                        valB = parseFloat(valB.replace(/[^0-9.-]/g, '')) || 0;
                        return newSort === 'asc' ? valA - valB : valB - valA;
                    }

                    // String comparison
                    return newSort === 'asc' ?
                        valA.localeCompare(valB) :
                        valB.localeCompare(valA);
                });

                // Reorder table
                $.each(rows, function(index, row) {
                    $tbody.append(row);
                    // Restripe rows
                    $(row).toggleClass('striped', index % 2 === 0);
                });
            });
        },

        // Table search/filter functionality
        initTableSearch: function() {
            $(document).on('keyup', '.bsp-v2-table-search', function() {
                var searchText = $(this).val().toLowerCase();
                var $widget = $(this).closest('.bsp-v2-widget');
                var $table = $widget.find('.bsp-v2-data-table');
                var matchCount = 0;

                if (!$table.length) return;

                $table.find('tbody tr').each(function() {
                    var text = $(this).text().toLowerCase();
                    var isMatch = text.indexOf(searchText) > -1;
                    $(this).toggle(isMatch);
                    if (isMatch) matchCount++;
                });

                // Show empty state if no results
                var $emptyMsg = $widget.find('.bsp-v2-search-empty');
                if (matchCount === 0 && searchText) {
                    if (!$emptyMsg.length) {
                        $widget.find('.bsp-v2-data-table').after(
                            '<p class="bsp-v2-search-empty" style="text-align: center; color: #999; padding: 20px;">🔍 No matches found for "' + 
                            BSP_V2_Frontend.escapeHtml(searchText) + '"</p>'
                        );
                    } else {
                        $emptyMsg.html('🔍 No matches found for "' + BSP_V2_Frontend.escapeHtml(searchText) + '"');
                    }
                } else {
                    $emptyMsg.remove();
                }
            });
        },

        // Live data refresh
        initLiveRefresh: function() {
            $(document).on('click', '.bsp-v2-refresh-btn', function() {
                var $btn = $(this);
                var $widget = $btn.closest('.bsp-v2-widget');
                
                $btn.html('🔄 Updating...').prop('disabled', true);

                setTimeout(function() {
                    location.reload();
                }, 500);
            });

            // Auto-refresh on interval if enabled
            var autoRefresh = localStorage.getItem('bsp_v2_frontend_auto_refresh');
            if (autoRefresh === 'enabled') {
                setInterval(function() {
                    location.reload();
                }, 5 * 60 * 1000); // 5 minutes
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var tooltipText = $(this).data('tooltip');
                if (!tooltipText) return;

                var $tooltip = $('<div class="bsp-v2-tooltip">' + BSP_V2_Frontend.escapeHtml(tooltipText) + '</div>');
                $('body').append($tooltip);

                var offset = $(this).offset();
                $tooltip.css({
                    position: 'absolute',
                    top: offset.top - $tooltip.outerHeight() - 10 + 'px',
                    left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2) + 'px'
                }).addClass('active');

                $(this).data('tooltip-element', $tooltip);
            }).on('mouseleave', '[data-tooltip]', function() {
                var $tooltip = $(this).data('tooltip-element');
                if ($tooltip) {
                    $tooltip.remove();
                }
            });
        },

        // Utility: Escape HTML
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Format number as currency
        formatCurrency: function(value, currency) {
            currency = currency || '$';
            return currency + parseFloat(value).toFixed(2);
        },

        // Format percentage
        formatPercentage: function(value, decimals) {
            decimals = decimals || 1;
            return parseFloat(value).toFixed(decimals) + '%';
        },

        // Export table to CSV
        exportTableCSV: function(selector, filename) {
            var csv = [];
            var $table = $(selector);

            // Add header row
            var headers = [];
            $table.find('th').each(function() {
                headers.push($(this).text());
            });
            csv.push(headers.join(','));

            // Add data rows
            $table.find('tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    row.push('"' + $(this).text().replace(/"/g, '""') + '"');
                });
                csv.push(row.join(','));
            });

            // Trigger download
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', filename || 'export.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        BSP_V2_Frontend.init();

        // Layout Toggle Functionality
        $(document).on('click', '.bsp-v2-layout-btn', function() {
            var $btn = $(this);
            var $widget = $btn.closest('.bsp-v2-widget');
            var layout = $btn.data('layout');

            // Update button states
            $widget.find('.bsp-v2-layout-btn').removeClass('active');
            $btn.addClass('active');

            // Toggle display
            var $table = $widget.find('.bsp-v2-data-table');
            var $grid = $widget.find('.bsp-v2-bets-grid');

            if (layout === 'table') {
                $table.addClass('bsp-v2-layout-active').show();
                $grid.removeClass('bsp-v2-layout-active').hide();
            } else {
                $table.removeClass('bsp-v2-layout-active').hide();
                $grid.addClass('bsp-v2-layout-active').show();
            }

            // Save preference
            localStorage.setItem('bsp_v2_layout_' + $widget.data('bet-type'), layout);
        });

        // Restore layout preference
        $('.bsp-v2-widget').each(function() {
            var $widget = $(this);
            var betType = $widget.data('bet-type');
            var savedLayout = localStorage.getItem('bsp_v2_layout_' + betType);

            if (savedLayout && savedLayout !== 'table') {
                $widget.find('[data-layout="' + savedLayout + '"]').click();
            }
        });

        // Expandable Row Details
        $(document).on('click', '.bsp-v2-expand-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var $row = $btn.closest('tr');
            var $detailsRow = $row.next('.bsp-v2-details-row');

            if ($detailsRow.is(':visible')) {
                $detailsRow.slideUp(300);
                $btn.text('→');
            } else {
                // Hide any other open details rows
                $row.closest('tbody').find('.bsp-v2-details-row:visible').slideUp(300);
                $row.closest('tbody').find('.bsp-v2-expand-btn').text('→');

                // Show this details row
                $detailsRow.slideDown(300);
                $btn.text('↓');
            }
        });

        // Copy to Clipboard
        $(document).on('click', '.bsp-v2-copy-btn', function() {
            var $btn = $(this);
            var details = $btn.data('details');

            if (!details) return;

            var text = '';
            for (var key in details) {
                if (details.hasOwnProperty(key)) {
                    text += key + ': ' + details[key] + '\n';
                }
            }

            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            // Show feedback
            var originalText = $btn.html();
            $btn.html('✓ Copied!').css('color', '#4caf50');
            setTimeout(function() {
                $btn.html(originalText).css('color', '');
            }, 2000);
        });

        // Export button functionality
        $(document).on('click', '[data-export-table]', function() {
            var selector = $(this).data('export-table');
            var filename = $(this).data('export-filename') || 'data.csv';
            BSP_V2_Frontend.exportTableCSV(selector, filename);
        });

        // Stripe tables (alternating row colors)
        $('.bsp-v2-data-table tbody tr:nth-child(odd)').addClass('striped');

        // Add loading class to interactive elements
        $(document).on('click', 'a[href*="#"], button', function() {
            if (!$(this).hasClass('no-loading')) {
                $(this).addClass('loading');
            }
        });

        // Refresh with proper animation
        $(document).on('click', '.bsp-v2-refresh-btn', function() {
            var $btn = $(this);
            $btn.css('animation', 'spin 1s linear');

            setTimeout(function() {
                $btn.css('animation', 'none');
                location.reload();
            }, 1500);
        });
    });

    // Expose to global scope for external use
    window.BSP_V2_Frontend = BSP_V2_Frontend;

})(jQuery);
