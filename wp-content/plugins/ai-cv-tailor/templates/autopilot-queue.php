<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$statuses = array(
	'New'           => 'New',
	'Analyzed'      => 'Analyzed',
	'Good Match'    => 'Good Match',
	'Awaiting Send' => 'Awaiting Send',
	'Applied'       => 'Applied',
	'Interview'     => 'Interview',
	'Won'           => 'Won',
	'Lost'          => 'Lost',
	'Rejected'      => 'Rejected'
);

$args = array(
	'post_type'      => 'freelance_job',
	'post_status'    => 'any',
	'posts_per_page' => -1,
);
$all_jobs = get_posts( $args );

$grouped_jobs = array();
foreach ( $statuses as $key => $label ) {
	$grouped_jobs[ $key ] = array();
}

foreach ( $all_jobs as $job ) {
	$status = get_post_meta( $job->ID, 'status', true );
	if ( ! $status || ! isset( $grouped_jobs[ $status ] ) ) {
		$status = 'New';
	}
	$grouped_jobs[ $status ][] = $job;
}
?>

<style>
.ai-cv-kanban {
	display: flex;
	gap: 15px;
	overflow-x: auto;
	padding-bottom: 20px;
	margin-top: 20px;
}
.ai-cv-column {
	background: #f0f0f1;
	border: 1px solid #ccd0d4;
	border-radius: 3px;
	min-width: 250px;
	flex: 0 0 250px;
	display: flex;
	flex-direction: column;
}
.ai-cv-column h2 {
	margin: 0;
	padding: 10px 15px;
	font-size: 14px;
	background: #fff;
	border-bottom: 1px solid #ccd0d4;
	border-radius: 3px 3px 0 0;
}
.ai-cv-cards {
	padding: 10px;
	flex: 1;
	overflow-y: auto;
	max-height: 70vh;
}
.ai-cv-card {
	background: #fff;
	border: 1px solid #e1e1e1;
	box-shadow: 0 1px 1px rgba(0,0,0,0.04);
	padding: 10px;
	margin-bottom: 10px;
	border-radius: 3px;
	font-size: 13px;
}
.ai-cv-card h4 {
	margin: 0 0 5px 0;
	font-size: 13px;
}
.ai-cv-card p {
	margin: 0 0 5px 0;
	color: #666;
}
.ai-cv-card .score {
	display: inline-block;
	background: #e5f5fa;
	color: #0073aa;
	padding: 2px 5px;
	border-radius: 3px;
	font-weight: bold;
	font-size: 11px;
}
.ai-cv-card .actions {
	margin-top: 8px;
	font-size: 12px;
}
</style>

<div class="wrap">
	<h1>Hakujono</h1>
	<p>Kanban-näkymä prosessin tiloista. (Kaikki järjestelmän toimeksiannot ryhmiteltynä tilan mukaan).</p>

	<div class="ai-cv-kanban">
		<?php foreach ( $statuses as $status_key => $status_label ) : ?>
			<div class="ai-cv-column">
				<h2><?php echo esc_html( $status_label ); ?> (<?php echo count( $grouped_jobs[ $status_key ] ); ?>)</h2>
				<div class="ai-cv-cards">
					<?php if ( empty( $grouped_jobs[ $status_key ] ) ) : ?>
						<p style="color:#999; text-align:center; margin-top:10px;">Ei toimeksiantoja.</p>
					<?php else : ?>
						<?php foreach ( $grouped_jobs[ $status_key ] as $job ) : 
							$company     = get_post_meta( $job->ID, 'company_name', true );
							$role_title  = get_post_meta( $job->ID, 'role_title', true );
							$match_score = get_post_meta( $job->ID, 'match_score', true );
							$app_id      = get_post_meta( $job->ID, 'generated_application_id', true );
							$edit_url    = get_edit_post_link( $job->ID );
						?>
							<div class="ai-cv-card">
								<h4><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $role_title ? $role_title : $job->post_title ); ?></a></h4>
								<p><?php echo esc_html( $company ? $company : 'Tuntematon yritys' ); ?></p>
								
								<?php if ( $match_score ) : ?>
									<span class="score">Score: <?php echo esc_html( $match_score ); ?></span>
								<?php endif; ?>
								
								<div class="actions" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
									<a class="button button-small" href="<?php echo esc_url( admin_url( 'post.php?post=' . $job->ID . '&action=edit' ) ); ?>">Muokkaa</a>
									<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin-post.php?action=ai_cv_tailor_analyze_job&job_id=' . $job->ID . '&_wpnonce=' . wp_create_nonce( 'ai_cv_tailor_analyze_job_' . $job->ID ) ) ); ?>">Analyze</a>
									
									<?php if ( $app_id ) : ?>
										<a class="button button-small button-primary" href="<?php echo esc_url( admin_url( 'post.php?post=' . $app_id . '&action=edit' ) ); ?>">Hakemus</a>
									<?php else : ?>
										<a class="button button-small button-secondary" href="<?php echo esc_url( admin_url( 'admin-post.php?action=ai_cv_tailor_generate_application_from_job&job_id=' . $job->ID . '&_wpnonce=' . wp_create_nonce( 'ai_cv_tailor_generate_application_' . $job->ID ) ) ); ?>">Luo hakemus</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
