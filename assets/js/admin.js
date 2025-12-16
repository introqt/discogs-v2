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
        initAsyncSearch();
        initImportModal();
        initClearCache();
        initLogActions();
        initStockManagement();
    });

    /**
     * Initialize async search functionality
     */
    function initAsyncSearch() {
        const searchForm = $('#ldg-search-form');
        const searchInput = $('#ldg-search-input');
        const resultsContainer = $('#ldg-search-results');
        const loadingIndicator = $('#ldg-search-loading');
        
        if (searchForm.length === 0) {
            return;
        }

        searchForm.on('submit', function(e) {
            e.preventDefault();
            
            const query = searchInput.val().trim();
            
            if (query.length < 2) {
                showNotice('Please enter at least 2 characters', 'warning');
                return;
            }
            
            performSearch(query, 1);
        });
        
        // Handle pagination clicks
        $(document).on('click', '.ldg-pagination a', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            const query = searchInput.val().trim();
            performSearch(query, page);
        });
    }

    /**
     * Perform async search
     */
    function performSearch(query, page) {
        const resultsContainer = $('#ldg-search-results');
        const loadingIndicator = $('#ldg-search-loading');
        
        loadingIndicator.show();
        resultsContainer.html('');
        
        $.ajax({
            url: ldgAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ldg_search_releases',
                nonce: ldgAdmin.searchNonce,
                query: query,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.results, response.data.pagination, query);
                } else {
                    showNotice(response.data.message || 'Search failed', 'error');
                }
            },
            error: function() {
                showNotice('Failed to perform search. Please try again.', 'error');
            },
            complete: function() {
                loadingIndicator.hide();
            }
        });
    }

    /**
     * Display search results
     */
    function displaySearchResults(results, pagination, query) {
        const resultsContainer = $('#ldg-search-results');
        
        if (!results || results.length === 0) {
            resultsContainer.html('<div class="ldg-no-results"><p>' + ldgAdmin.i18n.noResults + '</p></div>');
            return;
        }
        
        let html = '<p class="ldg-results-count">' + 
                   ldgAdmin.i18n.foundResults.replace('%d', results.length) + 
                   '</p><div class="ldg-results-grid">';
        
        results.forEach(function(result) {
            html += '<div class="ldg-result-item">';
            html += '<div class="ldg-result-image">';
            var imageUrl = result.cover_image || result.thumb;
            if (imageUrl) {
                html += '<img src="' + imageUrl + '" alt="' + escapeHtml(result.title) + '">';
            } else {
                html += '<div class="ldg-no-image">No Image</div>';
            }
            html += '</div>';
            html += '<div class="ldg-result-content">';
            html += '<h3 class="ldg-result-title">' + escapeHtml(result.title) + '</h3>';
            
            if (result.year) {
                html += '<p class="ldg-result-year">' + ldgAdmin.i18n.year + ': ' + result.year + '</p>';
            }
            
            if (result.label && result.label.length > 0) {
                html += '<p class="ldg-result-label">' + ldgAdmin.i18n.label + ': ' + escapeHtml(result.label.join(', ')) + '</p>';
            }
            
            if (result.format && result.format.length > 0) {
                html += '<p class="ldg-result-format">' + ldgAdmin.i18n.format + ': ' + escapeHtml(result.format.join(', ')) + '</p>';
            }
            
            html += '<div class="ldg-result-actions">';
            html += '<button type="button" class="button button-primary ldg-import-btn" data-release-id="' + result.id + '">' + ldgAdmin.i18n.import + '</button>';
            html += '<a href="https://www.discogs.com' + result.uri + '" target="_blank" class="button">' + ldgAdmin.i18n.viewOnDiscogs + '</a>';
            html += '</div></div></div>';
        });
        
        html += '</div>';
        
        // Add pagination
        if (pagination && pagination.pages > 1) {
            html += '<div class="ldg-pagination">';
            
            if (pagination.page > 1) {
                html += '<a href="#" class="button" data-page="' + (pagination.page - 1) + '">&laquo; ' + ldgAdmin.i18n.previous + '</a>';
            }
            
            html += '<span class="ldg-page-info">' + 
                    ldgAdmin.i18n.page + ' ' + pagination.page + ' ' + 
                    ldgAdmin.i18n.of + ' ' + pagination.pages + 
                    '</span>';
            
            if (pagination.page < pagination.pages) {
                html += '<a href="#" class="button" data-page="' + (pagination.page + 1) + '">' + ldgAdmin.i18n.next + ' &raquo;</a>';
            }
            
            html += '</div>';
        }
        
        resultsContainer.html(html);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

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
