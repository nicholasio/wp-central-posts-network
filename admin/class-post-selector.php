<?php

/**
 * Post Selector Class, Handle the post selector screen
 *
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */


class WPCPN_Post_Selector {

	private $screen_id;

	public function __construct() {
		$plugin = WPCPN::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		add_action( 'wp_ajax_wpcpn_get_posts_from_blog' , 'WPCPN_Post_Selector_Model::getPostsFromBlog' );
		add_action( 'wp_ajax_wpcpn_save_posts_list', 'WPCPN_Post_Selector_Model::savePostsList');
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->screen_id = add_menu_page(
			__( 'Network Posts Selector', $this->plugin_slug ),
			__( 'Network Posts Selector', $this->plugin_slug ),
			'manage_network',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-list-view',
			78
		);

		$status = apply_filters('wpcpn_activate_feature_requests', true);

		if ( $status ) {
			 add_submenu_page(
				$this->plugin_slug,
				__('Featured Requests', 'wpcpn'),
				__('Requests', 'wpcpn'),
				'manage_network',
				$this->plugin_slug . '_requests',
				array( $this, 'display_plugin_requests_admin_page')
			);
		}

		add_submenu_page(
			$this->plugin_slug,
			__('Older Publications', 'wpcpn'),
			__('History', 'wpcpn'),
			'manage_network',
			$this->plugin_slug . '_old_requests',
			array( $this, 'display_plugin_old_requests_admin_page')
		);
	}

	public function enqueue_admin_styles() {
		$screen = get_current_screen();

		if ( $this->screen_id == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WPCPN::VERSION );
		}

	}

	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if ( $this->screen_id == $screen->id ) {
			wp_register_script( $this->plugin_slug . '-fast-live-filters',
								plugins_url( 'assets/js/jquery-fast-live-filter.js', __FILE__),
								null,
								WPCPN::VERSION
				 			 );
			wp_enqueue_script( $this->plugin_slug . '-admin-script',
							   plugins_url( 'assets/js/admin.js', __FILE__ ),
							   array( $this->plugin_slug . '-fast-live-filters','jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-autocomplete' , 'jquery' ),
							   WPCPN::VERSION
							);
			wp_localize_script( $this->plugin_slug . '-admin-script', 'WPCPN_Variables',
								array(
							    	'nonce' => wp_create_nonce( WPCPN_Admin::NONCE ),
							    )
							  );
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$this->model = new WPCPN_Post_Selector_Model();
		include_once( 'views/admin.php' );
	}

	public function display_plugin_requests_admin_page() {
		include_once( 'views/admin-requests.php');
	}
	public function display_plugin_old_requests_admin_page() {
		include_once( 'views/admin-old-requests.php');
	}


}
