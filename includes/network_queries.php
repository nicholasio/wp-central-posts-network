<?php
//define( 'DIEONDBERROR', true );
/**
 * Retorna os posts de todos os sites da rede que atendam a consulta realizada.
 * @param  Array  $args Arqgumentos para a consulta
 * @return mixed       false se não existir nenhum post, e um Array de objetos caso existam posts
 */
function wpcpn_get_network_posts(Array $args) {
	// Prepare the SQL query with $wpdb
	global $wpdb;

	$args = wp_parse_args( $args , 
					array(
						'taxonomy_slug' => '',
						'term_slug' 	=> '',
						'offset'		=> 0,
						'size'			=> 5,
						'paged'			=> 1,
						's'				=> ''
					) 
			);

	$base_prefix = $wpdb->get_blog_prefix(0);
	$base_prefix = str_replace( '1_', '' , $base_prefix );

	// Because the get_blog_list() function is currently flagged as deprecated 
	// due to the potential for high consumption of resources, we'll use
	// $wpdb to roll out our own SQL query instead. Because the query can be
	// memory-intensive, we'll store the results using the Transients API
	//if ( false === ( $site_list = get_site_transient( 'multisite_site_list' ) ) ) {
	 $site_list = $wpdb->get_results( $wpdb->prepare('SELECT * FROM wp_blogs ORDER BY blog_id', null) );
	 //set_site_transient( 'multisite_site_list', $site_list, $expires );
	//}

	$limit	= absint( $args['size'] );
	$offset	= absint( esc_sql($args['offset']) + ( ($args['paged'] - 1) * $args['size']) );
	$query = '';
	// Merge the wp_posts results from all Multisite websites into a single result with MySQL "UNION"
	foreach ( $site_list as $site ) {
		 if( $site == $site_list[0] ) {
		    $posts_table = $base_prefix . "posts";
		    $blog_prefix = $base_prefix;
		 } else {
		    $posts_table = $base_prefix . $site->blog_id . "_posts";
		    $blog_prefix = $base_prefix . $site->blog_id . '_';
		 }

		 $blog_id = $site->blog_id;



		 $posts_table = esc_sql( $posts_table );
		 $blogs_table = esc_sql( $base_prefix . 'blogs' );

		 $query .= "(SELECT {$posts_table}.ID as post_id, {$posts_table}.post_title, {$posts_table}.post_date, {$blogs_table}.blog_id as site_id FROM $posts_table";
		 $query .= " INNER JOIN {$blogs_table} ON ({$blogs_table}.blog_id = {$blog_id}) ";

		 //If We have a taxonomy_slug
		 if ( ! empty($args['taxonomy_slug']) ) {

		 	$query .= 	" INNER JOIN {$blog_prefix}term_relationships wtr{$blog_id} ON ({$posts_table}.`ID` = wtr{$blog_id}.`object_id`)
						  INNER JOIN {$base_prefix}term_taxonomy wtt{$blog_id} ON (wtr{$blog_id}.`term_taxonomy_id` = wtt{$blog_id}.`term_taxonomy_id`)
						  INNER JOIN {$base_prefix}terms wt{$blog_id} ON (wt{$blog_id}.`term_id` = wtt{$blog_id}.`term_id`) ";
		 }

		 //@TODO busca por qualquer post_type ou busca customizável
		 $query .= " WHERE {$posts_table}.post_type = 'post' AND {$posts_table}.post_status = 'publish' ";

		//If We have a taxonomy_slug
		if ( ! empty($args['taxonomy_slug']) ) {

			$query .= " AND wtt{$blog_id}.taxonomy = '{$args['taxonomy_slug']}' AND wt{$blog_id}.`slug` = '{$args['term_slug']}'
						AND {$posts_table}.post_status = 'publish')";

		}

		if ( ! empty($args['s']) ) {
			$query .= " AND {$posts_table}.post_title LIKE '%" . $args['s'] . "%') ";
		}

		$allPosts = 0;
		if( $site !== end($site_list) ) {
			$query .= " UNION ";
		}
		else {
			//Calculando número total de registros.
			//$totalRows = $wpdb->prepare($query, $site->blog_id);
			$allPosts  = count( $wpdb->get_results($query, ARRAY_A) );

			$query .= " ORDER BY post_date DESC LIMIT $offset, $limit ";
		}
	    
	}
	// Sanitize and run the query
	//$_query = $wpdb->prepare($query, $site->blog_id);
	$network_posts = $wpdb->get_results( $query, ARRAY_A  );

	$wpcpn_posts = array();
	$wpcpn_posts['posts'] = $network_posts;
	$wpcpn_posts['count'] = $allPosts;

	// Set the Transients cache to expire every two hours
	//set_site_transient( 'recent_across_network', $recent_across_network, 60*60*2 );

	return $wpcpn_posts;
}

/**
 * Exibe o nome da categoria global a qual os posts estão sendo exibidos
 * @param  string  $prefix  Prefixo
 * @param  boolean $display Flag para exibir ou retornar
 * @return string           retorna uma string se display = false
 */
function wpcpn_single_cat_title( $prefix = '', $display = true ){
	$term_slug	= esc_sql( get_query_var('wpcpn_network_term') );
	
	$term_obj	= get_term_by( 'slug', $term_slug, esc_sql( get_query_var('wpcpn_network_tax') ) );
	
	$term_name	= apply_filters('wpcpn_single_cat_title', $term_obj->name);
	
	$term_name	= $prefix . $term_name;

	if ( $display )
		echo $term_name;
	else
		return $term_name;
}

/**
 * Retorna true se estiver em uma listagem de uma dada taxonomia
 * @return bool Verdadeiro se estiver em uma listagem global de posts de uma dada taxonomia
 */
function wpcpn_is_network_taxonomy( $taxonomy = 'category' ) {
	return 	get_query_var('wpcpn_network_term') && 
			get_query_var('wpcpn_query_type') &&
			$taxonomy === esc_sql( get_query_var('wpcpn_network_tax') ) ;
}

/**
 * Retorna true se estiver em uma listagem de busca global.
 * @return bool Verdadeiro se estive na página de resultados globais de busca
 */
function wpcpn_is_network_search( ) {
	return 	get_query_var('wpcpn_network_search') && 
			get_query_var('wpcpn_query_type');
}

function wpcpn_network_search_url() {
	return get_site_url(1, '');
}