<?php
/**
 * WordPress Central Network Plugin
 *
 * Um plugin que adiciona uma página interna a um determinado site em uma rede multi-site e
 * permite resgatar posts de outros sites na mesma rede.
 *
 * @TODO English description
 *
 * @package   WPPCN
 * @author    Nícholas André <nicholasandre@ufersa.com.br>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 UFERSA - Universidade Federal Rural do Semi-Árido
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Central Network Plugin
 * Plugin URI:        @TODO
 * Description:       Um plugin que adiciona uma página interna a um determinado site em uma rede multi-site e
 * 					  permite resgatar posts de outros sites na mesma rede.
 * Version:           1.0.2
 * Author:            Nícholas André
 * Author URI:        
 * Text Domain:       plugin-name-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('WPCPN_CAN_ADD_POST', 1);
define('WPCPN_CANT_ADD_POST', 0);
define('WPCPN_IS_MAIN_SITE', get_current_blog_id() == 1 );

require_once( plugin_dir_path( __FILE__ ) . 'includes/WPCPN_Fragment_Cache.php');
require_once( plugin_dir_path( __FILE__ ) . 'admin/models/class-wpcpn-admin-model.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/network_queries.php' );

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/functions.php');
require_once( plugin_dir_path( __FILE__ ) . 'public/models/class-wpcpn-admin-public-model.php');
require_once( plugin_dir_path( __FILE__ ) . 'public/class-wpcpn.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'WPCPN', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPCPN', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'WPCPN', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/**
 * O painel administrativo só deve ser disponibilizado para um super admin rodando no site principal (cujo ID é 1)
 */
if ( is_admin() && WPCPN_IS_MAIN_SITE) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wpcpn-admin.php' );
	add_action( 'after_setup_theme', array( 'WPCPN_Admin', 'get_instance' ) );

}
