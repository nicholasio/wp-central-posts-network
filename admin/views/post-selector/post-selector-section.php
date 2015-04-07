<div class="wpcpm-section wpcpn-namespace-<?php echo $section_slug; ?>"
		data-namespace="<?php echo $section_slug; ?>"
		data-max-posts="<?php echo $section['max_posts']; ?>"
		data-nposts="<?php echo $posts_selected['count']; ?>"
		data-on-select="<?php echo $perform_on_select ? '1' : '0'; ?>"
		data-on-error="<?php echo $on_error; ?>">

	<h3><?php printf(__('Section: %s', 'wpcpn'), $section['name'] );?></h3>
	<p><?php printf(__('This section supports up to %s posts.', 'wpcpn'), $section['max_posts']); ?></p>
	<p><?php echo $section['description']; ?></p>

	<table class="form-table">
		<tr valign="top">
			<td scope="row" width="50%">
				<select class="wpcpn-site-chooser">
					<option value="-1"><?php _e('Choose a site:', 'wpcpn'); ?></option>
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
			</td>

			<td>
				<div class="ui-widget">
					<label for="tags"><?php _e('Search for a post:', 'wpcpn'); ?></label>
					<input class="wpcpn-search">
				</div>
			</td>
		</tr>

		<tr>
			<td scope="row">
				<p><?php _e('Posts that will be displayed in this section', 'wpcpn'); ?></p>
				<ul class="connectedSortable sortable wpcpn-posts-selected">
					<?php
						if ( is_array($posts_selected['posts']) ) :

							foreach($posts_selected['posts'] as $post) :
									$post_id = $post['post_id'];
									$blog_id = $post['blog_id'];
					?>
								<li data-blog-id="<?php echo $blog_id; ?>" data-uid="<?php echo $blog_id; ?>-<?php echo $post_id; ?>" class="ui-state-default">
									<?php
										$_post = get_blog_post($blog_id, $post_id);
										echo get_blog_option($blog_id, 'blogname') . ': ' . $_post->post_title;
									?>
									<a class="dashicons dashicons-no" href="#"></a>
								</li>
					<?php
							endforeach;
					 	endif;
				 	?>
				</ul>
			</td>
			<td class="wpcpn-all-posts <?php echo $section_slug; ?>">
				<p><?php _e('Posts from the selected site', 'wpcpn'); ?></p>
				<span class="wpcpn-ajax-loader"></span>
				<ul class="connectedSortable sortable wpcpn-posts-to-choose">
					<!-- Populate by Ajax -->
				</ul>
			</td>
		</tr>
	</table>

	<input class="button-primary wpcpn-save-post-list" type="submit" name="" value="<?php _e( 'Save', 'wpcpn' ); ?>" />
	<span class="wpcpn-ajax-loader"></span>

	<div style="clear:both;"></div>
</div> <!-- end section -->
