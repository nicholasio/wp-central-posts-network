<?php

/**
 * Plugin Admin Menu Model Class
 *
 * @package WPCPN_Admin
 * @author  Nícholas André <nicholas@iotecnologia.com.br>
 */

class WPCPN_Post_Selector_Model {

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
	 * Popula o array $arrPosts
	 * It's not used anymore
	 *
	 * $arrPosts = array( BLOG_ID_1 =>
	 *						array( POST_TYPE_1 => array(....),
	 * 							... ,
	 *					   		POST_TYPE_N => array( .... )
	 *						),
	 *						BLOG_ID_N => ....
	 *				);
	 * @since     1.0.0
	 * @deprecated
	 */
	public function getAllPostsFromBlogs() {
		$this->blogs_ids 	= WPCPN::get_blog_ids();
		//Não faça o cache dos posts resgatados
		wp_suspend_cache_addition();

		$arrPosts = array();

		foreach( $this->blogs_ids as $blog_id ) {
			if ( $blog_id == 1 ) continue; //Pula o site principal

			$arrPosts[$blog_id]  = array();

			//Não é uma requisição ajax e não restaure o blog corrente
			$arrPosts[$blog_id]  = self::getPostsFromBlog($blog_id, false, false);
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
	public static function getPostsFromBlog($blog_id = null, $ajaxcall = false, $restore_current_blog = true) {
		$arrPosts = array();
		if ( $ajaxcall && is_null($blog_id) )
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
					'posts_per_page' 	=> -1
				)
			);
			if ( $posts )
				$arrPosts[$post_type] = $posts;

		}

		restore_current_blog();
		if ($ajaxcall) {
			echo json_encode($arrPosts);
			die();
		}

		return $arrPosts;

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

		$meta_key = WPCPN_Post_Selector_Model::META_KEY . $group . '_' . $section;
		$old_posts = get_option($meta_key);


		//Seta todos os posts já salvos que tenham solicitações como Aprovados
		foreach ($old_posts['posts'] as $blog_id =>  $_old_posts) {
			foreach ($_old_posts as $blog_post) {
				WPCPN_Requests::approve( $blog_id, $blog_post );
			}
		}

		$arrPosts = array();

		$count = 0;
		if ( is_array($posts) ) {
			foreach($posts as $blogPost) {
				$pieces = explode('-', $blogPost);
				$arrPosts['posts'][] = array('blog_id' => (int) $pieces[0],'post_id' => (int) $pieces[1]);

				//Se tiver alguma solicitação pendente deste post, atualize o status para publicado
				if ( ! WPCPN_Requests::get_request($pieces[0], $pieces[1]) ) {
					WPCPN_Requests::insert_request($pieces[0], $pieces[1], __('Published on the main site by the super admin.', 'wpcpn'));
				}

				WPCPN_Requests::publish($pieces[0], $pieces[1]);

				$count++;
			}
		}
		$arrPosts['count'] = $count;

		update_option($meta_key, $arrPosts);

		//Clear Cache
		self::clearCache($group, $section);

		do_action("wpcpn_posts_list_{$group}_{$section}");
		do_action("wpcpn_save_post_list", $group, $section);

		die();
	}

	/**
	 * Clear the cache for a given group and section of posts
	 */
	public static function clearCache($group, $section) {
		//cache is active?
		if ( ! wpcpn_is_cache_active() ) echo 1;

		//the $group and $section muste be cached?
		if ( wpcpn_should_fragment_cache($group, $section) || WPCPN::$cache_config['type'] != 'fragment-caching') {
			wpcpn_cache_delete($group, $section);
		} else {
			echo 1;
		}
	}

	/*
	 *	Retorna a lista de posts cadastrado para um dado group/seção
	 */
	public static function getPostsList( $group, $sectionSlug ) {
		$postLists =  get_option(WPCPN_Post_Selector_Model::META_KEY . $group . '_' . $sectionSlug);

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

