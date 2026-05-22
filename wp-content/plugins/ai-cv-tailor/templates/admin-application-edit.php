<div class="wrap ai-cv-tailor-wrap">
	<h1>Luo uusi hakemus</h1>
	
	<div id="ai-cv-app" class="ai-cv-app-container">
		
		<!-- Step 1: Input -->
		<div id="step-1" class="ai-cv-card step-active">
			<h2>Vaihe 1: Työpaikkailmoituksen tiedot</h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="app_company_name">Yritys *</label></th>
					<td><input type="text" id="app_company_name" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="app_job_title">Tehtävänimike *</label></th>
					<td><input type="text" id="app_job_title" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="app_job_url">Ilmoituksen URL</label></th>
					<td><input type="url" id="app_job_url" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="app_language">Kieli</label></th>
					<td>
						<?php $settings = get_option('ai_cv_settings', array()); $def_lang = $settings['default_language'] ?? 'fi'; ?>
						<select id="app_language">
							<option value="fi" <?php selected($def_lang, 'fi'); ?>>Suomi</option>
							<option value="en" <?php selected($def_lang, 'en'); ?>>Englanti</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="app_job_description">Työpaikkailmoituksen teksti *</label></th>
					<td><textarea id="app_job_description" rows="10" style="width: 100%;" required></textarea></td>
				</tr>
			</table>
			<p>
				<button id="btn-analyze" class="button button-primary button-hero">Analysoi OpenAI:lla</button>
			</p>
		</div>

		<!-- Step 2: Loading -->
		<div id="step-2" class="ai-cv-card step-hidden" style="text-align: center; padding: 50px;">
			<div class="spinner is-active" style="float:none; width: 40px; height: 40px; background-size: 40px;"></div>
			<h2>Analysoidaan...</h2>
			<p>Odota hetki, OpenAI lukee CV-datasi ja generoi räätälöityjä versioita kohderyhmille.</p>
		</div>

		<!-- Step 3: Review & Publish -->
		<div id="step-3" class="ai-cv-card step-hidden">
			<h2>Vaihe 3: Esikatselu ja julkaisu</h2>
			
			<div id="analysis-summary" class="notice notice-info inline" style="margin-top: 20px;">
				<!-- Filled by JS -->
			</div>

			<h3 style="margin-top: 30px;">Tarkista OpenAI:n palauttama JSON</h3>
			<p class="description">Voit tehdä hienosäätöä teksteihin suoraan JSON-datan kautta ennen julkaisua.</p>
			<textarea id="app_generated_json" rows="20" style="width: 100%; font-family: monospace;"></textarea>
			
			<p style="margin-top: 20px;">
				<button id="btn-publish" class="button button-primary button-hero">Tallenna ja julkaise salaiset linkit</button>
			</p>
		</div>

		<!-- Step 4: Success & Links -->
		<div id="step-4" class="ai-cv-card step-hidden">
			<h2>Valmis! Salaiset linkit luotu.</h2>
			<p>Kopioi linkit alta ja lähetä ne eteenpäin.</p>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Kohderyhmä</th>
						<th>Linkki</th>
						<th>Toiminto</th>
					</tr>
				</thead>
				<tbody id="generated-links-body">
					<!-- Filled by JS -->
				</tbody>
			</table>
			
			<p style="margin-top: 20px;">
				<a href="<?php echo admin_url('edit.php?post_type=ai_cv_application'); ?>" class="button">Palaa listaukseen</a>
			</p>
		</div>

	</div>
</div>
