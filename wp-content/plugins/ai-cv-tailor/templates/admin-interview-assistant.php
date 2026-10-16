<?php
/**
 * Admin Template: Haastatteluavustaja (Interview Assistant)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$assistant = AI_CV_Tailor_Interview_Assistant::get_instance();
$prep = get_user_meta( $user_id, 'ai_interview_active_prep', true );
if ( empty( $prep ) || ! is_array( $prep ) ) {
	$apps = get_posts( array( 'post_type' => 'ai_cv_application', 'posts_per_page' => 1 ) );
	$prep = array(
		'application_id' => ! empty( $apps ) ? $apps[0]->ID : 0,
		'language'       => 'fi',
		'mode'           => 'job_seeker',
		'project_ids'    => array(),
		'hr_criteria'    => array(
			'role'                  => 'Senior Ohjelmistokehittäjä / Arkkitehti',
			'key_competencies'      => 'PHP, WordPress, JavaScript, TypeScript, REST API, pilvipalvelut',
			'evaluation_guidelines' => 'Arvioi kokemus, ongelmanratkaisukyky ja viestintätaito.',
		),
	);
}

// Fetch active devices
$devices = get_user_meta( $user_id, 'ai_cv_interview_device_tokens', true );
if ( ! is_array( $devices ) ) {
	$devices = array();
}

// Fetch applications
$applications = get_posts( array(
	'post_type'      => 'ai_cv_application',
	'posts_per_page' => 50,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

// Fetch projects
$projects = get_posts( array(
	'post_type'      => 'cv_project',
	'posts_per_page' => 100,
	'post_status'    => 'publish',
) );

$settings = get_option( 'ai_cv_tailor_settings', array() );
$latest_pair_info = get_user_meta( $user_id, 'ai_interview_latest_pairing_code', true );
$has_active_code = ! empty( $latest_pair_info ) && ( $latest_pair_info['expires_at'] ?? 0 ) > time();
?>

<div class="wrap ai-cv-tailor-wrap">
	<h1 style="display:flex; align-items:center; gap:10px;">
		<span class="dashicons dashicons-format-chat" style="font-size:32px; width:32px; height:32px; color:#2271b1;"></span>
		i4ware® Job Seekers Autopilot AI — Haastatteluavustaja
	</h1>
	<p class="description" style="font-size:14px; max-width:900px;">
		Tästä näkymästä valmistellaan tulevan työhaastattelun taustatietopaketti, hallitaan turvallista laiteparitusta Windows 11 -työpöytäsovelluksen kanssa sekä testataan reaaliaikaista AI-vastausmoottoria.
	</p>

	<!-- Grid Layout -->
	<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
		
		<!-- CARD 1: LAITEPARITUS JA TURVALLISUUS -->
		<div class="ai-cv-card" style="border-top: 4px solid #2271b1;">
			<h2><span class="dashicons dashicons-admin-network" style="color:#2271b1;"></span> 1. Turvallinen Laiteparitus (Windows 11)</h2>
			<p>Yhdistä Windows 11 -sovellus tähän WordPress-asennukseen syöttämällä kertakäyttöinen parituskoodi. Sovellukseen ei koskaan tallenneta WordPress-salasanaasi tai suoria API-avaimia.</p>
			
			<div style="background:#f0f6fc; border:1px solid #c8d7e1; border-radius:8px; padding:15px; margin-bottom:15px; text-align:center;">
				<div style="font-size:12px; text-transform:uppercase; color:#50575e; font-weight:600; margin-bottom:5px;">Aktiivinen Parituskoodi</div>
				<div id="active-pair-code-display" style="font-size:32px; font-family:monospace; font-weight:bold; letter-spacing:4px; color:#135e96;">
					<?php echo $has_active_code ? esc_html( $latest_pair_info['code'] ) : '------'; ?>
				</div>
				<div id="pair-code-timer" style="font-size:12px; color:#646970; margin-top:5px;">
					<?php 
					if ( $has_active_code ) {
						$remain = $latest_pair_info['expires_at'] - time();
						echo 'Voimassa vielä noin ' . round( $remain / 60 ) . ' min';
					} else {
						echo 'Ei aktiivista koodia. Luo uusi koodi alta.';
					}
					?>
				</div>
				<button type="button" id="btn-generate-pair-code" class="button button-primary" style="margin-top:12px;">
					<span class="dashicons dashicons-update" style="margin-top:4px;"></span> Luo uusi 6-merkkinen parituskoodi
				</button>
			</div>

			<h3>Paritetut Laitteet</h3>
			<?php if ( empty( $devices ) ) : ?>
				<p style="color:#646970; font-style:italic;">Ei vielä paritettuja laitteita. Käynnistä Windows 11 -sovellus ja syötä yllä oleva koodi.</p>
			<?php else : ?>
				<table class="widefat striped" style="margin-top:10px;">
					<thead>
						<tr>
							<th>Laite</th>
							<th>Paritettu</th>
							<th>Viimeksi käytetty</th>
							<th>Toiminto</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $devices as $dev ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $dev['device_name'] ?? 'Windows 11' ); ?></strong></td>
								<td><?php echo esc_html( date( 'd.m.Y H:i', $dev['created_at'] ?? time() ) ); ?></td>
								<td><?php echo esc_html( date( 'd.m.Y H:i', $dev['last_used'] ?? time() ) ); ?></td>
								<td>
									<button type="button" class="button button-small button-link-delete btn-revoke-device" data-id="<?php echo esc_attr( $dev['id'] ); ?>">
										Pura paritus
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<div style="margin-top:15px; font-size:12px; color:#50575e; background:#fff; border-left:4px solid #72aee6; padding:10px;">
				<strong>Turvallisuusperiaate:</strong> Yhteys Windows-sovellukseen suojataan SHA-256 tokenilla. Tekoäly- ja LiveAvatar-avaimet säilytetään yksinomaan tällä WordPress-palvelimella.
			</div>
		</div>

		<!-- CARD 2: TOIMINTATILA (TYÖNHAKIJA VS HR ADVISOR) -->
		<div class="ai-cv-card" style="border-top: 4px solid #46b450;">
			<h2><span class="dashicons dashicons-businessman" style="color:#46b450;"></span> 2. Toimintatila ja Rooli</h2>
			<p>Valitse käytettävä rooli. Työnhakijan avustaja ja työnantajan HR-toiminnot on eristetty toisistaan.</p>

			<form id="form-interview-prep">
				<div style="margin-bottom:15px;">
					<label style="font-weight:600; display:block; margin-bottom:6px;">Aktiivinen Toimintatila:</label>
					<label style="margin-right:20px; font-size:14px;">
						<input type="radio" name="mode" value="job_seeker" <?php checked( $prep['mode'] ?? 'job_seeker', 'job_seeker' ); ?> />
						<strong>Työnhakijan haastatteluavustaja</strong> (Valmentaa hakijaa, ehdottaa vastauksia ja LiveAvatar-puhetta)
					</label>
					<br/>
					<label style="font-size:14px; margin-top:8px; display:inline-block;">
						<input type="radio" name="mode" value="hr_advisor" <?php checked( $prep['mode'] ?? '', 'hr_advisor' ); ?> />
						<strong>HR Advisor -tila</strong> (Haastattelijan/rekrytoijan työkalu: arviointikriteerit, syventävät kysymykset)
					</label>
				</div>

				<div id="hr-advisor-fields" style="<?php echo ( ( $prep['mode'] ?? '' ) === 'hr_advisor' ) ? '' : 'display:none;'; ?> background:#fcf9e8; border:1px solid #ecd580; border-radius:6px; padding:15px; margin-bottom:15px;">
					<h4 style="margin-top:0;">HR Advisor -arviointikriteerit ja kysymysrunko</h4>
					<div style="margin-bottom:10px;">
						<label style="font-weight:600; display:block;">Haettava Rooli / Titteli:</label>
						<input type="text" name="hr_role" class="regular-text" style="width:100%;" value="<?php echo esc_attr( $prep['hr_criteria']['role'] ?? 'Senior Ohjelmistokehittäjä' ); ?>" />
					</div>
					<div style="margin-bottom:10px;">
						<label style="font-weight:600; display:block;">Avainosaamisalueet (Competencies):</label>
						<textarea name="hr_competencies" rows="2" style="width:100%;"><?php echo esc_textarea( $prep['hr_criteria']['key_competencies'] ?? 'PHP, WordPress, JavaScript, TypeScript, REST API, pilvipalvelut, tiimityö' ); ?></textarea>
					</div>
					<div>
						<label style="font-weight:600; display:block;">Haastattelijan arviointiohjeet (Rubric):</label>
						<textarea name="hr_guidelines" rows="2" style="width:100%;"><?php echo esc_textarea( $prep['hr_criteria']['evaluation_guidelines'] ?? 'Arvioi kokemus, ongelmanratkaisukyky, kommunikaatio ja tekninen syvyys.' ); ?></textarea>
					</div>
				</div>

				<!-- CARD 3: VALMISTELUPAKETIN VALINTA -->
				<h3>3. Haastattelun Taustatietopaketti</h3>
				
				<div style="margin-bottom:12px;">
					<label for="select-application" style="font-weight:600; display:block; margin-bottom:4px;">Valitse Työpaikkailmoitus & Hakemus:</label>
					<select id="select-application" name="application_id" style="width:100%; max-width:100%;">
						<option value="0">-- Valitse kohdehakemus --</option>
						<?php foreach ( $applications as $app ) : ?>
							<?php 
							$comp = get_post_meta( $app->ID, '_company_name', true ) ?: ( get_post_meta( $app->ID, 'ai_cv_company_name', true ) ?: 'Yritys' );
							$score = get_post_meta( $app->ID, 'ai_cv_match_score', true );
							?>
							<option value="<?php echo esc_attr( $app->ID ); ?>" <?php selected( $prep['application_id'] ?? 0, $app->ID ); ?>>
								<?php echo esc_html( $comp . ' — ' . $app->post_title . ( $score ? " (Match {$score}%)" : '' ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="margin-bottom:12px;">
					<label for="select-language" style="font-weight:600; display:block; margin-bottom:4px;">Vastausten kieli:</label>
					<select id="select-language" name="language" style="width:200px;">
						<option value="fi" <?php selected( $prep['language'] ?? 'fi', 'fi' ); ?>>Suomi (Finnish)</option>
						<option value="en" <?php selected( $prep['language'] ?? '', 'en' ); ?>>English</option>
						<option value="sv" <?php selected( $prep['language'] ?? '', 'sv' ); ?>>Svenska (Swedish)</option>
						<option value="de" <?php selected( $prep['language'] ?? '', 'de' ); ?>>Deutsch (German)</option>
					</select>
				</div>

				<div style="margin-bottom:15px;">
					<label style="font-weight:600; display:block; margin-bottom:4px;">Valitut Vahvistetut Projektiesimerkit:</label>
					<p class="description" style="margin-bottom:8px;">Tekoäly saa viitata vastauksissaan ainoastaan valittuihin projekteihin ja CV-tietoihin. Älä keksi kokemusta faktatiedoiksi!</p>
					
					<div style="max-height:160px; overflow-y:auto; border:1px solid #dcdcde; background:#fff; padding:8px; border-radius:4px;">
						<?php 
						$selected_projects = $prep['project_ids'] ?? array();
						if ( empty( $projects ) ) : ?>
							<p style="color:#646970;">Ei projekteja rekisteröitynä cv_project -post tyypissä.</p>
						<?php else : ?>
							<?php foreach ( $projects as $proj ) : ?>
								<label style="display:block; margin-bottom:4px;">
									<input type="checkbox" name="project_ids[]" value="<?php echo esc_attr( $proj->ID ); ?>" <?php checked( in_array( $proj->ID, $selected_projects, true ) ); ?> />
									<strong><?php echo esc_html( $proj->post_title ); ?></strong>
									<?php 
									$meta_tech = get_post_meta( $proj->ID, '_cv_project_meta', true );
									if ( $meta_tech ) {
										echo ' <span style="color:#646970; font-size:12px;">(' . esc_html( $meta_tech ) . ')</span>';
									}
									?>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<div style="display:flex; gap:10px; align-items:center;">
					<button type="button" id="btn-save-prep" class="button button-primary">
						<span class="dashicons dashicons-saved" style="margin-top:4px;"></span> Tallenna haastattelun taustapaketti
					</button>
					<span id="save-prep-status" style="font-weight:600; color:#46b450;"></span>
				</div>
			</form>
		</div>
	</div>

	<!-- CARD 4: TESTAA AI-AVUSTAJAA REAALIAJASSA -->
	<div class="ai-cv-card" style="margin-top:25px; border-top: 4px solid #f0b849;">
		<h2><span class="dashicons dashicons-microphone" style="color:#f0b849;"></span> 4. Testaa AI-avustajaa suoraan hallintapaneelissa</h2>
		<p>Kokeile haastattelukysymystä. Tekoäly soveltaa sääntöjä: tunnistaa tarkoituksen, antaa vastauksen pääasian ensin, enintään kolme tukikohtaa, hakee todellisen esimerkin taustoistasi ilman keksittyjä faktoja, ja luo valmiin puheenvuoroluonnoksen.</p>

		<div style="display:flex; gap:10px; margin-bottom:15px;">
			<input type="text" id="test-question-input" style="flex:1; padding:8px 12px; font-size:15px;" placeholder="Esim: 'Kerro miten olet toteuttanut skaalautuvia REST API -rajapintoja tai pilviratkaisuja?' tai 'Mikä on suurin ammatillinen haasteesi?'" />
			<button type="button" id="btn-test-suggest" class="button button-primary" style="height:auto; font-size:14px;">
				<span class="dashicons dashicons-controls-play" style="margin-top:4px;"></span> Analysoi ja luo vastaus
			</button>
		</div>

		<div id="test-loading-indicator" style="display:none; padding:15px; color:#50575e; font-style:italic;">
			<span class="spinner is-active" style="float:none; margin-right:8px;"></span> Analysoidaan kysymystä ja etsitään vahvistettuja taustatietoja...
		</div>

		<!-- AI OUTPUT CONTAINER -->
		<div id="test-output-box" style="display:none; background:#f9f9f9; border:1px solid #ccd0d4; border-radius:8px; padding:20px;">
			<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
				
				<!-- Left Column: Private Operator Advice -->
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:1px; color:#646970; margin-bottom:6px;">Yksityinen ohjauspaneeli (Vain sinulle näkyvä)</div>
					
					<div style="background:#fff; border-left:4px solid #2271b1; padding:12px; border-radius:4px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
						<div style="font-weight:600; color:#135e96; font-size:13px; margin-bottom:4px;">🎯 Kysymyksen varsinainen tarkoitus (Intent):</div>
						<div id="ai-res-intent" style="font-size:14px; color:#1d2327;"></div>
					</div>

					<div style="background:#fff; border-left:4px solid #46b450; padding:12px; border-radius:4px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
						<div style="font-weight:600; color:#2e6d34; font-size:13px; margin-bottom:4px;">💡 Vastauksen pääasia ensin (Main Point):</div>
						<div id="ai-res-main-point" style="font-size:14px; font-weight:600; color:#1d2327;"></div>
					</div>

					<div style="background:#fff; border-left:4px solid #826eb4; padding:12px; border-radius:4px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
						<div style="font-weight:600; color:#5c478a; font-size:13px; margin-bottom:4px;">📌 Enintään 3 lyhyttä tukikohtaa:</div>
						<ul id="ai-res-points" style="margin:6px 0 0 20px; font-size:13px; color:#2c3338;"></ul>
					</div>

					<div style="background:#fff; border-left:4px solid #dba617; padding:12px; border-radius:4px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
						<div style="font-weight:600; color:#996800; font-size:13px; margin-bottom:4px;">🛠 Todellinen esimerkki vahvistetuista taustatiedoistasi:</div>
						<div id="ai-res-example" style="font-size:13px; color:#2c3338;"></div>
					</div>

					<div id="ai-res-clarify-box" style="display:none; background:#fff3cd; border:1px solid #ffeeba; padding:10px; border-radius:4px;">
						<div style="font-weight:600; color:#856404; font-size:13px;">❓ Ehdotettu tarkentava vastakysymys:</div>
						<div id="ai-res-clarify" style="font-size:13px; color:#856404; margin-top:4px;"></div>
					</div>
				</div>

				<!-- Right Column: Speech Draft & LiveAvatar Preview -->
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:bold; letter-spacing:1px; color:#646970; margin-bottom:6px;">LiveAvatar & Puheenvuoroluonnos</div>
					
					<div style="background:#fff; border:1px solid #c3c4c7; border-radius:6px; padding:15px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
						<label style="font-weight:600; display:block; margin-bottom:6px; color:#1d2327;">Muokattava puheenvuoroluonnos (Avatarin puhe):</label>
						<p class="description" style="margin-bottom:8px;">Voit muokata tekstiä ennen hyväksyntää. Tekstin muuttaminen mitätöi hyväksynnän automaattisesti turvallisuuden vuoksi.</p>
						<textarea id="ai-res-speech-draft" rows="4" style="width:100%; font-size:14px; padding:8px; line-height:1.5;"></textarea>

						<div style="margin-top:15px; display:flex; gap:10px; align-items:center;">
							<button type="button" id="btn-test-approve-speak" class="button button-primary" style="background:#2271b1; border-color:#2271b1;">
								<span class="dashicons dashicons-controls-volumeon" style="margin-top:4px;"></span> Hyväksy ja puhu
							</button>
							<button type="button" id="btn-test-stop-speak" class="button button-secondary" style="color:#d63638; border-color:#d63638;">
								<span class="dashicons dashicons-controls-pause" style="margin-top:4px;"></span> Välitön Lopeta puhe
							</button>
							<span id="speech-approval-status" style="font-size:12px; color:#646970;"></span>
						</div>

						<div style="margin-top:15px; background:#f0f0f1; border-radius:4px; padding:10px; font-size:12px; color:#50575e;">
							<strong>Puheoikeussääntö:</strong> Avatar puhuu vain nimenomaisesti hyväksytyn yksittäisen puheenvuoron. Puheen päätyttyä puhelupa poistuu välittömästi. Yksityiset ohjeet eivät koskaan siirry kokouksen chatiin.
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce = '<?php echo wp_create_nonce( 'ai_cv_interview_nonce' ); ?>';

	// Toggle HR mode fields
	$('input[name="mode"]').on('change', function() {
		if ($(this).val() === 'hr_advisor') {
			$('#hr-advisor-fields').slideDown();
		} else {
			$('#hr-advisor-fields').slideUp();
		}
	});

	// Generate new pairing code
	$('#btn-generate-pair-code').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true);
		$.post(ajaxUrl, {
			action: 'ai_cv_tailor_generate_pair_code',
			nonce: nonce
		}, function(res) {
			$btn.prop('disabled', false);
			if (res.success) {
				$('#active-pair-code-display').text(res.data.pairing_code);
				$('#pair-code-timer').text('Voimassa vielä noin 15 min.');
			} else {
				alert(res.data.message || 'Virhe koodia luotaessa.');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('Yhteysvirhe.');
		});
	});

	// Revoke device
	$('.btn-revoke-device').on('click', function() {
		if (!confirm('Haluatko varmasti purkaa tämän laitteen parituksen?')) return;
		var devId = $(this).data('id');
		var $row = $(this).closest('tr');
		$.post(ajaxUrl, {
			action: 'ai_cv_tailor_revoke_device',
			nonce: nonce,
			device_id: devId
		}, function(res) {
			if (res.success) {
				$row.fadeOut();
			} else {
				alert(res.data.message || 'Virhe paritusta purettaessa.');
			}
		});
	});

	// Save preparation package
	$('#btn-save-prep').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true);
		var formData = $('#form-interview-prep').serialize();
		formData += '&action=ai_cv_tailor_save_interview_prep&nonce=' + nonce;

		$.post(ajaxUrl, formData, function(res) {
			$btn.prop('disabled', false);
			if (res.success) {
				$('#save-prep-status').text('✓ Tallennettu!').fadeIn().delay(3000).fadeOut();
			} else {
				alert(res.data.message || 'Tallennusvirhe.');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			alert('Yhteysvirhe.');
		});
	});

	// Test AI answer suggestion
	$('#btn-test-suggest').on('click', function() {
		var q = $('#test-question-input').val().trim();
		if (!q) {
			alert('Syötä testikysymys.');
			return;
		}

		$('#test-loading-indicator').show();
		$('#test-output-box').hide();
		var mode = $('input[name="mode"]:checked').val() || 'job_seeker';

		$.post(ajaxUrl, {
			action: 'ai_cv_tailor_test_suggest_answer',
			nonce: nonce,
			question: q,
			mode: mode
		}, function(res) {
			$('#test-loading-indicator').hide();
			if (res.success && res.data.suggestion) {
				var s = res.data.suggestion;
				$('#ai-res-intent').text(s.intent || 'Ei tunnistettu');
				$('#ai-res-main-point').text(s.main_point || 'Ei pääasiaa');
				
				var $ul = $('#ai-res-points').empty();
				if (s.supporting_points && s.supporting_points.length) {
					$.each(s.supporting_points, function(i, pt) {
						$ul.append($('<li>').text(pt));
					});
				}

				$('#ai-res-example').text(s.real_example || 'Ei erillistä esimerkkiä');

				if (s.clarifying_question) {
					$('#ai-res-clarify').text(s.clarifying_question);
					$('#ai-res-clarify-box').show();
				} else {
					$('#ai-res-clarify-box').hide();
				}

				$('#ai-res-speech-draft').val(s.speech_draft || '');
				$('#speech-approval-status').text('Odottaa hyväksyntää').css('color', '#646970');
				$('#test-output-box').slideDown();
			} else {
				alert(res.data.message || 'Virhe ehdotusta luotaessa.');
			}
		}).fail(function() {
			$('#test-loading-indicator').hide();
			alert('Yhteysvirhe.');
		});
	});

	// Invalidate approval if user edits speech draft
	$('#ai-res-speech-draft').on('input', function() {
		$('#speech-approval-status').text('Tekstiä muokattu — vaatii uuden hyväksynnän').css('color', '#d63638');
	});

	// Approve & speak test using Web Speech API in browser
	$('#btn-test-approve-speak').on('click', function() {
		var text = $('#ai-res-speech-draft').val().trim();
		if (!text) {
			alert('Puheenvuoroluonnos on tyhjä.');
			return;
		}
		if ('speechSynthesis' in window) {
			window.speechSynthesis.cancel();
			var u = new SpeechSynthesisUtterance(text);
			var lang = $('#select-language').val() || 'fi';
			u.lang = (lang === 'en') ? 'en-US' : (lang === 'sv' ? 'sv-SE' : 'fi-FI');
			
			$('#speech-approval-status').text('Hyväksytty ja puhutaan...').css('color', '#46b450');
			u.onend = function() {
				$('#speech-approval-status').text('Puhe päättynyt — puhelupa rauennut').css('color', '#646970');
			};
			u.onerror = function() {
				$('#speech-approval-status').text('Puhe keskeytetty').css('color', '#d63638');
			};
			window.speechSynthesis.speak(u);
		} else {
			alert('Selaimesi ei tue Web Speech -äänisynteesiä.');
		}
	});

	// Stop speech immediately
	$('#btn-test-stop-speak').on('click', function() {
		if ('speechSynthesis' in window) {
			window.speechSynthesis.cancel();
		}
		$('#speech-approval-status').text('Puhe keskeytetty välittömästi! Puhelupa mitätöity.').css('color', '#d63638');
	});
});
</script>
