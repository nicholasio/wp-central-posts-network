<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholas@iotecnologia.com.br>
 * @license   GPL-2.0+
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<h3><span><?php _e('Define which posts will be displayed in each section.', 'wpcon'); ?></span></h3>
	<div class="inside">
		<h2 class="nav-tab-wrapper" style="padding:0 0 0 10px;">
			<?php
				$sections = array();
				$sections = apply_filters('wpcpn_posts_section', $sections);
				$currentGroupTab = '';
				if ( is_array($sections) ) :
					if ( isset( $_GET['tab']) )
						$currentGroupTab = $_GET['tab'];
					else
						$currentGroupTab = key($sections); // Primeiro 'key' do array

					foreach( $sections as $groupslug => $groups ) : ?>
					<a href="?page=wpcpn&tab=<?php echo $groupslug; ?>" class="nav-tab <?php if ( $currentGroupTab == $groupslug ) echo 'nav-tab-active' ?> "><?php echo $groups['name']?></a>
			<?php
					endforeach;
				endif;
			?>
		</h2>

		<?php
			if ( is_array($sections) ) {
				$arrPost   		= $this->model->getAllPostsFromBlogs();
				$currentSection = $sections[$currentGroupTab];

				echo '<div id="' . $currentGroupTab . '" class="wpcpn-group">';
					foreach($currentSection['sections'] as $section) {
						$perform_on_select = false;
						$ajaxName = 'wp_ajax_wpcpn_' . $section['slug'] . '_on_select';
						$on_error = '';
						if ( has_action($ajaxName) ) {
							$perform_on_select = true;
							if ( isset($section['on_error']) )
								$on_error = $section['on_error'];
						}
						$posts_selected = WPCPN_Post_Selector_Model::getPostsLists($currentGroupTab, $section['slug']);
						include('post-selector-section.php');
					}
				echo '</div>';
			}
		?>


	</div> <!-- .inside -->

</div>

