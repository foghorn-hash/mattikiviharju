<?php
/**
 * Settings Page Template
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings     = Gemini_Avatar_Settings::get_all();
$has_key      = Gemini_Avatar_Settings::has_api_key();
$key_source   = Gemini_Avatar_Settings::get_api_key_source();
$text_models  = Gemini_Avatar_Settings::get_text_models();
$tts_models   = Gemini_Avatar_Settings::get_tts_models();
$tts_voices   = Gemini_Avatar_Settings::get_tts_voices();
$index_stats  = Gemini_Avatar_Knowledge_Base::get_stats();

$all_post_types = array(
	'cv_experience' => 'Työkokemus (cv_experience)',
	'cv_project'    => 'Projektit (cv_project)',
	'cv_education'  => 'Koulutus (cv_education)',
	'cv_course'     => 'Kurssit ja koulutukset (cv_course)',
	'cv_skill'      => 'Osaaminen (cv_skill)',
	'cv_badge'      => 'Taidot ja sertifikaatit (cv_badge)',
	'cv_reference'  => 'Suosittelijat (cv_reference)',
	'page'          => 'Julkaistut sivut (page)',
	'post'          => 'Blogiartikkelit (post)',
);

$included_types = ! empty( $settings['included_post_types'] ) ? (array) $settings['included_post_types'] : array();
?>

<div class="wrap gemini-avatar-admin-wrap">
	<div class="gemini-admin-header">
		<div class="gemini-admin-header-title">
			<span class="dashicons dashicons-format-chat"></span>
			<div>
				<h1>Gemini AI -puhuva avatar ja avustaja</h1>
				<p class="description">Hallitse verkkosivuston tekoälyavustajaa, puhesynteesiä, tietopohjaa ja ulkoasua.</p>
			</div>
		</div>
		<div class="gemini-admin-status-pill <?php echo $has_key ? 'status-active' : 'status-missing'; ?>">
			<span class="status-dot"></span>
			<span>API-avain: <?php echo esc_html( $key_source ); ?></span>
		</div>
	</div>

	<?php settings_errors(); ?>

	<div class="gemini-admin-container">
		<form method="post" action="options.php" id="gemini-avatar-settings-form">
			<?php
			settings_fields( 'gemini_avatar_settings_group' );
			?>

			<!-- TABS NAVIGATION -->
			<nav class="nav-tab-wrapper gemini-tab-nav" aria-label="Asetussivut">
				<a href="#tab-api" class="nav-tab nav-tab-active" data-tab="tab-api">API & Mallit</a>
				<a href="#tab-persona" class="nav-tab" data-tab="tab-persona">Avustajan identiteetti & Ulkoasu</a>
				<a href="#tab-knowledge" class="nav-tab" data-tab="tab-knowledge">Tietopohja & Sisältö</a>
				<a href="#tab-limits" class="nav-tab" data-tab="tab-limits">Käyttörajat & Tietosuoja</a>
				<a href="#tab-features" class="nav-tab" data-tab="tab-features">Toiminnot</a>
			</nav>

			<!-- TAB 1: API & MALLIT -->
			<div id="tab-api" class="gemini-tab-content active">
				<div class="gemini-card">
					<h2>Google Gemini API -yhteys</h2>
					<p class="description">API-avain luetaan ensisijaisesti <code>wp-config.php</code> -vakiosta (<code>GOOGLE_AI_API_KEY</code> tai <code>GEMINI_API_KEY</code>) tai ympäristömuuttujasta turvallisuussyistä.</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="gemini_api_key">API-avain (Varavaihtoehto)</label></th>
							<td>
								<input type="password" id="gemini_api_key" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[api_key]" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text" autocomplete="off" placeholder="<?php echo $has_key ? '••••••••••••••••••••••••••••••••' : 'Syötä Google AI API-avain'; ?>" />
								<p class="description">
									<?php if ( $has_key ) : ?>
										<span class="text-success"><span class="dashicons dashicons-yes"></span> Aktiivinen avain on käytössä kohteesta: <strong><?php echo esc_html( $key_source ); ?></strong>.</span>
									<?php else : ?>
										<span class="text-warning"><span class="dashicons dashicons-warning"></span> Avainta ei ole vielä asetettu. Lisää vakio <code>define('GOOGLE_AI_API_KEY', 'avain');</code> <code>wp-config.php</code> -tiedostoon tai syötä se tähän.</span>
									<?php endif; ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_text_model">Vastausmalli (Teksti)</label></th>
							<td>
								<select id="gemini_text_model" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[text_model]">
									<?php foreach ( $text_models as $model_id => $model_label ) : ?>
										<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $settings['text_model'], $model_id ); ?>>
											<?php echo esc_html( $model_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Malli, joka vastaa kävijän kysymyksiin sivuston tietopohjan perusteella.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_tts_model">Puhesynteesimalli (TTS)</label></th>
							<td>
								<select id="gemini_tts_model" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[tts_model]">
									<?php foreach ( $tts_models as $tts_id => $tts_label ) : ?>
										<option value="<?php echo esc_attr( $tts_id ); ?>" <?php selected( $settings['tts_model'], $tts_id ); ?>>
											<?php echo esc_html( $tts_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Google Geminin oma TTS-malli, joka tuottaa audion.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_tts_voice">TTS-ääni</label></th>
							<td>
								<select id="gemini_tts_voice" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[tts_voice]">
									<?php foreach ( $tts_voices as $voice_id => $voice_label ) : ?>
										<option value="<?php echo esc_attr( $voice_id ); ?>" <?php selected( $settings['tts_voice'], $voice_id ); ?>>
											<?php echo esc_html( $voice_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Valitse puheen sävy ja ääni.</p>
							</td>
						</tr>
					</table>

					<div class="gemini-card-footer">
						<button type="button" class="button button-secondary" id="btn-test-api">
							<span class="dashicons dashicons-update"></span> Testaa Gemini API- ja TTS-yhteyttä
						</button>
						<span class="spinner" id="test-api-spinner"></span>
						<div id="test-api-result" class="gemini-test-result"></div>
					</div>
				</div>
			</div>

			<!-- TAB 2: PERSONA & ULKOASU -->
			<div id="tab-persona" class="gemini-tab-content">
				<div class="gemini-card">
					<h2>Avustajan persoona ja kieli</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="gemini_assistant_name">Avustajan nimi</label></th>
							<td>
								<input type="text" id="gemini_assistant_name" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[assistant_name]" value="<?php echo esc_attr( $settings['assistant_name'] ); ?>" class="regular-text" />
								<p class="description">Näytetään keskusteluikkunan otsikossa (oletus: "Matti Kiviharjun AI-avustaja").</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_default_language">Oletuskieli</label></th>
							<td>
								<select id="gemini_default_language" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[default_language]">
									<option value="fi" <?php selected( $settings['default_language'], 'fi' ); ?>>Suomi (FI)</option>
									<option value="en" <?php selected( $settings['default_language'], 'en' ); ?>>Englanti (EN)</option>
									<option value="sv" <?php selected( $settings['default_language'], 'sv' ); ?>>Ruotsi (SV)</option>
								</select>
								<p class="description">Avustaja mukautuu automaattisesti kävijän kysymyksen kieleen tai Polylang-kielivalintaan.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_greeting_fi">Aloitustervehdys (suomi)</label></th>
							<td>
								<textarea id="gemini_greeting_fi" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[greeting_fi]" rows="3" class="large-text"><?php echo esc_textarea( $settings['greeting_fi'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_greeting_en">Aloitustervehdys (englanti)</label></th>
							<td>
								<textarea id="gemini_greeting_en" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[greeting_en]" rows="3" class="large-text"><?php echo esc_textarea( $settings['greeting_en'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_greeting_sv">Aloitustervehdys (ruotsi)</label></th>
							<td>
								<textarea id="gemini_greeting_sv" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[greeting_sv]" rows="3" class="large-text"><?php echo esc_textarea( $settings['greeting_sv'] ?? '' ); ?></textarea>
							</td>
						</tr>
					</table>

					<h2>Avatarin ulkoasu ja profiilikuva</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="gemini_avatar_type">Avatarin visuaalinen tyyli</label></th>
							<td>
								<select id="gemini_avatar_type" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[avatar_type]">
									<option value="photo" <?php selected( $settings['avatar_type'], 'photo' ); ?>>Valokuva-avatar ääneen reagoivalla holografisella HUD-animaatiolla</option>
									<option value="svg" <?php selected( $settings['avatar_type'], 'svg' ); ?>>2D Tyylitelty vektorihahmo (räpyttelevät silmät ja liikkuva suu)</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">Avatarin kuva</th>
							<td>
								<div class="gemini-avatar-preview-box">
									<div class="gemini-avatar-img-preview" id="avatar-img-preview">
										<?php if ( ! empty( $settings['avatar_image_url'] ) ) : ?>
											<img src="<?php echo esc_url( $settings['avatar_image_url'] ); ?>" alt="Avatar Preview" />
										<?php else : ?>
											<span class="dashicons dashicons-admin-users"></span>
										<?php endif; ?>
									</div>
									<div class="gemini-avatar-actions">
										<input type="hidden" id="gemini_avatar_image_url" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[avatar_image_url]" value="<?php echo esc_url( $settings['avatar_image_url'] ); ?>" />
										<input type="hidden" id="gemini_avatar_image_id" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[avatar_image_id]" value="<?php echo esc_attr( $settings['avatar_image_id'] ); ?>" />
										<button type="button" class="button button-secondary" id="btn-upload-avatar">
											<span class="dashicons dashicons-format-image"></span> Valitse kuva mediakirjastosta
										</button>
										<button type="button" class="button button-link-delete" id="btn-remove-avatar" <?php echo empty( $settings['avatar_image_url'] ) ? 'style="display:none;"' : ''; ?>>
											Poista kuva
										</button>
										<p class="description">Suositus: Neliönmuotoinen kuva (esim. 600x600 px). Oletuksena käytetään sivuston CV-profiilikuvaa.</p>
									</div>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- TAB 3: TIETOPOHJA -->
			<div id="tab-knowledge" class="gemini-tab-content">
				<div class="gemini-card">
					<h2>Tietopohjan sisältövalinnat</h2>
					<p class="description">Avustaja vastaa ainoastaan näiden valittujen julkisten sisältöjen perusteella. Luonnoksia, yksityisiä sivuja tai muiden lisäosien luottamuksellisia tietoja ei koskaan sisällytetä.</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Sisällytettävät sisältötyypit</th>
							<td>
								<fieldset>
									<?php foreach ( $all_post_types as $pt_slug => $pt_label ) : ?>
										<label style="display:block; margin-bottom:8px;">
											<input type="checkbox" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[included_post_types][]" value="<?php echo esc_attr( $pt_slug ); ?>" <?php checked( in_array( $pt_slug, $included_types, true ) ); ?> />
											<strong><?php echo esc_html( $pt_label ); ?></strong>
										</label>
									<?php endforeach; ?>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_approved_extra_knowledge">Ylläpitäjän hyväksymät lisätiedot</label></th>
							<td>
								<textarea id="gemini_approved_extra_knowledge" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[approved_extra_knowledge]" rows="6" class="large-text"><?php echo esc_textarea( $settings['approved_extra_knowledge'] ); ?></textarea>
								<p class="description">Voit lisätä tähän yritystietoja, palvelukuvauksia, yhteydenotto-ohjeita tai erityishuomioita joita avustaja saa kertoa kävijöille.</p>
							</td>
						</tr>
					</table>

					<div class="gemini-index-stats-box">
						<h3>Tietopohjan tila ja välimuisti</h3>
						<div class="gemini-stats-grid">
							<div class="stat-item">
								<span class="stat-number" id="stat-total-chunks"><?php echo esc_html( $index_stats['total_chunks'] ); ?></span>
								<span class="stat-label">Indeksoitua kohdetta</span>
							</div>
							<div class="stat-item">
								<span class="stat-text" id="stat-last-updated"><?php echo $index_stats['last_updated'] ? esc_html( $index_stats['last_updated'] ) : 'Ei indeksoitu'; ?></span>
								<span class="stat-label">Viimeksi päivitetty</span>
							</div>
						</div>

						<div class="gemini-card-footer" style="padding-left:0; padding-right:0;">
							<button type="button" class="button button-primary" id="btn-rebuild-index">
								<span class="dashicons dashicons-update"></span> Päivitä tietopohja ja tyhjennä välimuisti
							</button>
							<span class="spinner" id="rebuild-index-spinner"></span>
							<div id="rebuild-index-result" class="gemini-test-result"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- TAB 4: KÄYTTÖRAJAT & TIETOSUOJA -->
			<div id="tab-limits" class="gemini-tab-content">
				<div class="gemini-card">
					<h2>Palvelinpuoliset käyttörajat ja turvallisuus</h2>
					<p class="description">Suojaa rajapintaa väärinkäytöksiltä ja hallitsee API-kustannuksia.</p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="gemini_rate_limit_minute">Käyttöraja per minuutti (IP)</label></th>
							<td>
								<input type="number" id="gemini_rate_limit_minute" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[rate_limit_per_minute]" value="<?php echo esc_attr( $settings['rate_limit_per_minute'] ); ?>" min="1" max="30" class="small-text" /> viestiä / min
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_rate_limit_hour">Käyttöraja per tunti (IP)</label></th>
							<td>
								<input type="number" id="gemini_rate_limit_hour" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[rate_limit_per_hour]" value="<?php echo esc_attr( $settings['rate_limit_per_hour'] ); ?>" min="5" max="200" class="small-text" /> viestiä / tunti
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_max_input_chars">Kysymyksen enimmäispituus</label></th>
							<td>
								<input type="number" id="gemini_max_input_chars" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[max_input_chars]" value="<?php echo esc_attr( $settings['max_input_chars'] ); ?>" min="50" max="1000" class="small-text" /> merkkiä
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_max_answer_tokens">Vastauksen enimmäispituus (Tokenit)</label></th>
							<td>
								<input type="number" id="gemini_max_answer_tokens" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[max_answer_tokens]" value="<?php echo esc_attr( $settings['max_answer_tokens'] ); ?>" min="100" max="800" class="small-text" /> tokenia (n. 200–350 sanaa)
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_temperature">Luovuuskerroin (Temperature)</label></th>
							<td>
								<input type="number" step="0.1" id="gemini_temperature" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[temperature]" value="<?php echo esc_attr( $settings['temperature'] ); ?>" min="0" max="1" class="small-text" />
								<p class="description">Alhaisempi arvo (0.2–0.4) tuottaa tarkkoja ja tiukasti tietopohjaan sitoutuneita vastauksia ilman hallusinointia.</p>
							</td>
						</tr>
					</table>

					<h2>Tietosuoja ja ilmoitukset kävijälle</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="gemini_privacy_notice">Tietosuojailmoituksen teksti</label></th>
							<td>
								<textarea id="gemini_privacy_notice" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[privacy_notice]" rows="3" class="large-text"><?php echo esc_textarea( $settings['privacy_notice'] ); ?></textarea>
								<p class="description">Näytetään keskusteluikkunassa ennen ensimmäisen viestin lähetystä.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gemini_privacy_policy_url">Tietosuojaselosteen linkki (URL)</label></th>
							<td>
								<input type="url" id="gemini_privacy_policy_url" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[privacy_policy_url]" value="<?php echo esc_url( $settings['privacy_policy_url'] ); ?>" class="regular-text" />
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- TAB 5: TOIMINNOT -->
			<div id="tab-features" class="gemini-tab-content">
				<div class="gemini-card">
					<h2>Toimintojen käyttöönotto</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">AI Avatar -widget sivustolla</th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[enable_widget]" value="1" <?php checked( $settings['enable_widget'], 1 ); ?> />
									<strong>Kytke AI-avatar ja keskusteluikkuna päälle sivuston julkisella puolella</strong>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">Gemini-puhesynteesi (TTS)</th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[enable_speech]" value="1" <?php checked( $settings['enable_speech'], 1 ); ?> />
									<strong>Ota puhesynteesi käyttöön</strong> (kävijä voi kuunnella vastaukset ääneen)
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">Mikrofonisyöte (Ääniohjaus)</th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( Gemini_Avatar_Settings::OPTION_KEY ); ?>[enable_voice_input]" value="1" <?php checked( $settings['enable_voice_input'], 1 ); ?> />
									<strong>Salli puhesyöte mikrofonilla</strong> (käyttää selaimen SpeechRecognition-rajapintaa, pyytää luvan vasta klikattaessa)
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<p class="submit">
				<?php submit_button( 'Tallenna asetukset', 'primary', 'submit', false ); ?>
			</p>
		</form>
	</div>
</div>
