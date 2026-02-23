<?php
$hero_headline = function_exists('get_field') ? get_field('cv_hero_headline') : '';
$hero_intro = function_exists('get_field') ? get_field('cv_hero_intro') : '';
$about_photo = function_exists('get_field') ? get_field('cv_about_photo') : null;
$primary_cta_label = function_exists('get_field') ? get_field('cv_primary_cta_label') : '';
$primary_cta_url = function_exists('get_field') ? get_field('cv_primary_cta_url') : '';
$secondary_cta_label = function_exists('get_field') ? get_field('cv_secondary_cta_label') : '';
$secondary_cta_url = function_exists('get_field') ? get_field('cv_secondary_cta_url') : '';
$profile_title = function_exists('get_field') ? get_field('cv_profile_title') : '';
$profile_body = function_exists('get_field') ? get_field('cv_profile_body') : '';
$profile_github_url = function_exists('get_field') ? get_field('cv_profile_github_url') : '';
$profile_youtube_url = function_exists('get_field') ? get_field('cv_profile_youtube_url') : '';
$experience_title = function_exists('get_field') ? get_field('cv_experience_title') : '';
$education_title = function_exists('get_field') ? get_field('cv_education_title') : '';
$courses_title = function_exists('get_field') ? get_field('cv_courses_title') : '';
$projects_title = function_exists('get_field') ? get_field('cv_projects_title') : '';
$skills_title = function_exists('get_field') ? get_field('cv_skills_title') : '';
$contact_title = function_exists('get_field') ? get_field('cv_contact_title') : '';
$contact_body = function_exists('get_field') ? get_field('cv_contact_body') : '';
$contact_email = function_exists('get_field') ? get_field('cv_contact_email') : '';
$contact_phone = function_exists('get_field') ? get_field('cv_contact_phone') : '';
$contact_business_id = function_exists('get_field') ? get_field('cv_contact_business_id') : '';
$contact_vat_id = function_exists('get_field') ? get_field('cv_contact_vat_id') : '';
$contact_linkedin = function_exists('get_field') ? get_field('cv_contact_linkedin') : '';
$contact_linkedin_label = function_exists('get_field') ? get_field('cv_contact_linkedin_label') : '';

$hero_headline = $hero_headline ?: get_bloginfo('name');
$hero_intro = $hero_intro ?: get_bloginfo('description');
$primary_cta_label = $primary_cta_label ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Schedule a call') : 'Schedule a call');
$primary_cta_url = $primary_cta_url ?: '#contact';
$secondary_cta_label = $secondary_cta_label ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('View work') : 'View work');
$secondary_cta_url = $secondary_cta_url ?: '#projects';
$profile_title = $profile_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Profile Snapshot') : 'Profile Snapshot');
$profile_body = $profile_body ?: 'Product-focused developer with a passion for clean UX, scalable systems, and measurable impact.';
$experience_title = $experience_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Experience') : 'Experience');
$education_title = $education_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Education') : 'Education');
$courses_title = $courses_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Additional training') : 'Additional training');
$projects_title = $projects_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Selected Projects') : 'Selected Projects');
$skills_title = $skills_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Skills') : 'Skills');
$contact_title = $contact_title ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Contact') : 'Contact');
$contact_body = $contact_body ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Let’s connect. Share your project goals and I’ll respond within 24 hours.') : 'Let’s connect. Share your project goals and I’ll respond within 24 hours.');
$contact_email = $contact_email ?: 'hello@example.com';
$contact_linkedin = $contact_linkedin ?: 'https://www.linkedin.com';
$contact_linkedin_label = $contact_linkedin_label ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('LinkedIn') : 'LinkedIn');
$is_admin_editor = is_user_logged_in() && current_user_can('manage_options');
$front_page_edit_url = '';
if ($is_admin_editor) {
	$front_page_id = function_exists('cv_one_pager_front_page_id') ? cv_one_pager_front_page_id() : (int) get_option('page_on_front');
	if ($front_page_id > 0) {
		$front_page_edit_url = get_edit_post_link($front_page_id);
	}
}
?>

<main>
	<section class="hero" id="about">
		<div class="container hero-grid">
			<div>
				<h1><?php echo esc_html($hero_headline); ?></h1>
				<?php if (!empty($about_photo) && is_array($about_photo)) : ?>
					<div class="about-photo inline">
						<img src="<?php echo esc_url($about_photo['url']); ?>" alt="<?php echo esc_attr($about_photo['alt'] ?: $hero_headline); ?>" />
					</div>
				<?php endif; ?>
				<p><?php echo esc_html($hero_intro); ?></p>
				<?php if ($is_admin_editor && !empty($front_page_edit_url)) : ?>
					<p><a href="<?php echo esc_url($front_page_edit_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Page') : 'Edit Page'); ?></a></p>
				<?php endif; ?>
				<div class="cta">
					<a class="button" href="<?php echo esc_url($primary_cta_url); ?>">
						<?php echo esc_html($primary_cta_label); ?>
					</a>
					<a class="button secondary" href="<?php echo esc_url($secondary_cta_url); ?>">
						<?php echo esc_html($secondary_cta_label); ?>
					</a>
				<a href="<?php echo esc_url(add_query_arg('cv_export_pdf', '1', home_url('/'))); ?>" class="nav-download" title="<?php echo esc_attr(function_exists('cv_one_pager_t') ? cv_one_pager_t('Download CV as PDF') : 'Download CV as PDF'); ?>">
					<i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
					<span class="sr-only"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Download PDF') : 'Download PDF'); ?></span>
					</a>
				</div>
			</div>
			<div class="hero-card">
				<h2><?php echo esc_html($profile_title); ?></h2>
				<p><?php echo esc_html($profile_body); ?></p>
				<div class="badges">
					<?php
					$badges_query = array(
						'post_type' => 'cv_badge',
						'numberposts' => -1,
						'orderby' => 'menu_order',
						'order' => 'DESC',
					);
					if (function_exists('pll_current_language')) {
						$badges_query['lang'] = pll_current_language();
					} else {
						$badges_query['suppress_filters'] = false;
					}
					$profile_badges = get_posts($badges_query);
					?>
					<?php foreach ($profile_badges as $badge) : ?>
						<span class="badge"><?php echo esc_html(get_the_title($badge)); ?></span>
					<?php endforeach; ?>
				</div>
				<?php if (!empty($profile_github_url) || !empty($profile_youtube_url)) : ?>
					<div class="profile-links">
						<?php if (!empty($profile_github_url)) : ?>
							<a class="profile-link" href="<?php echo esc_url($profile_github_url); ?>" target="_blank" rel="noopener">
								<i class="bi bi-github" aria-hidden="true"></i>
								<span><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('GitHub') : 'GitHub'); ?></span>
							</a>
						<?php endif; ?>
						<?php if (!empty($profile_youtube_url)) : ?>
							<a class="profile-link" href="<?php echo esc_url($profile_youtube_url); ?>" target="_blank" rel="noopener">
								<i class="bi bi-youtube" aria-hidden="true"></i>
								<span><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('YouTube') : 'YouTube'); ?></span>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="section" id="experience">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($experience_title); ?></h2>
			<div class="timeline">
				<?php
				$experience_query = array(
					'post_type' => 'cv_experience',
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'DESC',
				);
				if (function_exists('pll_current_language')) {
					$experience_query['lang'] = pll_current_language();
				} else {
					$experience_query['suppress_filters'] = false;
				}
				$experience_items = get_posts($experience_query);
				?>
				<?php foreach ($experience_items as $item) : ?>
					<?php 
					$company = get_post_meta($item->ID, '_cv_experience_company', true);
					$dates = get_post_meta($item->ID, '_cv_experience_dates', true); 
					?>
					<div class="card timeline-item">
						<?php if (!empty($company)) : ?>
							<h3><?php echo esc_html($company); ?></h3>
						<?php endif; ?>
						<strong><?php echo esc_html(get_the_title($item)); ?></strong>
						<?php if (!empty($dates)) : ?>
							<span><?php echo esc_html($dates); ?></span>
						<?php endif; ?>
						<?php if (!empty($item->post_excerpt)) : ?>
							<p><?php echo esc_html($item->post_excerpt); ?></p>
						<?php elseif (!empty($item->post_content)) : ?>
							<p><?php echo esc_html(wp_trim_words($item->post_content, 24)); ?></p>
						<?php endif; ?>
						<?php if ($is_admin_editor) : ?>
							<?php $edit_experience_url = get_edit_post_link($item->ID); ?>
							<?php if (!empty($edit_experience_url)) : ?>
								<p><a href="<?php echo esc_url($edit_experience_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Experience') : 'Edit Experience'); ?></a></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" id="education">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($education_title); ?></h2>
			<div class="timeline">
				<?php
				$education_query = array(
					'post_type' => 'cv_education',
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'DESC',
				);
				if (function_exists('pll_current_language')) {
					$education_query['lang'] = pll_current_language();
				} else {
					$education_query['suppress_filters'] = false;
				}
				$education_items = get_posts($education_query);
				?>
				<?php foreach ($education_items as $item) : ?>
					<?php $dates = get_post_meta($item->ID, '_cv_education_dates', true); ?>
					<div class="card timeline-item">
						<h3><?php echo esc_html(get_the_title($item)); ?></h3>
						<?php if (!empty($item->post_excerpt)) : ?>
							<p><?php echo esc_html($item->post_excerpt); ?></p>
						<?php if (!empty($dates)) : ?>
							<span><?php echo esc_html($dates); ?></span>
						<?php endif; ?>
						<?php elseif (!empty($item->post_content)) : ?>
							<p><?php echo esc_html(wp_trim_words($item->post_content, 24)); ?></p>
						<?php endif; ?>
						<?php if ($is_admin_editor) : ?>
							<?php $edit_education_url = get_edit_post_link($item->ID); ?>
							<?php if (!empty($edit_education_url)) : ?>
								<p><a href="<?php echo esc_url($edit_education_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Education') : 'Edit Education'); ?></a></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" id="courses">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($courses_title); ?></h2>
			<div class="card-grid">
				<?php
				$courses_query = array(
					'post_type' => 'cv_course',
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'DESC',
				);
				if (function_exists('pll_current_language')) {
					$courses_query['lang'] = pll_current_language();
				} else {
					$courses_query['suppress_filters'] = false;
				}
				$courses = get_posts($courses_query);
				?>
				<?php foreach ($courses as $course) : ?>
					<?php
					$dates = get_post_meta($course->ID, '_cv_course_dates', true);
					$provider = get_post_meta($course->ID, '_cv_course_provider', true);
					?>
					<article class="card">
						<h3><?php echo esc_html(get_the_title($course)); ?></h3>
						<?php if (!empty($provider)) : ?>
							<p><?php echo esc_html($provider); ?></p>
						<?php endif; ?>
						<?php if (!empty($dates)) : ?>
							<span><?php echo esc_html($dates); ?></span>
						<?php endif; ?>
						<?php if (!empty($course->post_content)) : ?>
							<p><?php echo esc_html(wp_trim_words($course->post_content, 24)); ?></p>
						<?php endif; ?>
						<?php if ($is_admin_editor) : ?>
							<?php $edit_course_url = get_edit_post_link($course->ID); ?>
							<?php if (!empty($edit_course_url)) : ?>
								<p><a href="<?php echo esc_url($edit_course_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Course') : 'Edit Course'); ?></a></p>
							<?php endif; ?>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" id="projects">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($projects_title); ?></h2>
			<div class="card-grid">
				<?php
				$projects_query = array(
					'post_type' => 'cv_project',
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'DESC',
				);
				if (function_exists('pll_current_language')) {
					$projects_query['lang'] = pll_current_language();
				} else {
					$projects_query['suppress_filters'] = false;
				}
				$projects = get_posts($projects_query);
				?>
				<?php foreach ($projects as $project) : ?>
					<?php
					$meta = get_post_meta($project->ID, '_cv_project_meta', true);
					$tech_stack_label = function_exists('cv_one_pager_t') ? cv_one_pager_t('Tech Stack:') : 'Tech Stack:';
					$link = get_post_meta($project->ID, '_cv_project_link', true);
					$link_label = get_post_meta($project->ID, '_cv_project_link_label', true);
					$youtube_url = get_post_meta($project->ID, '_cv_project_youtube', true);
					$screenshot_alts = get_post_meta($project->ID, '_cv_project_screenshot_alts', true);
					$screenshot_alts = is_array($screenshot_alts) ? $screenshot_alts : array();
					$screenshots = array();
					if (function_exists('get_field') && function_exists('acf_get_field_type') && acf_get_field_type('gallery')) {
						$screenshots = get_field('cv_project_screenshots', $project->ID);
					}
					if (empty($screenshots)) {
						$stored = get_post_meta($project->ID, '_cv_project_screenshots', true);
						if (is_array($stored)) {
							$screenshots = $stored;
						} elseif (is_string($stored) && $stored !== '') {
							$screenshots = array_filter(array_map('absint', explode(',', $stored)));
						}
					}
					
					// Extract YouTube video ID
					$youtube_id = '';
					if (!empty($youtube_url)) {
						preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $youtube_url, $matches);
						if (!empty($matches[1])) {
							$youtube_id = $matches[1];
						}
					}
					?>
					<article class="card">
						<h3><?php echo esc_html(get_the_title($project)); ?></h3>
						<?php if (!empty($meta)) : ?>
							<p class="project-tech-stack"><strong><?php echo esc_html($tech_stack_label); ?></strong> <?php echo esc_html($meta); ?></p>
						<?php endif; ?>
						<?php if (!empty($project->post_excerpt)) : ?>
							<p><?php echo esc_html($project->post_excerpt); ?></p>
						<?php elseif (!empty($project->post_content)) : ?>
							<p><?php echo esc_html(wp_trim_words($project->post_content, 24)); ?></p>
						<?php endif; ?>
						<?php if (!empty($screenshots) && is_array($screenshots) || !empty($youtube_id)) : ?>
							<div class="project-gallery js-project-gallery" aria-label="Project screenshots">
								<?php 
								$i = 0;
								
								// Show YouTube video first if available
								if (!empty($youtube_id)) :
									$i++;
									$youtube_thumb = 'https://img.youtube.com/vi/' . esc_attr($youtube_id) . '/mqdefault.jpg';
									$youtube_alt = $i . ' - ' . (get_the_title($project) ?: 'Project video');
								?>
									<button type="button" class="project-shot project-shot-video" data-youtube="<?php echo esc_attr($youtube_id); ?>" aria-label="Play video">
										<img src="<?php echo esc_url($youtube_thumb); ?>" alt="<?php echo esc_attr($youtube_alt); ?>" loading="lazy" />
										<i class="bi bi-play-circle project-shot-play-icon" aria-hidden="true"></i>
									</button>
								<?php 
								endif;
								
								// Show screenshots
								if (!empty($screenshots) && is_array($screenshots)) :
									foreach ($screenshots as $shot) : ?>
									<?php
									$thumb = '';
									$full = '';
									$alt = '';
									$attachment_id = 0;

									if (is_array($shot)) {
										$attachment_id = !empty($shot['ID']) ? (int) $shot['ID'] : 0;
										$thumb = !empty($shot['sizes']['thumbnail']) ? $shot['sizes']['thumbnail'] : (!empty($shot['url']) ? $shot['url'] : '');
										$full = !empty($shot['sizes']['large']) ? $shot['sizes']['large'] : (!empty($shot['url']) ? $shot['url'] : '');
										$alt = !empty($shot['alt']) ? $shot['alt'] : '';
									} else {
										$attachment_id = absint($shot);
										$thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
										$full = wp_get_attachment_image_url($attachment_id, 'large');
										$alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
									}

									if (empty($thumb)) {
										$thumb = $full;
									}
									if (empty($full)) {
										$full = $thumb;
									}
									if (empty($alt)) {
										$alt = get_the_title($project);
									}
									if ($attachment_id > 0 && isset($screenshot_alts[$attachment_id]) && $screenshot_alts[$attachment_id] !== '') {
										$alt = $screenshot_alts[$attachment_id];
									}

									if (empty($thumb) || empty($full)) {
										continue;
									}
									$i++;
									$shot_alt = $i . ' - ' . $alt;
									?>
									<button type="button" class="project-shot" data-full="<?php echo esc_url($full); ?>" aria-label="Open screenshot: <?php echo esc_attr($alt); ?>">
										<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($shot_alt); ?>" loading="lazy" />
									</button>
								<?php 
									endforeach;
								endif;
								?>
							</div>
						<?php endif; ?>
						<?php if (!empty($link)) : ?>
							<?php $link_text = !empty($link_label) ? $link_label : 'View project'; ?>
							<p><a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener"><?php echo esc_html($link_text); ?></a></p>
						<?php endif; ?>
						<?php if ($is_admin_editor) : ?>
							<?php $edit_project_url = get_edit_post_link($project->ID); ?>
							<?php if (!empty($edit_project_url)) : ?>
								<p><a href="<?php echo esc_url($edit_project_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Project') : 'Edit Project'); ?></a></p>
							<?php endif; ?>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="project-lightbox" aria-hidden="true">
				<button class="project-lightbox-close" type="button" aria-label="Close image">
					<i class="bi bi-x-lg" aria-hidden="true"></i>
				</button>
				<button class="project-lightbox-prev" type="button" aria-label="Previous image">
					<i class="bi bi-chevron-left" aria-hidden="true"></i>
				</button>
				<button class="project-lightbox-next" type="button" aria-label="Next image">
					<i class="bi bi-chevron-right" aria-hidden="true"></i>
				</button>
				<img class="project-lightbox-image" src="" alt="" />
				<div class="project-lightbox-video"></div>
				<div class="project-lightbox-caption"><strong></strong></div>
			</div>
		</div>
	</section>

	<section class="section" id="skills">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($skills_title); ?></h2>
			<div class="skill-list">
				<?php
				$skills_query = array(
					'post_type' => 'cv_skill',
					'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'DESC',
				);
				if (function_exists('pll_current_language')) {
					$skills_query['lang'] = pll_current_language();
				} else {
					$skills_query['suppress_filters'] = false;
				}
				$skills = get_posts($skills_query);
				?>
				<?php foreach ($skills as $skill) : ?>
					<div class="skill">
						<?php echo esc_html(get_the_title($skill)); ?>
						<?php if ($is_admin_editor) : ?>
							<?php $edit_skill_url = get_edit_post_link($skill->ID); ?>
							<?php if (!empty($edit_skill_url)) : ?>
								 <a href="<?php echo esc_url($edit_skill_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Skill') : 'Edit Skill'); ?></a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section" id="contact">
		<div class="container">
			<h2 class="section-title"><?php echo esc_html($contact_title); ?></h2>
			<div class="card">
				<p><?php echo esc_html($contact_body); ?></p>
				<?php if ($is_admin_editor && !empty($front_page_edit_url)) : ?>
					<p><a href="<?php echo esc_url($front_page_edit_url); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edit Page') : 'Edit Page'); ?></a></p>
				<?php endif; ?>
				<?php if (!empty($contact_business_id) || !empty($contact_vat_id)) : ?>
					<div class="contact-meta">
						<?php if (!empty($contact_business_id)) : ?>
						<p><strong><?php echo esc_html(cv_one_pager_t('Y-tunnus:')); ?></strong> <?php echo esc_html($contact_business_id); ?></p>
					<?php endif; ?>
					<?php if (!empty($contact_vat_id)) : ?>
						<p><strong><?php echo esc_html(cv_one_pager_t('ALV-rek. nro:')); ?></strong> <?php echo esc_html($contact_vat_id); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="cta">
					<a class="button" href="mailto:<?php echo esc_attr($contact_email); ?>">
						<?php echo esc_html($contact_email); ?>
					</a>
					<?php if (!empty($contact_phone)) : ?>
						<a class="button secondary" href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $contact_phone)); ?>">
							<?php echo esc_html($contact_phone); ?>
						</a>
					<?php endif; ?>
					<a class="button secondary" href="<?php echo esc_url($contact_linkedin); ?>" target="_blank" rel="noopener">
						<?php echo esc_html($contact_linkedin_label); ?>
					</a>
				</div>
			</div>
		</div>
	</section>
</main>
