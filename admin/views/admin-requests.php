<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php
		if ( isset($_GET['wpcpn_requests_nonce']) ) {
			check_admin_referer('wpcpn_change_status', 'wpcpn_requests_nonce');
			$blog_id = esc_sql($_GET['blog_id']);
			$post_id = esc_sql($_GET['post_id']);

			if ( $_GET['action'] == 'approve' ) {
				WPCPN_Requests::change_status('AP', $blog_id, $post_id);
			} else if ( $_GET['action'] == 'reject' ) {
				WPCPN_Requests::change_status('RJ', $blog_id, $post_id);
			} else if ( $_GET['action'] == 'awaiting' ) {
				WPCPN_Requests::change_status('AW', $blog_id, $post_id);
			}
		}
	?>
	<div class="inside">
		<p><?php _e('In the table below you can see all the requests for posts.', 'wpcpn'); ?>
		<?php _e('Note that in approving a request it will not automatically go to the main page, you must associate it to one or more post section in the post selector menu', 'wpcpn'); ?></p>
		<h3><?php _e('Status Legend', 'wpcpn'); ?></h3>
		<ul>
			<li><?php _e('Waiting Review - Waiting for the super admin review the request', 'wpcpn'); ?></li>
			<li><?php _e('Approved - Approved, but not published', 'wpcpn'); ?></li>
			<li><?php _e('Published - Published', 'wpcpn'); ?></li>
			<li><?php _e('Rejected - Rejected by the super admin', 'wpcpn'); ?></li>
		</ul>
		<h2><?php _e('Last Requests', 'wpcpn') ?></h2>
		<?php
			require_once( plugin_dir_path( __FILE__ ) . 'WP_List_Requests.php' );

			$requests_table = new WP_List_Requests();
			$requests_table->prepare_items();
		?>


		<?php $requests_table->display(); ?>

	</div>
</div>
