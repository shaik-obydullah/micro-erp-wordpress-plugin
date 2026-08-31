<?php
/**
 * Plugin Name: Obydullah Micro ERP
 * Author URI: https://obydullah.com
 * Plugin URI: https://obydullah.com/project/micro-erp-wordpress-plugin
 * Description: Obydullah Micro ERP — a lightweight ERP system for small businesses: contacts, accounting, HRM, and sales management, all inside WordPress.
 * Author: Shaik Obydullah
 * Text Domain: obydullah-micro-erp
 * Version: 1.0.0
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'OBY_MI_ERP_VERSION', '1.0.0' );
define( 'OBY_MI_ERP_FILE', __FILE__ );
define( 'OBY_MI_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'OBY_MI_ERP_URL', plugin_dir_url( __FILE__ ) );
define( 'OBY_MI_ERP_TABLE', 'oby_mi_erp_' );

require_once OBY_MI_ERP_PATH . 'includes/oby-mi-erp-helpers.php';
require_once OBY_MI_ERP_PATH . 'includes/class-oby-mi-erp-activator.php';
require_once OBY_MI_ERP_PATH . 'includes/class-oby-mi-erp.php';

register_activation_hook( __FILE__, array( 'Oby_Mi_Erp_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Oby_Mi_Erp_Activator', 'deactivate' ) );

function oby_mi_erp_bootstrap() {
	new Oby_Mi_Erp();
}
add_action( 'plugins_loaded', 'oby_mi_erp_bootstrap' );

