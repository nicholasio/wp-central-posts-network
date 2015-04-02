<?php

/**
 * Plugin Admin Menu Model Class
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholasandre@ufersa.edu.br>
 */

class WPCPN_Admin_Model {

	const META_KEY = 'wpcpn_posts_list_';
	/**
	 * Id's dos sites instalados na rede
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public $blogs_ids;

	/**
	 * Contém todos os posts de todos os blogs indexados pelo id do blog e pelo post_type
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public $arrPosts;

	/**
	 * Carrega todas as informações referentes ao Model
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		$this->blogs_ids 	= WPCPN::get_blog_ids();
		$this->arrPosts 	= $this->getAllPostsFromBlogs();
	}

	/**
	 * Popula o array $arrPosts
	 *
	 * $arrPosts = array( BLOG_ID_1 =>
	 *						array( POST_TYPE_1 => array(....),
	 * 							... ,
	 *					   		POST_TYPE_N => array( .... )
	 *						),
	 *						BLOG_ID_N => ....
	 *				);
	 * @since     1.0.0
	 */
	public function getAllPostsFromBlogs() {
		//Não faça o cache dos posts resgatados
		wp_suspend_cache_addition();

		$arrPosts = array();

		foreach( $this->blogs_ids as $blog_id ) {
			if ( $blog_id == 1 ) continue; //Pula o site principal

			$arrPosts[$blog_id]  = array();

			//Não é uma requisição ajax e não restaure o blog corrente
			$arrPosts[$blog_id]  = self::_getPostsFromBlog($blog_id, false, false);
		}

		//Restaura para o site principal
		switch_to_blog(1);

		return $arrPosts;
	}

	/**
	 * Retorna os posts de um determinado blog,
	 * atende tanto a uma chamada normal, como uma requisição ajax
	 *
	 * $arrPosts = array(
	 *					array( POST_TYPE_1 => array(....),
	 * 						... ,
	 *					   	POST_TYPE_N => array( .... )
	 *					)
	 *				);
	 * @since     1.0.0
	 */
	public static function _getPostsFromBlog($blog_id = null, $ajaxcall = false, $restore_current_blog = true) {
		$arrPosts = array();
		if ( $ajaxcall && is_null($blogid) )
			$blog_id   = intval($_GET['blog_id']);

		switch_to_blog( $blog_id );

		$post_types = get_post_types( array( 'public' => true ) );
		unset($post_types['attachment']);
		$post_types = apply_filters('wpcpn_set_post_types', $post_types);


		foreach( $post_types as $post_type ) {

			$posts = get_posts(
				array(
					'post_type' 		=> $post_type,
					'order_by'  		=> 'post_date',
					'posts_per_page' 	=> 50
				)
			);


			$arrPosts[$post_type] = $posts;

		}

		/*if ($restore_current_blog)
			restore_current_blog();*/

		restore_current_blog();
		if ($ajaxcall) {
			echo json_encode($arrPosts);
			die();
		}

		return $arrPosts;

	}

	public static function getPostsFromBlog($blog_id = null) {
		self::_getPostsFromBlog($blog_id, true, true);
	}

	/**
	 * Salva uma lista de posts (na ordem especificada pelo usuário)
	 * atende somente a chamadas via ajax (WordPress ajax handler: wpcpn_save_posts_list)
	 *
	 * @since     1.0.0
	 */
	public static function savePostsList() {
		$posts   = esc_sql($_POST['posts']);
		$group   = esc_sql($_POST['group']);
		$section = esc_sql($_POST['section']);
		$nonce   = esc_sql($_POST['nonce']);


		if ( ! wp_verify_nonce($nonce, WPCPN_Admin::NONCE) )	{
			echo '-1';
			die();
		}

		$meta_key = WPCPN_Admin_Model::META_KEY . $group . '_' . $section;
		$old_posts = get_option($meta_key);


		//Seta todos os posts já salvos que tenham solicitações como Aprovados
		foreach ($old_posts['posts'] as $blog_id =>  $_old_posts) {
			foreach ($_old_posts as $blog_post) {
				WPCPN_Requests::approve( $blog_id, $blog_post );
			}
		}

		/**
		 * Convertendo o array('blogid_postid', 'blogid1_postsid1')
		 * para array('blogid' => array('postsid'), 'blogid1' => 'postsid1')
		 **/
		$arrPosts = array();

		$count = 0;
		if ( is_array($posts) ) {
			foreach($posts as $blogPost) {
				$pieces = explode('-', $blogPost);
				$arrPosts['posts'][$pieces[0]][] = (int) $pieces[1];

				//Se tiver alguma solicitação pendente deste post, atualize o status para publicado
				if ( ! WPCPN_Requests::get_request($pieces[0], $pieces[1]) ) {
					WPCPN_Requests::insert_request($pieces[0], $pieces[1], 'Publicado na página principal pela ASSECOM sem solicitação');
				}

				WPCPN_Requests::publish($pieces[0], $pieces[1]);

				$count++;
			}
		}
		$arrPosts['count'] = $count;




		update_option($meta_key, $arrPosts);

		do_action("wpcpn_posts_list_{$group}_{$section}");
		do_action("wpcpn_save_post_list", $group, $section);

		die();
	}

	/*
	 *	Retorna a lista de posts cadastrado para um dado group/seção
	 */
	public static function getPostsLists( $group, $sectionSlug ) {
		$postLists =  get_option(WPCPN_Admin_Model::META_KEY . $group . '_' . $sectionSlug);

		if ( ! $postLists )
			return array('count' => 0);

		return $postLists;
	}

	public static function processRestrictions( $blog_id, $post, $restrictions ) {
		if ( ! is_array($restrictions) ) return true;

		$pass = true;

		foreach( $restrictions as $restriction_type => $restriction ) {
			switch($restriction_type) {
				case 'taxonomy':
					$taxonomy_slug	= $restriction['taxonomy_slug'];
					$term_slug		= $restriction['term_slug'];
					$pass			= $pass && has_term($term_slug, $taxonomy_slug, $post->ID);

				break;
				default:
					$pass			= $pass && apply_filters('wpcpn_restriction_' . $restriction_type, $pass, $post, $blog_id, $restrictions );
				break;
			}
		}

		return $pass;
	}
}
