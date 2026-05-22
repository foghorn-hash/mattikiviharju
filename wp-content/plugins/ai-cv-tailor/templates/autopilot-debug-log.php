<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-logger.php';

if ( isset( $_POST['clear_autopilot_logs'] ) && check_admin_referer( 'clear_autopilot_logs_nonce' ) ) {
	AI_CV_Tailor_Autopilot_Logger::clear_logs();
	echo '<div class="notice notice-success is-dismissible"><p>Lokit tyhjennetty.</p></div>';
}

$logs = AI_CV_Tailor_Autopilot_Logger::get_logs();
?>
<div class="wrap">
	<h1>Autopilot Debug Log</h1>
	<p>Tässä on Autopilotin suoritusloki (fetch, analyze, generate). Uusin tapahtuma on lopussa.</p>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'clear_autopilot_logs_nonce' ); ?>
		<p>
			<input type="submit" name="clear_autopilot_logs" class="button button-secondary" value="Tyhjennä Loki" onclick="return confirm('Haluatko varmasti tyhjentää lokin?');">
		</p>
	</form>

	<div style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: scroll; font-family: monospace;">
		<pre style="margin: 0; white-space: pre-wrap;"><?php echo $logs; ?></pre>
	</div>
</div>
