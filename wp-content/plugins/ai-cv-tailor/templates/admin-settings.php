<div class="wrap ai-cv-tailor-wrap">
	<h1>AI CV Tailor - Asetukset</h1>
	
	<?php settings_errors(); ?>
	
	<form method="post" action="options.php">
		<?php
			settings_fields( 'ai_cv_tailor_settings_group' );
			$settings = get_option( 'ai_cv_settings', array() );
		?>
		
		<div class="ai-cv-card">
			<h2>OpenAI API</h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="openai_api_key">API Key</label></th>
					<td>
						<input type="password" id="openai_api_key" name="ai_cv_settings[openai_api_key]" value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>" class="regular-text" />
						<p class="description">Tallennettu turvallisesti. Vaaditaan CV:n ja kirjeiden generoimiseen.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="model">Malli</label></th>
					<td>
						<select id="model" name="ai_cv_settings[model]">
							<option value="gpt-4o" <?php selected( $settings['model'] ?? '', 'gpt-4o' ); ?>>GPT-4o (Suositeltu)</option>
							<option value="gpt-4-turbo" <?php selected( $settings['model'] ?? '', 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
							<option value="gpt-3.5-turbo" <?php selected( $settings['model'] ?? '', 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo (Halvempi)</option>
						</select>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="ai-cv-card">
			<h2>Yleiset asetukset</h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="default_language">Oletuskieli</label></th>
					<td>
						<select id="default_language" name="ai_cv_settings[default_language]">
							<option value="fi" <?php selected( $settings['default_language'] ?? '', 'fi' ); ?>>Suomi</option>
							<option value="en" <?php selected( $settings['default_language'] ?? '', 'en' ); ?>>Englanti</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="link_expiration">Linkkien vanhenemisaika (päivää)</label></th>
					<td>
						<input type="number" id="link_expiration" name="ai_cv_settings[link_expiration]" value="<?php echo esc_attr( $settings['link_expiration'] ?? 30 ); ?>" class="small-text" min="1" max="365" />
					</td>
				</tr>
			</table>
		</div>

		<div class="ai-cv-card">
			<h2>Yhteystiedot</h2>
			<p class="description">Näitä tietoja käytetään luoduissa hakemuksissa ja profiileissa.</p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="company_name">Yrityksen nimi</label></th>
					<td><input type="text" id="company_name" name="ai_cv_settings[company_name]" value="<?php echo esc_attr( $settings['company_name'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="my_name">Oma nimi</label></th>
					<td><input type="text" id="my_name" name="ai_cv_settings[my_name]" value="<?php echo esc_attr( $settings['my_name'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="my_email">Sähköposti</label></th>
					<td><input type="email" id="my_email" name="ai_cv_settings[my_email]" value="<?php echo esc_attr( $settings['my_email'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="my_phone">Puhelinnumero</label></th>
					<td><input type="text" id="my_phone" name="ai_cv_settings[my_phone]" value="<?php echo esc_attr( $settings['my_phone'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="business_id">Y-tunnus</label></th>
					<td><input type="text" id="business_id" name="ai_cv_settings[business_id]" value="<?php echo esc_attr( $settings['business_id'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="website_url">Verkkosivun URL</label></th>
					<td><input type="url" id="website_url" name="ai_cv_settings[website_url]" value="<?php echo esc_attr( $settings['website_url'] ?? '' ); ?>" class="regular-text" /></td>
				</tr>
			</table>
		</div>
		
		<?php submit_button( 'Tallenna asetukset' ); ?>
	</form>
</div>
