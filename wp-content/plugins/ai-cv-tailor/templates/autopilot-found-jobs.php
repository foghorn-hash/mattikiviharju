<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = array(
	'post_type'      => 'freelance_job',
	'post_status'    => 'any',
	'posts_per_page' => 50,
	'orderby'        => 'date',
	'order'          => 'DESC'
);
$query = new WP_Query( $args );

if ( isset( $_GET['analyzed'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>Analyysi valmis toimeksiannolle ID ' . intval( $_GET['analyzed'] ) . '.</p></div>';
}
if ( isset( $_GET['generated'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>Hakemus generoitu toimeksiannolle ID ' . intval( $_GET['generated'] ) . '.</p></div>';
}
if ( isset( $_GET['rejected'] ) ) {
	echo '<div class="notice notice-warning is-dismissible"><p>Toimeksianto hylätty ID ' . intval( $_GET['rejected'] ) . '.</p></div>';
}
if ( isset( $_GET['reset'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>Toimeksianto nollattu ID ' . intval( $_GET['reset'] ) . '.</p></div>';
}
if ( isset( $_GET['email_sent'] ) ) {
	echo '<div class="notice notice-success is-dismissible"><p>Hakemus lähetetty onnistuneesti toimeksiannolle ID ' . intval( $_GET['email_sent'] ) . ' pääkäyttäjän sähköpostilla!</p></div>';
}
?>

<div class="wrap">
	<h1>Löydetyt toimeksiannot</h1>
	<p>Kaikki Autopilotin ja Google AI Pron löytämät toimeksiannot. (Näytetään viimeisimmät 50 kpl)</p>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 45px;">ID</th>
				<th style="width: 125px;">Analyysin Pvm</th>
				<th style="width: 160px;">Yritys & Tyyppi</th>
				<th>Rooli / Title & URL</th>
				<th style="width: 180px;">Kalastettu Julkinen Email</th>
				<th style="width: 130px;">Lähde</th>
				<th style="width: 70px;">Score</th>
				<th style="width: 90px;">Status</th>
				<th style="width: 160px;">Toiminnot</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); 
					$post_id       = get_the_ID();
					$company_name  = get_post_meta( $post_id, 'company_name', true );
					$role_title    = get_post_meta( $post_id, 'role_title', true );
					$source        = get_post_meta( $post_id, 'source', true );
					$source_url    = get_post_meta( $post_id, 'source_url', true );
					$job_type      = get_post_meta( $post_id, 'job_type', true );
					$analysis_date = get_post_meta( $post_id, 'analysis_date', true ) ?: get_the_date( 'Y-m-d H:i' );
					$contact_email = get_post_meta( $post_id, 'contact_email', true );
					$match_score   = get_post_meta( $post_id, 'match_score', true );
					$status        = get_post_meta( $post_id, 'status', true );
					$processed     = get_post_meta( $post_id, 'autopilot_processed', true );
					$app_id        = get_post_meta( $post_id, 'generated_application_id', true );
					
					$analyze_url  = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_analyze_job&post_id=' . $post_id ), 'analyze_job' );
					$generate_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_generate_application_from_job&post_id=' . $post_id ), 'generate_job' );
					$reject_url   = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_reject_job&post_id=' . $post_id ), 'reject_job' );
					$reset_url    = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_reset_job&post_id=' . $post_id . '&reset_score=1' ), 'reset_job' );
					$edit_url     = get_edit_post_link( $post_id );
				?>
					<tr>
						<td><strong>#<?php echo esc_html( $post_id ); ?></strong></td>
						<td><span style="font-size: 12px; color: #555;"><?php echo esc_html( $analysis_date ); ?></span></td>
						<td>
							<strong><?php echo esc_html( $company_name ?: 'Tuntematon' ); ?></strong>
							<?php if ( 'avoin_hakemus' === $job_type ) : ?>
								<br><span style="background: #e7f3fe; color: #1a73e8; padding: 1px 5px; border-radius: 3px; font-size: 11px;">Avoin hakemus</span>
							<?php elseif ( 'listattu' === $job_type ) : ?>
								<br><span style="background: #e6f4ea; color: #137333; padding: 1px 5px; border-radius: 3px; font-size: 11px;">Listattu työpaikka</span>
							<?php endif; ?>
						</td>
						<td>
							<strong><?php echo esc_html( $role_title ? $role_title : get_the_title() ); ?></strong>
							<?php if ( $source_url ) : ?>
								<br><a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer" style="color: #1a73e8;">
									<span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px; vertical-align: middle;"></span>
									Lähdelinkki
								</a>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $contact_email ) ) : ?>
								<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="font-weight: 600; color: #1a73e8;">
									<?php echo esc_html( $contact_email ); ?>
								</a>
							<?php else : ?>
								<span style="color: #999; font-style: italic;">Ei emailia</span>
							<?php endif; ?>
						</td>
						<td><span style="font-size: 12px;"><?php echo esc_html( $source ); ?></span></td>
						<td><strong><?php echo esc_html( $match_score !== '' ? $match_score . '%' : '-' ); ?></strong></td>
						<td>
							<?php if ( 'Applied' === $status ) : ?>
								<span style="background: #2e7d32; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;">Lähetetty</span>
							<?php elseif ( 'Good Match' === $status ) : ?>
								<span style="background: #0073aa; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;">Good Match</span>
							<?php else : ?>
								<?php echo esc_html( $status ); ?>
							<?php endif; ?>
							<?php if ( $app_id ) : ?>
								<br><a href="<?php echo esc_url( get_edit_post_link( $app_id ) ); ?>" style="font-size: 11px;">CV-linkit</a>
							<?php endif; ?>
						</td>
						<td>
							<div style="display: flex; flex-direction: column; gap: 3px;">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-cv-tailor-google-search' ) ); ?>" class="button button-small button-primary" style="background: #1a73e8; border-color: #1a73e8; text-align: center;">
									Lähetä hakemus
								</a>
								<div style="font-size: 11px; margin-top: 2px;">
									<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> | 
									<a href="<?php echo esc_url( $analyze_url ); ?>">Analyze</a> | 
									<a href="<?php echo esc_url( $generate_url ); ?>">Tee AI CV</a> | 
									<a href="<?php echo esc_url( $reject_url ); ?>" style="color: #a00;">Reject</a> | 
									<a href="<?php echo esc_url( $reset_url ); ?>" onclick="return confirm('Nollataan prosessointi ja score. Oletko varma?');">Reset</a>
								</div>
							</div>
						</td>
					</tr>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php else : ?>
				<tr><td colspan="9">Ei toimeksiantoja löytynyt.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
