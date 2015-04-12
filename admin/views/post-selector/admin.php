<?php
/**
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholas@iotecnologia.com.br>
 * @license   GPL-2.0+
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<h3><span><?php _e('Define which posts will be displayed in each section.', 'wpcpn'); ?></span></h3>
	<div class="inside">
		<h2 class="nav-tab-wrapper" style="padding:0 0 0 10px;">
			<?php
				$sections = apply_filters('wpcpn_posts_section', null);

				$currentGroupTab = '';
				if ( is_array($sections) ) :
					if ( isset( $_GET['tab']) )
						$currentGroupTab = $_GET['tab'];
					else
						$currentGroupTab = key($sections);

					foreach( $sections as $groupslug => $groups ) : ?>
					<a href="?page=wpcpn&tab=<?php echo $groupslug; ?>" class="nav-tab <?php if ( $currentGroupTab == $groupslug ) echo 'nav-tab-active' ?> "><?php echo $groups['name']?></a>
			<?php
					endforeach;
				endif;
			?>
		</h2>

		<?php
			if ( is_array($sections) ) {
				$currentGroup = $sections[$currentGroupTab];

				$sites        = WPCPN::get_blog_ids();

				echo '<div id="' . $currentGroupTab . '" class="wpcpn-group">';
					foreach($currentGroup['sections'] as $section_slug => $section) {
						$perform_on_select = false;
						$ajaxName = 'wp_ajax_wpcpn_before_select_' . $currentGroupTab . '_' . $section_slug;
						$on_error = __('This post can not be added to this section'	, 'wpcpn');
						if ( has_action($ajaxName) ) {
							$perform_on_select = true;
							if ( isset($section['on_error']) )
								$on_error = $section['on_error'];
						}
						$posts_selected = WPCPN_Post_Selector_Model::getPostsList($currentGroupTab, $section_slug);
						include('post-selector-section.php');
					}
				echo '</div>';
			}
		?>


	</div> <!-- .inside -->

</div>

