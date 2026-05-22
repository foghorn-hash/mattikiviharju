<?php

class AI_CV_Tailor_Application_Metaboxes {

	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_ai_cv_application', array( $this, 'save_meta_boxes' ) );
		add_action( 'admin_post_ai_cv_generate_links', array( $this, 'handle_generate_links' ) );
		add_action( 'admin_post_ai_cv_rebuild_summary', array( $this, 'handle_rebuild_summary' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'ai_cv_application_texts',
			'Hakemuskirjeet',
			array( $this, 'render_texts_metabox' ),
			'ai_cv_application',
			'normal',
			'high'
		);

		add_meta_box(
			'ai_cv_application_summary',
			'Copyable Application Summary',
			array( $this, 'render_summary_metabox' ),
			'ai_cv_application',
			'normal',
			'default'
		);
		
		add_meta_box(
			'ai_cv_application_links',
			'Application Links',
			array( $this, 'render_links_metabox' ),
			'ai_cv_application',
			'normal',
			'default'
		);

		add_meta_box(
			'ai_cv_application_actions',
			'Generate / Regenerate Actions',
			array( $this, 'render_actions_metabox' ),
			'ai_cv_application',
			'side',
			'default'
		);
	}

	public function render_texts_metabox( $post ) {
		$cover_letter      = get_post_meta( $post->ID, 'ai_cv_cover_letter', true );
		$motivation_letter = get_post_meta( $post->ID, 'ai_cv_motivation_letter', true );
		
		echo '<p><strong>Cover Letter (Yleinen)</strong></p>';
		wp_editor( $cover_letter, 'ai_cv_cover_letter', array(
			'media_buttons' => false,
			'textarea_rows' => 10
		) );

		echo '<p><strong>Motivation Letter (Yleinen)</strong></p>';
		wp_editor( $motivation_letter, 'ai_cv_motivation_letter', array(
			'media_buttons' => false,
			'textarea_rows' => 10
		) );
	}

	public function render_summary_metabox( $post ) {
		wp_nonce_field( 'save_ai_cv_application', 'ai_cv_application_nonce' );

		$company_name = get_post_meta( $post->ID, '_company_name', true );
		$role_title   = get_post_meta( $post->ID, '_job_title', true );
		$match_score  = get_post_meta( $post->ID, 'ai_cv_match_score', true );
		
		$hr_url        = get_post_meta( $post->ID, 'ai_cv_hr_url', true );
		$cto_url       = get_post_meta( $post->ID, 'ai_cv_cto_url', true );
		$ceo_url       = get_post_meta( $post->ID, 'ai_cv_ceo_url', true );
		$team_lead_url = get_post_meta( $post->ID, 'ai_cv_team_lead_url', true );
		$recruiter_url = get_post_meta( $post->ID, 'ai_cv_recruiter_url', true );
		
		$cover_letter      = get_post_meta( $post->ID, 'ai_cv_cover_letter', true );
		$motivation_letter = get_post_meta( $post->ID, 'ai_cv_motivation_letter', true );
		
		$settings = get_option( 'ai_cv_settings', array() );
		$delivery_terms_url = $settings['delivery_terms_url'] ?? '';
		$privacy_policy_url = $settings['privacy_policy_url'] ?? '';
		$terms_url          = $settings['terms_url'] ?? '';
		
		$summary_text = "i4ware® Job Seeker Autopilot AI Life-cycle Management System™\n\n";
		$summary_text .= "Company:\n" . $this->decode_plain_text( $company_name ) . "\n\n";
		$summary_text .= "Role:\n" . $this->decode_plain_text( $role_title ) . "\n\n";
		$summary_text .= "Match Score:\n" . $this->decode_plain_text( $match_score ) . "\n\n";
		$summary_text .= "Application Links:\n";
		$summary_text .= "HR: " . esc_url( $hr_url ) . "\n";
		$summary_text .= "CTO: " . esc_url( $cto_url ) . "\n";
		$summary_text .= "CEO: " . esc_url( $ceo_url ) . "\n";
		$summary_text .= "Team Lead: " . esc_url( $team_lead_url ) . "\n";
		$summary_text .= "Recruiter: " . esc_url( $recruiter_url ) . "\n\n";
		$summary_text .= "Cover Letter:\n" . $this->decode_plain_text( $cover_letter ) . "\n\n";
		$summary_text .= "Motivation Letter:\n" . $this->decode_plain_text( $motivation_letter ) . "\n\n";
		$summary_text .= "Commercial & Legal:\n";
		$summary_text .= "Company: i4ware Software\n";
		$summary_text .= "Business ID / Y-tunnus: 2739594-6\n";
		$summary_text .= "VAT ID: FI27395946\n";
		$summary_text .= "Delivery Terms: " . esc_url( $delivery_terms_url ) . "\n";
		$summary_text .= "Privacy Policy: " . esc_url( $privacy_policy_url ) . "\n";
		$summary_text .= "Terms of Service: " . esc_url( $terms_url ) . "\n";
		
		echo '<h2>i4ware&reg; Job Seeker Autopilot AI Life-cycle Management System&trade;</h2>';
		echo '<textarea readonly style="width: 100%; height: 300px; font-family: monospace; padding: 10px; background: #f0f0f1;">' . esc_textarea( $summary_text ) . '</textarea>';
		
		// Invisible fields to save basic meta if not generated yet
		echo '<input type="hidden" name="ai_cv_company_name" value="' . esc_attr( $company_name ) . '">';
		echo '<input type="hidden" name="ai_cv_role_title" value="' . esc_attr( $role_title ) . '">';
	}

	public function render_links_metabox( $post ) {
		$audiences = array(
			'hr'        => 'HR',
			'cto'       => 'CTO',
			'ceo'       => 'CEO',
			'team_lead' => 'Team Lead',
			'recruiter' => 'Recruiter'
		);
		
		echo '<table class="form-table">';
		foreach ( $audiences as $key => $label ) {
			$url = get_post_meta( $post->ID, 'ai_cv_' . $key . '_url', true );
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $label ) . '</th>';
			echo '<td>';
			echo '<input type="url" name="ai_cv_' . esc_attr( $key ) . '_url" value="' . esc_attr( $url ) . '" class="large-text" readonly>';
			if ( ! empty( $url ) ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" class="button button-secondary" style="margin-top: 5px;">Preview</a> ';
				echo '<button type="button" class="button button-secondary" style="margin-top: 5px;" onclick="navigator.clipboard.writeText(\'' . esc_url( $url ) . '\'); alert(\'Copied!\');">Copy</button>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	public function render_actions_metabox( $post ) {
		$generate_url = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_generate_links&post_id=' . $post->ID ), 'ai_cv_generate_links' );
		$rebuild_url  = wp_nonce_url( admin_url( 'admin-post.php?action=ai_cv_rebuild_summary&post_id=' . $post->ID ), 'ai_cv_rebuild_summary' );

		echo '<p><em>Huom: Regenerointi vaatii API-kutsun laajennuksen. "Generate All Links" luo uudet tokenit ja päivitykset olemassa oleviin.</em></p>';
		echo '<p><a href="' . esc_url( $generate_url ) . '" class="button button-primary" style="width:100%; margin-bottom:5px; text-align:center;">Generate All Links</a></p>';
		echo '<p><button type="button" class="button button-secondary" style="width:100%; margin-bottom:5px;" disabled>Regenerate HR</button></p>';
		echo '<p><button type="button" class="button button-secondary" style="width:100%; margin-bottom:5px;" disabled>Regenerate CTO</button></p>';
		echo '<p><button type="button" class="button button-secondary" style="width:100%; margin-bottom:5px;" disabled>Regenerate CEO</button></p>';
		echo '<p><button type="button" class="button button-secondary" style="width:100%; margin-bottom:5px;" disabled>Regenerate Team Lead</button></p>';
		echo '<p><button type="button" class="button button-secondary" style="width:100%; margin-bottom:5px;" disabled>Regenerate Recruiter</button></p>';
		echo '<p><a href="' . esc_url( $rebuild_url ) . '" class="button button-secondary" style="width:100%; text-align:center;">Rebuild Copy Summary</a></p>';
	}

	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['ai_cv_application_nonce'] ) || ! wp_verify_nonce( $_POST['ai_cv_application_nonce'], 'save_ai_cv_application' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields_text = array(
			'ai_cv_company_name',
			'ai_cv_role_title',
			'ai_cv_match_score'
		);

		foreach ( $fields_text as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		$fields_url = array(
			'ai_cv_hr_url',
			'ai_cv_cto_url',
			'ai_cv_ceo_url',
			'ai_cv_team_lead_url',
			'ai_cv_recruiter_url'
		);

		foreach ( $fields_url as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
		
		$fields_textarea = array(
			'ai_cv_cover_letter',
			'ai_cv_motivation_letter'
		);
		
		foreach ( $fields_textarea as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
		
		if ( isset( $_POST['ai_cv_copy_summary'] ) ) {
			update_post_meta( $post_id, 'ai_cv_copy_summary', sanitize_textarea_field( wp_unslash( $_POST['ai_cv_copy_summary'] ) ) );
		}
	}

	public function handle_generate_links() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ai_cv_generate_links' ) ) {
			wp_die( 'Turvatarkistus epäonnistui.' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Ei oikeuksia.' );
		}

		$audiences = array(
			'hr'        => 'hr',
			'cto'       => 'cto',
			'ceo'       => 'ceo',
			'team_lead' => 'team-lead',
			'recruiter' => 'recruiter'
		);

		foreach ( $audiences as $meta_key => $url_slug ) {
			$token = wp_generate_password( 32, false, false );
			update_post_meta( $post_id, 'ai_cv_' . $meta_key . '_token', $token );
			
			$url = home_url( '/ai-cv/' . $post_id . '/' . $url_slug . '/' . $token );
			update_post_meta( $post_id, 'ai_cv_' . $meta_key . '_url', $url );
		}

		$this->generate_application_texts( $post_id );
		$this->rebuild_summary( $post_id );

		wp_safe_redirect( add_query_arg( array(
			'post'   => $post_id,
			'action' => 'edit',
			'ai_cv_links_generated' => '1'
		), admin_url( 'post.php' ) ) );
		exit;
	}

	public function handle_rebuild_summary() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ai_cv_rebuild_summary' ) ) {
			wp_die( 'Turvatarkistus epäonnistui.' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Ei oikeuksia.' );
		}

		$this->rebuild_summary( $post_id );

		wp_safe_redirect( add_query_arg( array(
			'post'   => $post_id,
			'action' => 'edit',
			'message' => '4' // WP core message for 'Post updated'
		), admin_url( 'post.php' ) ) );
		exit;
	}

	private function decode_plain_text($value) {
		return html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	private function generate_application_texts( $post_id ) {
		$company_name = $this->decode_plain_text( get_post_meta( $post_id, '_company_name', true ) );
		$role_title   = $this->decode_plain_text( get_post_meta( $post_id, '_job_title', true ) );
		
		// Fallback content
		$cover_letter = "Kirjoita suomeksi hakemuskirje rooliin {$role_title} yritykselle {$company_name}. Mainitse i4ware Software, Y-tunnus 2739594-6, VAT FI27395946, B2B/freelance-toimitusmalli, ja että toteutus tehdään Delivery Terms ja Privacy Policy -ehtojen mukaisesti.";
		
		$motivation_letter = "Kirjoita suomeksi motivaatiokirje rooliin {$role_title}. Painota kiinnostusta ratkaista yrityksen teknisiä haasteita, full-stack-osaamista, OpenAI/AI-integraatioita, React/Laravel/PHP/WordPress/Jira/Atlassian-osaamista ja nopeaa käytännön toteutuskykyä.";

		// Only overwrite general texts if they are empty
		if ( ! get_post_meta( $post_id, 'ai_cv_cover_letter', true ) ) {
			update_post_meta( $post_id, 'ai_cv_cover_letter', $cover_letter );
		}
		if ( ! get_post_meta( $post_id, 'ai_cv_motivation_letter', true ) ) {
			update_post_meta( $post_id, 'ai_cv_motivation_letter', $motivation_letter );
		}

		// Also generate for audiences
		$audiences = array( 'hr', 'cto', 'ceo', 'team_lead', 'recruiter' );
		foreach ( $audiences as $aud ) {
			if ( ! get_post_meta( $post_id, 'ai_cv_' . $aud . '_cover_letter', true ) ) {
				update_post_meta( $post_id, 'ai_cv_' . $aud . '_cover_letter', $cover_letter );
			}
			if ( ! get_post_meta( $post_id, 'ai_cv_' . $aud . '_motivation_letter', true ) ) {
				update_post_meta( $post_id, 'ai_cv_' . $aud . '_motivation_letter', $motivation_letter );
			}
		}
	}

	private function rebuild_summary( $post_id ) {
		$company_name = $this->decode_plain_text( get_post_meta( $post_id, '_company_name', true ) );
		$role_title   = $this->decode_plain_text( get_post_meta( $post_id, '_job_title', true ) );
		$match_score  = $this->decode_plain_text( get_post_meta( $post_id, 'ai_cv_match_score', true ) );
		
		$hr_url        = get_post_meta( $post_id, 'ai_cv_hr_url', true );
		$cto_url       = get_post_meta( $post_id, 'ai_cv_cto_url', true );
		$ceo_url       = get_post_meta( $post_id, 'ai_cv_ceo_url', true );
		$team_lead_url = get_post_meta( $post_id, 'ai_cv_team_lead_url', true );
		$recruiter_url = get_post_meta( $post_id, 'ai_cv_recruiter_url', true );
		
		$cover_letter      = $this->decode_plain_text( get_post_meta( $post_id, 'ai_cv_cover_letter', true ) );
		$motivation_letter = $this->decode_plain_text( get_post_meta( $post_id, 'ai_cv_motivation_letter', true ) );
		
		$settings = get_option( 'ai_cv_settings', array() );
		$delivery_terms_url = $settings['delivery_terms_url'] ?? '';
		$privacy_policy_url = $settings['privacy_policy_url'] ?? '';
		$terms_url          = $settings['terms_url'] ?? '';

		$summary_text = "i4ware® Job Seeker Autopilot AI Life-cycle Management System™\n\n";
		$summary_text .= "Company:\n" . $company_name . "\n\n";
		$summary_text .= "Role:\n" . $role_title . "\n\n";
		$summary_text .= "Match Score:\n" . $match_score . "\n\n";
		$summary_text .= "Application Links:\n";
		$summary_text .= "HR: " . $hr_url . "\n";
		$summary_text .= "CTO: " . $cto_url . "\n";
		$summary_text .= "CEO: " . $ceo_url . "\n";
		$summary_text .= "Team Lead: " . $team_lead_url . "\n";
		$summary_text .= "Recruiter: " . $recruiter_url . "\n\n";
		$summary_text .= "Cover Letter:\n" . $cover_letter . "\n\n";
		$summary_text .= "Motivation Letter:\n" . $motivation_letter . "\n\n";
		$summary_text .= "Commercial & Legal:\n";
		$summary_text .= "Company: i4ware Software\n";
		$summary_text .= "Business ID / Y-tunnus: 2739594-6\n";
		$summary_text .= "VAT ID: FI27395946\n";
		$summary_text .= "Delivery Terms: " . $delivery_terms_url . "\n";
		$summary_text .= "Privacy Policy: " . $privacy_policy_url . "\n";
		$summary_text .= "Terms of Service: " . $terms_url . "\n";

		update_post_meta( $post_id, 'ai_cv_copy_summary', $summary_text );
	}

	public function admin_notices() {
		if ( isset( $_GET['ai_cv_links_generated'] ) && $_GET['ai_cv_links_generated'] === '1' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Application links generated successfully.</p></div>';
		}
	}
}
