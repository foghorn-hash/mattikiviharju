<div class="wrap">
	<h1>Löydetyt toimeksiannot</h1>
	
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Yritys & Rooli</th>
				<th>Lähde</th>
				<th>Match score</th>
				<th>Budjetti / Tuntihinta</th>
				<th>Etätyö / B2B</th>
				<th>Status</th>
				<th>Toiminnot</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$args = array(
				'post_type' => 'freelance_job',
				'posts_per_page' => 50,
				'post_status' => 'any',
			);
			$query = new WP_Query( $args );
			
			if ( $query->have_posts() ) :
				while ( $query->have_posts() ) : $query->the_post();
					$post_id = get_the_ID();
					$company = get_post_meta( $post_id, 'company_name', true );
					$role = get_post_meta( $post_id, 'role_title', true );
					$source = get_post_meta( $post_id, 'source', true );
					$score = get_post_meta( $post_id, 'match_score', true );
					$budget = get_post_meta( $post_id, 'budget', true ) . ' / ' . get_post_meta( $post_id, 'hourly_rate', true );
					$remote = get_post_meta( $post_id, 'remote', true );
					$b2b = get_post_meta( $post_id, 'b2b_possible', true );
					$status = get_post_meta( $post_id, 'status', true );
					?>
					<tr id="opportunity-<?php echo esc_attr( $post_id ); ?>">
						<td><strong><?php echo esc_html( $company ); ?></strong><br><?php echo esc_html( $role ); ?></td>
						<td><?php echo esc_html( $source ); ?></td>
						<td><?php echo esc_html( $score ); ?></td>
						<td><?php echo esc_html( $budget ); ?></td>
						<td><?php echo esc_html( $remote . ' / ' . $b2b ); ?></td>
						<td class="status-col"><?php echo esc_html( $status ); ?></td>
						<td>
							<button class="button action-analyze" data-id="<?php echo esc_attr( $post_id ); ?>">Analysoi</button>
							<button class="button action-generate" data-id="<?php echo esc_attr( $post_id ); ?>">Luo hakemus</button>
							<button class="button action-reject" data-id="<?php echo esc_attr( $post_id ); ?>">Hylkää</button>
							<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button">Muokkaa</a>
						</td>
					</tr>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<tr>
					<td colspan="7">Ei toimeksiantoja vielä.</td>
				</tr>
				<?php
			endif;
			?>
		</tbody>
	</table>
</div>
