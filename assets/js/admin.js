/**
 * LiveDG Admin Scripts
 *
 * @package LiveDG
 */

(function($) {
    'use strict';

    let lastSearchData = null;
    let savedSearchesById = {};

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
        const resultsContainer = $('#ldg-search-results');
        const loadingIndicator = $('#ldg-search-loading');
        
        if (searchForm.length === 0) {
            return;
        }

        initSavedSearches();

        searchForm.on('submit', function(e) {
            e.preventDefault();
            
            const searchData = getSearchFormData();

            if (!hasSearchCriteria(searchData)) {
                showNotice(ldgAdmin.i18n.enterKeywordOrFilter || 'Please enter a keyword or at least one filter.', 'warning');
                return;
            }
            
            performSearch(searchData, 1);
        });

        $('#ldg-reset-filters').on('click', function() {
            resetSearchForm();
        });
        
        // Handle pagination clicks
        $(document).on('click', '.ldg-pagination a', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            const searchData = lastSearchData || getSearchFormData();
            performSearch(searchData, page);
        });
    }

    /**
     * Perform async search
     */
    function performSearch(searchData, page) {
        const resultsContainer = $('#ldg-search-results');
        const loadingIndicator = $('#ldg-search-loading');

        lastSearchData = searchData;
        
        loadingIndicator.show();
        resultsContainer.html('');

        const payload = $.extend({}, searchData, {
            action: 'ldg_search_releases',
            nonce: ldgAdmin.searchNonce,
            page: page
        });
        
        $.ajax({
            url: ldgAdmin.ajaxUrl,
            type: 'POST',
            data: payload,
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.results, response.data.pagination);
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
    function displaySearchResults(results, pagination) {
        const resultsContainer = $('#ldg-search-results');
        
        if (!results || results.length === 0) {
            resultsContainer.html('<div class="ldg-no-results"><p>' + ldgAdmin.i18n.noResults + '</p></div>');
            return;
        }

        const totalItems = (pagination && pagination.items) ? pagination.items : results.length;
        
        let html = '<p class="ldg-results-count">' + 
                   ldgAdmin.i18n.foundResults.replace('%d', totalItems) + 
                   '</p><div class="ldg-results-grid">';
        
        results.forEach(function(result) {
            html += '<div class="ldg-result-card">';
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

            html += '<div class="ldg-result-meta">';
            if (result.year) {
                html += '<span>' + ldgAdmin.i18n.year + ': ' + result.year + '</span>';
            }

            if (result.label && result.label.length > 0) {
                html += '<span>' + ldgAdmin.i18n.label + ': ' + escapeHtml(result.label.join(', ')) + '</span>';
            }

            if (result.format && result.format.length > 0) {
                html += '<span>' + ldgAdmin.i18n.format + ': ' + escapeHtml(result.format.join(', ')) + '</span>';
            }
            html += '</div>';
            
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

    function getSearchFormData() {
        return {
            query: ($('#ldg-search-input').val() || '').trim(),
            artist: ($('#ldg-filter-artist').val() || '').trim(),
            release_title: ($('#ldg-filter-release-title').val() || '').trim(),
            label: ($('#ldg-filter-label').val() || '').trim(),
            catno: ($('#ldg-filter-catno').val() || '').trim(),
            year: ($('#ldg-filter-year').val() || '').toString().trim(),
            format: ($('#ldg-filter-format').val() || '').trim(),
            country: ($('#ldg-filter-country').val() || '').trim(),
            genre: ($('#ldg-filter-genre').val() || '').trim(),
            style: ($('#ldg-filter-style').val() || '').trim(),
            sort: ($('#ldg-sort').val() || '').trim(),
            sort_order: ($('#ldg-sort-order').val() || '').trim()
        };
    }

    function hasSearchCriteria(searchData) {
        const keyword = (searchData.query || '').trim();
        const hasKeyword = keyword.length >= 2;

        const hasAnyFilter = [
            'artist',
            'release_title',
            'label',
            'catno',
            'year',
            'format',
            'country',
            'genre',
            'style'
        ].some(function(key) {
            return (searchData[key] || '').toString().trim() !== '';
        });

        if (keyword.length > 0 && keyword.length < 2 && !hasAnyFilter) {
            return false;
        }

        return hasKeyword || hasAnyFilter;
    }

    function resetSearchForm() {
        $('#ldg-search-input').val('');
        $('#ldg-filter-artist').val('');
        $('#ldg-filter-release-title').val('');
        $('#ldg-filter-label').val('');
        $('#ldg-filter-catno').val('');
        $('#ldg-filter-year').val('');
        $('#ldg-filter-format').val('');
        $('#ldg-filter-country').val('');
        $('#ldg-filter-genre').val('');
        $('#ldg-filter-style').val('');
        $('#ldg-sort').val('');
        $('#ldg-sort-order').val('asc');
        lastSearchData = null;
        $('#ldg-search-results').html('');
    }

    function initSavedSearches() {
        const select = $('#ldg-saved-search-select');
        const loadButton = $('#ldg-load-saved-search');
        const saveButton = $('#ldg-save-current-search');
        const deleteButton = $('#ldg-delete-saved-search');

        if (select.length === 0) {
            return;
        }

        fetchSavedSearches();

        saveButton.on('click', function() {
            const name = window.prompt(ldgAdmin.i18n.savedSearchNamePrompt || 'Name this search:');
            if (!name) {
                return;
            }

            const searchData = getSearchFormData();

            if (!hasSearchCriteria(searchData)) {
                showNotice(ldgAdmin.i18n.cannotSaveEmptySearch || 'Cannot save an empty search.', 'warning');
                return;
            }

            const payload = $.extend({}, searchData, {
                action: 'ldg_save_search',
                nonce: ldgAdmin.searchNonce,
                name: name
            });

            $.ajax({
                url: ldgAdmin.ajaxUrl,
                type: 'POST',
                data: payload,
                success: function(response) {
                    if (response.success) {
                        updateSavedSearches(response.data.searches || []);
                        select.val(response.data.search.id);
                        showNotice(ldgAdmin.i18n.savedSearchSaved || 'Saved search.', 'success');
                    } else {
                        showNotice(response.data.message || 'Failed to save search.', 'error');
                    }
                },
                error: function() {
                    showNotice('Failed to save search. Please try again.', 'error');
                }
            });
        });

        loadButton.on('click', function() {
            const id = (select.val() || '').toString();
            if (!id) {
                showNotice(ldgAdmin.i18n.selectSavedSearch || 'Select a saved search first.', 'warning');
                return;
            }

            const search = savedSearchesById[id];
            if (!search) {
                showNotice('Saved search not found.', 'error');
                return;
            }

            applySavedSearchToForm(search);
            const searchData = getSearchFormData();
            performSearch(searchData, 1);
        });

        deleteButton.on('click', function() {
            const id = (select.val() || '').toString();
            if (!id) {
                showNotice(ldgAdmin.i18n.selectSavedSearch || 'Select a saved search first.', 'warning');
                return;
            }

            const confirmed = window.confirm(ldgAdmin.i18n.confirmDeleteSavedSearch || 'Delete this saved search?');
            if (!confirmed) {
                return;
            }

            $.ajax({
                url: ldgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ldg_delete_saved_search',
                    nonce: ldgAdmin.searchNonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        updateSavedSearches(response.data.searches || []);
                        showNotice(ldgAdmin.i18n.savedSearchDeleted || 'Saved search deleted.', 'success');
                    } else {
                        showNotice(response.data.message || 'Failed to delete saved search.', 'error');
                    }
                },
                error: function() {
                    showNotice('Failed to delete saved search. Please try again.', 'error');
                }
            });
        });
    }

    function fetchSavedSearches() {
        $.ajax({
            url: ldgAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ldg_get_saved_searches',
                nonce: ldgAdmin.searchNonce
            },
            success: function(response) {
                if (response.success) {
                    updateSavedSearches(response.data.searches || []);
                }
            }
        });
    }

    function updateSavedSearches(searches) {
        savedSearchesById = {};
        const select = $('#ldg-saved-search-select');

        select.find('option').not(':first').remove();

        searches.forEach(function(search) {
            if (!search || !search.id) {
                return;
            }

            savedSearchesById[search.id] = search;
            select.append($('<option>', {
                value: search.id,
                text: search.name
            }));
        });

        $('#ldg-load-saved-search').prop('disabled', searches.length === 0);
        $('#ldg-delete-saved-search').prop('disabled', searches.length === 0);
    }

    function applySavedSearchToForm(search) {
        const params = search.params || {};

        $('#ldg-search-input').val(search.query || '');
        $('#ldg-filter-artist').val(params.artist || '');
        $('#ldg-filter-release-title').val(params.release_title || '');
        $('#ldg-filter-label').val(params.label || '');
        $('#ldg-filter-catno').val(params.catno || '');
        $('#ldg-filter-year').val(params.year || '');
        $('#ldg-filter-format').val(params.format || '');
        $('#ldg-filter-country').val(params.country || '');
        $('#ldg-filter-genre').val(params.genre || '');
        $('#ldg-filter-style').val(params.style || '');
        $('#ldg-sort').val(params.sort || '');
        $('#ldg-sort-order').val(params.sort_order || 'asc');

        lastSearchData = null;
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
