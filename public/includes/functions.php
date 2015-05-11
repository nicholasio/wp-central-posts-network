<?php
function wpcpn_get_path_for_site( $site ) {
	$network_site_url = str_replace('http://', '', network_site_url());
    $network_site_url = str_replace('www.','', $network_site_url);
    $network_site_url = str_replace('/', '', $network_site_url);


    $site_path = get_site_by_path( "{$site}." . $network_site_url, '/');
    $site_path_url = '#';
    if ( $site_path ) $site_path_url = 'http://' . $site_path->domain;

    return $site_path_url;
}

//Cache functions

function wpcpn_is_cache_active() {
	return WPCPN::$cache_config !== false && is_array(WPCPN::$cache_config);
}

function wpcpn_should_fragment_cache($group, $section) {
	return isset(WPCPN::$cache_config['cache'][$group]) &&
	       in_array($section, WPCPN::$cache_config['cache'][$group]);
}

function wpcpn_cache_delete($group, $section) {
	switch(WPCPN::$cache_config['type']) {
		case 'fragment-caching':
			$cache_keys = get_site_option('wpcpn_cache_keys');

			if ( $cache_keys && isset($cache_keys[$group][$section]) ) {
				foreach($cache_keys[$group][$section] as $cache_key) {
					delete_transient('wpcpn-fragments_' . $cache_key); //@TODO improve this
				}
			}
		break;
		case 'wp-super-cache':
			if ( function_exists('wp_cache_clear_cache') ) {
				$GLOBALS["super_cache_enabled"] = 1;
				wp_cache_clear_cache();
			}
		break;
		case 'w3-total-cache':
			// Clear all W3 Total Cache
			if( class_exists('W3_Plugin_TotalCacheAdmin') )
			{
			    $plugin_totalcacheadmin = & w3_instance('W3_Plugin_TotalCacheAdmin');
			    $plugin_totalcacheadmin->flush_all();
			}
		break;
	}
}

function wpcpn_cache_get_instance($group, $section, $template = '', $params = '' ) {
	$hash_key = md5($group . '-' .  $section . '/' . json_encode($template) . json_encode($params));
	return new WPCPN_Fragment_Cache($hash_key, WPCPN::$cache_config['expiration'], false );
}

/**
 * Adds a cache key to options table because we need to know which cache keys are associated
 * with each $group-$section because we need to flush it when the user updates the posts list.
 */
function wpcpn_cache_add_key($group, $section, $key) {
	$cache_keys = get_site_option('wpcpn_cache_keys');
	if ( ! $cache_keys ) $cache_keys = array();

	if ( ! isset($cache_keys[$group][$section]) ) {
		$cache_keys[$group][$section] = array($key);
	} else if (  ! in_array($key, $cache_keys[$group][$section]) ) {
		$cache_keys[$group][$section][] = $key;
		update_site_option('wpcpn_cache_keys', $cache_keys);
	}

}

//API

function wpcpn_get_posts_list( $group_name, $section_name ) {
	return WPCPN_Post_Selector_Model::getPostsList( $group_name, $section_name );
}

function wpcpn_show_posts_section( $group_name, $section_name, Array $template, $params = array() )  {
	$section_posts	= wpcpn_get_posts_section( $group_name, $section_name, $params );
	if ( $section_posts ) {
		if ( wpcpn_is_cache_active() && wpcpn_should_fragment_cache($group_name, $section_name) ) {
			$cache = wpcpn_cache_get_instance($group_name, $section_name, $template, $params);
			if ( ! $cache->output() ) {
				echo '<!-- Started WPCPN Fragment Cache block ' . date('Y-m-d H:i:s'). ' -->' . PHP_EOL;
				wpcpn_show_posts($section_posts, $template);
				echo PHP_EOL . '<!-- End WPCPN Fragment Cache block -->';
				wpcpn_cache_add_key($group_name, $section_name,$cache->key);
				$cache->store();
			}
		} else {
			wpcpn_show_posts($section_posts, $template);
		}
	}
}

function wpcpn_show_posts( $posts, Array $template ) {
	$slug	= $template['template_slug'];
	$name	= isset($template['template_name']) && ! empty($template['template_name']) ?  '-' . $template['template_name'] : '';

	if ( $posts && is_array($posts) ) {
		global $post;
		foreach ($posts as $wpcpn_post) {
			switch_to_blog( $wpcpn_post['blog_id'] );
	        $post = get_post( $wpcpn_post['post_id'] );
	        setup_postdata($post);

	        include( locate_template($slug . $name . '.php') );

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
