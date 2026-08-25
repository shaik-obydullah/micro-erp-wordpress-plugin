<?php
/**
 * Plugin Name: Lime Micro ERP
 * Author URI: https://obydullah.com
 * Plugin URI: https://obydullah.com/project/micro-erp-wordpress-plugin
 * Description: Lime Micro ERP — a lightweight ERP system for small businesses: contacts, accounting, HRM, and sales management, all inside WordPress.
 * Author: Shaik Obydullah
 * Text Domain: lime-micro-erp
 * Version: 1.0.0
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'LI_MI_ERP_VERSION', '1.0.0' );
define( 'LI_MI_ERP_FILE', __FILE__ );
define( 'LI_MI_ERP_PATH', plugin_dir_path( __FILE__ ) );
define( 'LI_MI_ERP_URL', plugin_dir_url( __FILE__ ) );
define( 'LI_MI_ERP_TABLE', 'micro_erp_' );

require_once LI_MI_ERP_PATH . 'includes/helpers.php';
require_once LI_MI_ERP_PATH . 'includes/class-li-mi-erp-activator.php';
require_once LI_MI_ERP_PATH . 'includes/class-li-mi-erp.php';

register_activation_hook( __FILE__, array( 'LiMiErp_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LiMiErp_Activator', 'deactivate' ) );

function li_mi_erp_bootstrap() {
	new LiMiErp();
}
add_action( 'plugins_loaded', 'li_mi_erp_bootstrap' );

