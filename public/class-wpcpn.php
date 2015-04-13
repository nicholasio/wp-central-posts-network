<?php
/**
 * WordPress Central Posts Network
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholas@iotecnologia.com.br>
 * @license   GPL-2.0+
 */

/**
 * Main Plugin Class
 *
 *
 * @package WPCPN
 * @author  Nícholas André <nicholas@iotecnologia.com.br>
 */
class WPCPN {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wpcpn';

	/**
	 * Holds the cache config array
	 */
	public static $cache_config;


	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain') );

		self::$cache_config = apply_filters( 'wpcpn_cache_config', false );
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
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		if ( $network_wide ) {

			switch_to_blog(1);
			self::main_single_activate();
			self::install_tables();
			restore_current_blog();

		} else {
			echo "<p>Você precisa do Multisite Habilitado</p>";
			die();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( $network_wide ) {
			// Get all blog ids
			$blog_ids = self::get_blog_ids();
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::single_deactivate();
				restore_current_blog();
			}
		} else {
			self::single_deactivate();
		}


	 }

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) { }

	/**
	 * Install tables used by the plugin
	 * @return none
	 */
	public static function install_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wpcpn_featured_requests';

		$sql = 'CREATE TABLE ' . $table_name .' (
			ID bigint(11) NOT NULL AUTO_INCREMENT,
			blog_id bigint(11) NOT NULL,
			post_id bigint(11) NOT NULL,
			created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			published TIMESTAMP,
			message TEXT,
			status char(2) DEFAULT "AW",
			UNIQUE KEY ID (ID)
		);';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);

		$tables_version = '1.0.0';

		update_option('wpcpn_db_version', $tables_version);
	}


	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	public static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function main_single_activate() {
		//self::rewrite_rules();
		flush_rewrite_rules();
	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}
}


