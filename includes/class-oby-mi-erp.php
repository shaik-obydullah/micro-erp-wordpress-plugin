<?php
/**
 * Core plugin class: registers the admin menu, enqueues assets, dispatches
 * form submissions to the right handler, and renders admin screens.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-fiscal-years.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-settings.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-contacts.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-accounts.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-journal.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-employees.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-departments.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-attendance.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-leave.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-salary.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-quotations.php';
require_once OBY_MI_ERP_PATH . 'includes/forms/oby-mi-erp-sales.php';

/**
 * Bootstraps the plugin's admin menu, assets, and request handling.
 */
class ObyMiErp {

	/**
	 * Register the plugin's WordPress admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'oby_mi_erp_register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'oby_mi_erp_enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'oby_mi_erp_handle_forms' ) );
		add_filter( 'wp_admin_canonical_url', array( $this, 'oby_mi_erp_fix_canonical_url' ) );
	}

	/**
	 * WP core re-encodes "/" as "%2F" in the admin canonical URL, whose JS
	 * then rewrites the browser address bar. Keep slashes readable.
	 *
	 * @param string $url Canonical admin URL.
	 * @return string Filtered URL.
	 */
	public function oby_mi_erp_fix_canonical_url( $url ) {
		return str_replace( '%2F', '/', $url );
	}

	/**
	 * Register the plugin's top-level admin menu and all of its submenu pages.
	 *
	 * @return void
	 */
	public function oby_mi_erp_register_menu() {
		$cap = 'manage_options';

		add_menu_page(
			__( 'Obydullah Micro ERP', 'obydullah-micro-erp' ),
			__( 'Micro ERP', 'obydullah-micro-erp' ),
			$cap,
			'oby-mi-erp/dashboard',
			array( $this, 'oby_mi_erp_render_page' ),
			'dashicons-chart-area',
			57
		);

		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Dashboard', 'obydullah-micro-erp' ), __( 'Dashboard', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/dashboard', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Contacts', 'obydullah-micro-erp' ), __( 'Contacts', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/contacts', array( $this, 'oby_mi_erp_render_page' ) );

		$this->oby_mi_erp_add_header( __( 'Accounting', 'obydullah-micro-erp' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Chart of Accounts', 'obydullah-micro-erp' ), __( 'Chart of Accounts', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/accounts', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Journal Entries', 'obydullah-micro-erp' ), __( 'Journal Entries', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/journal', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Income', 'obydullah-micro-erp' ), __( 'Income', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/income', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Expenses', 'obydullah-micro-erp' ), __( 'Expenses', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/expenses', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Payable', 'obydullah-micro-erp' ), __( 'Payable', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/payable', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Receivable', 'obydullah-micro-erp' ), __( 'Receivable', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/receivable', array( $this, 'oby_mi_erp_render_page' ) );

		$this->oby_mi_erp_add_header( __( 'HRM', 'obydullah-micro-erp' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Employees', 'obydullah-micro-erp' ), __( 'Employees', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/employees', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Departments', 'obydullah-micro-erp' ), __( 'Departments', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/departments', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Attendance', 'obydullah-micro-erp' ), __( 'Attendance', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/attendance', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Leave', 'obydullah-micro-erp' ), __( 'Leave', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/leave', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Salary', 'obydullah-micro-erp' ), __( 'Salary', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/salary', array( $this, 'oby_mi_erp_render_page' ) );

		$this->oby_mi_erp_add_header( __( 'Sales', 'obydullah-micro-erp' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Quotations', 'obydullah-micro-erp' ), __( 'Quotations', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/quotations', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Sales Orders', 'obydullah-micro-erp' ), __( 'Sales Orders', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/sales', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Reports', 'obydullah-micro-erp' ), __( 'Reports', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/sales-reports', array( $this, 'oby_mi_erp_render_page' ) );

		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Settings', 'obydullah-micro-erp' ), __( 'Settings', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/settings', array( $this, 'oby_mi_erp_render_page' ) );
		add_submenu_page( 'oby-mi-erp/dashboard', __( 'Fiscal Years', 'obydullah-micro-erp' ), __( 'Fiscal Years', 'obydullah-micro-erp' ), $cap, 'oby-mi-erp/fiscal-years', array( $this, 'oby_mi_erp_render_page' ) );
	}

	/**
	 * Add a non-clickable section-header submenu item (e.g. "Accounting", "HRM").
	 *
	 * @param string $text Header label.
	 * @return void
	 */
	private function oby_mi_erp_add_header( $text ) {
		add_submenu_page(
			'oby-mi-erp/dashboard',
			'',
			'<span class="oby-mi-erp-menu-header">' . esc_html( $text ) . '</span>',
			'manage_options',
			'oby-mi-erp-header-' . sanitize_title( $text ),
			'__return_false'
		);
	}

	/**
	 * Enqueue the plugin's admin CSS/JS, only on the plugin's own screens.
	 *
	 * @return void
	 */
	public function oby_mi_erp_enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'oby-mi-erp' ) === false ) {
			return;
		}
		$css_ver = OBY_MI_ERP_VERSION . '.' . (int) filemtime( OBY_MI_ERP_PATH . 'assets/css/oby-mi-erp-admin.css' );
		wp_enqueue_style( 'oby-mi-erp-base', OBY_MI_ERP_URL . 'assets/css/oby-mi-erp-base.css', array(), $css_ver );
		wp_enqueue_style( 'oby-mi-erp-admin', OBY_MI_ERP_URL . 'assets/css/oby-mi-erp-admin.css', array( 'oby-mi-erp-base' ), $css_ver );
		wp_enqueue_script( 'oby-mi-erp-admin', OBY_MI_ERP_URL . 'assets/js/oby-mi-erp-admin.js', array( 'jquery' ), OBY_MI_ERP_VERSION, true );
	}

	/**
	 * Dispatch a submitted plugin form (via $_POST['oby_mi_erp_action']) to its
	 * handler, then redirect back to the calling page. Each individual handler
	 * verifies its own nonce before touching any data.
	 *
	 * @return void
	 */
	public function oby_mi_erp_handle_forms() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'obydullah-micro-erp' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only used to route to a handler below; each handler verifies its own nonce via oby_mi_erp_verify_nonce() before touching any data.
		$action = sanitize_key( wp_unslash( $_POST['oby_mi_erp_action'] ?? '' ) );

		if ( '' === $action ) {
			return;
		}

		switch ( $action ) {
			case 'save_fiscal_year':
				oby_mi_erp_handle_fiscal_year_form();
				break;
			case 'delete_fiscal_year':
				oby_mi_erp_handle_delete_fiscal_year();
				break;
			case 'activate_fiscal_year':
				oby_mi_erp_handle_activate_fiscal_year();
				break;
			case 'save_settings':
				oby_mi_erp_handle_settings_form();
				break;
			case 'save_contact':
			case 'update_contact':
				oby_mi_erp_handle_contact_form( $action );
				break;
			case 'delete_contact':
				oby_mi_erp_handle_delete_contact();
				break;
			case 'save_account':
			case 'update_account':
				oby_mi_erp_handle_account_form( $action );
				break;
			case 'delete_account':
				oby_mi_erp_handle_delete_account();
				break;
			case 'save_journal':
				oby_mi_erp_handle_journal_form();
				break;
			case 'save_transaction':
				oby_mi_erp_handle_transaction_form();
				break;
			case 'delete_journal':
				oby_mi_erp_handle_delete_journal();
				break;
			case 'save_employee':
			case 'update_employee':
				oby_mi_erp_handle_employee_form( $action );
				break;
			case 'delete_employee':
				oby_mi_erp_handle_delete_employee();
				break;
			case 'save_department':
			case 'update_department':
				oby_mi_erp_handle_department_form( $action );
				break;
			case 'delete_department':
				oby_mi_erp_handle_delete_department();
				break;
			case 'save_attendance':
				oby_mi_erp_handle_attendance_form();
				break;
			case 'delete_attendance':
				oby_mi_erp_handle_delete_attendance();
				break;
			case 'save_leave_type':
			case 'update_leave_type':
				oby_mi_erp_handle_leave_type_form( $action );
				break;
			case 'delete_leave_type':
				oby_mi_erp_handle_delete_leave_type();
				break;
			case 'save_leave_request':
				oby_mi_erp_handle_leave_request_form();
				break;
			case 'approve_leave':
				oby_mi_erp_handle_leave_status( 'approved' );
				break;
			case 'reject_leave':
				oby_mi_erp_handle_leave_status( 'rejected' );
				break;
			case 'mark_salary_paid':
				oby_mi_erp_handle_salary_paid();
				break;
			case 'save_quotation':
			case 'update_quotation':
				oby_mi_erp_handle_quotation_form();
				break;
			case 'delete_quotation':
				oby_mi_erp_handle_delete_quotation();
				break;
			case 'quotation_status':
				oby_mi_erp_handle_quotation_status();
				break;
			case 'convert_quotation':
				oby_mi_erp_handle_convert_quotation();
				break;
			case 'save_sale':
			case 'update_sale':
				oby_mi_erp_handle_sale_form();
				break;
			case 'delete_sale':
				oby_mi_erp_handle_delete_sale();
				break;
			case 'record_payment':
				oby_mi_erp_handle_record_payment();
				break;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read after the handler above already verified its own nonce; value is only used as a wp_safe_redirect() target, which only allows local/whitelisted hosts.
		$redirect = isset( $_POST['oby_mi_erp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['oby_mi_erp_redirect'] ) ) : admin_url( 'admin.php?page=oby-mi-erp/dashboard' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render the plugin admin screen matching the current 'page' query var,
	 * redirecting section-header menu clicks to their first real subpage.
	 *
	 * @return void
	 */
	public function oby_mi_erp_render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Menu page slug from WP core routing, read-only.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'oby-mi-erp/dashboard';

		if ( strpos( $page, 'oby-mi-erp-header-' ) === 0 ) {
			$redirect_map = array(
				'oby-mi-erp-header-accounting' => 'oby-mi-erp/accounts',
				'oby-mi-erp-header-hrm'        => 'oby-mi-erp/employees',
				'oby-mi-erp-header-sales'      => 'oby-mi-erp/quotations',
			);
			$target       = isset( $redirect_map[ $page ] ) ? $redirect_map[ $page ] : 'oby-mi-erp/dashboard';
			wp_safe_redirect( admin_url( 'admin.php?page=' . $target ) );
			exit;
		}

		$slug = str_replace( 'oby-mi-erp/', '', $page );
		$file = OBY_MI_ERP_PATH . 'admin/partials/oby-mi-erp-' . $slug . '.php';

		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Obydullah Micro ERP', 'obydullah-micro-erp' ) . '</h1><p>' . esc_html__( 'Page not found.', 'obydullah-micro-erp' ) . '</p></div>';
		}
	}
}
