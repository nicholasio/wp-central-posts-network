<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

Class WP_List_Requests extends WP_List_Table {

	private $curr_post;

	private $recents;

	public function __construct( $recents = true) {
		$this->recents = $recents;
		parent::__construct();
	}

	public function get_columns() {
		$columns = array(
			'ID'        => __('ID', 'wpcpn'),
			'blog_id'   => __('Site', 'wpcpn'),
			'post_id'   => __('Post', 'wpcpn'),
			'message'   => __('Message', 'wpcpn'),
			'created'   => __('Request Date', 'wpcpn'),
			'published' => __('Published Date', 'wpcpn'),

			'status'    => __('Status', 'wpcpn'),
			'actions'   => __('Actions', 'wpcpn')
		);

		if ( !$this->recents ) {
			unset($columns['status']);
			unset($columns['actions']);
		}

		return $columns;
	}

	public function get_bulk_actions() {
		$actions = array(

		    'delete'    => 'Delete',

		    'parsing'    => 'Parsen'
		);

		return $actions;
	}

	public function get_data($per_page, $current_page) {
		global $wpdb;
		$tableName = WPCPN_Requests::get_table_name();
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
		$tableName = WPCPN_Requests::get_table_name();

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
		    'total_items' => $total_items,
		    'per_page'    => $per_page
		) );
	}

	public function column_default( $item,  $column_name ) {
		if ( isset($item->{$column_name}) )
			$value = $item->{$column_name};
		switch( $column_name ) {
			case 'ID':
			case 'message':
				return $value;
			break;
			case 'published':
			case 'created':
				if ( strtotime($value) == 0 ) return '';
				return  date_i18n(get_option('date_format') . ' à\s H:i:s', strtotime($value));
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

				return "<strong>{$blogname}</strong> <br /> <a target='_blank' href='{$url}'>" . __('View Site', 'wpcpn') . "</a> | <a target='_blank' href='{$admin_url}'>" . __('View Panel', 'wpcpn') . "</a>";

			break;
			case 'status':
				switch( $value ) {
					case 'PB':
						return '<span style="color: green"><strong>'. __('Published', 'wpcpn') .'</strong></span>';
					break;
					case 'RJ':
						return '<span style="color: red">'. __('Rejected', 'wpcpn') .'</span>';
					case 'AW':
						return '<span style="color: orange">'. __('Waiting Review', 'wpcpn') .'</span>';
					break;
					case 'AP':
						return '<span style="color: green">'. __('Approved', 'wpcpn') .'</span>';
					break;
				}
			break;
			case 'post_id':
				if ( ! isset($this->curr_post->post) ) return;
				return "<strong>{$this->curr_post->post->post_title}</strong> <br /> <a target='_blank' href='{$this->curr_post->url}'>". __('Edit Post', 'wpcpn') ."</a> | <a target='_blank' href='{$this->curr_post->permalink}'>".__('View Post', 'wpcpn')."</a> ";
			break;
			case 'actions':
				$approve_url	= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=approve"), 'wpcpn_change_status' , 'wpcpn_requests_nonce');
				$reject_url		= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=reject"), 'wpcpn_change_status', 'wpcpn_requests_nonce');
				$awaiting_url	= wp_nonce_url(admin_url("admin.php?page=wpcpn_requests&blog_id={$item->blog_id}&post_id={$item->post_id}&action=awaiting"), 'wpcpn_change_status', 'wpcpn_requests_nonce');
				$approve		= "<a href='{$approve_url}'>"  . __('Approve', 'wpcpn') . "</a>";
				$reject			= "<a href='{$reject_url}'>"   . __('Reject', 'wpcpn') . "</a>";
				$awaiting		= "<a href='{$awaiting_url}'>" . __('Waiting', 'wpcpn') . "</a>";
				$sep			= "|";
				$sep2			= "|";
				if ( $item->status == 'PB' ) {
					$approve	= '';
					$reject		= '';
					$awaiting   = '';
					$sep		= __('No Actions', 'wpcpn');
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
				//return print_r($item, true);
			break;
		}
	}
}
