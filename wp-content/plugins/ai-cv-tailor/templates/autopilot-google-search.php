<?php
/**
 * Template: Google AI Pro Job Search & Recruitment Page Analyzer
 *
 * @package AI_CV_Tailor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AI_CV_TAILOR_DIR . 'includes/class-google-ai.php';
require_once AI_CV_TAILOR_DIR . 'includes/class-email-sender.php';

$google_ai    = new AI_CV_Tailor_Google_AI();
$email_sender = new AI_CV_Tailor_Email_Sender();

$is_configured = $google_ai->is_configured();
$admin_email   = $email_sender->get_admin_email();
$sender_name   = $email_sender->get_sender_name();
$current_model = $google_ai->get_model();

// Query Google AI analyzed jobs
$args = array(
	'post_type'      => 'freelance_job',
	'post_status'    => 'any',
	'posts_per_page' => 50,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'     => 'source',
			'value'   => 'Google AI',
			'compare' => 'LIKE',
		),
	),
);
$query = new WP_Query( $args );
?>

<div class="wrap ai-cv-google-search-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-search" style="font-size: 28px; width: 28px; height: 28px; vertical-align: middle; margin-right: 5px; color: #1a73e8;"></span>
		Google AI Pro Työpaikkahaku & Rekrysivujen Analyysi
	</h1>
	<hr class="wp-header-end">

	<?php if ( ! $is_configured ) : ?>
		<div class="notice notice-warning is-dismissible" style="border-left-color: #f0b840;">
			<p>
				<strong>Google AI (Gemini) API-avainta ei ole määritetty!</strong>
				Siirry <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-cv-tailor-autopilot-settings' ) ); ?>">Autopilot Asetukset</a> tai <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-cv-tailor-settings' ) ); ?>">Asetukset</a> -sivulle ja syötä Google AI API-avain ottaaksesi Google-työpaikkahaun käyttöön.
			</p>
		</div>
	<?php endif; ?>

	<div style="background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%); color: #ffffff; padding: 20px 25px; border-radius: 8px; margin-top: 15px; box-shadow: 0 4px 12px rgba(26,115,232,0.18);">
		<h2 style="color: #ffffff; margin-top: 0; font-size: 20px; font-weight: 600;">Hae ja kalasta työpaikkoja sekä avoimia hakemuksia tekoälyllä</h2>
		<p style="color: #e8f0fe; font-size: 14px; line-height: 1.6; margin-bottom: 8px; max-width: 900px;">
			Google AI Pro (Gemini 1.5 Pro) hakee Googlesta suomalaisia ja kansainvälisiä yrityksiä sekä toimeksiantoja eri hakusanoilla. Tekoäly tutkii yrityksen verkkosivujen rekrysivut, erottelee onko kyseessä <strong>listattu työpaikka</strong> vai <strong>avoin työhakemus</strong>, tallentaa <strong>analysoinnin päivämäärän ja suoran URL-osoitteen</strong>, <strong>kalastaa julkisen sähköpostin</strong> sivun lähdekoodista ja mahdollistaa hakemuksen lähettämisen suoraan sivuston pääkäyttäjän osoitteella (<strong><?php echo esc_html( $admin_email ); ?></strong>).
		</p>
		<div style="font-size: 13px; color: #d2e3fc;">
			<span>Aktiivinen malli: <strong><?php echo esc_html( $current_model ); ?></strong></span> &bull; 
			<span>Pääkäyttäjän lähetysosoite: <strong><?php echo esc_html( $admin_email ); ?></strong></span>
		</div>
	</div>

	<!-- Action cards grid -->
	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 25px;">
		
		<!-- Search by Keywords card -->
		<div class="ai-cv-card" style="margin-top: 0; border-top: 4px solid #1a73e8;">
			<h3 style="margin-top: 0; font-size: 16px;"><span class="dashicons dashicons-google" style="color: #1a73e8;"></span> Etsi Googlesta hakusanoilla</h3>
			<p class="description">Syötä hakusanat tai valitse alta valmis hakujono. Tekoäly etsii yritykset, hakee niiden rekrysivut ja analysoi ne automaattisesti.</p>
			
			<div style="display: flex; gap: 10px; margin-top: 12px;">
				<input type="text" id="ai-cv-search-keywords" class="regular-text" style="flex: 1; font-size: 15px; padding: 6px 12px;" placeholder="Esim. WordPress kehittäjä, PHP Laravel, avoin työhakemus..." value="WordPress kehittäjä remote Suomi">
				<select id="ai-cv-search-limit" style="font-size: 14px; padding: 6px;">
					<option value="3">3 kohdetta</option>
					<option value="5" selected>5 kohdetta</option>
					<option value="8">8 kohdetta</option>
				</select>
				<button type="button" id="btn-run-google-search" class="button button-primary" style="background: #1a73e8; border-color: #1a73e8; font-size: 14px; height: auto; padding: 6px 16px;">
					<span class="dashicons dashicons-search" style="vertical-align: middle;"></span> Hae ja analysoi Googlella
				</button>
			</div>

			<div style="margin-top: 12px; font-size: 13px;">
				<strong>Suositellut hakujonot:</strong>
				<div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
					<a href="#" class="preset-keyword button button-small" data-kw="WordPress kehittäjä remote Suomi">WordPress kehittäjä</a>
					<a href="#" class="preset-keyword button button-small" data-kw="Full-Stack Developer Laravel PHP Suomi">Full-Stack Laravel / PHP</a>
					<a href="#" class="preset-keyword button button-small" data-kw="Avoin hakemus ohjelmistokehittäjä ohjelmistotalo">Avoin hakemus (IT-talot)</a>
					<a href="#" class="preset-keyword button button-small" data-kw="Senior React TypeScript kehittäjä toimeksianto">React & TypeScript Senior</a>
					<a href="#" class="preset-keyword button button-small" data-kw="Atlassian Jira Forge developer consultant">Atlassian Jira & Forge</a>
					<a href="#" class="preset-keyword button button-small" data-kw="OpenAI AI sovelluskehittäjä rekrytointi">AI & OpenAI Kehittäjä</a>
				</div>
			</div>
		</div>

		<!-- Single URL Direct Analyzer card -->
		<div class="ai-cv-card" style="margin-top: 0; border-top: 4px solid #34a853;">
			<h3 style="margin-top: 0; font-size: 16px;"><span class="dashicons dashicons-admin-links" style="color: #34a853;"></span> Analysoi rekry-URL suoraan</h3>
			<p class="description">Löysitkö kiinnostavan rekrysivun tai avoimen haun? Syötä suora URL tähän.</p>
			
			<div style="margin-top: 12px;">
				<input type="url" id="ai-cv-direct-url" class="large-text" placeholder="https://yritys.fi/ura/avoin-hakemus" style="padding: 6px 10px; margin-bottom: 10px;">
				<button type="button" id="btn-analyze-direct-url" class="button button-secondary" style="width: 100%; text-align: center;">
					<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Analysoi sivu & kalasta email
				</button>
			</div>
		</div>

	</div>

	<!-- Live Status Container -->
	<div id="ai-cv-status-card" class="notice notice-info" style="display: none; margin-top: 20px; padding: 12px 15px;">
		<div style="display: flex; align-items: center; gap: 12px;">
			<span class="spinner is-active" style="float: none; margin: 0;"></span>
			<span id="ai-cv-status-text" style="font-size: 14px; font-weight: 500;">Google AI analysoi kohteita...</span>
		</div>
	</div>

	<!-- Results table -->
	<div style="margin-top: 30px;">
		<h2>
			Google AI Prolla löydetyt ja analysoidut toimeksiannot
			<span style="font-size: 14px; font-weight: normal; color: #666;">(Yhteensä <?php echo intval( $query->found_posts ); ?> kpl)</span>
		</h2>

		<table class="wp-list-table widefat fixed striped" style="margin-top: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
			<thead>
				<tr>
					<th style="width: 45px;">ID</th>
					<th style="width: 130px;">Analyysin Pvm & Aika</th>
					<th style="width: 180px;">Yritys & Tyyppi</th>
					<th>Rooli / Nimike & URL</th>
					<th style="width: 190px;">Kalastettu Julkinen Email</th>
					<th style="width: 75px;">Score</th>
					<th style="width: 100px;">Status</th>
					<th style="width: 180px;">Toiminnot</th>
				</tr>
			</thead>
			<tbody id="ai-cv-results-tbody">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post();
						$job_id         = get_the_ID();
						$company_name   = get_post_meta( $job_id, 'company_name', true ) ?: 'Yritys';
						$role_title     = get_post_meta( $job_id, 'role_title', true ) ?: get_the_title();
						$source_url     = get_post_meta( $job_id, 'source_url', true );
						$job_type       = get_post_meta( $job_id, 'job_type', true ) ?: 'listattu';
						$analysis_date  = get_post_meta( $job_id, 'analysis_date', true ) ?: get_the_date( 'Y-m-d H:i' );
						$contact_email  = get_post_meta( $job_id, 'contact_email', true );
						$match_score    = get_post_meta( $job_id, 'match_score', true );
						$status         = get_post_meta( $job_id, 'status', true ) ?: 'New';
						$app_id         = get_post_meta( $job_id, 'generated_application_id', true );
						$applied_date   = get_post_meta( $job_id, 'applied_date', true );

						$score_color = '#72aee6';
						if ( intval( $match_score ) >= 75 ) {
							$score_color = '#00a32a';
						} elseif ( intval( $match_score ) >= 50 ) {
							$score_color = '#2271b1';
						} else {
							$score_color = '#dba617';
						}

						$type_badge = ( 'avoin_hakemus' === $job_type )
							? '<span style="background: #e7f3fe; color: #1a73e8; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; margin-top: 3px;">Avoin työhakemus</span>'
							: '<span style="background: #e6f4ea; color: #137333; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; margin-top: 3px;">Listattu työpaikka</span>';
					?>
						<tr id="job-row-<?php echo esc_attr( $job_id ); ?>">
							<td><strong>#<?php echo esc_html( $job_id ); ?></strong></td>
							<td>
								<span style="font-size: 12px; color: #333; font-weight: 500;"><?php echo esc_html( $analysis_date ); ?></span>
							</td>
							<td>
								<strong><?php echo esc_html( $company_name ); ?></strong>
								<br><?php echo $type_badge; ?>
							</td>
							<td>
								<div style="font-weight: 600; color: #1d2327;"><?php echo esc_html( $role_title ); ?></div>
								<?php if ( ! empty( $source_url ) ) : ?>
									<div style="margin-top: 4px; font-size: 12px;">
										<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer" style="color: #1a73e8; text-decoration: none;">
											<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
											<?php echo esc_html( wp_trim_words( $source_url, 6, '...' ) ); ?>
										</a>
									</div>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $contact_email ) ) : ?>
									<div style="display: flex; align-items: center; gap: 4px;">
										<span class="dashicons dashicons-email-alt" style="color: #1a73e8; font-size: 16px; width: 16px; height: 16px;"></span>
										<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="font-weight: 600; color: #1a73e8;">
											<?php echo esc_html( $contact_email ); ?>
										</a>
									</div>
								<?php else : ?>
									<span style="color: #999; font-style: italic;">Ei emailia kalastettu</span>
								<?php endif; ?>
							</td>
							<td>
								<span style="display: inline-block; padding: 3px 8px; border-radius: 12px; background: <?php echo esc_attr( $score_color ); ?>; color: #ffffff; font-weight: bold; font-size: 12px;">
									<?php echo esc_html( $match_score !== '' ? $match_score . '%' : '-' ); ?>
								</span>
							</td>
							<td>
								<?php if ( 'Applied' === $status ) : ?>
									<span style="background: #2e7d32; color: #fff; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: bold;">Lähetetty</span>
									<?php if ( $applied_date ) : ?>
										<br><span style="font-size: 10px; color: #666;"><?php echo esc_html( substr( $applied_date, 0, 10 ) ); ?></span>
									<?php endif; ?>
								<?php elseif ( 'Good Match' === $status ) : ?>
									<span style="background: #0073aa; color: #fff; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: bold;">Hyvä match</span>
								<?php else : ?>
									<span style="background: #f0f0f1; color: #333; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: 500;"><?php echo esc_html( $status ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<div style="display: flex; flex-direction: column; gap: 4px;">
									<button type="button" class="button button-primary btn-open-send-modal" data-id="<?php echo esc_attr( $job_id ); ?>" style="background: #1a73e8; border-color: #1a73e8; font-size: 12px; padding: 0 8px; height: 26px; line-height: 24px;">
										<span class="dashicons dashicons-email" style="vertical-align: middle; font-size: 14px; width: 14px; height: 14px;"></span> Lähetä hakemus
									</button>
									<div style="display: flex; gap: 4px; margin-top: 2px;">
										<button type="button" class="button button-small btn-view-analysis" data-id="<?php echo esc_attr( $job_id ); ?>">Analyysi</button>
										<?php if ( ! $app_id ) : ?>
											<button type="button" class="button button-small action-generate" data-id="<?php echo esc_attr( $job_id ); ?>" style="color: #0073aa; font-weight: 600;">Tee AI CV</button>
										<?php else : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $app_id ) ); ?>" class="button button-small" target="_blank" title="Avaa hakemus ja CV-linkit">CV-linkit</a>
										<?php endif; ?>
									</div>
								</div>
							</td>
						</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				<?php else : ?>
					<tr>
						<td colspan="8" style="text-align: center; padding: 30px; color: #666;">
							Ei vielä Google AI Prolla analysoituja toimeksiantoja. Tee haku yläpuolelta aloittaaksesi!
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- SEND APPLICATION MODAL -->
	<div id="ai-cv-send-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); overflow-y: auto;">
		<div style="position: relative; background: #ffffff; width: 90%; max-width: 680px; margin: 40px auto; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); overflow: hidden;">
			
			<div style="background: #1a73e8; color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
				<h3 style="margin: 0; color: #ffffff; font-size: 17px; font-weight: 600;">
					<span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Lähetä työhakemus (Pääkäyttäjän sähköpostilla)
				</h3>
				<button type="button" class="btn-close-send-modal" style="background: transparent; border: none; color: #ffffff; font-size: 22px; cursor: pointer; line-height: 1;">&times;</button>
			</div>

			<div style="padding: 20px 25px; max-height: calc(85vh - 120px); overflow-y: auto;">
				<input type="hidden" id="modal-send-job-id" value="">
				
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
					<div>
						<label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">Lähettäjä (Pääkäyttäjä):</label>
						<input type="text" id="modal-sender-info" class="regular-text" style="width: 100%; background: #f0f0f1;" readonly value="<?php echo esc_attr( $sender_name . ' <' . $admin_email . '>' ); ?>">
					</div>
					<div>
						<label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">Vastaanottajan email (Kalastettu julkinen osoite):</label>
						<input type="email" id="modal-recipient-email" class="regular-text" style="width: 100%; font-weight: bold; color: #1a73e8;" value="" required>
					</div>
				</div>

				<div style="margin-bottom: 15px;">
					<label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">Sähköpostin Aihe:</label>
					<input type="text" id="modal-email-subject" class="large-text" style="width: 100%;" value="" required>
				</div>

				<div style="margin-bottom: 15px;">
					<label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">Hakemuskirje (Alkuperäinen ehdotettu teksti):</label>
					<textarea id="modal-email-body" rows="9" class="large-text" style="width: 100%; font-family: sans-serif; font-size: 14px; line-height: 1.5; padding: 10px;"></textarea>
				</div>

				<div id="modal-cv-links-box" style="background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; margin-bottom: 15px; font-size: 13px;">
					<strong>Liitettävät CV-linkit:</strong>
					<ul id="modal-cv-links-list" style="margin: 5px 0 0 18px;"></ul>
				</div>

				<div id="modal-footer-box" style="background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; margin-bottom: 15px; font-size: 13px;">
					<strong>Allekirjoitus / Footer (Asetuksista):</strong>
					<pre id="modal-footer-preview" style="margin: 6px 0 0 0; background: #ffffff; border: 1px solid #e5e5e5; padding: 8px 12px; font-size: 12px; font-family: monospace; white-space: pre-wrap; color: #333; border-radius: 3px;"></pre>
				</div>

				<div style="font-size: 11px; color: #888888; text-align: center; margin: 12px 0; font-style: italic;">
					Powered by i4ware Job Seeker AI based to Google Gemini API and OpenAI API
				</div>

				<div id="modal-send-status" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 15px;"></div>
			</div>

			<div style="background: #f6f7f7; padding: 12px 20px; border-top: 1px solid #dcdcde; display: flex; justify-content: space-between; align-items: center;">
				<span style="font-size: 12px; color: #666;">Lähetetään osoitteesta: <strong><?php echo esc_html( $admin_email ); ?></strong></span>
				<div style="display: flex; gap: 10px;">
					<button type="button" class="button button-secondary btn-close-send-modal">Peruuta</button>
					<button type="button" id="btn-submit-send-application" class="button button-primary" style="background: #1a73e8; border-color: #1a73e8;">
						<span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span> Lähetä Hakemus Nyt
					</button>
				</div>
			</div>

		</div>
	</div>

	<!-- ANALYSIS DETAILS MODAL -->
	<div id="ai-cv-analysis-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); overflow-y: auto;">
		<div style="position: relative; background: #ffffff; width: 90%; max-width: 650px; margin: 40px auto; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); overflow: hidden;">
			<div style="background: #23282d; color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
				<h3 id="analysis-modal-title" style="margin: 0; color: #ffffff; font-size: 16px;">Tekoälyanalyysin tiedot</h3>
				<button type="button" class="btn-close-analysis-modal" style="background: transparent; border: none; color: #ffffff; font-size: 22px; cursor: pointer;">&times;</button>
			</div>
			<div id="analysis-modal-content" style="padding: 20px 25px; max-height: calc(85vh - 100px); overflow-y: auto; font-size: 14px; line-height: 1.6;">
				<!-- Injected by JS -->
			</div>
			<div style="background: #f6f7f7; padding: 10px 20px; border-top: 1px solid #dcdcde; text-align: right;">
				<button type="button" class="button button-secondary btn-close-analysis-modal">Sulje</button>
			</div>
		</div>
	</div>

</div>

<script>
jQuery(document).ready(function($) {

	// Preset keywords click
	$('.preset-keyword').on('click', function(e) {
		e.preventDefault();
		$('#ai-cv-search-keywords').val($(this).data('kw'));
	});

	// Google AI Search button
	$('#btn-run-google-search').on('click', function(e) {
		e.preventDefault();
		var keywords = $('#ai-cv-search-keywords').val().trim();
		var limit = $('#ai-cv-search-limit').val();

		if(!keywords) {
			alert('Syötä hakusanat!');
			return;
		}

		var btn = $(this);
		btn.prop('disabled', true);
		$('#ai-cv-status-card').slideDown();
		$('#ai-cv-status-text').text('Google AI Pro etsii toimeksiantoja ja analysoi rekrysivuja hakusanalla "' + keywords + '"...');

		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_google_search',
			nonce: aiCvTailorAutopilotObj.nonce,
			keywords: keywords,
			limit: limit
		}, function(res) {
			btn.prop('disabled', false);
			$('#ai-cv-status-card').slideUp();

			if(res.success) {
				alert('Valmis! ' + res.data.message);
				location.reload();
			} else {
				alert('Virhe haussa: ' + res.data);
			}
		}).fail(function(xhr) {
			btn.prop('disabled', false);
			$('#ai-cv-status-card').slideUp();
			alert('Pyyntö epäonnistui: ' + xhr.statusText);
		});
	});

	// Direct URL Analyzer button
	$('#btn-analyze-direct-url').on('click', function(e) {
		e.preventDefault();
		var url = $('#ai-cv-direct-url').val().trim();
		if(!url) {
			alert('Syötä analysoitavan rekrysivun tai avoimen haun URL!');
			return;
		}

		var btn = $(this);
		btn.prop('disabled', true);
		$('#ai-cv-status-card').slideDown();
		$('#ai-cv-status-text').text('Haetaan ja analysoidaan sivua sekä kalastetaan sähköpostit: ' + url + '...');

		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_analyze_url',
			nonce: aiCvTailorAutopilotObj.nonce,
			url: url
		}, function(res) {
			btn.prop('disabled', false);
			$('#ai-cv-status-card').slideUp();

			if(res.success) {
				alert('Sivu analysoitu! Yritys: ' + res.data.company_name + ', Match: ' + res.data.match_score + '%, Email: ' + (res.data.contact_email || 'Ei emailia'));
				location.reload();
			} else {
				alert('Analyysi epäonnistui: ' + res.data);
			}
		}).fail(function(xhr) {
			btn.prop('disabled', false);
			$('#ai-cv-status-card').slideUp();
			alert('Pyyntö epäonnistui: ' + xhr.statusText);
		});
	});

	// Open Send Application Modal
	$(document).on('click', '.btn-open-send-modal', function(e) {
		e.preventDefault();
		var jobId = $(this).data('id');
		$('#modal-send-job-id').val(jobId);
		$('#modal-send-status').hide().empty();
		$('#btn-submit-send-application').prop('disabled', false).text('Lähetä Hakemus Nyt');

		// Fetch email preview data
		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_get_email_preview',
			nonce: aiCvTailorAutopilotObj.nonce,
			job_id: jobId
		}, function(res) {
			if(res.success) {
				var d = res.data;
				$('#modal-recipient-email').val(d.contact_email || '');
				$('#modal-email-subject').val(d.subject || '');
				$('#modal-email-body').val(d.cover_letter || '');

				var linksList = $('#modal-cv-links-list').empty();
				if(d.cv_links) {
					$.each(d.cv_links, function(label, link) {
						linksList.append('<li><a href="' + link + '" target="_blank">' + label + '</a> (' + link + ')</li>');
					});
				}
				if(d.footer) {
					$('#modal-footer-preview').text(d.footer);
					$('#modal-footer-box').show();
				} else {
					$('#modal-footer-box').hide();
				}
				$('#ai-cv-send-modal').fadeIn(200);
			} else {
				alert('Virhe ladattaessa hakemustietoja: ' + res.data);
			}
		});
	});

	// Close Send Modal
	$('.btn-close-send-modal').on('click', function() {
		$('#ai-cv-send-modal').fadeOut(200);
	});

	// Submit Send Application Email
	$('#btn-submit-send-application').on('click', function(e) {
		e.preventDefault();
		var jobId = $('#modal-send-job-id').val();
		var toEmail = $('#modal-recipient-email').val().trim();
		var subject = $('#modal-email-subject').val().trim();
		var body = $('#modal-email-body').val().trim();

		if(!toEmail) {
			alert('Syötä vastaanottajan sähköpostiosoite!');
			return;
		}
		if(!subject || !body) {
			alert('Aihe ja hakemuskirje ovat pakollisia!');
			return;
		}

		if(!confirm('Lähetetäänkö hakemus osoitteeseen ' + toEmail + ' pääkäyttäjän osoitteella (' + aiCvTailorAutopilotObj.admin_email + ')?')) {
			return;
		}

		var btn = $(this);
		btn.prop('disabled', true).text('Lähetetään sähköpostia...');
		$('#modal-send-status').show().removeClass('notice-error notice-success').addClass('notice-info').text('Lähetetään hakemusta wp_mail()-funktiolla...');

		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_send_application_email',
			nonce: aiCvTailorAutopilotObj.nonce,
			job_id: jobId,
			to_email: toEmail,
			subject: subject,
			body: body
		}, function(res) {
			if(res.success) {
				$('#modal-send-status').removeClass('notice-info notice-error').addClass('notice-success').html('<strong>Onnistui!</strong> ' + res.data.message);
				btn.text('Hakemus lähetetty!');
				setTimeout(function() {
					$('#ai-cv-send-modal').fadeOut(200);
					location.reload();
				}, 1500);
			} else {
				btn.prop('disabled', false).text('Yritä uudelleen');
				$('#modal-send-status').removeClass('notice-info notice-success').addClass('notice-error').text('Virhe lähetyksessä: ' + res.data);
			}
		}).fail(function() {
			btn.prop('disabled', false).text('Yritä uudelleen');
			$('#modal-send-status').removeClass('notice-info notice-success').addClass('notice-error').text('Yhteysvirhe sähköpostia lähetettäessä.');
		});
	});

	// Handle "Tee AI CV" click
	$(document).on('click', '.action-generate', function(e) {
		e.preventDefault();
		var btn = $(this);
		var jobId = btn.data('id');
		btn.text('Luodaan...').prop('disabled', true);

		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_autopilot_generate',
			nonce: aiCvTailorAutopilotObj.nonce,
			post_id: jobId
		}, function(res) {
			if(res.success) {
				alert('AI CV ja interaktiiviset CV-linkit luotu onnistuneesti! Alkuperäinen hakemuskirje on säilytetty.');
				location.reload();
			} else {
				alert('Virhe luotaessa AI CV:tä: ' + (res.data || 'Tuntematon virhe'));
				btn.text('Tee AI CV').prop('disabled', false);
			}
		}).fail(function() {
			alert('Yhteysvirhe luotaessa AI CV:tä.');
			btn.text('Tee AI CV').prop('disabled', false);
		});
	});

	// View Analysis Modal
	$(document).on('click', '.btn-view-analysis', function(e) {
		e.preventDefault();
		var jobId = $(this).data('id');

		$.post(aiCvTailorAutopilotObj.ajax_url, {
			action: 'ai_cv_tailor_get_email_preview',
			nonce: aiCvTailorAutopilotObj.nonce,
			job_id: jobId
		}, function(res) {
			if(res.success) {
				var d = res.data;
				var html = '<h4 style="margin-top:0;">' + (d.company_name || '') + ' - ' + (d.role_title || '') + '</h4>';
				html += '<p><strong>Tyyppi:</strong> ' + (d.job_type === 'avoin_hakemus' ? 'Avoin työhakemus' : 'Listattu työpaikka') + '</p>';
				html += '<p><strong>Lähdelinkki:</strong> <a href="' + d.source_url + '" target="_blank">' + d.source_url + '</a></p>';
				html += '<p><strong>Kalastettu Email:</strong> ' + (d.contact_email ? '<a href="mailto:' + d.contact_email + '">' + d.contact_email + '</a>' : 'Ei emailia') + '</p>';
				html += '<hr><p><strong>Alkuperäinen ehdotettu hakemuskirje:</strong></p>';
				html += '<div style="background:#f9f9f9; padding:12px; border-radius:4px; white-space:pre-wrap; font-size:13px; max-height:220px; overflow-y:auto; border:1px solid #e5e5e5;">' + (d.cover_letter || 'Ei kirjettä generoitu') + '</div>';

				if (d.cv_links && Object.keys(d.cv_links).length > 0) {
					html += '<hr><p><strong>Liitettävät interaktiiviset CV-linkit:</strong></p><ul style="margin: 0 0 10px 20px;">';
					$.each(d.cv_links, function(label, link) {
						html += '<li style="margin-bottom:4px;"><strong>' + label + ':</strong> <a href="' + link + '" target="_blank">' + link + '</a></li>';
					});
					html += '</ul>';
				}

				if (d.footer) {
					html += '<hr><p><strong>Allekirjoitus / Footer (Asetuksista):</strong></p>';
					html += '<pre style="background:#f9f9f9; padding:10px; border-radius:4px; font-family:monospace; font-size:12px; white-space:pre-wrap; border:1px solid #e5e5e5;">' + d.footer + '</pre>';
				}

				html += '<div style="margin-top:14px; padding-top:10px; border-top:1px solid #eee; font-size:11px; color:#888; text-align:center; font-style:italic;">Powered by i4ware Job Seeker AI based to Google Gemini API and OpenAI API</div>';

				$('#analysis-modal-content').html(html);
				$('#ai-cv-analysis-modal').fadeIn(200);
			}
		});
	});

	$('.btn-close-analysis-modal').on('click', function() {
		$('#ai-cv-analysis-modal').fadeOut(200);
	});

});
</script>
