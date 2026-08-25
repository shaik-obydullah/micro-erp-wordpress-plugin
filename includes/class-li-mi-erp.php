<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LI_MI_ERP_PATH . 'includes/forms/fiscal-years.php';
require_once LI_MI_ERP_PATH . 'includes/forms/settings.php';
require_once LI_MI_ERP_PATH . 'includes/forms/contacts.php';
require_once LI_MI_ERP_PATH . 'includes/forms/accounts.php';
require_once LI_MI_ERP_PATH . 'includes/forms/journal.php';
require_once LI_MI_ERP_PATH . 'includes/forms/employees.php';
require_once LI_MI_ERP_PATH . 'includes/forms/departments.php';
require_once LI_MI_ERP_PATH . 'includes/forms/attendance.php';
require_once LI_MI_ERP_PATH . 'includes/forms/leave.php';
require_once LI_MI_ERP_PATH . 'includes/forms/salary.php';
require_once LI_MI_ERP_PATH . 'includes/forms/quotations.php';
require_once LI_MI_ERP_PATH . 'includes/forms/sales.php';

class LiMiErp {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'li_mi_erp_register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'li_mi_erp_enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'li_mi_erp_handle_forms' ) );
		add_filter( 'wp_admin_canonical_url', array( $this, 'li_mi_erp_fix_canonical_url' ) );
	}

	/**
	 * WP core re-encodes "/" as "%2F" in the admin canonical URL, whose JS
	 * then rewrites the browser address bar. Keep slashes readable.
	 */
	public function li_mi_erp_fix_canonical_url( $url ) {
		return str_replace( '%2F', '/', $url );
	}

	public function li_mi_erp_register_menu() {
		$cap = 'manage_options';

		add_menu_page(
			__( 'Micro ERP', 'lime-micro-erp' ),
			__( 'Micro ERP', 'lime-micro-erp' ),
			$cap,
			'micro-erp/dashboard',
			array( $this, 'li_mi_erp_render_page' ),
			'dashicons-chart-area',
			25
		);

		add_submenu_page( 'micro-erp/dashboard', __( 'Dashboard', 'lime-micro-erp' ), __( 'Dashboard', 'lime-micro-erp' ), $cap, 'micro-erp/dashboard', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Contacts', 'lime-micro-erp' ), __( 'Contacts', 'lime-micro-erp' ), $cap, 'micro-erp/contacts', array( $this, 'li_mi_erp_render_page' ) );

		$this->li_mi_erp_add_header( __( 'Accounting', 'lime-micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Chart of Accounts', 'lime-micro-erp' ), __( 'Chart of Accounts', 'lime-micro-erp' ), $cap, 'micro-erp/accounts', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Journal Entries', 'lime-micro-erp' ), __( 'Journal Entries', 'lime-micro-erp' ), $cap, 'micro-erp/journal', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Income', 'lime-micro-erp' ), __( 'Income', 'lime-micro-erp' ), $cap, 'micro-erp/income', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Expenses', 'lime-micro-erp' ), __( 'Expenses', 'lime-micro-erp' ), $cap, 'micro-erp/expenses', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Payable', 'lime-micro-erp' ), __( 'Payable', 'lime-micro-erp' ), $cap, 'micro-erp/payable', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Receivable', 'lime-micro-erp' ), __( 'Receivable', 'lime-micro-erp' ), $cap, 'micro-erp/receivable', array( $this, 'li_mi_erp_render_page' ) );

		$this->li_mi_erp_add_header( __( 'HRM', 'lime-micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Employees', 'lime-micro-erp' ), __( 'Employees', 'lime-micro-erp' ), $cap, 'micro-erp/employees', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Departments', 'lime-micro-erp' ), __( 'Departments', 'lime-micro-erp' ), $cap, 'micro-erp/departments', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Attendance', 'lime-micro-erp' ), __( 'Attendance', 'lime-micro-erp' ), $cap, 'micro-erp/attendance', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Leave', 'lime-micro-erp' ), __( 'Leave', 'lime-micro-erp' ), $cap, 'micro-erp/leave', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Salary', 'lime-micro-erp' ), __( 'Salary', 'lime-micro-erp' ), $cap, 'micro-erp/salary', array( $this, 'li_mi_erp_render_page' ) );

		$this->li_mi_erp_add_header( __( 'Sales', 'lime-micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Quotations', 'lime-micro-erp' ), __( 'Quotations', 'lime-micro-erp' ), $cap, 'micro-erp/quotations', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Sales Orders', 'lime-micro-erp' ), __( 'Sales Orders', 'lime-micro-erp' ), $cap, 'micro-erp/sales', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Reports', 'lime-micro-erp' ), __( 'Reports', 'lime-micro-erp' ), $cap, 'micro-erp/sales-reports', array( $this, 'li_mi_erp_render_page' ) );

		add_submenu_page( 'micro-erp/dashboard', __( 'Settings', 'lime-micro-erp' ), __( 'Settings', 'lime-micro-erp' ), $cap, 'micro-erp/settings', array( $this, 'li_mi_erp_render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Fiscal Years', 'lime-micro-erp' ), __( 'Fiscal Years', 'lime-micro-erp' ), $cap, 'micro-erp/fiscal-years', array( $this, 'li_mi_erp_render_page' ) );
	}

	private function li_mi_erp_add_header( $text ) {
		add_submenu_page(
			'micro-erp/dashboard',
			'',
			'<span class="micro-erp-menu-header">' . esc_html( $text ) . '</span>',
			'manage_options',
			'micro-erp-header-' . sanitize_title( $text ),
			'__return_false'
		);
	}

	public function li_mi_erp_enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'micro-erp' ) === false ) {
			return;
		}
		$css_ver = LI_MI_ERP_VERSION . '.' . (int) filemtime( LI_MI_ERP_PATH . 'assets/css/li-mi-erp-admin.css' );
		wp_enqueue_style( 'li-mi-erp-base', LI_MI_ERP_URL . 'assets/css/base.css', array(), $css_ver );
		wp_enqueue_style( 'li-mi-erp-admin', LI_MI_ERP_URL . 'assets/css/li-mi-erp-admin.css', array( 'li-mi-erp-base' ), $css_ver );
		wp_enqueue_script( 'li-mi-erp-admin', LI_MI_ERP_URL . 'assets/js/li-mi-erp-admin.js', array( 'jquery' ), LI_MI_ERP_VERSION, true );
	}

	public function li_mi_erp_handle_forms() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['li_mi_erp_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['li_mi_erp_action'] ) );

		switch ( $action ) {
			case 'save_fiscal_year':
				li_mi_erp_handle_fiscal_year_form();
				break;
			case 'delete_fiscal_year':
				li_mi_erp_handle_delete_fiscal_year();
				break;
			case 'activate_fiscal_year':
				li_mi_erp_handle_activate_fiscal_year();
				break;
			case 'save_settings':
				li_mi_erp_handle_settings_form();
				break;
			case 'save_contact':
			case 'update_contact':
				li_mi_erp_handle_contact_form( $action );
				break;
			case 'delete_contact':
				li_mi_erp_handle_delete_contact();
				break;
			case 'save_account':
			case 'update_account':
				li_mi_erp_handle_account_form( $action );
				break;
			case 'delete_account':
				li_mi_erp_handle_delete_account();
				break;
			case 'save_journal':
				li_mi_erp_handle_journal_form();
				break;
			case 'save_transaction':
				li_mi_erp_handle_transaction_form();
				break;
			case 'delete_journal':
				li_mi_erp_handle_delete_journal();
				break;
			case 'save_employee':
			case 'update_employee':
				li_mi_erp_handle_employee_form( $action );
				break;
			case 'delete_employee':
				li_mi_erp_handle_delete_employee();
				break;
			case 'save_department':
			case 'update_department':
				li_mi_erp_handle_department_form( $action );
				break;
			case 'delete_department':
				li_mi_erp_handle_delete_department();
				break;
			case 'save_attendance':
				li_mi_erp_handle_attendance_form();
				break;
			case 'delete_attendance':
				li_mi_erp_handle_delete_attendance();
				break;
			case 'save_leave_type':
			case 'update_leave_type':
				li_mi_erp_handle_leave_type_form( $action );
				break;
			case 'delete_leave_type':
				li_mi_erp_handle_delete_leave_type();
				break;
			case 'save_leave_request':
				li_mi_erp_handle_leave_request_form();
				break;
			case 'approve_leave':
				li_mi_erp_handle_leave_status( 'approved' );
				break;
			case 'reject_leave':
				li_mi_erp_handle_leave_status( 'rejected' );
				break;
			case 'mark_salary_paid':
				li_mi_erp_handle_salary_paid();
				break;
			case 'save_quotation':
			case 'update_quotation':
				li_mi_erp_handle_quotation_form( $action );
				break;
			case 'delete_quotation':
				li_mi_erp_handle_delete_quotation();
				break;
			case 'quotation_status':
				li_mi_erp_handle_quotation_status();
				break;
			case 'convert_quotation':
				li_mi_erp_handle_convert_quotation();
				break;
			case 'save_sale':
			case 'update_sale':
				li_mi_erp_handle_sale_form( $action );
				break;
			case 'delete_sale':
				li_mi_erp_handle_delete_sale();
				break;
			case 'record_payment':
				li_mi_erp_handle_record_payment();
				break;
		}

		$redirect = isset( $_POST['li_mi_erp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['li_mi_erp_redirect'] ) ) : admin_url( 'admin.php?page=micro-erp/dashboard' );
		wp_safe_redirect( $redirect );
		exit;
	}

	public function li_mi_erp_render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Menu page slug from WP core routing, read-only.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'micro-erp/dashboard';

		if ( strpos( $page, 'micro-erp-header-' ) === 0 ) {
			$redirect_map = array(
				'micro-erp-header-accounting' => 'micro-erp/accounts',
				'micro-erp-header-hrm'        => 'micro-erp/employees',
				'micro-erp-header-sales'       => 'micro-erp/quotations',
			);
			$target = isset( $redirect_map[ $page ] ) ? $redirect_map[ $page ] : 'micro-erp/dashboard';
			wp_safe_redirect( admin_url( 'admin.php?page=' . $target ) );
			exit;
		}

		$slug = str_replace( 'micro-erp/', '', $page );
		$file = LI_MI_ERP_PATH . 'admin/partials/' . $slug . '.php';

		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Micro ERP', 'lime-micro-erp' ) . '</h1><p>' . esc_html__( 'Page not found.', 'lime-micro-erp' ) . '</p></div>';
		}
	}
}