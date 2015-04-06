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
	use WPCPN_Singleton;

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '0.5';

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
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'query_vars', array( $this, 'query_vars') );
		add_filter( 'category_link', array( $this, 'category_link'), 10, 2);


		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

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
		self::rewrite_rules();
		flush_rewrite_rules();
	}

	private static function rewrite_rules() {
		//Regras para busca
		add_rewrite_rule('portal/search/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?wpcpn_query_type=search&wpcpn_network_search=$matches[1]&paged=$matches[2]', 'top');
		add_rewrite_rule('portal/search/([^/]+)/?', 'index.php?wpcpn_query_type=search&wpcpn_network_search=$matches[1]', 'top');

		//Regras para taxonomias
		add_rewrite_rule('portal/categoria/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=category&wpcpn_network_term=$matches[1]&paged=$matches[2]' , 'top');
		add_rewrite_rule('portal/categoria/([^/]+)/?', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=category&wpcpn_network_term=$matches[1]' , 'top');

		add_rewrite_rule('portal/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=$matches[1]&wpcpn_network_term=$matches[2]&paged=$matches[3]' , 'top');
		add_rewrite_rule('portal/([^/]+)/([^/]+)/?', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=$matches[1]&wpcpn_network_term=$matches[2]' , 'top');

	}


	public function init() {
		self::rewrite_rules();
		$this->load_plugin_textdomain();
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

	/**
	 * Define o link para que a listagem de categorias sejam globais.
	 * @param  string $catlink Link para a categoria
	 * @param  id $catid   id da categoria
	 * @return string          link filtrado
	 */
	public function category_link( $catlink, $catid ) {
		$term_obj = get_term($catid, 'category');
		return get_site_url( 1 ) . '/portal/categoria/' . $term_obj->slug;
	}


	/**
	 * Define nossas próprias query_vars
	 * @param  array $vars query_vars que já estão registradas
	 * @return array       query_vars
	 */
	public function query_vars( $vars )  {
		$vars[] = 'wpcpn_network_term';
		$vars[] = 'wpcpn_network_tax';
		$vars[] = 'wpcpn_query_type';
		$vars[] = 'wpcpn_network_search';
		return $vars;
	}

	/**
	 * Carrega o template associado as nossas rewrite_rules
	 */
	public function template_redirect() {
		global $wp_query;

		if ( isset($wp_query->query_vars['wpcpn_query_type']) ) {

			$query_type = get_query_var('wpcpn_query_type');
			$wpcpn_posts = $this->query();

			switch ( $query_type) {
				case 'taxonomy':
					$tax = esc_sql( get_query_var('wpcpn_network_tax') );

					if ( locate_template("wpcpn-network-{$tax}.php") ) {
						include(locate_template("wpcpn-network-{$tax}.php"));
					}

				break;
				case 'search':
					if ( locate_template("wpcpn-network-search.php") ) {
						include(locate_template("wpcpn-network-search.php"));
					}
				break;
			}

			$this->cleanQuery();

			die();

		}
	}

	/**
	 * Realiza uma consulta pelos parâmetros passados
	 * @return array object array contendo todos os posts encontrados
	 */
	public function query() {
		$query_type = get_query_var('wpcpn_query_type');
		$wpcpn_posts = array();

		switch ($query_type) {
			case 'taxonomy':
				$wpcpn_posts = wpcpn_get_network_posts(
					array(
						'taxonomy_slug' => esc_sql( get_query_var('wpcpn_network_tax') ),
						'term_slug' 	=> esc_sql( get_query_var('wpcpn_network_term') ),
						'size'			=> 10,
						'paged'			=> max(1, get_query_var('paged') )
					)
				);
			break;
			case 'search':
				$wpcpn_posts = wpcpn_get_network_posts(
					array(
						'size' 		=> 10,
						'paged'		=> max(1, get_query_var('paged') ),
						's' 		=> esc_sql( get_query_var('wpcpn_network_search') )
					)
				);
			break;
		}

		return $wpcpn_posts;
	}

	public function cleanQuery() {

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

}


