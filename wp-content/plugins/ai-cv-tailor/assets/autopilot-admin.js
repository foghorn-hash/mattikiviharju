jQuery(document).ready(function($) {

	$('.action-analyze').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var postId = btn.data('id');
		
		btn.text('Analysoidaan...').prop('disabled', true);
		
		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_autopilot_analyze',
			nonce: aiCvTailorAutopilotObj.nonce,
			post_id: postId
		}, function(response) {
			if(response.success) {
				alert('Analyysi valmis! Score: ' + response.data.match_score);
				location.reload();
			} else {
				alert('Virhe: ' + response.data);
				btn.text('Analysoi').prop('disabled', false);
			}
		});
	});

	$('.action-generate').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var postId = btn.data('id');
		
		btn.text('Luodaan...').prop('disabled', true);
		
		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_autopilot_generate',
			nonce: aiCvTailorAutopilotObj.nonce,
			post_id: postId
		}, function(response) {
			if(response.success) {
				alert('Hakemus luotu! Voit tarkastella sitä Hakemukset-välilehdellä.');
				location.reload();
			} else {
				alert('Virhe: ' + response.data);
				btn.text('Luo hakemus').prop('disabled', false);
			}
		});
	});

	$('.action-reject').on('click', function(e) {
		e.preventDefault();
		var btn = $(this);
		var postId = btn.data('id');
		
		if(!confirm('Haluatko varmasti hylätä tämän?')) return;
		
		btn.text('Hylätään...').prop('disabled', true);
		
		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_autopilot_reject',
			nonce: aiCvTailorAutopilotObj.nonce,
			post_id: postId
		}, function(response) {
			if(response.success) {
				$('#opportunity-' + postId).find('.status-col').text('Rejected');
				btn.text('Hylätty').prop('disabled', true);
			} else {
				alert('Virhe: ' + response.data);
				btn.text('Hylkää').prop('disabled', false);
			}
		});
	});

});
