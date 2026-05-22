<div class="wrap ai-cv-tailor-wrap">
	<h1>Tilastot</h1>
	
	<?php
	$stats_class = new AI_CV_Tailor_Statistics();
	$summary = $stats_class->get_stats_summary();
	?>
	
	<div class="ai-cv-card">
		<h2>Yleiskatsaus</h2>
		<p><strong>Kokonaisavaukset:</strong> <?php echo esc_html( $summary['total'] ); ?></p>
		
		<h3>Avaukset kohderyhmittäin</h3>
		<?php if ( empty( $summary['by_audience'] ) ) : ?>
			<p>Ei dataa.</p>
		<?php else : ?>
			<ul>
			<?php foreach ( $summary['by_audience'] as $row ) : ?>
				<li><strong><?php echo esc_html( strtoupper( $row['audience'] ) ); ?>:</strong> <?php echo esc_html( $row['count'] ); ?></li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	
	<div class="ai-cv-card">
		<h2>Viimeisimmät avaukset</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Aika</th>
					<th>Kohderyhmä</th>
					<th>Hakemus ID</th>
					<th>IP (Hash)</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $summary['recent'] ) ) : ?>
					<tr><td colspan="4">Ei avauksia vielä.</td></tr>
				<?php else : ?>
					<?php foreach ( $summary['recent'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['timestamp'] ); ?></td>
						<td><?php echo esc_html( strtoupper( $row['audience'] ) ); ?></td>
						<td><a href="<?php echo admin_url( 'post.php?post=' . intval( $row['application_id'] ) . '&action=edit' ); ?>"><?php echo intval( $row['application_id'] ); ?></a></td>
						<td><span title="<?php echo esc_attr( $row['user_agent'] ); ?>"><?php echo esc_html( substr( $row['ip_hash'], 0, 8 ) ); ?>...</span></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

</div>
