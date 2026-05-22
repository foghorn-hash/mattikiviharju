<?php

class AI_CV_Tailor_Statistics {

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ai_cv_statistics';
	}

	public static function create_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			application_id bigint(20) NOT NULL,
			audience varchar(50) NOT NULL,
			token varchar(64) NOT NULL,
			timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			ip_hash varchar(64) NOT NULL,
			user_agent text NOT NULL,
			referrer text NOT NULL,
			PRIMARY KEY  (id),
			KEY application_id (application_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function init() {
		add_action( 'ai_cv_tailor_track_view', array( $this, 'track_view' ), 10, 3 );
	}

	public function track_view( $application_id, $audience, $token ) {
		global $wpdb;
		$table_name = self::get_table_name();

		// Anonymize IP (hash it)
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$ip_hash = empty( $ip ) ? '' : wp_hash( $ip );
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$referrer = $_SERVER['HTTP_REFERER'] ?? '';

		$wpdb->insert( 
			$table_name, 
			array( 
				'application_id' => $application_id,
				'audience'       => $audience,
				'token'          => $token,
				'timestamp'      => current_time( 'mysql' ),
				'ip_hash'        => $ip_hash,
				'user_agent'     => substr( $user_agent, 0, 500 ),
				'referrer'       => substr( $referrer, 0, 500 )
			) 
		);
	}
	
	public function get_stats_summary() {
		global $wpdb;
		$table_name = self::get_table_name();
		
		$total_views = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
		$views_by_audience = $wpdb->get_results("SELECT audience, COUNT(*) as count FROM $table_name GROUP BY audience", ARRAY_A);
		$recent_views = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 20", ARRAY_A);
		
		return array(
			'total' => $total_views,
			'by_audience' => $views_by_audience,
			'recent' => $recent_views
		);
	}
}
