<?php

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */


class WPCPN_Admin {

	use WPCPN_Singleton;

	const NONCE = 'wpcpn_nonce_form';

	/**
	 * Holds a reference to the model class
	 *
	 * @since    1.0.0
	 *
	 * @var      Model
	 */
	public $model = null;

	public $post_selector;


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


		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );


		$status = apply_filters('wpcpn_activate_feature_requests', true );
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
				$request = WPCPN_Requests::get_request($blog_id, $post_id);

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
		if ( 'edit-post' == $screen->id ) {
			wp_enqueue_style( 'wp-jquery-ui-dialog');
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


		if (  'edit-post' == $screen->id ) {
			wp_enqueue_script( 'wpcpn_requisition_modal' , plugins_url( 'assets/js/edit-posts.js' , __FILE__),  array('jquery-ui-dialog'));
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
