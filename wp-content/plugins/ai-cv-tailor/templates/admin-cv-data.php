<div class="wrap ai-cv-tailor-wrap">
	<h1>AI CV Tailor - CV-data</h1>
	
	<p class="description">Tähän tallennetaan kaikki perusdatasi. OpenAI käyttää näitä tietoja ja valitsee niistä parhaiten työpaikkailmoitukseen sopivat osat.</p>
	
	<?php settings_errors(); ?>
	
	<form method="post" action="options.php">
		<?php
			settings_fields( 'ai_cv_tailor_data_group' );
			$cv_data = get_option( 'ai_cv_profile_data', array() );
			
			// Format for display
			$cv_data_json = '';
			if ( ! empty( $cv_data ) ) {
				$cv_data_json = wp_json_encode( $cv_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			} else {
				// Default skeleton
				$default_skeleton = array(
					'profile_summary' => '',
					'skills' => array(),
					'technologies' => array(),
					'experience' => array(
						array(
							'title' => '',
							'company' => '',
							'period' => '',
							'description' => ''
						)
					),
					'projects' => array(
						array(
							'name' => '',
							'description' => '',
							'technologies' => array()
						)
					),
					'education' => array(),
					'certificates' => array(),
					'testimonials' => array(),
					'languages' => array(),
					'links' => array(),
					'availability' => '',
					'billing' => ''
				);
				$cv_data_json = wp_json_encode( $default_skeleton, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			}
		?>
		
		<div class="ai-cv-card">
			<h2>CV:n JSON-data</h2>
			<p>Muokkaa alla olevaa JSON-objektia. Varmista, että syntaksi on oikein.</p>
			
			<textarea id="ai_cv_profile_data" name="ai_cv_profile_data" rows="30" style="width: 100%; font-family: monospace;"><?php echo esc_textarea( $cv_data_json ); ?></textarea>
		</div>
		
		<?php submit_button( 'Tallenna CV-data' ); ?>
	</form>
</div>
