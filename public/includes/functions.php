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