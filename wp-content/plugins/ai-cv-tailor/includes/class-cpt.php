<?php

class AI_CV_Tailor_CPT {

	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		// We'll manage meta boxes primarily through our custom UI, but we can register standard ones if needed.
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Työpaikkailmoitukset',
			'singular_name'      => 'Työpaikkailmoitus',
			'menu_name'          => 'Työpaikkailmoitukset',
			'name_admin_bar'     => 'Työpaikkailmoitus',
			'add_new'            => 'Luo uusi',
			'add_new_item'       => 'Luo uusi työpaikkailmoitus',
			'new_item'           => 'Uusi työpaikkailmoitus',
			'edit_item'          => 'Muokkaa',
			'view_item'          => 'Näytä',
			'all_items'          => 'Kaikki työpaikkailmoitukset',
			'search_items'       => 'Etsi',
			'not_found'          => 'Ei löytynyt.',
			'not_found_in_trash' => 'Ei löytynyt roskakorista.'
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // Handled by our custom menu
			'query_var'          => true,
			'rewrite'            => false, // We use custom routing
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ) // We use custom meta for the rest
		);

		register_post_type( 'ai_cv_application', $args );
	}
}
