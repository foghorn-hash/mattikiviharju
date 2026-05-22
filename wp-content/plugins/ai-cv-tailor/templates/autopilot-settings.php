<div class="wrap">
	<h1>Autopilot Asetukset</h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'ai_cv_autopilot_settings_group' );
		do_settings_sections( 'ai_cv_autopilot_settings_group' );
		$options = get_option( 'ai_cv_autopilot_settings', array() );

		$test_result = get_transient( 'ai_cv_openai_test_result' );
		if ( $test_result ) {
			if ( strpos( $test_result, 'error' ) !== false ) {
				echo '<div class="notice notice-error is-dismissible"><p>Test Result: ' . esc_html( $test_result ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>Test Result: ' . esc_html( $test_result ) . '</p></div>';
			}
			delete_transient( 'ai_cv_openai_test_result' );
		}
		?>
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
		<?php submit_button(); ?>
	</form>

	<hr>
	<h2>OpenAI Testaus</h2>
	<p>Tarkista, että API avain on määritetty ja toimii oikein.</p>
	<?php $test_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_test_openai' ), 'test_openai' ); ?>
	<a href="<?php echo esc_url( $test_url ); ?>" class="button button-secondary">Test OpenAI Connection</a>
</div>
