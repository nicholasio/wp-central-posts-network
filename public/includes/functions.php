<?php

/**
 * Realiza uma simples troca de prefixo, para permitir consultar simples aos posts de outros blogs.
 * @param  int $blog_id ID do blog desejado
 * @return null
 */
function wpcpn_light_switch_to_blog( $blog_id ) {
	global $wpdb;

	$wpdb->prefix = $wpdb->base_prefix  . $blog_id;
}


function wpcpn_light_restore_to_main_blog(){
	global $wpdb;

	$wpdb->prefix = $wpdb->base_prefix;
}

function wpcpn_get_path_for_site( $site ) {
	$network_site_url = str_replace('http://', '', network_site_url());
    $network_site_url = str_replace('www.','', $network_site_url);
    $network_site_url = str_replace('/', '', $network_site_url);


    $site_path = get_site_by_path( "{$site}." . $network_site_url, '/');
    $site_path_url = '#';
    if ( $site_path ) $site_path_url = 'http://' . $site_path->domain;

    return $site_path_url;
}

//API

function wpcpn_get_posts_list( $group_name, $section_name ) {
	return WPCPN_Post_Selector_Model::getPostsList( $group_name, $section_name );
}

/*
 * @TODO Object Cache
 */
function wpcpn_show_posts_section( $group_name, $section_name, Array $template, $params = array() )  {
	$section	= wpcpn_get_posts_section( $group_name, $section_name, $params );
	if ( $section ) {
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
			switch_to_blog( $wpcpn_post['blog_id'] );
	        $post = get_post( $wpcpn_post['post_id'] );
	        setup_postdata($post);

	        include( locate_template($slug . '-' . $name . '.php') );

	        wp_reset_postdata();
	        restore_current_blog();
		}
	}
}

function wpcpn_get_posts_section(  $group_name, $section_name, $params = array() ) {
	$section 	= wpcpn_get_posts_list( $group_name, $section_name );
	$section    = $section['posts'];

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
