/**
 * JavaScript functionality for Bulk Post Translation in WordPress Admin (edit.php).
 * Allows users to select all or specific posts and translate them sequentially to EN and SV drafts.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initBulkActions();
    });

    function initBulkActions() {
        var $listTable = $('#posts-filter, .wp-list-table');
        if (!$listTable.length) {
            return;
        }

        // Insert Quick Action Buttons into table nav actions
        var $topActions = $('.tablenav.top .bulkactions, .tablenav.top .actions').first();
        if ($topActions.length && !$('#cv-oai-btn-translate-selected').length) {
            var $buttonsWrap = $('<span class="cv-oai-bulk-buttons-group" style="display:inline-flex; align-items:center; gap:5px; margin-left:8px; vertical-align:middle;"></span>');
            
            var $btnTranslateSelected = $('<button type="button" class="button button-primary cv-oai-bulk-btn" id="cv-oai-btn-translate-selected" title="Käännä valitut artikkelit kielelle En ja Sv">' +
                '<span class="dashicons dashicons-translation" style="vertical-align:text-bottom; margin-right:3px; font-size:16px; width:16px; height:16px;"></span> ' +
                (cvOaiPllBulkL10n.str_btn_translate || 'Käännä valitut: En & Sv') +
            '</button>');

            var $btnSelectAllAndTranslate = $('<button type="button" class="button button-secondary cv-oai-bulk-btn" id="cv-oai-btn-select-all-and-translate" title="Valitse kaikki tämän sivun artikkelit ja käännä ne kielelle En ja Sv">' +
                '<span class="dashicons dashicons-forms" style="vertical-align:text-bottom; margin-right:3px; font-size:16px; width:16px; height:16px;"></span> ' +
                (cvOaiPllBulkL10n.str_btn_select_all || '⚡ Valitse kaikki ja käännä: En & Sv') +
            '</button>');

            $buttonsWrap.append($btnTranslateSelected).append($btnSelectAllAndTranslate);
            $topActions.append($buttonsWrap);
        }

        // Handle "⚡ Valitse kaikki ja käännä: En & Sv"
        $(document).on('click', '#cv-oai-btn-select-all-and-translate', function(e) {
            e.preventDefault();
            
            // Check all checkboxes in the table
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', true).trigger('change');
            $('tbody th.check-column input[type="checkbox"], tbody td.check-column input[type="checkbox"]').prop('checked', true);

            var selectedPosts = getSelectedPosts();
            if (!selectedPosts.length) {
                alert(cvOaiPllBulkL10n.str_no_posts || 'Ei valittuja artikkeleita.');
                return;
            }

            openBulkModal(selectedPosts, ['en', 'sv']);
        });

        // Handle "🌐 Käännä valitut: En & Sv"
        $(document).on('click', '#cv-oai-btn-translate-selected', function(e) {
            e.preventDefault();
            
            var selectedPosts = getSelectedPosts();
            if (!selectedPosts.length) {
                alert(cvOaiPllBulkL10n.str_no_posts || 'Valitse ensin vähintään yksi artikkeli taulukosta.');
                return;
            }

            openBulkModal(selectedPosts, ['en', 'sv']);
        });

        // Handle standard "Massatoiminnot" dropdown submit (doaction and doaction2)
        $(document).on('click', '#doaction, #doaction2', function(e) {
            var isTop = (this.id === 'doaction');
            var selectName = isTop ? 'action' : 'action2';
            var actionVal = $('select[name="' + selectName + '"]').val();

            var targetLangs = null;
            if (actionVal === 'cv_oai_pll_bulk_en_sv') {
                targetLangs = ['en', 'sv'];
            } else if (actionVal === 'cv_oai_pll_bulk_en') {
                targetLangs = ['en'];
            } else if (actionVal === 'cv_oai_pll_bulk_sv') {
                targetLangs = ['sv'];
            }

            if (targetLangs) {
                e.preventDefault();
                var selectedPosts = getSelectedPosts();
                if (!selectedPosts.length) {
                    alert(cvOaiPllBulkL10n.str_no_posts || 'Valitse ensin vähintään yksi artikkeli taulukosta.');
                    return;
                }
                openBulkModal(selectedPosts, targetLangs);
            }
        });
    }

    /**
     * Extracts selected post IDs and titles from the list table.
     */
    function getSelectedPosts() {
        var posts = [];
        var seenIds = {};

        $('tbody th.check-column input[type="checkbox"]:checked, tbody td.check-column input[type="checkbox"]:checked').each(function() {
            var postId = $(this).val();
            if (postId && !seenIds[postId] && postId !== 'on') {
                seenIds[postId] = true;
                var $row = $(this).closest('tr');
                var title = $row.find('.row-title').text().trim() || 
                            $row.find('strong > a').text().trim() || 
                            ('Artikkeli #' + postId);
                posts.push({
                    id: postId,
                    title: title
                });
            }
        });

        return posts;
    }

    /**
     * Creates and opens the Bulk Translation modal.
     */
    function openBulkModal(posts, targetLangs) {
        var $overlay = $('#cv-oai-bulk-modal-overlay');
        if (!$overlay.length) {
            $overlay = createModalMarkup();
            $('body').append($overlay);
            bindModalEvents($overlay);
        }

        // Language label mapping
        var langLabels = {
            'en': 'English (EN)',
            'sv': 'Svenska (SV)',
            'fi': 'Suomi (FI)'
        };

        // Construct task queue
        var tasks = [];
        $.each(posts, function(pIdx, p) {
            $.each(targetLangs, function(lIdx, lang) {
                tasks.push({
                    postId: p.id,
                    title: p.title,
                    targetLang: lang,
                    targetLabel: langLabels[lang] || lang.toUpperCase()
                });
            });
        });

        // Store tasks on modal
        $overlay.data('tasks', tasks);
        $overlay.data('isCancelled', false);
        $overlay.data('isRunning', false);

        // Update modal summary info
        var langNamesList = targetLangs.map(function(l) { return langLabels[l] || l.toUpperCase(); }).join(' ja ');
        var summaryHtml = '<strong>' + posts.length + '</strong> artikkelia valittu. ' +
            'Kohdekielet: <strong>' + langNamesList + '</strong>. ' +
            'Yhteensä <strong>' + tasks.length + '</strong> käännöstehtävää.';
        $('#cv-oai-modal-summary').html(summaryHtml);

        // Reset progress UI
        $('#cv-oai-progress-status').text('Valmiina aloitukseen');
        $('#cv-oai-progress-percent').text('0%');
        $('#cv-oai-progress-bar').css('width', '0%').removeClass('is-complete');
        $('#cv-oai-bulk-log').empty();

        // Button states
        $('#cv-oai-bulk-start-btn').show().prop('disabled', false).html(
            '<span class="dashicons dashicons-controls-play" style="vertical-align:text-bottom; margin-right:4px;"></span> ' +
            'Aloita kääntäminen (' + tasks.length + ' tehtävää)'
        );
        $('#cv-oai-bulk-cancel-btn').hide();
        $('#cv-oai-bulk-reload-btn').hide();
        $('#cv-oai-bulk-options-row').show();

        $overlay.fadeIn(200);
    }

    /**
     * Constructs the modal HTML structure.
     */
    function createModalMarkup() {
        var html = 
        '<div id="cv-oai-bulk-modal-overlay" style="display:none;">' +
            '<div id="cv-oai-bulk-modal-card">' +
                '<div class="cv-oai-modal-header">' +
                    '<div class="cv-oai-modal-title">' +
                        '<span class="dashicons dashicons-translation" style="font-size:22px; width:22px; height:22px; margin-right:8px; vertical-align:middle; color:#2271b1;"></span>' +
                        '<span>' + (cvOaiPllBulkL10n.str_modal_title || 'OpenAI Polylang Käännin — En & Sv') + '</span>' +
                    '</div>' +
                    '<button type="button" class="cv-oai-modal-close" id="cv-oai-modal-close-x" title="Sulje">&times;</button>' +
                '</div>' +

                '<div class="cv-oai-modal-body">' +
                    '<div class="cv-oai-modal-summary" id="cv-oai-modal-summary"></div>' +

                    '<div class="cv-oai-options-row" id="cv-oai-bulk-options-row">' +
                        '<label><input type="checkbox" id="cv-oai-bulk-overwrite-drafts" checked="checked" /> Korvaa olemassa olevat luonnokset</label>' +
                        '<label><input type="checkbox" id="cv-oai-bulk-skip-published" checked="checked" /> Ohita jos julkaistu käännös on jo olemassa</label>' +
                    '</div>' +

                    '<div class="cv-oai-progress-section">' +
                        '<div class="cv-oai-progress-labels">' +
                            '<span id="cv-oai-progress-status">Odottaa aloitusta...</span>' +
                            '<span id="cv-oai-progress-percent">0%</span>' +
                        '</div>' +
                        '<div class="cv-oai-progress-track">' +
                            '<div class="cv-oai-progress-bar" id="cv-oai-progress-bar"></div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="cv-oai-log-section">' +
                        '<div class="cv-oai-log-header">Tapahtumaloki:</div>' +
                        '<div class="cv-oai-bulk-log" id="cv-oai-bulk-log"></div>' +
                    '</div>' +
                '</div>' +

                '<div class="cv-oai-modal-footer">' +
                    '<button type="button" class="button button-primary button-hero" id="cv-oai-bulk-start-btn">Aloita kääntäminen</button>' +
                    '<button type="button" class="button button-secondary" id="cv-oai-bulk-cancel-btn" style="display:none;">Keskeytä</button>' +
                    '<button type="button" class="button button-primary button-hero" id="cv-oai-bulk-reload-btn" style="display:none;">' +
                        '<span class="dashicons dashicons-update" style="vertical-align:text-bottom; margin-right:4px;"></span> ' +
                        (cvOaiPllBulkL10n.str_close || 'Sulje ja päivitä sivu') +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        return $(html);
    }

    /**
     * Binds events to the modal buttons and controls.
     */
    function bindModalEvents($overlay) {
        // Close modal
        $overlay.on('click', '#cv-oai-modal-close-x', function() {
            var isRunning = $overlay.data('isRunning');
            if (isRunning) {
                if (confirm('Käännösajo on käynnissä. Haluatko varmasti keskeyttää ja sulkea ikkunan?')) {
                    $overlay.data('isCancelled', true);
                    $overlay.fadeOut(200);
                }
            } else {
                $overlay.fadeOut(200);
            }
        });

        // Close on backdrop click (only if not running)
        $overlay.on('click', function(e) {
            if (e.target.id === 'cv-oai-bulk-modal-overlay') {
                if (!$overlay.data('isRunning')) {
                    $overlay.fadeOut(200);
                }
            }
        });

        // Start processing
        $overlay.on('click', '#cv-oai-bulk-start-btn', function() {
            startBulkTranslation($overlay);
        });

        // Cancel processing
        $overlay.on('click', '#cv-oai-bulk-cancel-btn', function() {
            if (confirm(cvOaiPllBulkL10n.str_confirm_cancel || 'Haluatko varmasti keskeyttää kääntämisen käynnissä olevan artikkelin jälkeen?')) {
                $overlay.data('isCancelled', true);
                $(this).prop('disabled', true).text('Keskeytetään...');
                logMessage('⚠ Keskeytetään eräajo nykyisen artikkelin valmistuttua...', 'warning');
            }
        });

        // Reload button
        $overlay.on('click', '#cv-oai-bulk-reload-btn', function() {
            window.location.reload();
        });
    }

    /**
     * Starts sequential processing of all tasks in the queue.
     */
    function startBulkTranslation($overlay) {
        var tasks = $overlay.data('tasks') || [];
        if (!tasks.length) {
            return;
        }

        $overlay.data('isRunning', true);
        $overlay.data('isCancelled', false);

        $('#cv-oai-bulk-start-btn').hide();
        $('#cv-oai-bulk-options-row').hide();
        $('#cv-oai-bulk-cancel-btn').show().prop('disabled', false).text('Keskeytä');

        var total = tasks.length;
        var completed = 0;
        var successCount = 0;
        var skippedCount = 0;
        var errorCount = 0;

        logMessage('🚀 Aloitetaan eräajo (' + total + ' tehtävää)...', 'info');

        var overwriteDraft = $('#cv-oai-bulk-overwrite-drafts').is(':checked') ? 1 : 0;
        var skipPublished  = $('#cv-oai-bulk-skip-published').is(':checked') ? 1 : 0;

        function runNextTask(index) {
            if (index >= total) {
                // All finished
                finishBulkTranslation($overlay, successCount, skippedCount, errorCount, total);
                return;
            }

            if ($overlay.data('isCancelled')) {
                logMessage('🛑 Eräajo keskeytetty käyttäjän toimesta. Käsiteltiin ' + completed + '/' + total + ' tehtävää.', 'warning');
                $('#cv-oai-bulk-cancel-btn').hide();
                $('#cv-oai-bulk-reload-btn').show().text('Sulje ja päivitä sivu');
                $overlay.data('isRunning', false);
                return;
            }

            var task = tasks[index];
            var taskNum = index + 1;

            $('#cv-oai-progress-status').html(
                '<span class="spinner is-active" style="float:none; margin:0 5px 0 0; vertical-align:middle;"></span> ' +
                '[' + taskNum + '/' + total + '] Käännetään: <strong>' + escapeHtml(task.title) + '</strong> &rarr; <strong>' + task.targetLabel + '</strong>'
            );

            var postData = {
                action: 'cv_oai_pll_bulk_translate_item',
                nonce: cvOaiPllBulkL10n.nonce,
                post_id: task.postId,
                target_lang: task.targetLang,
                overwrite_draft: overwriteDraft,
                skip_published: skipPublished
            };

            $.ajax({
                url: cvOaiPllBulkL10n.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: postData,
                timeout: 180000 // 3 minutes timeout per post
            }).done(function(res) {
                completed++;
                updateProgress(completed, total);

                if (res && res.success) {
                    if (res.data && res.data.skipped) {
                        skippedCount++;
                        logMessage(
                            'ℹ [' + taskNum + '/' + total + '] ' + escapeHtml(task.title) + ' &rarr; ' +
                            '<span class="cv-badge cv-badge-lang">' + task.targetLang.toUpperCase() + '</span> ' +
                            '<span class="cv-badge cv-badge-skip">OHITETTU</span> ' +
                            escapeHtml(res.data.message || 'Ohitettu'),
                            'skipped'
                        );
                    } else {
                        successCount++;
                        var draftUrl = res.data && res.data.draft_url ? res.data.draft_url : '';
                        var draftId = res.data && res.data.draft_id ? res.data.draft_id : '';
                        var draftLinkHtml = draftUrl ? ' (<a href="' + draftUrl + '" target="_blank" style="color:#2271b1; text-decoration:underline;">Muokkaa luonnosta #' + draftId + '</a>)' : '';

                        logMessage(
                            '✓ [' + taskNum + '/' + total + '] ' + escapeHtml(task.title) + ' &rarr; ' +
                            '<span class="cv-badge cv-badge-lang">' + task.targetLang.toUpperCase() + '</span> ' +
                            '<span class="cv-badge cv-badge-success">VALMIS</span>' + draftLinkHtml,
                            'success'
                        );
                    }
                } else {
                    errorCount++;
                    var errorMsg = (res && res.data && res.data.message) ? res.data.message : 'Tuntematon virhe.';
                    logMessage(
                        '✗ [' + taskNum + '/' + total + '] ' + escapeHtml(task.title) + ' &rarr; ' +
                        '<span class="cv-badge cv-badge-lang">' + task.targetLang.toUpperCase() + '</span> ' +
                        '<span class="cv-badge cv-badge-error">VIRHE</span> ' + escapeHtml(errorMsg),
                        'error'
                    );
                }

                // Run next task
                runNextTask(index + 1);

            }).fail(function(xhr, status, error) {
                completed++;
                errorCount++;
                updateProgress(completed, total);

                logMessage(
                    '✗ [' + taskNum + '/' + total + '] ' + escapeHtml(task.title) + ' &rarr; ' +
                    '<span class="cv-badge cv-badge-lang">' + task.targetLang.toUpperCase() + '</span> ' +
                    '<span class="cv-badge cv-badge-error">YHTEYSVIRHE</span> ' + escapeHtml(error || status),
                    'error'
                );

                // Run next task
                runNextTask(index + 1);
            });
        }

        // Start first task
        runNextTask(0);
    }

    /**
     * Updates progress bar and percentage display.
     */
    function updateProgress(completed, total) {
        var pct = Math.round((completed / total) * 100);
        $('#cv-oai-progress-bar').css('width', pct + '%');
        $('#cv-oai-progress-percent').text(pct + '% (' + completed + '/' + total + ')');
    }

    /**
     * Handles completion of all tasks in the queue.
     */
    function finishBulkTranslation($overlay, successCount, skippedCount, errorCount, total) {
        $overlay.data('isRunning', false);
        $('#cv-oai-progress-bar').addClass('is-complete');
        $('#cv-oai-bulk-cancel-btn').hide();

        var finalStatus = '🎉 Kaikki käännökset suoritettu! (' + successCount + ' onnistui, ' + skippedCount + ' ohitettiin, ' + errorCount + ' virhettä)';
        $('#cv-oai-progress-status').html('<strong style="color:#00a32a;">' + finalStatus + '</strong>');

        logMessage('🎉 ' + finalStatus, 'info');

        var countdown = 3;
        var $reloadBtn = $('#cv-oai-bulk-reload-btn');
        $reloadBtn.show().html('<span class="dashicons dashicons-update" style="vertical-align:text-bottom; margin-right:4px;"></span> Sulje ja päivitä sivu (' + countdown + 's)');

        var countdownTimer = setInterval(function() {
            countdown--;
            if (countdown > 0) {
                $reloadBtn.html('<span class="dashicons dashicons-update" style="vertical-align:text-bottom; margin-right:4px;"></span> Sulje ja päivitä sivu (' + countdown + 's)');
            } else {
                clearInterval(countdownTimer);
                window.location.reload();
            }
        }, 1000);
    }

    /**
     * Appends a message to the modal log console.
     */
    function logMessage(htmlContent, type) {
        var now = new Date();
        var timeStr = ('0' + now.getHours()).slice(-2) + ':' +
                      ('0' + now.getMinutes()).slice(-2) + ':' +
                      ('0' + now.getSeconds()).slice(-2);

        var color = '#333';
        if (type === 'success') color = '#155724';
        if (type === 'skipped') color = '#856404';
        if (type === 'error')   color = '#721c24';
        if (type === 'warning') color = '#d63638';
        if (type === 'info')    color = '#005a9c';

        var $entry = $('<div class="cv-oai-log-entry" style="color:' + color + ';">' +
            '<span class="cv-oai-log-time">[' + timeStr + ']</span> ' +
            htmlContent +
        '</div>');

        var $log = $('#cv-oai-bulk-log');
        $log.append($entry);
        $log.scrollTop($log[0].scrollHeight);
    }

    /**
     * Basic HTML escaping helper.
     */
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})(jQuery);
