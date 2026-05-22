<?php

class AI_CV_Tailor_Autopilot_Logger {

	public static function log( $message, $level = 'INFO' ) {
		$upload_dir = wp_upload_dir();
		$plugin_upload_dir = trailingslashit( $upload_dir['basedir'] ) . 'ai-cv-tailor';
		
		if ( ! file_exists( $plugin_upload_dir ) ) {
			wp_mkdir_p( $plugin_upload_dir );
		}

		$log_file = $plugin_upload_dir . '/autopilot.log';
		$timestamp = date( 'Y-m-d H:i:s' );
		
		// If message is array or object, json_encode it
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message );
		}
		
		$formatted_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
		
		error_log( $formatted_message, 3, $log_file );
		
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			if ( $level === 'ERROR' ) {
				WP_CLI::error( $message, false );
			} elseif ( $level === 'SUCCESS' ) {
				WP_CLI::success( $message );
			} else {
				WP_CLI::log( $message );
			}
		}
	}

	public static function info( $message ) {
		self::log( $message, 'INFO' );
	}

	public static function error( $message ) {
		self::log( $message, 'ERROR' );
	}
	
	public static function success( $message ) {
		self::log( $message, 'SUCCESS' );
	}
	
	public static function get_logs() {
		$upload_dir = wp_upload_dir();
		$log_file = trailingslashit( $upload_dir['basedir'] ) . 'ai-cv-tailor/autopilot.log';
		
		if ( file_exists( $log_file ) ) {
			// Get last 1000 lines approx
			$content = file_get_contents( $log_file );
			return htmlspecialchars( $content );
		}
		
		return 'Ei lokitietoja saatavilla.';
	}
	
	public static function clear_logs() {
		$upload_dir = wp_upload_dir();
		$log_file = trailingslashit( $upload_dir['basedir'] ) . 'ai-cv-tailor/autopilot.log';
		
		if ( file_exists( $log_file ) ) {
			unlink( $log_file );
		}
	}
}
