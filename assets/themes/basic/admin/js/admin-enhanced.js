/**
 * SeedBetArt Ai Bot - Enhanced Admin Dashboard JavaScript
 */

(function($) {
    'use strict';

    console.log('[BSP V2 Admin] Script loaded and executing');

    var BSP_V2_Admin = {
        refreshInterval: null,
        adminColorModeStorageKey: 'bsp_v2_admin_color_mode',
        init: function() {
            console.log('[BSP V2 Admin] Initializing...');
            this.bindEvents();
            this.setupAutoRefresh();
            this.initClipboardCopy();
            console.log('[BSP V2 Admin] Initialization complete');
        },

        bindEvents: function() {
            console.log('[BSP V2 Admin] Binding events...');
            
            // API validation buttons
            $(document).on('click', '.bsp-v2-validate-btn', function(e) {
                console.log('[BSP V2 Admin] Validate button clicked');
                e.preventDefault();
                var api = $(this).data('api');
                console.log('[BSP V2 Admin] API type:', api);
                BSP_V2_Admin.validateApi($(this), api);
            });

            // Theme toggle
            $(document).on('click', '.bsp-v2-theme-toggle', function() {
                console.log('[BSP V2 Admin] Theme toggle clicked');
                BSP_V2_Admin.toggleTheme($(this));
            });

            // Theme change button
            $(document).on('click', '#bsp-v2-change-theme-btn', function() {
                console.log('[BSP V2 Admin] Change theme button clicked');
                BSP_V2_Admin.changeTheme($(this));
            });

            // Theme card selection
            $(document).on('click', '.bsp-v2-theme-select-btn', function() {
                console.log('[BSP V2 Admin] Theme card button clicked');
                var $card = $(this).closest('.bsp-v2-theme-card');
                var theme = $card.data('theme');
                BSP_V2_Admin.selectThemeFromCard($(this), theme);
            });

            // Refresh data
            $(document).on('click', '.bsp-v2-refresh-data', function() {
                console.log('[BSP V2 Admin] Refresh data clicked');
                BSP_V2_Admin.refreshData();
            });

            // Unlink API button
            $(document).on('click', '.bsp-v2-unlink-btn', function(e) {
                console.log('[BSP V2 Admin] Unlink button clicked');
                e.preventDefault();
                var api = $(this).data('api');
                BSP_V2_Admin.unlinkApi($(this), api);
            });
            
            console.log('[BSP V2 Admin] Event binding complete');
        },

        validateApi: function($button, api) {
            console.log('[BSP V2 Admin] validateApi called with API:', api);
            
            // Map api name to input field id
            var inputMap = {
                'odds': 'bsp_v2_api_key_odds',
                'football': 'bsp_v2_api_key_football',
                'openai': 'bsp_v2_api_key_openai'
            };
            
            var inputId = inputMap[api];
            console.log('[BSP V2 Admin] Looking for input ID:', inputId);
            
            var apiKeyValue = $('#' + inputId).val();
            console.log('[BSP V2 Admin] API key value found:', apiKeyValue ? 'Yes (length: ' + apiKeyValue.length + ')' : 'No');
            
            if (!apiKeyValue) {
                console.log('[BSP V2 Admin] No API key value, showing error');
                $button.closest('.bsp-v2-form-group').find('.bsp-v2-validation-status').html(
                    '<span class="bsp-v2-validation-error">✗ Please enter an API key first</span>'
                );
                return;
            }

            var originalText = $button.text();
            var originalBgColor = $button.css('background');
            console.log('[BSP V2 Admin] Original button text:', originalText);
            console.log('[BSP V2 Admin] Original background:', originalBgColor);
            
            $button.prop('disabled', true).html('🔄 Validating...').css('opacity', '0.7');

            var action = 'bsp_v2_validate_' + api + '_api';
            console.log('[BSP V2 Admin] Sending AJAX request with action:', action);
            console.log('[BSP V2 Admin] AJAX URL:', bspV2Data.ajaxurl);
            console.log('[BSP V2 Admin] Nonce available:', bspV2Data.nonce ? 'Yes' : 'No');

            $.ajax({
                type: 'POST',
                url: bspV2Data.ajaxurl,
                data: (function() {
                    var ajaxData = {
                        action: action,
                        nonce: bspV2Data.nonce,
                        api_key: apiKeyValue
                    };
                    
                    // For OpenAI, include the selected model
                    if (api === 'openai') {
                        var selectedModel = $('#bsp_v2_openai_model').val();
                        if (selectedModel) {
                            ajaxData.model = selectedModel;
                        }
                    }
                    
                    return ajaxData;
                })(),
                success: function(response) {
                    console.log('[BSP V2 Admin] AJAX success response:', response);
                    var statusDiv = $('#status-' + api);
                    var apiDisplayName = api.charAt(0).toUpperCase() + api.slice(1);
                    
                    if (response.success) {
                        console.log('[BSP V2 Admin] Validation successful');
                        // Lock input, disable+green the Validate button, append Unlink button
                        var $input = $('#' + inputId);
                        $input.prop('readonly', true).addClass('bsp-v2-input-locked');
                        statusDiv.html('<span class="bsp-v2-validation-ok">✓ ' + apiDisplayName + '-API is validated</span>');
                        $button.html('✓ Validated')
                               .addClass('validated')
                               .prop('disabled', true)
                               .css({'opacity': '1'});
                        $button.after('<button type="button" class="bsp-v2-unlink-btn" data-api="' + api + '">🔴 Unlink API</button>');
                        BSP_V2_Admin.showNotice('✓ ' + apiDisplayName + ' API validated successfully!', 'success');
                    } else {
                        console.log('[BSP V2 Admin] Validation failed:', response.data);
                        // Failure - turn red and KEEP IT RED
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error';
                        statusDiv.html('<span class="bsp-v2-validation-error">✗ Validation Failed</span><br><span class="bsp-v2-validation-error-detail">Error: ' + errorMessage + '</span>');
                        $button.addClass('bsp-v2-validate-failed').html('✗ Failed').prop('disabled', false);
                        BSP_V2_Admin.showNotice('✗ Validation failed: ' + errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('[BSP V2 Admin] AJAX error:', status, error);
                    console.log('[BSP V2 Admin] Response status:', xhr.status);
                    console.log('[BSP V2 Admin] Response text:', xhr.responseText);
                    var statusDiv = $('#status-' + api);
                    var errorMsg = 'Network error: ' + error;
                    
                    // Try to parse error from response
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        }
                    } catch(e) {
                        console.log('[BSP V2 Admin] Could not parse JSON response:', e);
                    }
                    
                    // If response is HTML (error page), show a more helpful message
                    if (xhr.responseText && xhr.responseText.indexOf('<') === 0) {
                        console.log('[BSP V2 Admin] Received HTML response instead of JSON');
                        if (xhr.status === 0) {
                            errorMsg = 'Network unreachable - Check your internet connection';
                        } else if (xhr.status >= 500) {
                            errorMsg = 'Server error - Your WordPress server may be having issues';
                        } else {
                            errorMsg = 'Unexpected response from server - Check Activity Log for details';
                        }
                    }
                    
                    console.log('[BSP V2 Admin] Error message:', errorMsg);
                    
                    // Failure - turn red and KEEP IT RED
                    statusDiv.html('<span class="bsp-v2-validation-error">✗ Validation Error</span><br><span class="bsp-v2-validation-error-detail">Error: ' + errorMsg + '</span>');
                    $button.addClass('bsp-v2-validate-failed').html('✗ Error').prop('disabled', false);
                    BSP_V2_Admin.showNotice('✗ ' + errorMsg, 'error');
                }
            });
        },

        unlinkApi: function($button, api) {
            console.log('[BSP V2 Admin] unlinkApi called with API:', api);

            var apiDisplayName = api === 'openai' ? 'OpenAI' : api.charAt(0).toUpperCase() + api.slice(1);
            if (!confirm('Unlink the ' + apiDisplayName + ' API? The stored key will be removed.')) {
                return;
            }

            var inputMap = {
                'odds': 'bsp_v2_api_key_odds',
                'football': 'bsp_v2_api_key_football',
                'openai': 'bsp_v2_api_key_openai'
            };

            $button.prop('disabled', true).html('🔄 Unlinking...').css('opacity', '0.7');

            $.ajax({
                type: 'POST',
                url: bspV2Data.ajaxurl,
                data: {
                    action: 'bsp_v2_unlink_api',
                    nonce: bspV2Data.nonce,
                    api: api
                },
                success: function(response) {
                    var $input = $('#' + inputMap[api]);
                    var $statusDiv = $('#status-' + api);

                    if (response.success) {
                        $input.val('').prop('readonly', false).removeClass('bsp-v2-input-locked');
                        $statusDiv.html('<span class="bsp-v2-validation-pending">⊘ Not validated yet</span>');
                        var $validateBtn = $button.siblings('.bsp-v2-validate-btn');
                        $validateBtn.html('✓ Validate')
                                    .removeClass('validated')
                                    .prop('disabled', false)
                                    ;
                        $button.remove();
                        BSP_V2_Admin.showNotice('✓ ' + apiDisplayName + ' API unlinked', 'success');
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error';
                        $button.prop('disabled', false).html('🔴 Unlink API').css('opacity', '1');
                        BSP_V2_Admin.showNotice('✗ Failed to unlink: ' + errorMessage, 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).html('🔴 Unlink API').css('opacity', '1');
                    BSP_V2_Admin.showNotice('✗ Network error while unlinking API', 'error');
                }
            });
        },

        showNotice: function(message, type) {
            var noticeClass = 'notice notice-' + (type || 'info') + ' is-dismissible';
            var $notice = $('<div class="' + noticeClass + '"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button></div>');

            $('#wpbody').prepend($notice);

            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        },

        setupAutoRefresh: function() {
            var autoRefresh = localStorage.getItem('bsp_v2_auto_refresh');
            if (autoRefresh === 'enabled') {
                BSP_V2_Admin.startAutoRefresh();
            }
        },

        startAutoRefresh: function() {
            BSP_V2_Admin.refreshInterval = setInterval(function() {
                BSP_V2_Admin.refreshData();
            }, 5 * 60 * 1000); // 5 minutes
        },

        stopAutoRefresh: function() {
            if (BSP_V2_Admin.refreshInterval) {
                clearInterval(BSP_V2_Admin.refreshInterval);
                BSP_V2_Admin.refreshInterval = null;
            }
        },

        refreshData: function() {
            var $btn = $('.bsp-v2-refresh-data');
            if ($btn.length) {
                $btn.html('🔄 Refreshing...');
                location.reload();
            }
        },

        applyAdminColorMode: function(mode, $button) {
            if (mode === 'dark') {
                $('html').attr('data-bsp-theme', 'dark');
            } else {
                $('html').removeAttr('data-bsp-theme');
            }

            if ($button && $button.length) {
                $button.html(mode === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode');
            }
        },

        toggleTheme: function($button) {
            var currentMode = localStorage.getItem(this.adminColorModeStorageKey) || 'light';
            var newMode = currentMode === 'light' ? 'dark' : 'light';

            localStorage.setItem(this.adminColorModeStorageKey, newMode);
            this.applyAdminColorMode(newMode, $button);
        },

        submitThemeChange: function(theme, $button, originalText, contextLabel) {
            $.ajax({
                url: bspV2Data.ajaxurl,
                method: 'POST',
                data: {
                    action: 'bsp_v2_change_theme',
                    nonce: bspV2Data.nonce,
                    theme: theme
                },
                success: function(response) {
                    console.log('[BSP V2 Admin] ' + contextLabel + ' success:', response);

                    if (response.success) {
                        $button.html('✓ Applied').css('background', 'linear-gradient(135deg, #4caf50 0%, #45a049 100%)');

                        setTimeout(function() {
                            var redirectUrl = response.data && response.data.redirect_url ? response.data.redirect_url : window.location.href;
                            console.log('[BSP V2 Admin] Reloading page to apply theme:', redirectUrl);
                            window.location.assign(redirectUrl);
                        }, 400);
                        return;
                    }

                    var message = response && response.data && response.data.message ? response.data.message : 'Unknown error';
                    alert('Error: ' + message);
                    $button.prop('disabled', false).html(originalText);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('[BSP V2 Admin] ' + contextLabel + ' error:', textStatus, errorThrown);
                    alert('Error changing theme: ' + textStatus);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        changeTheme: function($button) {
            console.log('[BSP V2 Admin] changeTheme called');
            
            var theme = $('#bsp-v2-theme').val();
            
            if (!theme) {
                alert('Please select a theme');
                return;
            }
            
            console.log('[BSP V2 Admin] Changing to theme:', theme);
            
            var originalText = $button.text();
            $button.prop('disabled', true).html('🔄 Applying...');

            this.submitThemeChange(theme, $button, originalText, 'Theme change');
        },

        selectThemeFromCard: function($button, theme) {
            console.log('[BSP V2 Admin] selectThemeFromCard called with theme:', theme);
            
            var originalText = $button.text();
            $button.prop('disabled', true).html('🔄 Applying...');

            this.submitThemeChange(theme, $button, originalText, 'Theme card selection');
        },

        initClipboardCopy: function() {
            $(document).on('click', '.bsp-v2-copy-to-clipboard', function() {
                var text = $(this).data('copy');
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();

                var originalText = $(this).text();
                $(this).text('✓ Copied!');
                setTimeout(function() {
                    $(this).text(originalText);
                }.bind(this), 2000);
            });
        }
    };

    $(document).ready(function() {
        console.log('[BSP V2 Admin] Document ready');
        console.log('[BSP V2 Admin] bspV2Data available:', typeof bspV2Data !== 'undefined' ? 'Yes' : 'No');
        if (typeof bspV2Data !== 'undefined') {
            console.log('[BSP V2 Admin] AJAX URL:', bspV2Data.ajaxurl);
            console.log('[BSP V2 Admin] Nonce:', bspV2Data.nonce);
        }
        
        BSP_V2_Admin.init();

        // Restore theme preference
        var savedColorMode = localStorage.getItem(BSP_V2_Admin.adminColorModeStorageKey) || 'light';
        BSP_V2_Admin.applyAdminColorMode(savedColorMode, $('.bsp-v2-theme-toggle').first());

        // Sort table columns
        $(document).on('click', '.bsp-v2-data-table th[data-sortable]', function() {
            var $table = $(this).closest('.bsp-v2-data-table');
            var $tbody = $table.find('tbody');
            var rows = $tbody.find('tr').get();
            var columnIndex = $(this).index();
            var isNumeric = $(this).data('type') === 'numeric';
            var currentSort = $(this).data('sort') || 'asc';
            var newSort = currentSort === 'asc' ? 'desc' : 'asc';

            // Update sort indicators
            $table.find('th[data-sortable]').removeData('sort').html(function() {
                return $(this).text().replace(/\s*[\u2191\u2193]$/, '');
            });
            $(this).data('sort', newSort).append(newSort === 'asc' ? ' ↑' : ' ↓');

            // Sort rows
            rows.sort(function(a, b) {
                var valA = $(a).find('td').eq(columnIndex).text().trim();
                var valB = $(b).find('td').eq(columnIndex).text().trim();

                if (isNumeric) {
                    valA = parseFloat(valA) || 0;
                    valB = parseFloat(valB) || 0;
                    return newSort === 'asc' ? valA - valB : valB - valA;
                }

                return newSort === 'asc' ?
                    valA.localeCompare(valB) :
                    valB.localeCompare(valA);
            });

            // Reorder table
            $.each(rows, function() {
                $tbody.append(this);
            });
        });

        // Live search in tables
        $(document).on('keyup', '.bsp-v2-table-search', function() {
            var searchText = $(this).val().toLowerCase();
            var $table = $($(this).data('target'));

            $table.find('tbody tr').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchText) > -1);
            });
        });

        // Pagination
        $(document).on('click', '.bsp-v2-pagination a', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            BSP_V2_Admin.loadPage(page);
        });
    });

    window.BSP_V2_Admin = BSP_V2_Admin;

})(jQuery);
