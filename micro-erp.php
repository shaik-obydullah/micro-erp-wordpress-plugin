<?php
/**
 * Plugin Name:       Micro ERP
 * Plugin URI:        https://example.com/micro-erp
 * Description:       A lightweight ERP system for small businesses — contacts, accounting, HRM, and sales management, all inside WordPress.
 * Version:           1.0.0
 * Author:            Obydullah
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       micro-erp
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MICRO_ERP_VERSION', '1.0.0' );
define( 'MICRO_ERP_FILE', __FILE__ );
define( 'MICRO_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MICRO_ERP_URL', plugin_dir_url( __FILE__ ) );
define( 'MICRO_ERP_TABLE', 'micro_erp_' );

require_once MICRO_ERP_PATH . 'includes/helpers.php';
require_once MICRO_ERP_PATH . 'includes/class-activator.php';
require_once MICRO_ERP_PATH . 'includes/class-micro-erp.php';

register_activation_hook( __FILE__, array( 'MicroERP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MicroERP_Activator', 'deactivate' ) );

function micro_erp_bootstrap() {
	new MicroERP();
}
add_action( 'plugins_loaded', 'micro_erp_bootstrap' );
