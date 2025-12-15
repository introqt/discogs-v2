/**
 * LiveDG Admin Scripts
 *
 * @package LiveDG
 */

(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    $(document).ready(function() {
        initImportModal();
        initClearCache();
        initLogActions();
        initStockManagement();
    });

    /**
     * Initialize import modal functionality
     */
    function initImportModal() {
        const modal = $('#ldg-import-modal');
        const closeButtons = $('.ldg-modal-close');
        
        $(document).on('click', '.ldg-import-btn', function() {
            const releaseId = $(this).data('release-id');
            $('#ldg-import-release-id').val(releaseId);
            modal.fadeIn();
        });

        closeButtons.on('click', function() {
            modal.fadeOut();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is(modal)) {
                modal.fadeOut();
            }
        });

        $('#ldg-import-form').on('submit', function(e) {
            e.preventDefault();
            handleImport();
        });
    }

    /**
     * Handle product import
     */
    function handleImport() {
        const form = $('#ldg-import-form');
        const submitButton = form.find('button[type="submit"]');
        const originalText = submitButton.text();

        submitButton.prop('disabled', true).text(ldgAdmin.i18n.importing || 'Importing...');

        const formData = {
            action: 'ldg_import_release',
            nonce: $('#ldg_import_nonce').val(),
            release_id: $('#ldg-import-release-id').val(),
            price: $('#ldg-import-price').val(),
            status: $('#ldg-import-status').val(),
            manage_stock: $('#ldg-import-manage-stock').is(':checked'),
            stock_quantity: $('#ldg-import-stock-quantity').val()
        };

        $.ajax({
            url: ldgAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice(ldgAdmin.i18n.importSuccess, 'success');
                    $('#ldg-import-modal').fadeOut();
                    form[0].reset();
                } else {
                    showNotice(response.data.message || ldgAdmin.i18n.importError, 'error');
                }
            },
            error: function() {
                showNotice(ldgAdmin.i18n.importError, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Initialize clear cache functionality
     */
    function initClearCache() {
        $('#ldg-clear-cache').on('click', function() {
            if (!confirm(ldgAdmin.i18n.confirmClearCache || 'Are you sure you want to clear the cache?')) {
                return;
            }

            const button = $(this);
            const originalText = button.text();

            button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: ldgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ldg_clear_cache',
                    nonce: ldgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Cache cleared successfully', 'success');
                    } else {
                        showNotice('Failed to clear cache', 'error');
                    }
                },
                error: function() {
                    showNotice('Failed to clear cache', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Initialize log actions
     */
    function initLogActions() {
        const contextModal = $('#ldg-log-context-modal');
        const closeButtons = contextModal.find('.ldg-modal-close');

        $(document).on('click', '.ldg-view-context', function() {
            const context = $(this).data('context');
            const formattedContext = JSON.stringify(context, null, 2);
            $('#ldg-log-context-content').text(formattedContext);
            contextModal.fadeIn();
        });

        closeButtons.on('click', function() {
            contextModal.fadeOut();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is(contextModal)) {
                contextModal.fadeOut();
            }
        });

        $('#ldg-clear-logs').on('click', function() {
            const button = $(this);
            const originalText = button.text();

            button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: ldgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ldg_clear_logs',
                    nonce: ldgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Logs cleared successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice('Failed to clear logs', 'error');
                    }
                },
                error: function() {
                    showNotice('Failed to clear logs', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });

        $('#ldg-export-logs').on('click', function() {
            const button = $(this);
            const originalText = button.text();

            button.prop('disabled', true).text('Exporting...');

            $.ajax({
                url: ldgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ldg_export_logs',
                    nonce: ldgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Logs exported successfully', 'success');
                        window.location.href = response.data.url;
                    } else {
                        showNotice('Failed to export logs', 'error');
                    }
                },
                error: function() {
                    showNotice('Failed to export logs', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Initialize stock management toggle
     */
    function initStockManagement() {
        $('#ldg-import-manage-stock').on('change', function() {
            const stockField = $('#ldg-stock-quantity-field');
            
            if ($(this).is(':checked')) {
                stockField.slideDown();
            } else {
                stockField.slideUp();
            }
        });
    }

    /**
     * Show admin notice
     *
     * @param {string} message Notice message
     * @param {string} type Notice type (success, error, warning, info)
     */
    function showNotice(message, type = 'info') {
        const notice = $('<div>', {
            class: 'notice notice-' + type + ' is-dismissible ldg-notice',
            html: '<p>' + message + '</p>'
        });

        $('.wrap > h1').after(notice);

        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });

        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);
