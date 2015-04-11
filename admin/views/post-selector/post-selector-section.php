<div class="wpcpm-section wpcpn-namespace-<?php echo $section_slug; ?>"
		data-namespace="<?php echo $section_slug; ?>"
		data-max-posts="<?php echo $section['max_posts']; ?>"
		data-nposts="<?php echo $posts_selected['count']; ?>"
		data-on-select="<?php echo $perform_on_select ? '1' : '0'; ?>"
		data-on-error="<?php echo $on_error; ?>">

	<table class="form-table wpcpn-table">
		<tr>
			<td colspan="2">
				<h4><?php printf(__('Section: %s', 'wpcpn'), $section['name'] );?></h4>
				<p><?php printf(__('This section supports up to %s posts.', 'wpcpn'), $section['max_posts']); ?></p>
				<p><?php echo $section['description']; ?></p>
			</td>
		</tr>
		<tr>
			<td scope="row" width="50%">
				<div class="wpcpn-wrapper-box">
					<div class="wpcpn-wrapper-search">
						<select class="wpcpn-site-chooser" data-placeholder="<?php _e('Choose a site', 'wpcpn'); ?>" style="width: 100%">
							<option></option>
							<?php
								if ( ! $section['include_main_site'] ) unset($sites[0]);
								foreach ($sites as $blog_id ) :
									if ( $section['sites'] === 'all' || (is_array($section['blogs']) && in_array($blog_id, $section['blogs']) ) ) :
							?>
										<option value="<?php echo $blog_id; ?>"><?php echo get_blog_option($blog_id, 'blogname'); ?></option>
							<?php
									endif;
								endforeach;
							?>
						</select>
					</div>
					<div class="wpcpn-wrapper-search wpcpn-search-posts">
						<input class="wpcpn-search" type="text" placeholder="<?php _e('Search', 'wpcpn'); ?>">
					</div>
					<div class="wpcpn-area wpcpn-all-posts <?php echo $section_slug; ?>">
						<span class="wpcpn-ajax-loader"></span>
						<ul class="connectedSortable sortable wpcpn-posts-to-choose">
							<!-- Populate by Ajax -->
						</ul>
					</div> <!-- .wpcpn-area -->
				</div> <!-- .wpcpn-wrapper-box -->
			</td>
			<td>
				<div class=" wpcpn-wrapper-box">
					<div class="wpcpn-wrapper-search wpcpn-search-posts-selected">
						<input class="wpcpn-search" type="text" placeholder="<?php _e('Search', 'wpcpn'); ?>">
					</div>
					<div class="wpcpn-area">
						<ul class="connectedSortable sortable wpcpn-posts-selected">
							<?php
								if ( is_array($posts_selected['posts']) ) :
									foreach($posts_selected['posts'] as $post) :
											$post_id = $post['post_id'];
											$blog_id = $post['blog_id'];
							?>
										<li data-blog-id="<?php echo $blog_id; ?>" data-uid="<?php echo $blog_id; ?>-<?php echo $post_id; ?>" class="">
											<?php
												$_post = get_blog_post($blog_id, $post_id);
												echo $_post->post_title;
											?>
											<span class="wpcpn-site-info"><?php echo get_blog_option($blog_id, 'blogname'); ?></span>
											<a class="dashicons dashicons-no" href="#"></a>
										</li>
							<?php
									endforeach;
							 	endif;
						 	?>
						</ul>
					</div>
				</div> <!-- .wpcpn-all-posts -->
			</td>
			<tr>
				<td colspan="2" >
					<input class="button-primary wpcpn-save-post-list" type="submit" name="" value="<?php _e( 'Save', 'wpcpn' ); ?>" />
					<span class="wpcpn-ajax-loader wpcpn-ajax-loader-btn"></span>
				</td>
			</tr>
		</tr>
	</table>



	<div style="clear:both;"></div>
</div> <!-- end section -->
