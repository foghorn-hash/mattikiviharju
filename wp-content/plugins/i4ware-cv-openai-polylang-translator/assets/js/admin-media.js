/**
 * JavaScript implementation for the AI Media Alt & Metadata Generator.
 * Handles single/bulk media processing, live log printing, progress updates, cost estimates, and list table pagination.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initMediaDashboard();
        initMediaLibraryColumn();
    });

    /**
     * Controls the main dashboard Media tab view, listing, pagination, and bulk runner.
     */
    function initMediaDashboard() {
        var $tabContent = $('#cv-oai-pll-media-panel');
        if (!$tabContent.length) {
            return;
        }

        // DOM elements
        var $searchBtn       = $('#cv-oai-media-search-btn');
        var $searchInput     = $('#cv-oai-media-search-input');
        var $filterSelect    = $('#cv-oai-media-filter-status');
        var $bulkBtn         = $('#cv-oai-media-bulk-run-btn');
        var $selectAll       = $('#cv-oai-media-select-all');
        var $listBody        = $('#cv-oai-media-list-table-body');
        var $progressBar     = $('#cv-oai-media-progress-bar');
        var $progressText    = $('#cv-oai-media-progress-text');
        var $progressWrap    = $('#cv-oai-media-progress-wrap');
        var $liveLog         = $('#cv-oai-media-live-log');
        var $tokensVal       = $('#cv-oai-media-stat-tokens');
        var $costVal         = $('#cv-oai-media-stat-cost');
        
        var $prevPageBtn     = $('#cv-oai-media-prev-page');
        var $nextPageBtn     = $('#cv-oai-media-next-page');
        var $pageInfo        = $('#cv-oai-media-page-info');

        // State variables
        var currentPage = 1;
        var totalPages = 1;
        var selectedIds = [];
        var isBulkRunning = false;
        var bulkCancelRequested = false;
        var totalTokensUsed = 0;
        var totalCostUsed = 0.0;

        // Initial Load
        fetchMediaList();

        // Search & Filter listeners
        $searchBtn.on('click', function() {
            currentPage = 1;
            fetchMediaList();
        });

        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                fetchMediaList();
            }
        });

        $filterSelect.on('change', function() {
            currentPage = 1;
            fetchMediaList();
        });

        // Pagination listeners
        $prevPageBtn.on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                fetchMediaList();
            }
        });

        $nextPageBtn.on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                fetchMediaList();
            }
        });

        // Bulk Selection Checkbox
        $selectAll.on('change', function() {
            var checked = $(this).is(':checked');
            $listBody.find('.cv-oai-media-select-item').prop('checked', checked).trigger('change');
        });

        $listBody.on('change', '.cv-oai-media-select-item', function() {
            updateSelectedList();
        });

        function updateSelectedList() {
            selectedIds = [];
            $listBody.find('.cv-oai-media-select-item:checked').each(function() {
                selectedIds.push($(this).val());
            });
            $bulkBtn.prop('disabled', selectedIds.length === 0 || isBulkRunning);
            $bulkBtn.text(cvOaiPllQueueL10n.run_bulk_label ? cvOaiPllQueueL10n.run_bulk_label.replace('%d', selectedIds.length) : 'Run Bulk Analysis (' + selectedIds.length + ')');
        }

        // Fetch Media Items via AJAX
        function fetchMediaList() {
            $listBody.html('<tr><td colspan="5" style="text-align:center; padding: 20px;">Loading images...</td></tr>');
            $selectAll.prop('checked', false);
            updateSelectedList();

            var ajaxData = {
                action: 'cv_oai_pll_get_media_list',
                nonce: cvOaiPllQueueL10n.nonce,
                paged: currentPage,
                search: $searchInput.val(),
                filter_status: $filterSelect.val()
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        renderListTable(response.data.items);
                        totalPages = response.data.total_pages;
                        currentPage = response.data.current;
                        updatePaginationControls();
                    } else {
                        $listBody.html('<tr><td colspan="5" style="text-align:center; color:red; padding: 20px;">' + response.data.message + '</td></tr>');
                    }
                })
                .fail(function() {
                    $listBody.html('<tr><td colspan="5" style="text-align:center; color:red; padding: 20px;">Server connection error.</td></tr>');
                });
        }

        function renderListTable(items) {
            if (!items || items.length === 0) {
                $listBody.html('<tr><td colspan="5" style="text-align:center; padding: 20px;">No images found matching criteria.</td></tr>');
                return;
            }

            var html = '';
            items.forEach(function(item) {
                html += '<tr data-id="' + item.id + '">';
                html += '<td><input type="checkbox" class="cv-oai-media-select-item" value="' + item.id + '" /></td>';
                html += '<td>';
                if (item.thumb) {
                    html += '<img src="' + item.thumb + '" style="max-width:50px; max-height:50px; border-radius:3px; display:block;" />';
                } else {
                    html += '<span style="display:inline-block; width:50px; height:50px; background:#f0f0f1; border-radius:3px;"></span>';
                }
                html += '</td>';
                html += '<td><strong>' + item.title + '</strong><br/><code style="font-size:11px;">' + item.filename + '</code></td>';
                
                // Status Dots
                html += '<td><div style="display:flex; gap:6px;">';
                ['fi', 'en', 'sv'].forEach(function(lang) {
                    var lData = item.status[lang];
                    var hasMeta = lData && lData.alt && lData.title;
                    var color = hasMeta ? '#46b450' : '#dc3232';
                    var tooltip = lang.toUpperCase() + ': ' + (hasMeta ? 'Alt & Title generated' : 'Missing metadata');
                    html += '<span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:' + color + ';" title="' + tooltip + '"></span>';
                });
                html += '</div></td>';

                // Action
                html += '<td>';
                html += '<button type="button" class="button button-small cv-oai-pll-media-table-run-btn" data-id="' + item.id + '">Analyze</button>';
                html += '<span class="spinner" style="float:none; margin:0 0 0 5px; vertical-align:middle;"></span>';
                html += '</td>';
                html += '</tr>';
            });

            $listBody.html(html);
        }

        function updatePaginationControls() {
            $prevPageBtn.prop('disabled', currentPage <= 1);
            $nextPageBtn.prop('disabled', currentPage >= totalPages || totalPages === 0);
            $pageInfo.text(totalPages > 0 ? currentPage + ' / ' + totalPages : '0 / 0');
        }

        // Inline Single Image Action inside Dashboard List Table
        $listBody.on('click', '.cv-oai-pll-media-table-run-btn', function() {
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var id = $btn.data('id');
            var $spinner = $row.find('.spinner');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            var ajaxData = {
                action: 'cv_oai_pll_analyze_media',
                id: id,
                nonce: cvOaiPllQueueL10n.nonce
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);

                    if (response.success) {
                        // Refresh row status
                        fetchMediaList();
                    } else {
                        alert('Analysis failed: ' + response.data.message);
                    }
                })
                .fail(function() {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    alert('Connection error.');
                });
        } );

        // Bulk Queue Implementation
        $bulkBtn.on('click', function() {
            if (isBulkRunning) {
                // We act as Cancel button
                bulkCancelRequested = true;
                logOutput('Stopping queue worker... Please wait until current item completes.');
                $bulkBtn.prop('disabled', true);
                return;
            }

            if (selectedIds.length === 0) {
                return;
            }

            // Start bulk run
            isBulkRunning = true;
            bulkCancelRequested = false;
            $bulkBtn.text('Stop Queue Worker').removeClass('button-primary').addClass('button-link-delete');
            disableDashboardInputs(true);

            $progressWrap.show();
            $liveLog.show().empty();
            updateProgressBar(0, selectedIds.length);

            logOutput('Queue started: Processing ' + selectedIds.length + ' image(s)...');
            processNextBulkItem(0);
        });

        function processNextBulkItem(index) {
            if (bulkCancelRequested) {
                logOutput('Queue worker paused by user.');
                endBulkRun();
                return;
            }

            if (index >= selectedIds.length) {
                logOutput('Queue worker finished! All selected images have been successfully analyzed.');
                endBulkRun();
                return;
            }

            var currentId = selectedIds[index];
            updateProgressBar(index, selectedIds.length);
            logOutput('Analyzing Image #' + currentId + ' (' + (index + 1) + ' of ' + selectedIds.length + ')...');

            // Find corresponding row in table to animate spinner if visible
            var $row = $listBody.find('tr[data-id="' + currentId + '"]');
            var $spinner = $row.find('.spinner');
            $spinner.addClass('is-active');

            var ajaxData = {
                action: 'cv_oai_pll_analyze_media',
                id: currentId,
                nonce: cvOaiPllQueueL10n.nonce
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $spinner.removeClass('is-active');
                    if (response.success) {
                        logOutput('Image #' + currentId + ' successfully analyzed. Alt & title updated.');
                        
                        // Accumulate API cost
                        totalTokensUsed += response.data.tokens;
                        totalCostUsed += parseFloat(response.data.cost);
                        $tokensVal.text(totalTokensUsed.toLocaleString());
                        $costVal.text('$' + totalCostUsed.toFixed(4));

                        // Set completed status visually in the table row
                        if ($row.length) {
                            $row.find('td:nth-child(4) div span').css('background', '#46b450'); // Turn dots green
                            $row.find('.cv-oai-media-select-item').prop('checked', false);
                        }
                    } else {
                        logOutput('Failed Image #' + currentId + ': ' + response.data.message, true);
                    }

                    // Process next with a tiny pause
                    setTimeout(function() {
                        processNextBulkItem(index + 1);
                    }, 500);
                })
                .fail(function() {
                    $spinner.removeClass('is-active');
                    logOutput('Failed Image #' + currentId + ': Server connection error.', true);
                    setTimeout(function() {
                        processNextBulkItem(index + 1);
                    }, 1000);
                });
        }

        function endBulkRun() {
            isBulkRunning = false;
            bulkCancelRequested = false;
            $bulkBtn.text('Run Bulk Analysis').removeClass('button-link-delete').addClass('button-primary');
            disableDashboardInputs(false);
            updateSelectedList();
            fetchMediaList();
        }

        function disableDashboardInputs(disable) {
            $searchInput.prop('disabled', disable);
            $searchBtn.prop('disabled', disable);
            $filterSelect.prop('disabled', disable);
            $prevPageBtn.prop('disabled', disable || currentPage <= 1);
            $nextPageBtn.prop('disabled', disable || currentPage >= totalPages);
            $selectAll.prop('disabled', disable);
            $listBody.find('.cv-oai-media-select-item').prop('disabled', disable);
            $listBody.find('.cv-oai-pll-media-table-run-btn').prop('disabled', disable);
        }

        function updateProgressBar(current, total) {
            var percent = Math.round((current / total) * 100);
            $progressBar.css('width', percent + '%');
            $progressText.text(percent + '% (' + current + ' / ' + total + ')');
        }

        function logOutput(msg, isError) {
            var timestamp = new Date().toLocaleTimeString();
            var colorStyle = isError ? 'color: #dc3232; font-weight: bold;' : 'color: #1d2327;';
            var line = '<div style="margin-bottom: 5px; ' + colorStyle + '">[' + timestamp + '] ' + msg + '</div>';
            $liveLog.append(line);
            $liveLog.scrollTop($liveLog[0].scrollHeight);
        }
    }

    /**
     * Controls the quick single-action button integration in the media library list column.
     */
    function initMediaLibraryColumn() {
        // Use delegation to support dynamically loaded or refreshed media list tables
        $(document).on('click', '.cv-oai-pll-quick-analyze-btn', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');
            var nonce = $btn.data('nonce');
            var $container = $btn.closest('.cv-oai-media-status-container');
            var $spinner = $container.find('.spinner');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            var ajaxData = {
                action: 'cv_oai_pll_analyze_media',
                id: id,
                nonce: nonce
            };

            $.post(ajaxurl, ajaxData)
                .done(function(response) {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);

                    if (response.success) {
                        // Change dots to green
                        $container.find('span[title]').css('background', '#46b450');
                    } else {
                        alert('Analysis failed: ' + response.data.message);
                    }
                })
                .fail(function() {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    alert('Server connection error.');
                });
        });
    }

})(jQuery);
