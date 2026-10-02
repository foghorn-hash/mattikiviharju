/**
 * JS controller for the i4ware AI SEO Agent plugin.
 * Handles the single-post edit metabox generation, counters, and the bulk queue worker.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initSingleEditorMetabox();
        initBulkDashboard();
    });

    /**
     * Sidebar Editor Metabox logic
     */
    function initSingleEditorMetabox() {
        var $root = $('#i4ware-ai-seo-metabox-root');
        if (!$root.length) {
            return;
        }

        var postId      = $root.data('post-id');
        var $genBtn     = $('#i4ware-ai-seo-generate-btn');
        var $applyBtn   = $('#i4ware-ai-seo-apply-btn');
        var $spinner    = $('#i4ware-ai-seo-spinner');
        var $results    = $('#i4ware-ai-seo-results');
        var $titleInput = $('#i4ware-seo-output-title');
        var $descText   = $('#i4ware-seo-output-desc');
        var $keysInput  = $('#i4ware-seo-output-keywords');
        var $recsWrap   = $('#i4ware-seo-recommendations-wrap');
        var $recsList   = $('#i4ware-seo-recommendations-list');
        var $statusMsg  = $('#i4ware-ai-seo-status-msg');

        // Character counter initialization
        $('.i4ware-seo-counter').each(function() {
            updateCounter($(this));
        });

        // Monitor inputs for count updates
        $titleInput.on('input change keyup', function() {
            updateCounter($('.i4ware-seo-counter[data-target="i4ware-seo-output-title"]'));
        });

        $descText.on('input change keyup', function() {
            updateCounter($('.i4ware-seo-counter[data-target="i4ware-seo-output-desc"]'));
        });

        // Trigger AI SEO Generation
        $genBtn.on('click', function(e) {
            e.preventDefault();

            $genBtn.prop('disabled', true);
            $applyBtn.prop('disabled', true);
            $spinner.addClass('is-active').show();
            $statusMsg.css('color', '#2c3338').text(i4wareAiSeoL10n.analyzing);

            var ajaxData = {
                action: 'i4ware_ai_seo_analyze',
                post_id: postId,
                nonce: i4wareAiSeoL10n.nonce
            };

            $.post(i4wareAiSeoL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $genBtn.prop('disabled', false);
                    $applyBtn.prop('disabled', false);
                    $spinner.removeClass('is-active').hide();

                    if (response.success) {
                        $titleInput.val(response.data.seo_title);
                        $descText.val(response.data.meta_description);
                        $keysInput.val(response.data.keywords);

                        // Trigger length calculations
                        $titleInput.trigger('change');
                        $descText.trigger('change');

                        // Populate recommendations
                        $recsList.empty();
                        if (response.data.recommendations && response.data.recommendations.length > 0) {
                            response.data.recommendations.forEach(function(rec) {
                                $recsList.append('<li>' + rec + '</li>');
                            });
                            $recsWrap.show();
                        } else {
                            $recsWrap.hide();
                        }

                        $results.slideDown();
                        $statusMsg.text('');
                    } else {
                        $statusMsg.css('color', 'red').text(i4wareAiSeoL10n.failed + response.data.message);
                    }
                })
                .fail(function() {
                    $genBtn.prop('disabled', false);
                    $spinner.removeClass('is-active').hide();
                    $statusMsg.css('color', 'red').text(i4wareAiSeoL10n.failed + 'Connection error.');
                });
        });

        // Apply and Save SEO modifications
        $applyBtn.on('click', function(e) {
            e.preventDefault();

            $applyBtn.prop('disabled', true);
            $genBtn.prop('disabled', true);
            $spinner.addClass('is-active').show();
            $statusMsg.css('color', '#2c3338').text(i4wareAiSeoL10n.applying);

            var ajaxData = {
                action: 'i4ware_ai_seo_save',
                post_id: postId,
                seo_title: $titleInput.val(),
                meta_description: $descText.val(),
                keywords: $keysInput.val(),
                nonce: i4wareAiSeoL10n.nonce
            };

            $.post(i4wareAiSeoL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $applyBtn.prop('disabled', false);
                    $genBtn.prop('disabled', false);
                    $spinner.removeClass('is-active').hide();

                    if (response.success) {
                        $statusMsg.css('color', 'green').text(i4wareAiSeoL10n.applied);
                        setTimeout(function() {
                            $statusMsg.text('');
                        }, 3000);
                    } else {
                        $statusMsg.css('color', 'red').text(i4wareAiSeoL10n.failed + response.data.message);
                    }
                })
                .fail(function() {
                    $applyBtn.prop('disabled', false);
                    $genBtn.prop('disabled', false);
                    $spinner.removeClass('is-active').hide();
                    $statusMsg.css('color', 'red').text(i4wareAiSeoL10n.failed + 'Connection error.');
                });
        });

        // Helper: updates length counter and colors
        function updateCounter($counterEl) {
            var targetId = $counterEl.data('target');
            var $target = $('#' + targetId);
            if (!$target.length) return;

            var val = $target.val() || '';
            var count = val.length;
            var min = parseInt($counterEl.data('min'));
            var max = parseInt($counterEl.data('max'));

            $counterEl.text(count + ' / ' + max);

            if (count === 0) {
                $counterEl.css('color', '#646970');
            } else if (count >= min && count <= max) {
                $counterEl.css('color', 'green'); // Optimal
            } else if (count < min) {
                $counterEl.css('color', '#cca300'); // Short
            } else {
                $counterEl.css('color', 'red'); // Long
            }
        }
    }

    /**
     * Admin Dashboard Bulk Optimizer Tab logic
     */
    function initBulkDashboard() {
        var $bulkPanel = $('#i4ware-ai-seo-bulk-panel');
        if (!$bulkPanel.length) {
            return;
        }

        // DOM elements
        var $searchBtn       = $('#i4ware-seo-search-btn');
        var $searchInput     = $('#i4ware-seo-search');
        var $filterSelect    = $('#i4ware-seo-filter');
        var $bulkBtn         = $('#i4ware-seo-bulk-run');
        var $selectAll       = $('#i4ware-seo-select-all');
        var $tableBody       = $('#i4ware-seo-table-body');
        var $progressWrap    = $('#i4ware-seo-progress-wrap');
        var $progressBar     = $('#i4ware-seo-progress-bar');
        var $progressText    = $('#i4ware-seo-progress-text');
        var $tokensVal       = $('#i4ware-seo-stat-tokens');
        var $costVal         = $('#i4ware-seo-stat-cost');
        var $liveLog         = $('#i4ware-seo-live-log');
        
        var $prevPageBtn     = $('#i4ware-seo-prev-page');
        var $nextPageBtn     = $('#i4ware-seo-next-page');
        var $pageInfo        = $('#i4ware-seo-page-info');

        // State variables
        var currentPage = 1;
        var totalPages = 1;
        var selectedIds = [];
        var isBulkRunning = false;
        var bulkCancelRequested = false;
        var totalTokensUsed = 0;
        var totalCostUsed = 0.0;

        // Load content
        fetchContentList();

        // Search & filter listeners
        $searchBtn.on('click', function() {
            currentPage = 1;
            fetchContentList();
        });

        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                fetchContentList();
            }
        });

        $filterSelect.on('change', function() {
            currentPage = 1;
            fetchContentList();
        });

        // Pagination
        $prevPageBtn.on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                fetchContentList();
            }
        });

        $nextPageBtn.on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                fetchContentList();
            }
        });

        // Select All checkboxes
        $selectAll.on('change', function() {
            var checked = $(this).is(':checked');
            $tableBody.find('.i4ware-seo-select-item').prop('checked', checked).trigger('change');
        });

        $tableBody.on('change', '.i4ware-seo-select-item', function() {
            updateSelectedList();
        });

        function updateSelectedList() {
            selectedIds = [];
            $tableBody.find('.i4ware-seo-select-item:checked').each(function() {
                selectedIds.push($(this).val());
            });
            $bulkBtn.prop('disabled', selectedIds.length === 0 || isBulkRunning);
            $bulkBtn.text('Run Bulk Optimization (' + selectedIds.length + ')');
        }

        // Fetch Posts and Pages via AJAX
        function fetchContentList() {
            $tableBody.html('<tr><td colspan="5" style="text-align:center; padding:20px;">Loading posts & pages...</td></tr>');
            $selectAll.prop('checked', false);
            updateSelectedList();

            var ajaxData = {
                action: 'i4ware_ai_seo_bulk_list',
                nonce: i4wareAiSeoL10n.nonce,
                paged: currentPage,
                search: $searchInput.val(),
                filter_status: $filterSelect.val()
            };

            $.post(i4wareAiSeoL10n.ajax_url, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        renderListTable(response.data.items);
                        totalPages = response.data.total_pages;
                        currentPage = response.data.current;
                        updatePaginationControls();
                    } else {
                        $tableBody.html('<tr><td colspan="5" style="text-align:center; color:red; padding:20px;">' + response.data.message + '</td></tr>');
                    }
                })
                .fail(function() {
                    $tableBody.html('<tr><td colspan="5" style="text-align:center; color:red; padding:20px;">Server connection error.</td></tr>');
                });
        }

        function renderListTable(items) {
            if (!items || items.length === 0) {
                $tableBody.html('<tr><td colspan="5" style="text-align:center; padding:20px;">No posts or pages found.</td></tr>');
                return;
            }

            var html = '';
            items.forEach(function(item) {
                var statusText = item.has_meta ? '<span style="color:green;">Meta Present</span>' : '<span style="color:#cca300;">Missing Meta</span>';
                if (item.optimized_at) {
                    statusText = '<span style="color:#2271b1; font-weight:bold;">AI Optimized</span>';
                }

                html += '<tr data-id="' + item.id + '">';
                html += '<td><input type="checkbox" class="i4ware-seo-select-item" value="' + item.id + '" /></td>';
                html += '<td><strong>' + item.title + '</strong> (ID: ' + item.id + ')</td>';
                html += '<td style="text-transform: capitalize;">' + item.type + ' (' + item.status + ')</td>';
                html += '<td>' + statusText + '</td>';
                html += '<td>';
                html += '<button type="button" class="button button-small i4ware-seo-table-btn" data-id="' + item.id + '">Optimize</button>';
                html += '<span class="spinner" style="float:none; margin:0 0 0 5px; vertical-align:middle;"></span>';
                html += '</td>';
                html += '</tr>';
            });

            $tableBody.html(html);
        }

        function updatePaginationControls() {
            $prevPageBtn.prop('disabled', currentPage <= 1);
            $nextPageBtn.prop('disabled', currentPage >= totalPages || totalPages === 0);
            $pageInfo.text(totalPages > 0 ? currentPage + ' / ' + totalPages : '0 / 0');
        }

        // Inline Single Post Action inside Bulk Table
        $tableBody.on('click', '.i4ware-seo-table-btn', function() {
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var id = $btn.data('id');
            var $spinner = $row.find('.spinner');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            // 1. Run AI analysis
            var ajaxAnalyze = {
                action: 'i4ware_ai_seo_analyze',
                post_id: id,
                nonce: i4wareAiSeoL10n.nonce
            };

            $.post(i4wareAiSeoL10n.ajax_url, ajaxAnalyze)
                .done(function(response) {
                    if (response.success) {
                        // 2. Save SEO values directly
                        var ajaxSave = {
                            action: 'i4ware_ai_seo_save',
                            post_id: id,
                            seo_title: response.data.seo_title,
                            meta_description: response.data.meta_description,
                            keywords: response.data.keywords,
                            nonce: i4wareAiSeoL10n.nonce
                        };

                        $.post(i4wareAiSeoL10n.ajax_url, ajaxSave)
                            .done(function(saveRes) {
                                $spinner.removeClass('is-active');
                                $btn.prop('disabled', false);

                                if (saveRes.success) {
                                    fetchContentList(); // refresh status
                                } else {
                                    alert('Save failed: ' + saveRes.data.message);
                                }
                            })
                            .fail(function() {
                                $spinner.removeClass('is-active');
                                $btn.prop('disabled', false);
                                alert('Save connection error.');
                            });
                    } else {
                        $spinner.removeClass('is-active');
                        $btn.prop('disabled', false);
                        alert('Analysis failed: ' + response.data.message);
                    }
                })
                .fail(function() {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    alert('Analysis connection error.');
                });
        });

        // Bulk Queue Runner
        $bulkBtn.on('click', function() {
            if (isBulkRunning) {
                bulkCancelRequested = true;
                logOutput('Stopping bulk optimizer... Waiting for current item to finish.');
                $bulkBtn.prop('disabled', true);
                return;
            }

            if (selectedIds.length === 0) {
                return;
            }

            isBulkRunning = true;
            bulkCancelRequested = false;
            $bulkBtn.text('Stop Queue Worker').removeClass('button-primary').addClass('button-link-delete');
            disableDashboardInputs(true);

            $progressWrap.show();
            $liveLog.show().empty();
            updateProgressBar(0, selectedIds.length);

            logOutput('Bulk Optimization started for ' + selectedIds.length + ' item(s)...');
            processNextBulkItem(0);
        });

        function processNextBulkItem(index) {
            if (bulkCancelRequested) {
                logOutput('Bulk optimizer paused by user.');
                endBulkRun();
                return;
            }

            if (index >= selectedIds.length) {
                logOutput('Bulk optimizer completed! All checked posts/pages are optimized.');
                endBulkRun();
                return;
            }

            var currentId = selectedIds[index];
            updateProgressBar(index, selectedIds.length);
            logOutput('Analyzing Post #' + currentId + ' (' + (index + 1) + ' of ' + selectedIds.length + ')...');

            var $row = $tableBody.find('tr[data-id="' + currentId + '"]');
            var $spinner = $row.find('.spinner');
            $spinner.addClass('is-active');

            // 1. Analyze
            var ajaxAnalyze = {
                action: 'i4ware_ai_seo_analyze',
                post_id: currentId,
                nonce: i4wareAiSeoL10n.nonce
            };

            $.post(i4wareAiSeoL10n.ajax_url, ajaxAnalyze)
                .done(function(response) {
                    if (response.success) {
                        logOutput('Generated SEO Meta for Post #' + currentId + ' | Saving metadata...');
                        
                        // 2. Save
                        var ajaxSave = {
                            action: 'i4ware_ai_seo_save',
                            post_id: currentId,
                            seo_title: response.data.seo_title,
                            meta_description: response.data.meta_description,
                            keywords: response.data.keywords,
                            nonce: i4wareAiSeoL10n.nonce
                        };

                        $.post(i4wareAiSeoL10n.ajax_url, ajaxSave)
                            .done(function(saveRes) {
                                $spinner.removeClass('is-active');
                                if (saveRes.success) {
                                    logOutput('Successfully optimized Post #' + currentId + ' | Title: ' + response.data.seo_title);
                                    
                                    totalTokensUsed += response.data.tokens;
                                    totalCostUsed += parseFloat(response.data.cost);
                                    $tokensVal.text(totalTokensUsed.toLocaleString());
                                    $costVal.text('$' + totalCostUsed.toFixed(4));

                                    if ($row.length) {
                                        $row.find('td:nth-child(4)').html('<span style="color:#2271b1; font-weight:bold;">AI Optimized</span>');
                                        $row.find('.i4ware-seo-select-item').prop('checked', false);
                                    }
                                } else {
                                    logOutput('Failed saving Post #' + currentId + ': ' + saveRes.data.message, true);
                                }

                                setTimeout(function() {
                                    processNextBulkItem(index + 1);
                                }, 500);
                            })
                            .fail(function() {
                                $spinner.removeClass('is-active');
                                logOutput('Failed saving Post #' + currentId + ': Connection error.', true);
                                setTimeout(function() {
                                    processNextBulkItem(index + 1);
                                }, 1000);
                            });
                    } else {
                        $spinner.removeClass('is-active');
                        logOutput('Failed analyzing Post #' + currentId + ': ' + response.data.message, true);
                        setTimeout(function() {
                            processNextBulkItem(index + 1);
                        }, 1000);
                    }
                })
                .fail(function() {
                    $spinner.removeClass('is-active');
                    logOutput('Failed analyzing Post #' + currentId + ': Connection error.', true);
                    setTimeout(function() {
                        processNextBulkItem(index + 1);
                    }, 1000);
                });
        }

        function endBulkRun() {
            isBulkRunning = false;
            bulkCancelRequested = false;
            $bulkBtn.text('Run Bulk Optimization').removeClass('button-link-delete').addClass('button-primary');
            disableDashboardInputs(false);
            updateSelectedList();
            fetchContentList();
        }

        function disableDashboardInputs(disable) {
            $searchInput.prop('disabled', disable);
            $searchBtn.prop('disabled', disable);
            $filterSelect.prop('disabled', disable);
            $prevPageBtn.prop('disabled', disable || currentPage <= 1);
            $nextPageBtn.prop('disabled', disable || currentPage >= totalPages);
            $selectAll.prop('disabled', disable);
            $tableBody.find('.i4ware-seo-select-item').prop('disabled', disable);
            $tableBody.find('.i4ware-seo-table-btn').prop('disabled', disable);
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

})(jQuery);
 TintColor: '#cca300'
