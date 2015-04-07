<?php
	foreach($blog_posts as $post_type => $_posts ) :
		if (  isset($section['post_types']) &&  is_array($section['post_types']) &&
					 ! in_array($post_type, $section['post_types']) )
					continue;

			//Só precisamos trocar/restaurar se tivermos restrições a serem processadas
			if (isset($section['restrictions']) )
				switch_to_blog($blog_id);

			foreach($_posts as $post ) :
				/**
				 * Processa as restrições, se algo não atender as restrições o post não é listado para essa section.
				 */
				if ( isset($section['restrictions']) &&
					 ! WPCPN_Post_Selector_Model::processRestrictions($blog_id, $post, $section['restrictions']) )
					continue;

				$uid =  $blog_id . '-' . $post->ID;

				$state = 1;
				if ( wpcpn_array_search_for_array( $posts_selected['posts'],
												  array( 'blog_id' => $blog_id,
												  	     'post_id' => $post->ID
												  ) ) ) {
					$state = 2;
				}

				$class = ($state == 2) ? 'dashicons-yes' : 'dashicons-plus-alt';

	?>

		<li class="ui-state-default" data-uid="<?php echo $uid ?>" data-post-id="<?php echo $post->ID; ?>" data-state="<?php echo $state; ?>">
			<?php echo get_blog_option($blog_id, 'blogname') . ': ' . $post->post_title; ?>
			<a class="dashicons <?php echo $class; ?>" href="#"></a>
			<span class="wpcpn-ajax-loader"></span>
		</li>

	<?php 	endforeach; //Inner foreach;
		//Só precisamos trocar/restaurar o blog se tiver restrições a serem processadas
		if (isset($section['restrictions']) )
			restore_current_blog();
	?>
<?php endforeach; ?>
