<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

Class WP_List_Requests extends WP_List_Table {

	private $curr_post;

	private $recents;

	public function __construct( $recents = true) {
		$this->recents = $recents;
	}

	public function get_columns() {
		$columns = array(
			'ID' => 'ID',
			'blog_id' => 'Site',
			'post_id' => 'Post',
			'message' => 'Mensagem',
			'created'  => 'Data da solicitação',
			'published' => 'Data da publicação',
			
			'status' => 'Status',
			'actions' => 'Ações'
		);

		if ( !$this->recents ) {
			unset($columns['status']);
			unset($columns['actions']);
		}

		return $columns;
	}

	public function get_data($per_page, $current_page) {
		global $wpdb;
		$tableName = WPCPN_Admin_Public_Model::get_table_name();
		$limit = "LIMIT " . ($current_page-1) * $per_page . "," . $per_page;

		$sql = "SELECT * FROM {$tableName} WHERE published = '0000-00-00 00:00:00' OR ( status != 'AP' AND published != '0000-00-00 00:00:00') ORDER BY ID DESC {$limit}";

		if ( ! $this->recents ) 
			$sql = "SELECT * FROM {$tableName} WHERE published != '0000-00-00 00:00:00' AND status = 'AP' ORDER BY ID DESC {$limit}";
		

		return $wpdb->get_results(
			$sql
		);
	}

	public function get_total_items() {
		global $wpdb;
		$tableName = WPCPN_Admin_Public_Model::get_table_name();

		$sql = "SELECT COUNT(ID) FROM {$tableName} WHERE published = '0000-00-00 00:00:00' OR ( status != 'AP' AND published != '0000-00-00 00:00:00') ORDER BY ID DESC";

		if ( ! $this->recents ) 
			$sql = "SELECT COUNT(ID) FROM {$tableName} WHERE published != '0000-00-00 00:00:00' AND status = 'AP' ORDER BY ID DESC";
		

		return $wpdb->get_var(
			$sql
		);
	}
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden  = array('ID');
		$sortable = array();
		$this->_column_headers  = array($columns, $hidden, $sortable);

		$per_page = 15;
		$current_page = $this->get_pagenum();

		$total_items = $this->get_total_items();

		$this->items = $this->get_data($per_page, $current_page);
	
		$this->set_pagination_args( array(
		    'total_items' => $total_items,                  //WE have to calculate the total number of items
		    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		) );
	}

	public function column_default( $item,  $column_name ) {
		$value = $item->{$column_name};
		switch( $column_name ) {
			case 'ID':
			case 'message':
				return $value;
			break;
			case 'published':
			case 'created':
				if ( strtotime($value) == 0 ) return '';
				return  date('d/m/Y à\s H:i:s', strtotime($value));
			break;
			case 'blog_id':
				switch_to_blog($item->blog_id);

				$blogname	= get_option('blogname');
				$url		= home_url();
				$admin_url	= $url . '/wp-admin';
				$post 		= get_post($item->post_id);

				$this->curr_post = new stdClass();
				$this->curr_post->post = $post;
				$this->curr_post->url  = $admin_url . "/post.php?post={$item->post_id}&action=edit";
				$this->curr_post->permalink = get_permalink($item->post_id);

				restore_current_blog();

				return "<strong>{$blogname}</strong> <br /> <a target='_blank' href='{$url}'> Ver Site</a> | <a target='_blank' href='{$admin_url}'>Ver Painel</a>";
				
			break;
			case 'status':
				switch( $value ) {
					case 'PB':
						return '<span style="color: green"><strong>Publicado</strong></span>';
					break;
					case 'RJ':
						return '<span style="color: red">Rejeitado</span>';
					case 'AW':
						return '<span style="color: orange">Aguardando</span>';
					break;
					case 'AP':
						return '<span style="color: green">Aprovado</span>';
					break;
				}
			break;
			case 'post_id':
				return "<strong>{$this->curr_post->post->post_title}</strong> <br /> <a target='_blank' href='{$this->curr_post->url}'>Editar Post</a> | <a target='_blank' href='{$this->curr_post->permalink}'>Ver Post</a> ";
			break;
			case 'actions':
				$approve_url	= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=approve"), 'wpcpn_change_status' , 'wpcpn_requests_nonce');
				$reject_url		= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=reject"), 'wpcpn_change_status', 'wpcpn_requests_nonce');
				$awaiting_url	= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=awaiting"), 'wpcpn_change_status', 'wpcpn_requests_nonce');
				$approve		= "<a href='{$approve_url}'>Aprovar</a>";
				$reject			= "<a href='{$reject_url}'>Rejeitar</a>";
				$awaiting		= "<a href='{$awaiting_url}'>Aguardando</a>";
				$sep			= "|";
				$sep2			= "|";
				if ( $item->status == 'PB' ) {
					$approve	= '';
					$reject		= '';
					$awaiting   = '';
					$sep		= 'Sem Ações';
					$sep2 		= '';
				} else if ( $item->status == 'RJ' ) {
					$reject	= '';
					$sep 	= '';
				} else if ( $item->status == 'AP' ) {
					$approve	= '';
					$sep 		= '';
				} else if ( $item->status == 'AW' ) {
					$awaiting	= '';
					$sep2		= '';
				}

				return " {$approve} {$sep} {$reject} {$sep2} {$awaiting}";
			break;
			default:
				return print_r($item, true);
			break;
		}
	}
}