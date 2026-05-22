<?php
global $ai_cv_tailor_data;

if ( ! isset( $ai_cv_tailor_data ) ) {
	wp_die( 'Virheellinen pyyntö.' );
}

$post     = $ai_cv_tailor_data['post'];
$audience = $ai_cv_tailor_data['audience'];
$settings = $ai_cv_tailor_data['settings'];

$json_data = get_post_meta( $post->ID, '_openai_analysis', true );
$analysis  = json_decode( $json_data, true );

if ( ! isset( $analysis['audiences'][ $audience ] ) ) {
	wp_die( 'Tätä versiota ei löydy.' );
}

$content = $analysis['audiences'][ $audience ];

// Clean up variables
$cv_title        = esc_html( $content['cv_title'] ?? get_post_meta( $post->ID, '_job_title', true ) );
$profile_summary = wp_kses_post( $content['profile_summary'] ?? '' );
$skills          = isset( $content['selected_skills'] ) && is_array( $content['selected_skills'] ) ? array_map( 'esc_html', $content['selected_skills'] ) : array();
$projects        = isset( $content['selected_projects'] ) && is_array( $content['selected_projects'] ) ? $content['selected_projects'] : array();
$experience      = isset( $content['selected_experience'] ) && is_array( $content['selected_experience'] ) ? $content['selected_experience'] : array();

$prefix = 'ai_cv_' . str_replace('-', '_', $audience);

$cover_letter = get_post_meta( $post->ID, $prefix . '_cover_letter', true );
if ( ! $cover_letter ) {
	$cover_letter = get_post_meta( $post->ID, 'ai_cv_cover_letter', true );
}
$cover_letter = nl2br( wp_kses_post( $cover_letter ) );

$motivation = get_post_meta( $post->ID, $prefix . '_motivation_letter', true );
if ( ! $motivation ) {
	$motivation = get_post_meta( $post->ID, 'ai_cv_motivation_letter', true );
}
$motivation = nl2br( wp_kses_post( $motivation ) );

$my_name = esc_html( $settings['my_name'] ?? '' );
$my_email = esc_html( $settings['my_email'] ?? '' );
$my_phone = esc_html( $settings['my_phone'] ?? '' );
$business_id = esc_html( $settings['business_id'] ?? '' );
$website_url = esc_url( $settings['website_url'] ?? '' );

$company_name = esc_html( $settings['company_name'] ?? '' );
$vat_id = esc_html( $settings['vat_id'] ?? '' );
$delivery_terms_url = esc_url( $settings['delivery_terms_url'] ?? '' );
$privacy_policy_url = esc_url( $settings['privacy_policy_url'] ?? '' );
$terms_url = esc_url( $settings['terms_url'] ?? '' );
$dpa_url = esc_url( $settings['dpa_url'] ?? '' );
$sla_url = esc_url( $settings['sla_url'] ?? '' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo $cv_title; ?> - <?php echo $my_name; ?></title>
	<?php wp_head(); ?>
</head>
<body class="ai-cv-public-body">

<div class="ai-cv-container">
	
	<header class="ai-cv-header">
		<h1><?php echo $my_name; ?></h1>
		<div class="contact-info">
			<?php if ( $my_email ) : ?><a href="mailto:<?php echo $my_email; ?>"><?php echo $my_email; ?></a><?php endif; ?>
			<?php if ( $my_phone ) : ?> | <span><?php echo $my_phone; ?></span><?php endif; ?>
			<?php if ( $website_url ) : ?> | <a href="<?php echo $website_url; ?>" target="_blank">Portfolio</a><?php endif; ?>
		</div>
		<div class="business-id">
			<?php if ( $business_id ) : ?><span>Y-tunnus: <?php echo $business_id; ?> (Laskutus mahdollista)</span><?php endif; ?>
		</div>
	</header>

	<main class="ai-cv-main">
		
		<?php if ( empty( $profile_summary ) && empty( $cover_letter ) && empty( $motivation ) ) : ?>
			<section class="ai-cv-section">
				<div class="content-box">
					<p><em>Content is being generated. Please check back soon.</em></p>
				</div>
			</section>
		<?php else : ?>
			
			<?php if ( ! empty( $profile_summary ) ) : ?>
			<section class="ai-cv-section cv-profile">
				<h2><?php echo $cv_title; ?></h2>
				<div class="content-box">
					<p><?php echo $profile_summary; ?></p>
				</div>
			</section>
			<?php endif; ?>
			
			<?php if ( ! empty( $cover_letter ) ) : ?>
			<section class="ai-cv-section cv-cover-letter">
				<h2>Hakemuskirje</h2>
				<div class="content-box">
					<?php echo $cover_letter; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $motivation ) ) : ?>
			<section class="ai-cv-section cv-motivation">
				<h2>Motivaatio & Perustelut</h2>
				<div class="content-box">
					<?php echo $motivation; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $skills ) ) : ?>
			<section class="ai-cv-section cv-skills">
				<h2>Avainosaaminen</h2>
				<ul class="skills-list">
					<?php foreach ( $skills as $skill ) : ?>
						<li><?php echo $skill; ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $experience ) ) : ?>
			<section class="ai-cv-section cv-experience">
				<h2>Valittu työkokemus</h2>
				<?php foreach ( $experience as $exp ) : ?>
					<div class="experience-item">
						<h3><?php echo esc_html( $exp['title'] ?? '' ); ?> @ <?php echo esc_html( $exp['company'] ?? '' ); ?></h3>
						<span class="period"><?php echo esc_html( $exp['period'] ?? '' ); ?></span>
						<p><?php echo wp_kses_post( $exp['description'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $projects ) ) : ?>
			<section class="ai-cv-section cv-projects">
				<h2>Valitut projektit</h2>
				<?php foreach ( $projects as $proj ) : ?>
					<div class="project-item">
						<h3><?php echo esc_html( $proj['name'] ?? '' ); ?></h3>
						<p><?php echo wp_kses_post( $proj['description'] ?? '' ); ?></p>
					</div>
				<?php endforeach; ?>
			</section>
			<?php endif; ?>
			
		<?php endif; ?>

		<section class="ai-cv-section cv-legal">
			<h2>Commercial & Legal</h2>
			<div class="content-box">
				<?php if ( $business_id ) : ?><p><strong>Y-tunnus:</strong> <?php echo $business_id; ?></p><?php endif; ?>
				<?php if ( $vat_id ) : ?><p><strong>VAT ID:</strong> <?php echo $vat_id; ?></p><?php endif; ?>
				<ul class="legal-links">
					<?php if ( $delivery_terms_url ) : ?><li><a href="<?php echo $delivery_terms_url; ?>" target="_blank">Delivery Terms</a></li><?php endif; ?>
					<?php if ( $privacy_policy_url ) : ?><li><a href="<?php echo $privacy_policy_url; ?>" target="_blank">Privacy Policy</a></li><?php endif; ?>
					<?php if ( $terms_url ) : ?><li><a href="<?php echo $terms_url; ?>" target="_blank">Terms of Service</a></li><?php endif; ?>
					<?php if ( $dpa_url ) : ?><li><a href="<?php echo $dpa_url; ?>" target="_blank">Data Processing Agreement</a></li><?php endif; ?>
					<?php if ( $sla_url ) : ?><li><a href="<?php echo $sla_url; ?>" target="_blank">Service Level Agreement</a></li><?php endif; ?>
				</ul>
			</div>
		</section>

	</main>
	
	<footer class="ai-cv-footer">
		<div class="footer-legal">
			<?php if ( $company_name ) : ?><strong><?php echo $company_name; ?></strong><br><?php endif; ?>
			<?php if ( $business_id ) : ?>Y-tunnus: <?php echo $business_id; ?><br><?php endif; ?>
			<?php if ( $vat_id ) : ?>VAT ID: <?php echo $vat_id; ?><br><?php endif; ?>
			
			<div class="footer-links" style="margin-top:10px;">
				<?php if ( $delivery_terms_url ) : ?><a href="<?php echo $delivery_terms_url; ?>" target="_blank">Delivery Terms</a> | <?php endif; ?>
				<?php if ( $privacy_policy_url ) : ?><a href="<?php echo $privacy_policy_url; ?>" target="_blank">Privacy Policy</a> | <?php endif; ?>
				<?php if ( $terms_url ) : ?><a href="<?php echo $terms_url; ?>" target="_blank">Terms of Service</a> | <?php endif; ?>
			</div>
		</div>
		<p style="margin-top: 15px;">Luottamuksellinen - Vain vastaanottajalle.</p>
		<p><a href="<?php echo home_url(); ?>">Palaa pääsivustolle</a></p>
	</footer>

</div>

<?php wp_footer(); ?>
</body>
</html>
