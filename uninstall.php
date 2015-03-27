<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholasandre@ufersa.edu.br>
 * @license   GPL-2.0+
 * @link      http://
 * @copyright 2014 UFERSA
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
switch_to_blog(1);
$GLOBALS['wpdb']->query("DROP TABLE `" . $GLOBALS['wpdb']->prefix."wpcpn_featured_requests`");
$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->prefix."options` WHERE option_name LIKE 'wpcpn\_posts\_list%' ");
//Delete all options keys that was created
delete_option('wpcpn_db_version');
restore_current_blog();

die();

if ( is_multisite() ) {

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		/* @TODO: delete all transient, options and files you may have added
		delete_transient( 'TRANSIENT_NAME' );
		delete_option('OPTION_NAME');
		//info: remove custom file directory for main site
		$upload_dir = wp_upload_dir();
		$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
		if (is_dir($directory)) {
			foreach(glob($directory.'*.*') as $v){
				unlink($v);
			}
			rmdir($directory);
		}
		*/
	if ( $blogs ) {

	 	foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			/* @TODO: delete all transient, options and files you may have added
			delete_transient( 'TRANSIENT_NAME' );
			delete_option('OPTION_NAME');
			//info: remove custom file directory for main site
			$upload_dir = wp_upload_dir();
			$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
			if (is_dir($directory)) {
				foreach(glob($directory.'*.*') as $v){
					unlink($v);
				}
				rmdir($directory);
			}
			//info: remove and optimize tables
			$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."TABLE_NAME`");
			$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");
			*/
			restore_current_blog();
		}
	}

} else {
	/* @TODO: delete all transient, options and files you may have added
	delete_transient( 'TRANSIENT_NAME' );
	delete_option('OPTION_NAME');
	//info: remove custom file directory for main site
	$upload_dir = wp_upload_dir();
	$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
	if (is_dir($directory)) {
		foreach(glob($directory.'*.*') as $v){
			unlink($v);
		}
		rmdir($directory);
	}
	//info: remove and optimize tables
	$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."TABLE_NAME`");
	$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");
	*/
}