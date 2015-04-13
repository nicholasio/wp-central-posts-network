<?php

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */


class WPCPN_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;



	const NONCE = 'wpcpn_nonce_form';

	/**
	 * Holds a reference to the model class
	 *
	 * @since    0.5
	 *
	 * @var      Model
	 */
	public $model = null;

	/**
	 * Holds a reference to the post selector class
	 *
	 * @since 0.5
	 *
	 * @var Object
	 */
	public $post_selector;


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     0.1
	 */
	private function __construct() {
		$plugin = WPCPN::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );


		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );


		$status = apply_filters('wpcpn_activate_featured_requests', true );

		if ( get_current_blog_id() != 1 && $status ) {
				add_filter( 'post_row_actions', array( $this, 'post_row_actions') );
				add_filter( 'manage_edit-post_columns', array($this, 'add_post_columns') );
				add_action( 'manage_posts_custom_column' , array( $this, 'post_custom_columns' ) );
				add_filter( 'manage_edit-post_sortable_columns', array( $this, 'post_sortable_columns') );
				add_action( 'admin_footer', array($this, 'admin_footer') );
		}

		if ( WPCPN_IS_MAIN_SITE && current_user_can('manage_network') ) {
			$this->post_selector = new WPCPN_Post_Selector();
		}
	}

	/**
	 * Return an instance of theclass.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of the class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Filter the links that are displayed bellow each post on the post listings]
	 * Callback function for the post_row_actions filter
	 *
	 * @see WPCPN_Admin::__construct()
	 * @param  Array $actions Contains all the links
	 * @return Array          Array containing the modified links
	 */
	public function post_row_actions( $actions ) {
		$actions['wpcpn_feature'] = '<a href="#" class="wpcpn-open-modal" data-wpcpn-post-title="'.get_the_title().'" data-wpcpn-post-id="' . get_the_ID() . '" data-wpcpn-blog-id="' . get_current_blog_id() . '">' . __("Request featured in home", 'wpcpn') . '</a>';
		return $actions;
	}

	/**
	 * Adds a new column to the post editor escreen
	 *
	 * @see WPCPN_Admin::__construct()
	 * @param  Array $columns Contains all the columns
	 * @return Array $columns Return the news and modified columns
	 */
	public function add_post_columns( $columns ) {
	 	$column_meta = array( 'wpcpn_requests' => __('Featured Request Status', 'wpcpn')  );
		$columns = array_slice( $columns, 0, 2, true ) + $column_meta + array_slice( $columns, 2, NULL, true );
		return $columns;
	}

	/**
	 * Define what are display for each column row
	 *
	 * @see WPCPN_Admin::__construct()
	 * @param Array $columns Contains all the columns
	 * @return Array $columns Return the text displayed for each column row
	 */
	public function post_custom_columns( $column ) {
		global $post;

		$blog_id = get_current_blog_id();
		$post_id = $post->ID;
		switch ( $column ) {
			case 'wpcpn_requests':
				$request = WPCPN_Requests::get_request($blog_id, $post_id);

				if ( $request == NULL )
					echo _('Unsolicited', 'wpcpn');
				else {

					if ( $request->status == 'AW' )
						echo '<span class="dashicons dashicons-visibility" title="' . __('Waiting review', 'wpcpn') . '"></span> <br />' . __('Waiting review', 'wpcpn') . '. </br> ' . __('Requested in') . ' ' . date_i18n( get_option('date_format') , strtotime($request->created));
					else if ( $request->status == 'AP' && $request->published == '0000-00-00 00:00:00')
						echo '<span class="dashicons dashicons-yes" title="'.__('Approved', 'wpcpn').'"></span> <br />' . __('Approved, but not published', 'wpcpn') . '. </br> ' . __('Requested in') . ' ' .  date_i18n( get_option('date_format') , strtotime($request->created) );
					else if ( $request->status == 'AP' && $request->published != '0000-00-00 00:00:00')
						echo '<span class="dashicons dashicons-yes" title="'.__('Approved', 'wpcpn').'"></span> <br />Publicado no passado: ' . date_i18n( get_option('date_format') , strtotime($request->published) );
					else if ( $request->status == 'PB' )
						echo '<span class="dashicons dashicons-yes" title="'.__('Approved', 'wpcpn').'">
							 </span><span title="'.__('Published', 'wpcpn').'" class="dashicons dashicons-admin-home"></span>
							 <br />'.__('Approved and Published', 'wpcpn').'.<br /> ' . __('Published in', 'wpcpn') . ' ' . date_i18n( get_option('date_format') , strtotime($request->published));
					else if ( $request->status == 'RJ' )
						echo '<span class="dashicons dashicons-no" title="'.__('Rejected', 'wpcpn').'"></span><br /> <span style="color: red">'.__('Rejected', 'wpcpn').'</span> <br /> '. ' </br> ' . __('Requested in') . ' ' . date_i18n( get_option('date_format') , strtotime($request->created));
				}


			break;
		}
	}

	/**
	 * Define what columns are sortable
	 */
	public function post_sortable_columns( $columns ) {
		$columns['wpcpn_requests'] = __('Featured Request Status', 'wpcpn');
		return $columns;
	}

	/**
	 * HTML that are displayed in the footer
	 */
	public function admin_footer() {

		$screen = get_current_screen();
		if ( 'edit-post' != $screen->id )
			return;

		ob_start();
		?>
		<div style="display:none" id="wpcpn-modal-content">
			<h3><?php _e('You are requesting highlight for this post in the main site', 'wpcpn'); ?>:</h3>
			<h4 class="wpcpn-post-title">"<span></span>"</h4>
			<p><?php _e('Please describe the reasons for your request. This request will not automatically put this post on the main site, it
						 must be approved by a super administrator.', 'wpcpn'); ?></p>
			<p><?php _e('Be aware that your post can be edited before posting in the main site.', 'wpcpn'); ?></p>
			<h3><?php _e('Reasons', 'wpcpn'); ?>:</h3>
			<input type="hidden" name="wpcpn-post-id" value="" />
			<input type="hidden" name="wpcpn-blog-id" value="" />

			<textarea name="wpcpn-request-reason-text" class="wpcpn-request-reason-text" cols="30" rows="10"></textarea>
		</div>
		<?php
		$content = ob_get_contents();
		echo $content;
	}


	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 *
	 * @since     0.5.0
	 */
	public function enqueue_admin_styles() {
		$screen = get_current_screen();

		if ( 'edit-post' == $screen->id ) {
			wp_enqueue_style( 'wp-jquery-ui-dialog');
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 *
	 * @since     0.5
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();

		if (  'edit-post' == $screen->id ) {
			wp_enqueue_script( 'wpcpn_requisition_modal' , plugins_url( 'assets/js/edit-posts.js' , __FILE__),  array('jquery-ui-dialog'));

			$editPostsl10n = array(
				'dialog_title'      => __('Informations about your request', 'wpcon'),
				'btn_send'          => __('Send', 'wpcpn'),
				'btn_close'         => __('Close', 'wpcpn'),
				'request_duplicate' => __('Duplicated Request', 'wpcpn'),
				'request_success'   => __('Successfully sent request', 'wpcpn'),
				'request_error'     => __('It happened a problem with your request', 'wpcpn')
 			);

			wp_localize_script( 'wpcpn_requisition_modal', 'EditPosts', $editPostsl10n );
		}

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
