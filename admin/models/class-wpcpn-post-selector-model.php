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
	 * Sites Id's
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public $blogs_ids;

	/**
	 * Return the posts from a given blog
	 *
	 * $arrPosts = array(
	 *					array( POST_TYPE_1 => array(....),
	 * 						... ,
	 *					   	POST_TYPE_N => array( .... )
	 *					)
	 *				);
	 * @since     1.0.0
	 */
	public static function getPostsFromBlog($blog_id, $post_types = null) {
		$arrPosts = array();

		switch_to_blog( $blog_id );

		if ( ! is_array( $post_types ) ) {
			$post_types = array('post');
		}

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

		return $arrPosts;

	}

	/**
	 * Save the posts list (in the order specefied by the user)
	 * (WordPress ajax handler: wpcpn_save_posts_list)
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


		//Set old posts with the approve status (they aren't published anymore)
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

				//If we have pending requests for this post, we need to update it status
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

		do_action("wpcpn_save_posts_list_{$group}_{$section}");
		do_action("wpcpn_save_posts_list", $group, $section);

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
	 *	Return the posts list from a group and section
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

