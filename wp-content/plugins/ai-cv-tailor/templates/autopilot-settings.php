<div class="wrap">
	<h1>Autopilot Asetukset</h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'ai_cv_autopilot_settings_group' );
		do_settings_sections( 'ai_cv_autopilot_settings_group' );
		$options = get_option( 'ai_cv_autopilot_settings', array() );

		// OpenAI test transient
		$test_result = get_transient( 'ai_cv_openai_test_result' );
		if ( $test_result ) {
			if ( strpos( $test_result, 'error' ) !== false ) {
				echo '<div class="notice notice-error is-dismissible"><p>OpenAI Test: ' . esc_html( $test_result ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>OpenAI Test: ' . esc_html( $test_result ) . '</p></div>';
			}
			delete_transient( 'ai_cv_openai_test_result' );
		}

		// Google AI test transient
		$google_test_result = get_transient( 'ai_cv_google_ai_test_result' );
		if ( $google_test_result ) {
			if ( strpos( $google_test_result, 'error' ) !== false ) {
				echo '<div class="notice notice-error is-dismissible"><p>Google AI Test: ' . esc_html( $google_test_result ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>Google AI Test: ' . esc_html( $google_test_result ) . '</p></div>';
			}
			delete_transient( 'ai_cv_google_ai_test_result' );
		}
		?>

		<div class="card" style="max-width: 1000px; padding: 20px; margin-top: 15px; border-left: 4px solid #1a73e8;">
			<h2 style="margin-top: 0; color: #1a73e8;"><span class="dashicons dashicons-google"></span> Google AI Pro (Gemini API) Työpaikkahaku & Rekry-analyysi</h2>
			<p class="description">Google AI Pro (Gemini) etsii Googlesta toimeksiantoja eri hakusanoilla, tutkii yritysten rekrysivut, erottelee listatut työpaikat ja avoimet työhakemukset, kalastaa julkiset sähköpostit ja mahdollistaa hakemuksen lähetyksen suoraan sivuston pääkäyttäjän osoitteella.</p>
			
			<table class="form-table">
				<tr>
					<th scope="row"><label for="google_ai_api_key">Google AI (Gemini) API-avain</label></th>
					<td>
						<input type="password" id="google_ai_api_key" name="ai_cv_autopilot_settings[google_ai_api_key]" value="<?php echo esc_attr( $options['google_ai_api_key'] ?? '' ); ?>" class="regular-text" autocomplete="off" />
						<p class="description">Voit hankkia avaimen ilmaiseksi osoitteesta <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a>. Voidaan määritellä myös <code>wp-config.php</code> -tiedostossa vakiolla <code>GOOGLE_AI_API_KEY</code>.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="google_ai_model">Gemini Malli</label></th>
					<td>
						<select id="google_ai_model" name="ai_cv_autopilot_settings[google_ai_model]">
							<option value="gemini-3.5-flash" <?php selected( $options['google_ai_model'] ?? 'gemini-3.5-flash', 'gemini-3.5-flash' ); ?>>Gemini 3.5 Flash (Suositeltu: Nopea, luotettava ja ilmaiskiintiöllä toimiva)</option>
							<option value="gemini-flash-latest" <?php selected( $options['google_ai_model'] ?? '', 'gemini-flash-latest' ); ?>>Gemini Flash Latest (Googlen uusin tuotanto-Flash)</option>
							<option value="gemini-3.1-flash-lite" <?php selected( $options['google_ai_model'] ?? '', 'gemini-3.1-flash-lite' ); ?>>Gemini 3.1 Flash Lite (Erittäin nopea)</option>
							<option value="gemini-3.1-pro-preview" <?php selected( $options['google_ai_model'] ?? '', 'gemini-3.1-pro-preview' ); ?>>Gemini 3.1 Pro Preview (Uusin Pro-malli, vaatii GCP-laskutustilin)</option>
							<option value="gemini-pro-latest" <?php selected( $options['google_ai_model'] ?? '', 'gemini-pro-latest' ); ?>>Gemini Pro Latest (Vaatii GCP-laskutustilin)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Google Search Grounding</th>
					<td>
						<label>
							<input type="checkbox" name="ai_cv_autopilot_settings[google_search_grounding]" value="1" <?php checked( 1, $options['google_search_grounding'] ?? 1 ); ?> />
							Käytä reaaliaikaista Google Search Grounding -työkalua toimeksiantojen ja rekrysivujen etsimiseen
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="google_cse_id">Google Custom Search Engine ID (Optio)</label></th>
					<td>
						<input type="text" id="google_cse_id" name="ai_cv_autopilot_settings[google_cse_id]" value="<?php echo esc_attr( $options['google_cse_id'] ?? '' ); ?>" class="regular-text" />
						<p class="description">Valinnainen: Jos sinulla on Google Custom Search Engine (CSE) määritettynä tiettyihin rekryportaaleihin.</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="card" style="max-width: 1000px; padding: 20px; margin-top: 20px; border-left: 4px solid #0073aa;">
			<h2 style="margin-top: 0;"><span class="dashicons dashicons-email"></span> Hakemusten lähetys (Pääkäyttäjän sähköposti)</h2>
			<p class="description">Kun toimeksiannosta on kalastettu julkinen sähköposti, hakemus lähetetään näillä tiedoilla <code>wp_mail()</code>-toiminnolla.</p>
			
			<table class="form-table">
				<tr>
					<th scope="row"><label for="admin_sender_email">Pääkäyttäjän lähetysosoite (From / Reply-To)</label></th>
					<td>
						<input type="email" id="admin_sender_email" name="ai_cv_autopilot_settings[admin_sender_email]" value="<?php echo esc_attr( $options['admin_sender_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text" />
						<p class="description">Oletus: Sivuston pääkäyttäjän sähköposti (<strong><?php echo esc_html( get_option( 'admin_email' ) ); ?></strong>).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="admin_sender_name">Lähettäjän näyttönimi</label></th>
					<td>
						<input type="text" id="admin_sender_name" name="ai_cv_autopilot_settings[admin_sender_name]" value="<?php echo esc_attr( $options['admin_sender_name'] ?? 'Matti Kiviharju | i4ware Software' ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>
		</div>

		<div class="card" style="max-width: 1000px; padding: 20px; margin-top: 20px;">
			<h2 style="margin-top: 0;">Pisteytys ja suodatusehdot</h2>
			<table class="form-table">
				<tr>
					<th scope="row">Minimipisteet analyysissä (esim. 50)</th>
					<td><input type="number" name="ai_cv_autopilot_settings[min_match_score]" value="<?php echo esc_attr( $options['min_match_score'] ?? '50' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">Minimipisteet hakemusehdotukselle (esim. 75)</th>
					<td><input type="number" name="ai_cv_autopilot_settings[min_score_app_suggestion]" value="<?php echo esc_attr( $options['min_score_app_suggestion'] ?? '75' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">Sallitut teknologiat (pilkulla erotettuna)</th>
					<td><textarea name="ai_cv_autopilot_settings[allowed_tech]" rows="3" class="large-text"><?php echo esc_textarea( $options['allowed_tech'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row">Estetyt teknologiat (pilkulla erotettuna)</th>
					<td><textarea name="ai_cv_autopilot_settings[blocked_tech]" rows="3" class="large-text"><?php echo esc_textarea( $options['blocked_tech'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row">Minimituntihinta (€/h)</th>
					<td><input type="number" name="ai_cv_autopilot_settings[min_hourly_rate]" value="<?php echo esc_attr( $options['min_hourly_rate'] ?? '80' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row">Vain Remote</th>
					<td>
						<label><input type="checkbox" name="ai_cv_autopilot_settings[remote_only]" value="1" <?php checked( 1, $options['remote_only'] ?? 0 ); ?> /> Kyllä</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Vain B2B (Laskutus Y-tunnuksella)</th>
					<td>
						<label><input type="checkbox" name="ai_cv_autopilot_settings[b2b_only]" value="1" <?php checked( 1, $options['b2b_only'] ?? 0 ); ?> /> Kyllä</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Debug Mode (Lisää lokitusta)</th>
					<td>
						<label><input type="checkbox" name="ai_cv_autopilot_settings[debug_mode]" value="1" <?php checked( 1, $options['debug_mode'] ?? 0 ); ?> /> Kyllä</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Force Generate Test Mode (Luo hakemus huonollakin scorella)</th>
					<td>
						<label><input type="checkbox" name="ai_cv_autopilot_settings[force_generate_test_mode]" value="1" <?php checked( 1, $options['force_generate_test_mode'] ?? 0 ); ?> /> Kyllä</label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button( 'Tallenna kaikki asetukset' ); ?>
	</form>

	<hr>
	<h2>API-yhteyksien testaus</h2>
	<div style="display: flex; gap: 15px; margin-top: 10px;">
		<div>
			<?php $google_test_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_test_google_ai' ), 'test_google_ai' ); ?>
			<a href="<?php echo esc_url( $google_test_url ); ?>" class="button button-primary" style="background: #1a73e8; border-color: #1a73e8;">
				<span class="dashicons dashicons-google" style="vertical-align: middle;"></span> Testaa Google AI Pro -yhteys
			</a>
		</div>
		<div>
			<?php $openai_test_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_test_openai' ), 'test_openai' ); ?>
			<a href="<?php echo esc_url( $openai_test_url ); ?>" class="button button-secondary">
				Testaa OpenAI -yhteys
			</a>
		</div>
	</div>
</div>
