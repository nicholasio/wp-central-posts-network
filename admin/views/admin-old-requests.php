<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	
	<div class="inside">

		<h3>Publicações Antigas</h3>

		<?php
			require_once( plugin_dir_path( __FILE__ ) . 'WP_List_Requests.php' );
			$requests_table = new WP_List_Requests(false);
			$requests_table->prepare_items();
		?>

		<?php $requests_table->display(); ?>
	</div>
</div>