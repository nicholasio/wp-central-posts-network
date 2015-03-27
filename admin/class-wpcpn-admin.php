<?php

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */


class WPCPN_Admin {

	const NONCE = 'wpcpn_nonce_form';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	
	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	protected $plugin_screen_sub_hook_suffix = null;

	/**
	 * Holds a reference to the model class
	 *
	 * @since    1.0.0
	 *
	 * @var      Model
	 */
	public $model = null;


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		

		$plugin = WPCPN::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
		add_action( 'wp_ajax_wpcpn_get_posts_from_blog' , 'WPCPN_Admin_Model::getPostsFromBlog' );
		add_action( 'wp_ajax_wpcpn_save_posts_list', 'WPCPN_Admin_Model::savePostsList');
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * A parte administrativa só deve ser utilizado por Super Admins e no site principal da rede
		 */
		if( /*is_super_admin() || */ ! WPCPN_IS_MAIN_SITE ) {
			return;
		}

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WPCPN::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
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
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_menu_page(
			__( 'Seletor de Posts', $this->plugin_slug ),
			__( 'Seletor de Posts', $this->plugin_slug ),
			'manage_network',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-list-view',
			78
		);

		$status = apply_filters('wpcpn_activate_feature_requests', true);

		if ( $status ) {
			$this->plugin_screen_sub_hook_suffix = add_submenu_page(
				$this->plugin_slug,
				'Solicitações de Destaque',
				'Solicitações',
				'manage_network',
				$this->plugin_slug . '_requests',
				array( $this, 'display_plugin_requests_admin_page')
			);
		}

		add_submenu_page(
			$this->plugin_slug,
			'Publicações Antigas',
			'Histórico',
			'manage_network',
			$this->plugin_slug . '_old_requests',
			array( $this, 'display_plugin_old_requests_admin_page')
		);



		

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$this->model = new WPCPN_Admin_Model();
		include_once( 'views/admin.php' );
	}

	public function display_plugin_requests_admin_page() {
		include_once( 'views/admin-requests.php');
	}
	public function display_plugin_old_requests_admin_page() {
		include_once( 'views/admin-old-requests.php');
	}
	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

}
