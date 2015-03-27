<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php 
		if ( isset($_GET['wpcpn_requests_nonce']) ) {
			check_admin_referer('wpcpn_change_status', 'wpcpn_requests_nonce');
			$blog_id = esc_sql($_GET['blog_id']);
			$post_id = esc_sql($_GET['post_id']);

			if ( $_GET['action'] == 'approve' ) {
				WPCPN_Admin_Public_Model::change_status('AP', $blog_id, $post_id);
			} else if ( $_GET['action'] == 'reject' ) {
				WPCPN_Admin_Public_Model::change_status('RJ', $blog_id, $post_id);
			} else if ( $_GET['action'] == 'awaiting' ) {
				WPCPN_Admin_Public_Model::change_status('AW', $blog_id, $post_id);
			}
		}
	?>
	<div class="inside">
		<p>Na tabela abaixo é possível visualizar todas as solicitações de destaque de posts. </p>
		<p>Note que ao aprovar uma solicitação ela não vai automaticamente para a página principal, é necessário associar ela a alguma
			seção no menu Seletor de Posts.</p>
		<h3>Status possíveis</h3>
		<ul>
			<li>Aguardando - Aguardando análise</li>
			<li>Aprovado - Aprovado mas não publicado</li>
			<li>Publicado - Publicado</li>
			<li>Rejeitado - Rejeitado para publicação</li>
		</ul>
		<h2>Últimas solicitações</h2>
		<?php 
			require_once( plugin_dir_path( __FILE__ ) . 'WP_List_Requests.php' );

			$requests_table = new WP_List_Requests();
			$requests_table->prepare_items();
		?>


		<?php $requests_table->display(); ?>

	</div>
</div>