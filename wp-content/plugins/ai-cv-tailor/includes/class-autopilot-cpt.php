<?php

class AI_CV_Tailor_Autopilot_CPT {

	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Freelance Toimeksiannot',
			'singular_name'      => 'Freelance Toimeksianto',
			'menu_name'          => 'Toimeksiannot',
			'name_admin_bar'     => 'Toimeksianto',
			'add_new'            => 'Luo uusi',
			'add_new_item'       => 'Luo uusi toimeksianto',
			'new_item'           => 'Uusi toimeksianto',
			'edit_item'          => 'Muokkaa',
			'view_item'          => 'Näytä',
			'all_items'          => 'Kaikki toimeksiannot',
			'search_items'       => 'Etsi',
			'not_found'          => 'Ei löytynyt.',
			'not_found_in_trash' => 'Ei löytynyt roskakorista.'
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false, // Handled completely by custom Admin UI
			'show_in_menu'       => false, 
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		);

		register_post_type( 'freelance_job', $args );
	}
}
