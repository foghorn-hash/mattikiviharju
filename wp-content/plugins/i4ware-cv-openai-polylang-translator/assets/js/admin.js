/**
 * JavaScript functionality for the CV OpenAI Polylang Translator plugin.
 * Handles AJAX translation workflows and human review warnings before publishing.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initTranslationMetabox();
        initPublishValidation();
        initSelectAll();
    });

    /**
     * Controls the translation meta box interface on source posts.
     */
    function initTranslationMetabox() {
        var $root = $('#cv-oai-pll-meta-box-root');
        if (!$root.length) {
            return;
        }

        var postId = $root.data('post-id');
        var existingTranslations = $root.data('existing-translations') || {};
        
        var $langSelect       = $('#cv_oai_pll_target_lang');
        var $btn              = $('#cv_oai_pll_submit_btn');
        var $spinner          = $('#cv-oai-pll-spinner');
        var $overwriteWrap    = $('#cv-oai-pll-overwrite-container');
        var $overwriteMsg     = $('#cv-oai-pll-overwrite-msg');
        var $overwriteLink    = $('#cv-oai-pll-open-existing-link');
        var $overwriteConfirm = $('#cv_oai_pll_confirm_overwrite');
        var $publishedWarning = $('#cv-oai-pll-published-warning');
        var $statusMsg        = $('#cv-oai-pll-status-message');

        // Initial state
        $spinner.hide();
        $overwriteWrap.hide();
        $publishedWarning.hide();

        // Check language selection and adjust UI / button state
        function checkLanguageState() {
            var selectedLang = $langSelect.val();
            $overwriteWrap.hide();
            $publishedWarning.hide();
            $btn.prop('disabled', false);

            if (!selectedLang) {
                $btn.prop('disabled', true);
                return;
            }

            // Check if translation already exists for the selected target language
            if (existingTranslations[selectedLang]) {
                var translation = existingTranslations[selectedLang];
                
                if (translation.status === 'publish') {
                    // Published translations cannot be overwritten
                    $publishedWarning.show();
                    $btn.prop('disabled', true);
                } else if (translation.status === 'draft') {
                    // Draft translations require overwrite checkbox confirmation
                    $overwriteMsg.text('A draft translation already exists in ' + selectedLang.toUpperCase() + '.');
                    $overwriteLink.attr('href', translation.edit_url);
                    $overwriteWrap.show();

                    if (!$overwriteConfirm.is(':checked')) {
                        $btn.prop('disabled', true);
                    }
                }
            }
        }

        $langSelect.on('change', checkLanguageState);
        $overwriteConfirm.on('change', checkLanguageState);

        // Submit action click handler
        $btn.on('click', function(e) {
            e.preventDefault();

            var selectedLang = $langSelect.val();
            if (!selectedLang) {
                alert(cvOaiPllL10n.select_lang_error);
                return;
            }

            // If draft translation exists, confirm checkbox
            if (existingTranslations[selectedLang] && existingTranslations[selectedLang].status === 'draft') {
                if (!$overwriteConfirm.is(':checked')) {
                    alert(cvOaiPllL10n.confirm_overwrite_error);
                    return;
                }
            }

            // Disable UI inputs
            $btn.prop('disabled', true);
            $langSelect.prop('disabled', true);
            $overwriteConfirm.prop('disabled', true);
            $spinner.show();
            $statusMsg.css('color', '#2c3338').text(cvOaiPllL10n.translating_msg);

            var ajaxData = {
                action: 'cv_oai_pll_translate',
                nonce: $('#cv_oai_pll_nonce').val(),
                post_id: postId,
                target_lang: selectedLang,
                confirm_overwrite: $overwriteConfirm.is(':checked') ? '1' : '0',
                opt_title: $('#cv_oai_pll_opt_title').is(':checked') ? '1' : '',
                opt_excerpt: $('#cv_oai_pll_opt_excerpt').is(':checked') ? '1' : '',
                opt_content: $('#cv_oai_pll_opt_content').is(':checked') ? '1' : '',
                opt_acf: $('#cv_oai_pll_opt_acf').is(':checked') ? '1' : '',
                opt_caption: $('#cv_oai_pll_opt_caption').is(':checked') ? '1' : '',
                opt_alt: $('#cv_oai_pll_opt_alt').is(':checked') ? '1' : ''
            };

            $.post(cvOaiPllL10n.ajax_url, ajaxData)
                .done(function(response) {
                    $spinner.hide();
                    if (response.success) {
                        $statusMsg.css('color', 'green').html(
                            cvOaiPllL10n.success_msg + '<br /><a href="' + response.data.draft_url + '" class="button button-secondary" style="margin-top: 5px;">' + cvOaiPllL10n.open_draft_lbl + '</a>'
                        );
                        // Refresh page after a delay to show logs and update state
                        setTimeout(function() {
                            window.location.reload();
                        }, 3500);
                    } else {
                        $statusMsg.css('color', 'red').text(cvOaiPllL10n.translation_failed_lbl + response.data.message);
                        $btn.prop('disabled', false);
                        $langSelect.prop('disabled', false);
                        $overwriteConfirm.prop('disabled', false);
                        checkLanguageState();
                    }
                })
                .fail(function() {
                    $spinner.hide();
                    $statusMsg.css('color', 'red').text(cvOaiPllL10n.translation_failed_lbl + 'Server connection error.');
                    $btn.prop('disabled', false);
                    $langSelect.prop('disabled', false);
                    $overwriteConfirm.prop('disabled', false);
                    checkLanguageState();
                });
        });
    }

    /**
     * Hooks into the post publish event to warn if translation is not reviewed.
     */
    function initPublishValidation() {
        // Use capture phase on document to capture clicking publish buttons in Classic/Block editors
        document.addEventListener('click', function(e) {
            var target = e.target;
            if (!target) {
                return;
            }

            var isPublishBtn = false;

            // Check if classic publish button
            if (target.id === 'publish') {
                isPublishBtn = true;
            } 
            // Check if Gutenberg publish buttons
            else if (
                target.classList.contains('editor-post-publish-button') || 
                target.classList.contains('editor-post-publish-panel__toggle') || 
                target.classList.contains('editor-post-publish-button__button')
            ) {
                isPublishBtn = true;
            }

            if (isPublishBtn) {
                var $reviewMetaBox = $('.cv-oai-pll-review-box-inner');
                if ($reviewMetaBox.length) {
                    var $checkbox = $reviewMetaBox.find('input[name="cv_oai_review_completed"]');
                    if ($checkbox.length && !$checkbox.is(':checked')) {
                        var confirmed = confirm(cvOaiPllL10n.confirm_publish_warning);
                        if (!confirmed) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // For Gutenberg, reset the publish loading state by unlocking/dispatching if needed
                            if (window.wp && wp.data && wp.data.dispatch) {
                                wp.data.dispatch('core/editor').unlockPostPublishing();
                            }
                        }
                    }
                }
            }
        }, true); // Use capture phase
    }

    /**
     * Initializes Select All checkboxes on the settings page.
     */
    function initSelectAll() {
        $('#cv_oai_pll_post_types_select_all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('#cv_oai_pll_post_types_fieldset input[type="checkbox"]').prop('checked', isChecked);
        });
        
        $('#cv_oai_pll_acf_fields_select_all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('#cv_oai_pll_acf_fields_container input[type="checkbox"]').prop('checked', isChecked);
        });
    }

})(jQuery);
