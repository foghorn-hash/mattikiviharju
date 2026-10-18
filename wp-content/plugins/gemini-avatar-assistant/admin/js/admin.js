/* global jQuery, geminiAvatarAdmin, wp */
(function ($) {
	'use strict';

	$(function () {
		// Tab Switching
		$('.gemini-tab-nav .nav-tab').on('click', function (e) {
			e.preventDefault();
			var target = $(this).data('tab');

			$('.gemini-tab-nav .nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');

			$('.gemini-tab-content').removeClass('active');
			$('#' + target).addClass('active');

			if (window.location.hash !== '#' + target) {
				window.location.hash = target;
			}
		});

		// Activate tab from URL hash if exists
		if (window.location.hash) {
			var hash = window.location.hash.substring(1);
			var $tabLink = $('.gemini-tab-nav .nav-tab[data-tab="' + hash + '"]');
			if ($tabLink.length) {
				$tabLink.trigger('click');
			}
		}

		// Media Uploader for Avatar Image
		var mediaUploader;
		$('#btn-upload-avatar').on('click', function (e) {
			e.preventDefault();

			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media({
				title: 'Valitse avattaren profiilikuva',
				button: {
					text: 'Aseta avatariksi'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			});

			mediaUploader.on('select', function () {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#gemini_avatar_image_url').val(attachment.url);
				$('#gemini_avatar_image_id').val(attachment.id);

				$('#avatar-img-preview').html('<img src="' + attachment.url + '" alt="Avatar Preview" />');
				$('#btn-remove-avatar').show();
			});

			mediaUploader.open();
		});

		$('#btn-remove-avatar').on('click', function (e) {
			e.preventDefault();
			$('#gemini_avatar_image_url').val('');
			$('#gemini_avatar_image_id').val(0);
			$('#avatar-img-preview').html('<span class="dashicons dashicons-admin-users"></span>');
			$(this).hide();
		});

		// Test Gemini API Connection
		$('#btn-test-api').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $spinner = $('#test-api-spinner');
			var $result = $('#test-api-result');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$result.removeClass('success error').hide().text('');

			$.ajax({
				url: geminiAvatarAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'gemini_avatar_test_api',
					nonce: geminiAvatarAdmin.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');

					if (res.success && res.data) {
						var msg = '<strong>✓ ' + res.data.message + '</strong><br/>';
						msg += '• Tekstimallin vasteaika: ' + res.data.text_time + '<br/>';
						msg += '• Esimerkkivastaus: "' + res.data.text_sample + '"<br/>';
						if (res.data.tts_ok) {
							msg += '• Gemini TTS -puhesynteesi: Toimii loistavasti (viive: ' + res.data.tts_time + ', palautti WAV-audion)';
						} else {
							msg += '• Gemini TTS: ' + (res.data.tts_error || 'Ei audiota');
						}
						$result.addClass('success').html(msg).show();
					} else {
						var errMsg = (res.data && res.data.message) ? res.data.message : 'Yhteysvirhe.';
						$result.addClass('error').html('<strong>Virhe:</strong> ' + errMsg).show();
					}
				},
				error: function (xhr, status, error) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$result.addClass('error').html('<strong>Yhteysvirhe:</strong> ' + error).show();
				}
			});
		});

		// Rebuild Knowledge Base Index
		$('#btn-rebuild-index').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $spinner = $('#rebuild-index-spinner');
			var $result = $('#rebuild-index-result');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');
			$result.removeClass('success error').hide().text('');

			$.ajax({
				url: geminiAvatarAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'gemini_avatar_rebuild_index',
					nonce: geminiAvatarAdmin.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');

					if (res.success && res.data) {
						$result.addClass('success').html(res.data.message).show();
						if (res.data.stats) {
							$('#stat-total-chunks').text(res.data.stats.total_chunks);
							$('#stat-last-updated').text(res.data.stats.last_updated);
						}
					} else {
						var errMsg = (res.data && res.data.message) ? res.data.message : 'Indeksointi epäonnistui.';
						$result.addClass('error').html('<strong>Virhe:</strong> ' + errMsg).show();
					}
				},
				error: function (xhr, status, error) {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
					$result.addClass('error').html('<strong>Virhe:</strong> ' + error).show();
				}
			});
		});
	});
})(jQuery);
