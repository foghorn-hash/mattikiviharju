<?php
/**
 * Application Email Sender
 *
 * Sends job applications to fished public emails using the WordPress site administrator email.
 *
 * @package AI_CV_Tailor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_CV_Tailor_Email_Sender {

	/**
	 * Get site administrator email
	 *
	 * Defaults to get_option( 'admin_email' )
	 *
	 * @return string
	 */
	public function get_admin_email() {
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['admin_sender_email'] ) && is_email( $autopilot_settings['admin_sender_email'] ) ) {
			return sanitize_email( $autopilot_settings['admin_sender_email'] );
		}

		$base_settings = get_option( 'ai_cv_tailor_settings', array() );
		if ( ! empty( $base_settings['my_email'] ) && is_email( $base_settings['my_email'] ) ) {
			return sanitize_email( $base_settings['my_email'] );
		}

		$admin_email = get_option( 'admin_email' );
		return ! empty( $admin_email ) ? sanitize_email( $admin_email ) : 'matti.kiviharju@i4ware.fi';
	}

	/**
	 * Get administrator sender display name
	 *
	 * @return string
	 */
	public function get_sender_name() {
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['admin_sender_name'] ) ) {
			return sanitize_text_field( $autopilot_settings['admin_sender_name'] );
		}

		$base_settings = get_option( 'ai_cv_tailor_settings', array() );
		if ( ! empty( $base_settings['my_name'] ) ) {
			$name = $base_settings['my_name'];
			if ( ! empty( $base_settings['company_name'] ) ) {
				$name .= ' | ' . $base_settings['company_name'];
			}
			return sanitize_text_field( $name );
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * Prepare application email data (subject, body, recipient, links)
	 *
	 * @param int $job_id
	 * @return array|WP_Error
	 */
	public function prepare_email_data( $job_id ) {
		$post = get_post( $job_id );
		if ( ! $post || 'freelance_job' !== $post->post_type ) {
			return new WP_Error( 'not_found', 'Toimeksiantoa ei löytynyt ID:llä ' . $job_id );
		}

		$company_name   = get_post_meta( $job_id, 'company_name', true ) ?: 'Yritys';
		$role_title     = get_post_meta( $job_id, 'role_title', true ) ?: $post->post_title;
		$job_type       = get_post_meta( $job_id, 'job_type', true ) ?: 'listattu';
		$contact_email  = get_post_meta( $job_id, 'contact_email', true );
		$contact_person = get_post_meta( $job_id, 'contact_person', true );
		$source_url     = get_post_meta( $job_id, 'source_url', true );
		$analysis_json  = get_post_meta( $job_id, 'ai_analysis_json', true );
		$analysis       = ! empty( $analysis_json ) ? json_decode( $analysis_json, true ) : array();

		$app_id = get_post_meta( $job_id, 'generated_application_id', true );

		$base_settings = get_option( 'ai_cv_tailor_settings', array() );
		$my_name       = $base_settings['my_name'] ?? 'Matti Kiviharju';
		$my_title      = $base_settings['my_title'] ?? 'Entrepreneur & Founder & Chief Software Architect';
		$my_company    = $base_settings['company_name'] ?? 'i4ware Software';
		$my_phone      = $base_settings['my_phone'] ?? '+358 40 820 0691';
		$my_email      = $this->get_admin_email();
		$website_url   = $base_settings['website_url'] ?? 'https://www.i4ware.fi/';
		$business_id   = $base_settings['business_id'] ?? '2739594-6';
		$vat_id        = $base_settings['vat_id'] ?? 'FI27395946';
		$terms_url     = $base_settings['delivery_terms_url'] ?? '';
		$privacy_url   = $base_settings['privacy_policy_url'] ?? '';

		// Footer signature from settings
		$default_footer = "-- \n--\n{$my_name}\n{$my_title}\n{$my_company}\n{$website_url}\n{$my_phone}\nVAT-ID: {$vat_id}\nCorporate ID: {$business_id}";
		$email_footer   = ! empty( $base_settings['email_footer'] ) ? $base_settings['email_footer'] : $default_footer;

		// Subject
		$suggested_subject = get_post_meta( $job_id, 'suggested_subject', true );
		if ( ! empty( $suggested_subject ) ) {
			$subject = $suggested_subject;
		} elseif ( 'avoin_hakemus' === $job_type ) {
			$subject = "Avoin työhakemus: {$role_title} - {$my_name} / {$my_company}";
		} else {
			$subject = "Työhakemus: {$role_title} - {$my_name} / {$my_company}";
		}

		// Body letter: Preserve the original tailored cover letter
		$cover_letter = '';
		$original_cover_letter = get_post_meta( $job_id, 'suggested_cover_letter', true );
		if ( ! empty( $original_cover_letter ) ) {
			$cover_letter = $original_cover_letter;
		} elseif ( $app_id ) {
			$cover_letter = get_post_meta( $app_id, 'ai_cv_cover_letter', true );
		}
		if ( empty( $cover_letter ) && ! empty( $analysis['suggested_cover_letter'] ) ) {
			$cover_letter = $analysis['suggested_cover_letter'];
		}

		// Fallback default letter if no AI letter was generated
		if ( empty( $cover_letter ) ) {
			$greeting = ! empty( $contact_person ) ? "Hei {$contact_person} / {$company_name}," : "Hei yrityksen {$company_name} rekrytointitiimi,";
			$cover_letter = "{$greeting}\n\nOlen kiinnostunut tarjoamaan osaamistani toimeksiantoonne / tiimiinne ({$role_title}). Minulla on laaja yli 15 vuoden kokemus vaativasta ohjelmistokehityksestä (PHP, Laravel, WordPress, React, Node.js, pilviarkkitehtuurit ja AI-integraatiot).\n\nTyöskentelen joustavasti B2B-laskutuksella yritykseni i4ware Softwaren kautta tai suoralla sopimuksella.";
		}

		// CV links
		$cv_links = array();
		if ( $app_id ) {
			$audiences = array(
				'cto'       => 'Tekninen CV (CTO / Lead)',
				'hr'        => 'Rekrytoijan CV (HR)',
				'recruiter' => 'Konsultti- / Headhunter-CV',
			);
			foreach ( $audiences as $aud_key => $aud_label ) {
				$url = get_post_meta( $app_id, 'ai_cv_' . $aud_key . '_url', true );
				if ( ! empty( $url ) ) {
					$cv_links[ $aud_label ] = $url;
				}
			}
		}
		if ( empty( $cv_links ) ) {
			$cv_links['Interaktiivinen CV'] = $website_url;
		}

		return array(
			'job_id'         => $job_id,
			'company_name'   => $company_name,
			'role_title'     => $role_title,
			'job_type'       => $job_type,
			'contact_email'  => $contact_email,
			'contact_person' => $contact_person,
			'admin_email'    => $my_email,
			'sender_name'    => $this->get_sender_name(),
			'subject'        => $subject,
			'cover_letter'   => $cover_letter,
			'cv_links'       => $cv_links,
			'footer'         => $email_footer,
			'email_footer'   => $email_footer,
			'source_url'     => $source_url,
			'sender_details' => array(
				'name'        => $my_name,
				'title'       => $my_title,
				'company'     => $my_company,
				'phone'       => $my_phone,
				'email'       => $my_email,
				'website'     => $website_url,
				'business_id' => $business_id,
				'vat_id'      => $vat_id,
				'terms_url'   => $terms_url,
				'privacy_url' => $privacy_url,
			),
			'powered_by'     => 'Powered by i4ware Job Seeker AI based to Google Gemini API and OpenAI API',
		);
	}

	/**
	 * Build full HTML email body
	 *
	 * @param array $data Output from prepare_email_data()
	 * @return string
	 */
	public function build_html_body( $data ) {
		$details = $data['sender_details'];
		$paragraphs = explode( "\n\n", trim( $data['cover_letter'] ) );

		$letter_html = '';
		foreach ( $paragraphs as $p ) {
			$p_clean = trim( $p );
			if ( ! empty( $p_clean ) ) {
				$letter_html .= '<p style="margin: 0 0 14px 0; line-height: 1.6; color: #333333; font-size: 15px;">' . nl2br( esc_html( $p_clean ) ) . '</p>';
			}
		}

		$links_html = '';
		if ( ! empty( $data['cv_links'] ) ) {
			$links_html .= '<div style="margin: 25px 0; padding: 16px; background-color: #f4f7f9; border-left: 4px solid #0073aa; border-radius: 4px;">';
			$links_html .= '<p style="margin: 0 0 10px 0; font-weight: bold; color: #1d2327;">Tutustu räätälöityyn interaktiiviseen CV:hen:</p>';
			$links_html .= '<ul style="margin: 0; padding-left: 20px;">';
			foreach ( $data['cv_links'] as $label => $link ) {
				$links_html .= '<li style="margin-bottom: 6px;"><a href="' . esc_url( $link ) . '" style="color: #0073aa; text-decoration: underline; font-weight: 600;">' . esc_html( $label ) . ' &rarr;</a></li>';
			}
			$links_html .= '</ul>';
			$links_html .= '</div>';
		}

		$legal_html = '';
		if ( ! empty( $details['terms_url'] ) || ! empty( $details['privacy_url'] ) ) {
			$legal_html .= '<p style="font-size: 12px; color: #888888; margin-top: 25px; line-height: 1.4; border-top: 1px solid #e5e5e5; padding-top: 15px;">';
			$legal_html .= 'Palvelut tarjoaa ' . esc_html( $details['company'] ) . ' (Y-tunnus: ' . esc_html( $details['business_id'] ) . ').<br>';
			if ( ! empty( $details['terms_url'] ) ) {
				$legal_html .= '<a href="' . esc_url( $details['terms_url'] ) . '" style="color: #888888; text-decoration: underline;">Toimitusehdot (Delivery Terms)</a> &bull; ';
			}
			if ( ! empty( $details['privacy_url'] ) ) {
				$legal_html .= '<a href="' . esc_url( $details['privacy_url'] ) . '" style="color: #888888; text-decoration: underline;">Tietosuojaseloste (Privacy Policy)</a>';
			}
			$legal_html .= '</p>';
		}

		// Format footer from settings
		$footer_raw = ! empty( $data['footer'] ) ? $data['footer'] : '';
		if ( empty( $footer_raw ) ) {
			$footer_raw = "-- \n--\n{$details['name']}\n{$details['title']}\n{$details['company']}\n{$details['website']}\n{$details['phone']}\nVAT-ID: {$details['vat_id']}\nCorporate ID: {$details['business_id']}";
		}

		$footer_lines = explode( "\n", $footer_raw );
		$footer_html_lines = array();
		foreach ( $footer_lines as $fline ) {
			$escaped = esc_html( rtrim( $fline ) );
			// Make URL clickable
			$escaped = preg_replace( '/(https?:\/\/[^\s<]+)/', '<a href="$1" style="color: #0073aa; text-decoration: underline;" target="_blank">$1</a>', $escaped );
			// Make phone clickable
			$escaped = preg_replace( '/(\+358[\s0-9]+)/', '<a href="tel:$1" style="color: #0073aa; text-decoration: none;">$1</a>', $escaped );
			// Make email clickable
			$escaped = preg_replace( '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', '<a href="mailto:$1" style="color: #0073aa; text-decoration: none;">$1</a>', $escaped );
			$footer_html_lines[] = $escaped;
		}
		$footer_formatted = implode( "<br>\n\t", $footer_html_lines );

		$powered_by_text = ! empty( $data['powered_by'] ) ? esc_html( $data['powered_by'] ) : 'Powered by i4ware Job Seeker AI based to Google Gemini API and OpenAI API';

		$html = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{$data['subject']}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #fafafa; padding: 20px; margin: 0;">
<div style="max-width: 650px; margin: 0 auto; background: #ffffff; padding: 35px; border-radius: 6px; border: 1px solid #e1e4e8; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">

{$letter_html}

{$links_html}

<div style="margin-top: 25px; line-height: 1.6; color: #333333; font-family: monospace; font-size: 13px; border-top: 1px solid #eaeaea; padding-top: 18px;">
	{$footer_formatted}
</div>

{$legal_html}

<div style="margin-top: 22px; padding-top: 14px; border-top: 1px solid #f0f0f0; font-size: 11px; color: #888888; text-align: center; font-style: italic;">
	{$powered_by_text}
</div>

</div>
</body>
</html>
EOT;

		return $html;
	}

	/**
	 * Build plain text email body
	 *
	 * @param array $data Output from prepare_email_data()
	 * @return string
	 */
	public function build_plain_body( $data ) {
		$text = trim( $data['cover_letter'] ) . "\n\n";

		if ( ! empty( $data['cv_links'] ) ) {
			$text .= "Tutustu räätälöityyn interaktiiviseen CV:hen:\n";
			foreach ( $data['cv_links'] as $label => $link ) {
				$text .= "- {$label}: {$link}\n";
			}
			$text .= "\n";
		}

		$text .= trim( $data['footer'] ) . "\n\n";
		$text .= "---\nPowered by i4ware Job Seeker AI based to Google Gemini API and OpenAI API\n";
		return $text;
	}

	/**
	 * Send the job application via WordPress wp_mail()
	 *
	 * Uses site administrator email as From and Reply-To header.
	 *
	 * @param int    $job_id
	 * @param string $to_email       Optional custom recipient override
	 * @param string $custom_subject Optional custom subject override
	 * @param string $custom_body    Optional custom body override
	 * @return array|WP_Error
	 */
	public function send_job_application( $job_id, $to_email = '', $custom_subject = '', $custom_body = '' ) {
		$prep = $this->prepare_email_data( $job_id );
		if ( is_wp_error( $prep ) ) {
			return $prep;
		}

		$recipient = ! empty( $to_email ) ? sanitize_email( $to_email ) : sanitize_email( $prep['contact_email'] );
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			return new WP_Error( 'missing_recipient', 'Ei kelvollista julkista sähköpostiosoitetta määritetty toimeksiannolle.' );
		}

		$subject = ! empty( $custom_subject ) ? sanitize_text_field( $custom_subject ) : $prep['subject'];

		if ( ! empty( $custom_body ) ) {
			$prep['cover_letter'] = $custom_body;
		}

		$html_body = $this->build_html_body( $prep );

		$admin_email = $this->get_admin_email();
		$sender_name = $this->get_sender_name();

		$headers = array(
			'From: ' . $sender_name . ' <' . $admin_email . '>',
			'Reply-To: ' . $admin_email,
			'Content-Type: text/html; charset=UTF-8',
			'X-Mailer: i4ware Job Seeker AI',
			'X-Powered-By: i4ware Job Seeker AI based to Google Gemini API and OpenAI API',
		);

		AI_CV_Tailor_Autopilot_Logger::info( "Sending application email for job ID {$job_id} to {$recipient} from {$admin_email}" );

		$sent = wp_mail( $recipient, $subject, $html_body, $headers );

		if ( ! $sent ) {
			AI_CV_Tailor_Autopilot_Logger::error( "wp_mail failed to send application to {$recipient}" );
			return new WP_Error( 'mail_send_failed', "Sähköpostin lähetys epäonnistui osoitteeseen: {$recipient}. Tarkista sivuston sähköpostiasetukset (SMTP)." );
		}

		$now = current_time( 'Y-m-d H:i:s' );

		// Update post metadata on success
		update_post_meta( $job_id, 'status', 'Applied' );
		update_post_meta( $job_id, 'applied_date', $now );
		update_post_meta( $job_id, 'applied_to_email', $recipient );
		update_post_meta( $job_id, 'applied_subject', $subject );
		update_post_meta( $job_id, 'follow_up_date', date( 'Y-m-d', strtotime( '+7 days' ) ) );

		// If there is an associated ai_cv_application, update it as well
		$app_id = get_post_meta( $job_id, 'generated_application_id', true );
		if ( $app_id ) {
			update_post_meta( $app_id, 'status', 'Applied' );
			update_post_meta( $app_id, 'applied_date', $now );
			update_post_meta( $app_id, 'applied_to_email', $recipient );
		}

		AI_CV_Tailor_Autopilot_Logger::success( "Application successfully sent to {$recipient} on {$now}" );

		return array(
			'success'          => true,
			'job_id'           => $job_id,
			'recipient'        => $recipient,
			'from_email'       => $admin_email,
			'applied_date'     => $now,
			'status'           => 'Applied',
			'message'          => "Hakemus lähetetty onnistuneesti osoitteeseen {$recipient} pääkäyttäjän osoitteella ({$admin_email})!",
		);
	}
}
