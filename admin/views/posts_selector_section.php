<div class="wpcpm-section wpcpn-namespace-<?php echo $section['slug']; ?>" 
			data-namespace="<?php echo $section['slug']; ?>" 
			data-max-posts="<?php echo $section['max_posts']; ?>" 
			data-nposts="<?php echo $posts_selected['count']; ?>"
			data-on-select="<?php echo $perform_on_select ? '1' : '0'; ?>"
			data-on-error="<?php echo $on_error; ?>">

	<h3>Seção: <?php echo $section['name']; ?></h3>
	<p>Esta seção suporta até <?php echo $section['max_posts']; ?> posts</p>
	<p><?php echo $section['description']; ?></p>

	<table class="form-table">
		<tr valign="top">
			
			<td scope="row" width="50%">
				<select class="wpcpn-site-chooser">
					<option value="-1">Escolha uma unidade:</option>
					<?php 
						foreach ($arrPost as $blog_id => $posts) : 
							if ( $section['blogs'] == 'all' || (is_array($section['blogs']) && in_array($blog_id, $section['blogs']) ) ) : 
					?>
						<option value="<?php echo $blog_id; ?>"><?php echo get_blog_option($blog_id, 'blogname'); ?></option>
					<?php 
							endif;
						endforeach; 
					?>
				</select>
			</td>

			<td>
				<div class="ui-widget">
					<label for="tags">Busca por post: </label>
					<input class="wpcpn-search">
				</div>
			</td>
		</tr>

		<tr>
			<td scope="row">
				<p>Posts que irão aparecer nesta seção</p>
				<ul class="connectedSortable sortable wpcpn-posts-selected">
					<?php if ( is_array($posts_selected['posts']) ) : ?>
						<?php foreach($posts_selected['posts'] as $blog_id => $blogPosts) : ?>
							<?php foreach( $blogPosts as $postID ) :  ?>
								<li data-blog-id="<?php echo $blog_id; ?>" data-uid="<?php echo $blog_id; ?>-<?php echo $postID; ?>" class="ui-state-default"> 
									<?php 
										$_post = get_blog_post($blog_id, $postID); 
										echo get_blog_option($blog_id, 'blogname') . ': ' . $_post->post_title;
									?>
									<a class="dashicons dashicons-no"href="#"></a>
								</li>
							<?php endforeach; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</td>
			<td class="wpcpn-all-posts <?php echo $section['slug']; ?>">
				<p>Posts deste site</p>
				<ul class="connectedSortable sortable wpcpn-posts-to-choose">
					
				</ul>
				<div style="display:none" class="sites">

					<?php foreach( $arrPost as $blog_id => $posts_types ) :  ?>
						<ul data-blog_id="<?php echo $blog_id ?>" class="wpcpn-posts-list">
							<?php foreach ($posts_types as $post_type_name => $posts ) : 
									if (   isset($section['post_types']) &&  is_array($section['post_types']) &&
										 ! in_array($post_type_name, $section['post_types']) ) 
										continue;

									//Só precisamos trocar/restaurar se tivermos restrições a serem processadas
									if (isset($section['restrictions']) ) 
										switch_to_blog($blog_id);

									foreach($posts as $post ) :
										/**
										 * Processa as restrições, se algo não atender as restrições o post não é listado para essa section.
										 */
										if ( isset($section['restrictions']) && 
											 ! WPCPN_Admin_Model::processRestrictions($blog_id, $post, $section['restrictions']) )
											continue;

										$uid =  $blog_id . '-' . $post->ID;

										$state = 1;
										if ( isset($posts_selected['posts'][$blog_id]) && in_array($post->ID, $posts_selected['posts'][$blog_id]) ){
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
								endforeach; //Middle Foreach
							?>
						</ul>
					<?php endforeach; ?>

				</div>
				
			</td>
		</tr>
	</table>
	
	<input class="button-primary wpcpn-save-post-list" type="submit" name="" value="<?php _e( 'Save' ); ?>" /> 
	<span class="wpcpn-ajax-loader"></span>	

	<div style="clear:both;"></div>
</div> <!-- end section -->