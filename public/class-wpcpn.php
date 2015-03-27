<?php
/**
 * WordPress Central Network posts
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholasandre@ufersa.edu.br>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2014 UFERSA
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 *
 * @package WPCPN
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */
class WPCPN {

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
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public $model = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'query_vars', array( $this, 'query_vars') );
		add_filter( 'category_link', array( $this, 'category_link'), 10, 2);

		if ( get_current_blog_id() != 1 ) {
			//@TODO Usar o filtro wpcpn_activate_feature_requests
			$status = false;

			if ( $status ) {
				add_filter( 'post_row_actions', array( $this, 'post_row_actions') );
				add_filter( 'manage_edit-post_columns', array($this, 'add_post_columns') );
				add_action( 'manage_posts_custom_column' , array( $this, 'post_custom_columns' ) );
				add_filter( 'manage_edit-post_sortable_columns', array( $this, 'post_sortable_columns') );	
			}
			
		}

		if ( is_admin() ) {
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
			add_action('admin_footer', array( $this, 'admin_footer') );
			$this->model = new WPCPN_Admin_Public_Model();
		}
			
			

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
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
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
	 * Instala as tabelas requeridas pelo plugin no banco de dados
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
		add_rewrite_rule('portal/categoria/([^/]+)/page/?([0-9]{1,})/?$', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=category&wpcpn_network_term=$matches[1]&paged=$matches[2]' , 'top');
		add_rewrite_rule('portal/categoria/([^/]+)/?', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=category&wpcpn_network_term=$matches[1]' , 'top');
		

		add_rewrite_rule('portal/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=$matches[1]&wpcpn_network_term=$matches[2]&paged=$matches[3]' , 'top');
		add_rewrite_rule('portal/([^/]+)/([^/]+)/?', 	'index.php?wpcpn_query_type=taxonomy&wpcpn_network_tax=$matches[1]&wpcpn_network_term=$matches[2]' , 'top');

	}


	public function init() {
		self::rewrite_rules();
		$this->load_plugin_textdomain();
	}

	public function admin_enqueue_scripts() {
		global $pagenow;
		if ( $pagenow == 'edit.php' ) {
			wp_enqueue_script (  'wpcpn_requisition_modal' ,       
                        plugins_url( 'assets/js/admin-public.js' , __FILE__)  ,       
                        array('jquery-ui-dialog')); 
		                   
		    wp_enqueue_style (  'wp-jquery-ui-dialog');
		}
	}

	public function admin_footer() {
		ob_start();
		?>
		<div style="display:none" id="wpcpn-modal-content">
			<h3>Você está solicitando destaque para o post:</h3>
			<h4 class="wpcpn-post-title">"<span></span>"</h4>
			<p>Por Favor, descreva os motivos da sua solicitação.
				Esta solicitação não irá colocar a notícias automaticamente na página inicial, ela
				precisa ser aprovada por um administrador do portal.</p>
			<p>As notícias podem sofrer alterações pela ASSECOM antes de ir para a página principal.</p>
			<h3>Motivo:</h3>
			<input type="hidden" name="wpcpn-post-id" value="" />
			<input type="hidden" name="wpcpn-blog-id" value="" />

			<textarea name="wpcpn-request-reason-text" class="wpcpn-request-reason-text" cols="30" rows="10"></textarea>
		</div>
		<?php
		$content = ob_get_contents();
		echo $content;
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
	 * Filtra os links que são exibidos abaixo de cada post na listagem de posts
	 * Função callback para o filtro post_row_actions
	 * @see  WPCPN::__construct()
	 * @param  Array $actions Contém todos os links
	 * @return Array          Array contendo os links modificados
	 */
	public function post_row_actions( $actions ) {
		$actions['wpcpn_feature'] = '<a href="#" class="wpcpn-open-modal" data-wpcpn-post-title="'.get_the_title().'" data-wpcpn-post-id="' . get_the_ID() . '" data-wpcpn-blog-id="' . get_current_blog_id() . '">Solicitar Destaque na Home</a>';
		return $actions;
	}

	public function add_post_columns( $columns ) {
	 	$column_meta = array( 'wpcpn_requests' => 'Status da Solicitação de Destaque' );
		$columns = array_slice( $columns, 0, 2, true ) + $column_meta + array_slice( $columns, 2, NULL, true );
		return $columns;
	}

	public function post_custom_columns( $column ) {
		global $post;

		$blog_id = get_current_blog_id();
		$post_id = $post->ID;
		switch ( $column ) {
			case 'wpcpn_requests':
				$request = WPCPN_Admin_Public_Model::get_request($blog_id, $post_id);

				if ( $request == NULL )
					echo 'Não Solicitado';
				else {

					if ( $request->status == 'AW' )
						echo '<span class="dashicons dashicons-visibility" title="Aguardando Análise"></span> <br />Aguardando Análise. </br> Solicitado em ' . date('d/m/Y H:i:s', strtotime($request->created));
					else if ( $request->status == 'AP' && $request->published == '0000-00-00 00:00:00')
						echo '<span class="dashicons dashicons-yes" title="Aprovado"></span> <br />Aprovado (Mas não publicada)<br />Solicitado em ' . date('d/m/Y H:i:s', strtotime($request->created) );
					else if ( $request->status == 'AP' && $request->published != '0000-00-00 00:00:00')
						echo '<span class="dashicons dashicons-yes" title="Aprovado"></span> <br />Publicado no passado: ' . date('d/m/Y H:i:s', strtotime($request->published) );
					else if ( $request->status == 'PB' ) 
						echo '<span class="dashicons dashicons-yes" title="Aprovado"></span><span title="Publicado" class="dashicons dashicons-admin-home"></span> <br />Aprovado e Publicado.<br /> Publicado em ' . date('d/m/Y H:i:s', strtotime($request->published));
					else if ( $request->status == 'RJ' ) 
						echo '<span class="dashicons dashicons-no" title="Rejeitado"></span><br /> <span style="color: red">Rejeitado</span> <br /> Solicitiado em ' . date('d/m/Y H:i:s', strtotime($request->created));
				}


			break;
		}
	}

	public function post_sortable_columns( $columns ) {
		$columns['wpcpn_requests'] = 'Status da Solicitação de Destaque';
		return $columns;
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


//API

function wpcpn_get_grouped_posts_list( $group_name, $section_name ) {
	return WPCPN_Admin_Model::getPostsLists( $group_name, $section_name );
}

function wpcpn_get_posts_list( $group_name, $section_name ) {
	$arrPosts = wpcpn_get_grouped_posts_list( $group_name, $section_name );

	if ( ! isset($arrPosts['posts']) ||  ! is_array($arrPosts['posts']) ) return false; 
	$nonGrouped = array();
	foreach($arrPosts['posts'] as $site_id => $site_posts) {
		foreach ($site_posts as $post_id) {
			$nonGrouped[] = array('post_id' => $post_id, 'site_id' => $site_id);
		}
	} 

	return $nonGrouped;
}

/*
 * @TODO Object Cache
 */
function wpcpn_show_posts_section( $group_name, $section_name, Array $template, $params = array() )  {
	/*$slug		= $template['template_slug'];
	$name		= isset($template['template_name']) ? $template['template_name'] : '';*/
	
	$section	= wpcpn_get_posts_section( $group_name, $section_name, $params );

	if ( $section ) {
		/*global $post;
	    foreach($section as $wpcpn_post) {
	        switch_to_blog($wpcpn_post['site_id']);
	        $post = get_post( $wpcpn_post['post_id'] );
	        setup_postdata($post);

	        include(locate_template($slug . '-' . $name . '.php'));

	        wp_reset_postdata();
	        restore_current_blog();
	    }*/
	    wpcpn_show_posts($section, $template);	
	} else {
		echo '<p>Seção não definida.</p>';
	}
}

function wpcpn_show_posts( $posts, Array $template ) {
	$slug	= $template['template_slug'];
	$name	= isset($template['template_name']) ? $template['template_name'] : '';

	if ( $posts && is_array($posts) ) {
		global $post;
		foreach ($posts as $wpcpn_post) {
			switch_to_blog($wpcpn_post['site_id']);
	        $post = get_post( $wpcpn_post['post_id'] );
	        setup_postdata($post);

	        include(locate_template($slug . '-' . $name . '.php'));

	        wp_reset_postdata();
	        restore_current_blog();
		}
	}
}

function wpcpn_get_posts_section(  $group_name, $section_name, $params = array() ) {
	$section 	= wpcpn_get_posts_list( $group_name, $section_name );
	if ( ! is_array($section) ) {
		return false;
	}

	$params 	= wp_parse_args($params, 
					array(
						'limit' => count($section),
						'offset' => 0
					)
				  );

	$section 	= array_slice($section, $params['offset'], $params['limit'], true);

	return $section;
}