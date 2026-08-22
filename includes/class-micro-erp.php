<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MICRO_ERP_PATH . 'includes/forms/fiscal-years.php';
require_once MICRO_ERP_PATH . 'includes/forms/settings.php';
require_once MICRO_ERP_PATH . 'includes/forms/contacts.php';
require_once MICRO_ERP_PATH . 'includes/forms/accounts.php';
require_once MICRO_ERP_PATH . 'includes/forms/journal.php';
require_once MICRO_ERP_PATH . 'includes/forms/employees.php';
require_once MICRO_ERP_PATH . 'includes/forms/departments.php';
require_once MICRO_ERP_PATH . 'includes/forms/attendance.php';
require_once MICRO_ERP_PATH . 'includes/forms/leave.php';
require_once MICRO_ERP_PATH . 'includes/forms/salary.php';
require_once MICRO_ERP_PATH . 'includes/forms/quotations.php';
require_once MICRO_ERP_PATH . 'includes/forms/sales.php';

class MicroERP {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_forms' ) );
		add_filter( 'wp_admin_canonical_url', array( $this, 'fix_canonical_url' ) );
	}

	/**
	 * WP core re-encodes "/" as "%2F" in the admin canonical URL, whose JS
	 * then rewrites the browser address bar. Keep slashes readable.
	 */
	public function fix_canonical_url( $url ) {
		return str_replace( '%2F', '/', $url );
	}

	public function register_menu() {
		$cap = 'manage_options';

		add_menu_page(
			__( 'Micro ERP', 'micro-erp' ),
			__( 'Micro ERP', 'micro-erp' ),
			$cap,
			'micro-erp/dashboard',
			array( $this, 'render_page' ),
			'dashicons-chart-area',
			25
		);

		add_submenu_page( 'micro-erp/dashboard', __( 'Dashboard', 'micro-erp' ), __( 'Dashboard', 'micro-erp' ), $cap, 'micro-erp/dashboard', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Contacts', 'micro-erp' ), __( 'Contacts', 'micro-erp' ), $cap, 'micro-erp/contacts', array( $this, 'render_page' ) );

		$this->add_header( __( 'Accounting', 'micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Chart of Accounts', 'micro-erp' ), __( 'Chart of Accounts', 'micro-erp' ), $cap, 'micro-erp/accounts', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Journal Entries', 'micro-erp' ), __( 'Journal Entries', 'micro-erp' ), $cap, 'micro-erp/journal', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Income', 'micro-erp' ), __( 'Income', 'micro-erp' ), $cap, 'micro-erp/income', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Expenses', 'micro-erp' ), __( 'Expenses', 'micro-erp' ), $cap, 'micro-erp/expenses', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Payable', 'micro-erp' ), __( 'Payable', 'micro-erp' ), $cap, 'micro-erp/payable', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Receivable', 'micro-erp' ), __( 'Receivable', 'micro-erp' ), $cap, 'micro-erp/receivable', array( $this, 'render_page' ) );

		$this->add_header( __( 'HRM', 'micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Employees', 'micro-erp' ), __( 'Employees', 'micro-erp' ), $cap, 'micro-erp/employees', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Departments', 'micro-erp' ), __( 'Departments', 'micro-erp' ), $cap, 'micro-erp/departments', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Attendance', 'micro-erp' ), __( 'Attendance', 'micro-erp' ), $cap, 'micro-erp/attendance', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Leave', 'micro-erp' ), __( 'Leave', 'micro-erp' ), $cap, 'micro-erp/leave', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Salary', 'micro-erp' ), __( 'Salary', 'micro-erp' ), $cap, 'micro-erp/salary', array( $this, 'render_page' ) );

		$this->add_header( __( 'Sales', 'micro-erp' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Quotations', 'micro-erp' ), __( 'Quotations', 'micro-erp' ), $cap, 'micro-erp/quotations', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Sales Orders', 'micro-erp' ), __( 'Sales Orders', 'micro-erp' ), $cap, 'micro-erp/sales', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Reports', 'micro-erp' ), __( 'Reports', 'micro-erp' ), $cap, 'micro-erp/sales-reports', array( $this, 'render_page' ) );

		add_submenu_page( 'micro-erp/dashboard', __( 'Settings', 'micro-erp' ), __( 'Settings', 'micro-erp' ), $cap, 'micro-erp/settings', array( $this, 'render_page' ) );
		add_submenu_page( 'micro-erp/dashboard', __( 'Fiscal Years', 'micro-erp' ), __( 'Fiscal Years', 'micro-erp' ), $cap, 'micro-erp/fiscal-years', array( $this, 'render_page' ) );
	}

	private function add_header( $text ) {
		add_submenu_page(
			'micro-erp/dashboard',
			'',
			'<span class="micro-erp-menu-header">' . esc_html( $text ) . '</span>',
			'manage_options',
			'micro-erp-header-' . sanitize_title( $text ),
			'__return_false'
		);
	}

	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'micro-erp' ) === false ) {
			return;
		}
		$css_ver = MICRO_ERP_VERSION . '.' . (int) filemtime( MICRO_ERP_PATH . 'assets/css/micro-erp-admin.css' );
		wp_enqueue_style( 'micro-erp-base', MICRO_ERP_URL . 'assets/css/base.css', array(), $css_ver );
		wp_enqueue_style( 'micro-erp-admin', MICRO_ERP_URL . 'assets/css/micro-erp-admin.css', array( 'micro-erp-base' ), $css_ver );
		wp_enqueue_script( 'micro-erp-admin', MICRO_ERP_URL . 'assets/js/micro-erp-admin.js', array( 'jquery' ), MICRO_ERP_VERSION, true );
	}

	public function handle_forms() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['micro_erp_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['micro_erp_action'] ) );

		switch ( $action ) {
			case 'save_fiscal_year':
				micro_erp_handle_fiscal_year_form();
				break;
			case 'delete_fiscal_year':
				micro_erp_handle_delete_fiscal_year();
				break;
			case 'activate_fiscal_year':
				micro_erp_handle_activate_fiscal_year();
				break;
			case 'save_settings':
				micro_erp_handle_settings_form();
				break;
			case 'save_contact':
			case 'update_contact':
				micro_erp_handle_contact_form( $action );
				break;
			case 'delete_contact':
				micro_erp_handle_delete_contact();
				break;
			case 'save_account':
			case 'update_account':
				micro_erp_handle_account_form( $action );
				break;
			case 'delete_account':
				micro_erp_handle_delete_account();
				break;
			case 'save_journal':
				micro_erp_handle_journal_form();
				break;
			case 'save_transaction':
				micro_erp_handle_transaction_form();
				break;
			case 'delete_journal':
				micro_erp_handle_delete_journal();
				break;
			case 'save_employee':
			case 'update_employee':
				micro_erp_handle_employee_form( $action );
				break;
			case 'delete_employee':
				micro_erp_handle_delete_employee();
				break;
			case 'save_department':
			case 'update_department':
				micro_erp_handle_department_form( $action );
				break;
			case 'delete_department':
				micro_erp_handle_delete_department();
				break;
			case 'save_attendance':
				micro_erp_handle_attendance_form();
				break;
			case 'delete_attendance':
				micro_erp_handle_delete_attendance();
				break;
			case 'save_leave_type':
			case 'update_leave_type':
				micro_erp_handle_leave_type_form( $action );
				break;
			case 'delete_leave_type':
				micro_erp_handle_delete_leave_type();
				break;
			case 'save_leave_request':
				micro_erp_handle_leave_request_form();
				break;
			case 'approve_leave':
				micro_erp_handle_leave_status( 'approved' );
				break;
			case 'reject_leave':
				micro_erp_handle_leave_status( 'rejected' );
				break;
			case 'mark_salary_paid':
				micro_erp_handle_salary_paid();
				break;
			case 'save_quotation':
			case 'update_quotation':
				micro_erp_handle_quotation_form( $action );
				break;
			case 'delete_quotation':
				micro_erp_handle_delete_quotation();
				break;
			case 'quotation_status':
				micro_erp_handle_quotation_status();
				break;
			case 'convert_quotation':
				micro_erp_handle_convert_quotation();
				break;
			case 'save_sale':
			case 'update_sale':
				micro_erp_handle_sale_form( $action );
				break;
			case 'delete_sale':
				micro_erp_handle_delete_sale();
				break;
			case 'record_payment':
				micro_erp_handle_record_payment();
				break;
		}

		$redirect = isset( $_POST['micro_erp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['micro_erp_redirect'] ) ) : admin_url( 'admin.php?page=micro-erp/dashboard' );
		wp_safe_redirect( $redirect );
		exit;
	}

	public function render_page() {
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
		$file = MICRO_ERP_PATH . 'admin/partials/' . $slug . '.php';

		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Micro ERP', 'micro-erp' ) . '</h1><p>' . esc_html__( 'Page not found.', 'micro-erp' ) . '</p></div>';
		}
	}
}
