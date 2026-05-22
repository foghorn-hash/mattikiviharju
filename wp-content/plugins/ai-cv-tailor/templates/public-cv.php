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
$cv_title        = esc_html( $content['cv_title'] ?? '' );
$profile_summary = wp_kses_post( $content['profile_summary'] ?? '' );
$skills          = isset( $content['selected_skills'] ) && is_array( $content['selected_skills'] ) ? array_map( 'esc_html', $content['selected_skills'] ) : array();
$projects        = isset( $content['selected_projects'] ) && is_array( $content['selected_projects'] ) ? $content['selected_projects'] : array();
$experience      = isset( $content['selected_experience'] ) && is_array( $content['selected_experience'] ) ? $content['selected_experience'] : array();
$cover_letter    = nl2br( wp_kses_post( $content['cover_letter'] ?? '' ) );
$motivation      = nl2br( wp_kses_post( $content['motivation_letter'] ?? '' ) );

$my_name = esc_html( $settings['my_name'] ?? '' );
$my_email = esc_html( $settings['my_email'] ?? '' );
$my_phone = esc_html( $settings['my_phone'] ?? '' );
$business_id = esc_html( $settings['business_id'] ?? '' );
$website_url = esc_url( $settings['website_url'] ?? '' );

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
		
		<section class="ai-cv-section cv-profile">
			<h2><?php echo $cv_title; ?></h2>
			<div class="content-box">
				<p><?php echo $profile_summary; ?></p>
			</div>
		</section>
		
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

	</main>
	
	<footer class="ai-cv-footer">
		<p>Luottamuksellinen - Vain vastaanottajalle.</p>
		<p><a href="<?php echo home_url(); ?>">Palaa pääsivustolle</a></p>
	</footer>

</div>

<?php wp_footer(); ?>
</body>
</html>
