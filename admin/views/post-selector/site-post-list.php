<?php
	$post_count = 0;
	foreach($blog_posts as $post_type => $_posts ) :
			//If we have restrictions, we need switch_to_blog
			if (isset($section['restrictions']) )
				switch_to_blog($blog_id);

			$blogname = get_blog_option($blog_id, 'blogname');

			foreach($_posts as $post ) :
				/**
				 * Process the restrictions, if something do not met the restrictions, then the post will not be shown
				 */
				if ( isset($section['restrictions']) &&
					 ! WPCPN_Post_Selector_Model::processRestrictions($blog_id, $post, $section['restrictions']) ) {
					continue;
				} else {
					$post_count++;
				}

				$uid =  $blog_id . '-' . $post->ID;

				$state = 1;
				if ( isset( $posts_selected['posts'] ) && wpcpn_array_search_for_array( $posts_selected['posts'],
												  array( 'blog_id' => $blog_id,
												  	     'post_id' => $post->ID
												  ) ) ) {
					$state = 2;
				}

				$class = ($state == 2) ? 'dashicons-yes' : 'dashicons-plus-alt';

	?>

		<li data-uid="<?php echo $uid ?>" data-post-id="<?php echo $post->ID; ?>" data-state="<?php echo $state; ?>">
			<?php echo $post->post_title; ?>
			<span class="wpcpn-site-info"><?php echo $blogname; ?></span>
			<a class="dashicons <?php echo $class; ?>" href="#"></a>
			<span class="wpcpn-ajax-loader"></span>
		</li>

	<?php 	endforeach; //Inner foreach;
		//if we had restrictions we need to restoure now
		if (isset($section['restrictions']) )
			restore_current_blog();
	?>
<?php endforeach; ?>

<?php if ( $post_count == 0 ) : ?>
	<?php echo '<li class="wpcpn-no-posts-found">' . __('No posts has met the restrictions', 'wpcpn') . '</li>'; ?>
<?php endif; ?>
