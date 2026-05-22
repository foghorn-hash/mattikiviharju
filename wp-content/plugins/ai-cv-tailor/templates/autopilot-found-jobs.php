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
?>

<div class="wrap">
	<h1>Löydetyt toimeksiannot</h1>
	<p>Viimeisimmät Autopilotin löytämät toimeksiannot. (Näytetään max 50 kpl)</p>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 50px;">ID</th>
				<th>Päivämäärä</th>
				<th>Yritys</th>
				<th>Rooli / Title</th>
				<th>Lähde</th>
				<th style="width: 80px;">Score</th>
				<th>Status</th>
				<th>Processed</th>
				<th>Toiminnot</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); 
					$post_id = get_the_ID();
					$company_name = get_post_meta( $post_id, 'company_name', true );
					$role_title   = get_post_meta( $post_id, 'role_title', true );
					$source       = get_post_meta( $post_id, 'source', true );
					$source_url   = get_post_meta( $post_id, 'source_url', true );
					$match_score  = get_post_meta( $post_id, 'match_score', true );
					$status       = get_post_meta( $post_id, 'status', true );
					$processed    = get_post_meta( $post_id, 'autopilot_processed', true );
					$app_id       = get_post_meta( $post_id, 'generated_application_id', true );
					
					$analyze_url  = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_analyze_job&post_id=' . $post_id ), 'analyze_job' );
					$generate_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_generate_application_from_job&post_id=' . $post_id ), 'generate_job' );
					$reject_url   = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_reject_job&post_id=' . $post_id ), 'reject_job' );
					$reset_url    = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_tailor_reset_job&post_id=' . $post_id . '&reset_score=1' ), 'reset_job' );
					$edit_url     = get_edit_post_link( $post_id );
				?>
					<tr>
						<td><?php echo esc_html( $post_id ); ?></td>
						<td><?php echo get_the_date(); ?></td>
						<td><?php echo esc_html( $company_name ); ?></td>
						<td>
							<strong><?php echo esc_html( $role_title ? $role_title : get_the_title() ); ?></strong>
							<?php if ( $source_url ) : ?>
								<br><a href="<?php echo esc_url( $source_url ); ?>" target="_blank">Lähdelinkki</a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $source ); ?></td>
						<td><strong><?php echo esc_html( $match_score ); ?></strong></td>
						<td>
							<?php echo esc_html( $status ); ?>
							<?php if ( $app_id ) : ?>
								<br><a href="<?php echo esc_url( get_edit_post_link( $app_id ) ); ?>">Katso Hakemus</a>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $processed ); ?></td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>">Edit</a> | 
							<a href="<?php echo esc_url( $analyze_url ); ?>">Analyze</a> | 
							<a href="<?php echo esc_url( $generate_url ); ?>">Generate</a><br>
							<a href="<?php echo esc_url( $reject_url ); ?>" style="color: #a00;">Reject</a> | 
							<a href="<?php echo esc_url( $reset_url ); ?>" onclick="return confirm('Nollataan prosessointi ja score. Oletko varma?');">Reset</a>
						</td>
					</tr>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php else : ?>
				<tr><td colspan="9">Ei toimeksiantoja löytynyt.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
