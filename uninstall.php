<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WPCPN
 * @author    Nícholas André <nicholas@iotecnologia.com.br>
 * @license   GPL-2.0+
 * @link      http://
 * @link      https://github.com/nicholasio/wp-central-posts-network
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


switch_to_blog(1);
global $wpdb;
$GLOBALS['wpdb']->query("DROP TABLE `" . $GLOBALS['wpdb']->prefix."wpcpn_featured_requests`");
$GLOBALS['wpdb']->query("DELETE FROM `" .$GLOBALS['wpdb']->prefix."options` WHERE option_name LIKE 'wpcpn\_posts\_list%' ");
//Delete all options keys that was created
delete_option('wpcpn_db_version');
restore_current_blog();

