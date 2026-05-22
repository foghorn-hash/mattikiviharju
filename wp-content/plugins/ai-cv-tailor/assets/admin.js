jQuery(document).ready(function($) {

	// Generate content
	$('#btn-analyze').on('click', function(e) {
		e.preventDefault();
		
		var company = $('#app_company_name').val();
		var title = $('#app_job_title').val();
		var desc = $('#app_job_description').val();
		
		if (!company || !title || !desc) {
			alert('Täytä vähintään yritys, nimike ja ilmoituksen teksti.');
			return;
		}
		
		// Move to step 2
		$('#step-1').removeClass('step-active').addClass('step-hidden');
		$('#step-2').removeClass('step-hidden').addClass('step-active');
		
		var data = {
			action: 'ai_cv_tailor_generate',
			nonce: aiCvTailorObj.nonce,
			job_description: desc,
			language: $('#app_language').val()
		};
		
		$.post(aiCvTailorObj.ajax_url, data, function(response) {
			if (response.success) {
				// Move to step 3
				$('#step-2').removeClass('step-active').addClass('step-hidden');
				$('#step-3').removeClass('step-hidden').addClass('step-active');
				
				var jsonStr = JSON.stringify(response.data, null, 2);
				$('#app_generated_json').val(jsonStr);
				
				// Optional: format a nice summary
				var analysis = response.data.job_analysis;
				if (analysis) {
					var html = '<h3>Analyysin yhteenveto</h3>';
					html += '<p><strong>Rooli:</strong> ' + (analysis.summary || '') + '</p>';
					html += '<p><strong>Vaatimukset:</strong> ' + (analysis.required_skills ? analysis.required_skills.join(', ') : '') + '</p>';
					html += '<p><strong>Puutteet/riskit:</strong> ' + (analysis.risks_or_gaps ? analysis.risks_or_gaps.join(', ') : 'Ei havaittu') + '</p>';
					$('#analysis-summary').html(html);
				}
				
			} else {
				alert('Virhe: ' + response.data);
				$('#step-2').removeClass('step-active').addClass('step-hidden');
				$('#step-1').removeClass('step-hidden').addClass('step-active');
			}
		}).fail(function() {
			alert('Palvelinvirhe analysoinnissa.');
			$('#step-2').removeClass('step-active').addClass('step-hidden');
			$('#step-1').removeClass('step-hidden').addClass('step-active');
		});
	});

	// Publish
	$('#btn-publish').on('click', function(e) {
		e.preventDefault();
		
		var $btn = $(this);
		$btn.prop('disabled', true).text('Tallennetaan...');
		
		var data = {
			action: 'ai_cv_tailor_save',
			nonce: aiCvTailorObj.nonce,
			company_name: $('#app_company_name').val(),
			job_title: $('#app_job_title').val(),
			job_url: $('#app_job_url').val(),
			job_description: $('#app_job_description').val(),
			language: $('#app_language').val(),
			json_data: $('#app_generated_json').val()
		};
		
		$.post(aiCvTailorObj.ajax_url, data, function(response) {
			if (response.success) {
				$('#step-3').removeClass('step-active').addClass('step-hidden');
				$('#step-4').removeClass('step-hidden').addClass('step-active');
				
				var urls = response.data.urls;
				var tbody = '';
				$.each(urls, function(audience, url) {
					tbody += '<tr>';
					tbody += '<td><strong>' + audience.toUpperCase() + '</strong></td>';
					tbody += '<td><a href="' + url + '" target="_blank">' + url + '</a></td>';
					tbody += '<td><button type="button" class="button btn-copy" data-url="' + url + '">Kopioi linkki</button></td>';
					tbody += '</tr>';
				});
				$('#generated-links-body').html(tbody);
				
			} else {
				alert('Tallennus epäonnistui: ' + response.data);
				$btn.prop('disabled', false).text('Tallenna ja julkaise salaiset linkit');
			}
		}).fail(function() {
			alert('Palvelinvirhe tallennuksessa.');
			$btn.prop('disabled', false).text('Tallenna ja julkaise salaiset linkit');
		});
	});

	// Copy link
	$(document).on('click', '.btn-copy', function() {
		var url = $(this).data('url');
		navigator.clipboard.writeText(url).then(function() {
			alert('Linkki kopioitu!');
		}, function(err) {
			alert('Kopiointi epäonnistui.');
		});
	});

});
