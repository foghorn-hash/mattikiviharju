<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-sources.php';
$sources_handler = new AI_CV_Tailor_Autopilot_Sources();

// Handle manual source
if ( isset( $_POST['submit_manual'] ) && check_admin_referer( 'add_manual_source' ) ) {
	$data = array(
		'company_name' => sanitize_text_field( $_POST['company_name'] ),
		'role_title'   => sanitize_text_field( $_POST['role_title'] ),
		'source_url'   => esc_url_raw( $_POST['source_url'] ),
		'description'  => sanitize_textarea_field( $_POST['description'] )
	);
	$post_id = $sources_handler->save_manual_source( $data );
	if ( $post_id ) {
		echo '<div class="notice notice-success is-dismissible"><p>Manuaalinen lähde lisätty.</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>Virhe lisättäessä lähdettä.</p></div>';
	}
}

// Handle automated source addition
if ( isset( $_POST['add_auto_source'] ) && check_admin_referer( 'add_auto_source' ) ) {
	$sources = get_option( 'ai_cv_autopilot_sources_list', array() );
	
	$new_source = array(
		'name' => sanitize_text_field( $_POST['source_name'] ),
		'type' => sanitize_text_field( $_POST['source_type'] ),
		'url'  => esc_url_raw( $_POST['source_url'] )
	);
	
	if ( ! empty( $new_source['name'] ) && ! empty( $new_source['url'] ) ) {
		$sources[] = $new_source;
		update_option( 'ai_cv_autopilot_sources_list', $sources );
		echo '<div class="notice notice-success is-dismissible"><p>Automaattinen lähde lisätty.</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>Nimi ja URL ovat pakollisia.</p></div>';
	}
}

// Handle automated source deletion
if ( isset( $_POST['delete_auto_source'] ) && check_admin_referer( 'delete_auto_source_' . $_POST['delete_index'] ) ) {
	$sources = get_option( 'ai_cv_autopilot_sources_list', array() );
	$index = intval( $_POST['delete_index'] );
	if ( isset( $sources[ $index ] ) ) {
		unset( $sources[ $index ] );
		$sources = array_values( $sources ); // Re-index array
		update_option( 'ai_cv_autopilot_sources_list', $sources );
		echo '<div class="notice notice-success is-dismissible"><p>Lähde poistettu.</p></div>';
	}
}

$automated_sources = get_option( 'ai_cv_autopilot_sources_list', array() );
?>
<div class="wrap">
	<h1>Toimeksiantolähteet</h1>
	<p>Tässä hallitset RSS-syötteitä ja muita lähteitä, joista Autopilot hakee uusia toimeksiantoja automaattisesti.</p>
	
	<h2>Automaattiset lähteet (RSS / API)</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Nimi</th>
				<th>Tyyppi</th>
				<th>URL</th>
				<th>Toiminnot</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $automated_sources ) ) : ?>
				<tr><td colspan="4">Ei lähteitä.</td></tr>
			<?php else : ?>
				<?php foreach ( $automated_sources as $index => $source ) : ?>
					<tr>
						<td><?php echo esc_html( $source['name'] ); ?></td>
						<td><?php echo esc_html( strtoupper( $source['type'] ) ); ?></td>
						<td><a href="<?php echo esc_url( $source['url'] ); ?>" target="_blank"><?php echo esc_url( $source['url'] ); ?></a></td>
						<td>
							<form method="post" action="" style="display:inline;">
								<?php wp_nonce_field( 'delete_auto_source_' . $index ); ?>
								<input type="hidden" name="delete_index" value="<?php echo esc_attr( $index ); ?>">
								<input type="submit" name="delete_auto_source" class="button button-link-delete" value="Poista" onclick="return confirm('Oletko varma?');">
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h3>Lisää uusi automaattinen lähde</h3>
	<form method="post" action="">
		<?php wp_nonce_field( 'add_auto_source' ); ?>
		<table class="form-table">
			<tr>
				<th>Lähdetyyppi</th>
				<td>
					<select name="source_type">
						<option value="rss">RSS</option>
						<!-- LinkedIn, Upwork jne. tulevaisuudessa -->
					</select>
				</td>
			</tr>
			<tr>
				<th>Lähteen Nimi (esim. Upwork RSS)</th>
				<td><input type="text" name="source_name" class="regular-text" required></td>
			</tr>
			<tr>
				<th>Lähde URL</th>
				<td><input type="url" name="source_url" class="regular-text" required></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="add_auto_source" class="button button-primary" value="Lisää Automaattinen Lähde"></p>
	</form>
	
	<hr>
	
	<h2>Lisää manuaalinen ilmoitus</h2>
	<form method="post" action="">
		<?php wp_nonce_field( 'add_manual_source' ); ?>
		<table class="form-table">
			<tr>
				<th>Yritys</th>
				<td><input type="text" name="company_name" class="regular-text" required></td>
			</tr>
			<tr>
				<th>Rooli</th>
				<td><input type="text" name="role_title" class="regular-text" required></td>
			</tr>
			<tr>
				<th>URL</th>
				<td><input type="url" name="source_url" class="regular-text"></td>
			</tr>
			<tr>
				<th>Kuvaus</th>
				<td><textarea name="description" rows="5" class="large-text" required></textarea></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="submit_manual" class="button button-secondary" value="Lisää manuaalisesti"></p>
	</form>
</div>
