/**
 * Translation Queue Dashboard JavaScript runner for the CV OpenAI Polylang Translator plugin.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initQueueDashboard();
    });

    function initQueueDashboard() {
        var $panel = $('#cv-oai-pll-queue-panel');
        if (!$panel.length) {
            return;
        }

        var $targetLang      = $('#cv_oai_pll_queue_target_lang');
        var $scanBtn         = $('#cv-oai-pll-scan-btn');
        var $scanStringsBtn  = $('#cv-oai-pll-scan-strings-btn');
        var $workerBtn       = $('#cv-oai-pll-worker-toggle-btn');
        var $clearBtn        = $('#cv-oai-pll-clear-queue-btn');
        var $retryAllBtn     = $('#cv-oai-pll-retry-all-btn');
        var $progressBar     = $('#cv-oai-pll-progress-bar');
        var $progressText    = $('#cv-oai-pll-progress-text');
        var $errorLogWrap    = $('#cv-oai-pll-error-log-table-wrap');

        // Worker state variables
        var isWorkerRunning = false;
        var pendingCount = 0;
        var processingCount = 0;

        // Poll stats initially
        fetchStats();

        // Target language select change listener
        $targetLang.on('change', function() {
            var val = $(this).val();
            $scanBtn.prop('disabled', !val);
            $scanStringsBtn.prop('disabled', !val);
            checkWorkerButtonState();
        });

        // Enable or disable worker toggle button based on pending items
        function checkWorkerButtonState() {
            var selectedLang = $targetLang.val();
            var totalPending = pendingCount + processingCount;
            
            if (totalPending > 0 && selectedLang) {
                $workerBtn.prop('disabled', false);
            } else {
                if (!isWorkerRunning) {
                    $workerBtn.prop('disabled', true);
                }
            }
        }

        // Fetch queue stats from server
        function fetchStats(callback) {
            var ajaxData = {
                action: 'cv_oai_pll_get_queue_stats',
                nonce: cvOaiPllQueueL10n.nonce
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        var stats = response.data.stats;
                        
                        // Update individual stats values
                        $('#cv-oai-pll-stat-total').text(stats.total);
                        $('#cv-oai-pll-stat-pending').text(stats.pending);
                        $('#cv-oai-pll-stat-processing').text(stats.processing);
                        $('#cv-oai-pll-stat-completed').text(stats.completed);
                        $('#cv-oai-pll-stat-failed').text(stats.failed);
                        
                        // Update Cost and Token counters
                        $('#cv-oai-pll-stat-tokens').text(response.data.tokens_total.toLocaleString());
                        $('#cv-oai-pll-stat-cost').text(response.data.cost_total);

                        // Save local counters for worker toggle checks
                        pendingCount = stats.pending;
                        processingCount = stats.processing;

                        // Calculate progress percentage
                        var progressPercent = 0;
                        if (stats.total > 0) {
                            progressPercent = Math.round((stats.completed / stats.total) * 100);
                        }
                        $progressBar.css('width', progressPercent + '%');
                        $progressText.text(progressPercent + '% (' + stats.completed + ' / ' + stats.total + ')');

                        // Update error table log
                        $errorLogWrap.html(response.data.error_log_html);

                        // Show/Hide Retry All button
                        if (stats.failed > 0) {
                            $retryAllBtn.show();
                        } else {
                            $retryAllBtn.hide();
                        }

                        // Update current action status text
                        if (isWorkerRunning && response.data.next_item_desc) {
                            $('#cv-oai-pll-current-action').text(response.data.next_item_desc);
                        } else if (!isWorkerRunning) {
                            $('#cv-oai-pll-current-action').text('');
                        }

                        checkWorkerButtonState();
                        
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });
        }

        // Action: Scan Website and Populate Queue
        $scanBtn.on('click', function(e) {
            e.preventDefault();
            runScanner('all');
        });

        // Action: Scan Missing Strings Only
        $scanStringsBtn.on('click', function(e) {
            e.preventDefault();
            runScanner('strings');
        });

        function runScanner(scanType) {
            var selectedLang = $targetLang.val();
            if (!selectedLang) {
                alert(cvOaiPllQueueL10n.no_lang_error);
                return;
            }

            var overwrite = $('#cv-oai-pll-overwrite-queue').is(':checked') ? 1 : 0;

            $scanBtn.prop('disabled', true);
            $scanStringsBtn.prop('disabled', true);
            $targetLang.prop('disabled', true);
            $('#cv-oai-pll-overwrite-queue').prop('disabled', true);
            $progressText.text(cvOaiPllQueueL10n.scanning_msg);

            var ajaxData = {
                action: 'cv_oai_pll_scan_content',
                nonce: cvOaiPllQueueL10n.nonce,
                target_lang: selectedLang,
                scan_type: scanType,
                overwrite: overwrite
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                    $targetLang.prop('disabled', false);
                    $('#cv-oai-pll-overwrite-queue').prop('disabled', false);
                    $scanBtn.prop('disabled', false);
                    $scanStringsBtn.prop('disabled', false);
                    fetchStats();
                })
                .fail(function() {
                    alert('Scan failed. Server connection error.');
                    $targetLang.prop('disabled', false);
                    $('#cv-oai-pll-overwrite-queue').prop('disabled', false);
                    $scanBtn.prop('disabled', false);
                    $scanStringsBtn.prop('disabled', false);
                    fetchStats();
                });
        }

        // Action: Worker Toggle (Pause/Resume worker execution loop)
        $workerBtn.on('click', function(e) {
            e.preventDefault();
            if (isWorkerRunning) {
                stopWorker();
            } else {
                startWorker();
            }
        });

        function startWorker() {
            isWorkerRunning = true;
            $workerBtn.text(cvOaiPllQueueL10n.worker_running_lbl).addClass('button-secondary').removeClass('button-primary');
            $scanBtn.prop('disabled', true);
            $scanStringsBtn.prop('disabled', true);
            $targetLang.prop('disabled', true);
            $('#cv-oai-pll-overwrite-queue').prop('disabled', true);
            $clearBtn.prop('disabled', true);

            // Execute first loop batch
            runBatchWorker();
        }

        function stopWorker() {
            isWorkerRunning = false;
            $workerBtn.text(cvOaiPllQueueL10n.worker_paused_lbl).addClass('button-primary').removeClass('button-secondary');
            $scanBtn.prop('disabled', !$targetLang.val());
            $scanStringsBtn.prop('disabled', !$targetLang.val());
            $targetLang.prop('disabled', false);
            $('#cv-oai-pll-overwrite-queue').prop('disabled', false);
            $clearBtn.prop('disabled', false);
            fetchStats();
        }

        // Background batch execution runner
        function runBatchWorker() {
            if (!isWorkerRunning) {
                return;
            }

            var bypassCache = $('#cv-oai-pll-overwrite-queue').is(':checked') ? 1 : 0;

            var ajaxData = {
                action: 'cv_oai_pll_process_queue_batch',
                nonce: cvOaiPllQueueL10n.nonce,
                bypass_cache: bypassCache
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        // Log the details to the live log!
                        if (response.data.details && response.data.details.length > 0) {
                            var $log = $('#cv-oai-pll-live-log');
                            $log.show();
                            response.data.details.forEach(function(detail) {
                                var statusIcon = detail.success ? '<span style="color:green;">✔</span>' : '<span style="color:red;">❌</span>';
                                var langName = detail.target_language ? detail.target_language.toUpperCase() : '';
                                var typeLabel = detail.type ? '[' + detail.type.toUpperCase() + ']' : '';
                                var msg = '<div>' + statusIcon + ' ' + typeLabel + ' ' + detail.name + ' ➔ ' + langName;
                                if (!detail.success && detail.error) {
                                    msg += ' <span style="color:red; font-style:italic;">(' + detail.error + ')</span>';
                                }
                                msg += '</div>';
                                $log.append(msg);
                            });
                            // Auto scroll log to bottom
                            $log.scrollTop($log[0].scrollHeight);
                        }

                        fetchStats(function() {
                            var remaining = pendingCount + processingCount;
                            
                            // If items remain in queue and worker is still running, execute next batch
                            if (remaining > 0 && isWorkerRunning) {
                                setTimeout(runBatchWorker, 800);
                            } else {
                                // Completed!
                                stopWorker();
                                $('#cv-oai-pll-current-action').text('All translations finished.');
                                alert('All queued translations processed.');
                            }
                        });
                    } else {
                        alert('Batch error: ' + response.data.message);
                        stopWorker();
                    }
                })
                .fail(function() {
                    alert('Server connection lost during batch. Queue worker paused.');
                    stopWorker();
                });
        }

        // Action: Clear Queue
        $clearBtn.on('click', function(e) {
            e.preventDefault();
            if (!confirm(cvOaiPllQueueL10n.confirm_clear_queue)) {
                return;
            }

            $clearBtn.prop('disabled', true);
            var ajaxData = {
                action: 'cv_oai_pll_clear_queue',
                nonce: cvOaiPllQueueL10n.nonce
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $clearBtn.prop('disabled', false);
                    fetchStats();
                })
                .fail(function() {
                    alert('Failed to clear queue. Server error.');
                    $clearBtn.prop('disabled', false);
                });
        } );

        // Action: Retry All Failed Items
        $retryAllBtn.on('click', function(e) {
            e.preventDefault();
            $retryAllBtn.prop('disabled', true);
            
            var ajaxData = {
                action: 'cv_oai_pll_retry_failed',
                nonce: cvOaiPllQueueL10n.nonce
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function() {
                    $retryAllBtn.prop('disabled', false);
                    fetchStats();
                })
                .fail(function() {
                    alert('Retry request failed. Server error.');
                    $retryAllBtn.prop('disabled', false);
                });
        });

        // Action: Retry Single Failed Item
        $errorLogWrap.on('click', '.cv-oai-pll-retry-single-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            $btn.prop('disabled', true);

            var ajaxData = {
                action: 'cv_oai_pll_retry_failed',
                nonce: cvOaiPllQueueL10n.nonce,
                item_id: id
            };

            $.post(cvOaiPllQueueL10n.ajax_url, ajaxData)
                .done(function() {
                    fetchStats();
                })
                .fail(function() {
                    alert('Retry single item request failed. Server error.');
                    $btn.prop('disabled', false);
                });
        });
    }

})(jQuery);
