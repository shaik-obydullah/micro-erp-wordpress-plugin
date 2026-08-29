<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ObyMiErp_Activator {

	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$t       = $wpdb->prefix . OBY_MI_ERP_TABLE;

		$sql = array();

		$sql[] = "CREATE TABLE {$t}fiscal_years (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY is_active (is_active)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}contacts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(20) NOT NULL DEFAULT 'customer',
			name varchar(191) NOT NULL,
			email varchar(191) DEFAULT NULL,
			phone varchar(50) DEFAULT NULL,
			address text DEFAULT NULL,
			company varchar(191) DEFAULT NULL,
			tax_id varchar(100) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY type (type),
			KEY status (status),
			KEY name (name)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}settings (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			option_key varchar(100) NOT NULL,
			option_value longtext DEFAULT NULL,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY option_key (option_key)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}audit_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			action varchar(100) NOT NULL,
			entity_type varchar(50) NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			description text DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY entity_type (entity_type),
			KEY entity_id (entity_id),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}accounts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(50) NOT NULL,
			name varchar(191) NOT NULL,
			type varchar(50) NOT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY type (type),
			KEY parent_id (parent_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}journal_entries (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_date date NOT NULL,
			reference_type varchar(50) DEFAULT NULL,
			reference_id bigint(20) unsigned DEFAULT NULL,
			description varchar(255) NOT NULL,
			fiscal_year_id bigint(20) unsigned NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY entry_date (entry_date),
			KEY reference_type (reference_type),
			KEY reference_id (reference_id),
			KEY fiscal_year_id (fiscal_year_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}journal_lines (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) unsigned NOT NULL,
			account_id bigint(20) unsigned NOT NULL,
			debit decimal(12,2) NOT NULL DEFAULT 0.00,
			credit decimal(12,2) NOT NULL DEFAULT 0.00,
			description varchar(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY entry_id (entry_id),
			KEY account_id (account_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}departments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			description text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}employees (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			employee_id varchar(50) NOT NULL,
			name varchar(191) NOT NULL,
			email varchar(191) DEFAULT NULL,
			phone varchar(50) DEFAULT NULL,
			department_id bigint(20) unsigned DEFAULT NULL,
			designation varchar(100) DEFAULT NULL,
			date_of_join date DEFAULT NULL,
			date_of_birth date DEFAULT NULL,
			gender varchar(20) DEFAULT NULL,
			address text DEFAULT NULL,
			basic_salary decimal(12,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY employee_id (employee_id),
			KEY user_id (user_id),
			KEY department_id (department_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}attendance (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			employee_id bigint(20) unsigned NOT NULL,
			date date NOT NULL,
			check_in time DEFAULT NULL,
			check_out time DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'present',
			hours_worked decimal(4,2) DEFAULT NULL,
			notes text DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY employee_id (employee_id),
			KEY date (date),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}leave_types (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			days_per_year int(3) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}leave_requests (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			employee_id bigint(20) unsigned NOT NULL,
			leave_type_id bigint(20) unsigned NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			total_days decimal(4,1) NOT NULL,
			reason text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			approved_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY employee_id (employee_id),
			KEY leave_type_id (leave_type_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}salary_payments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			employee_id bigint(20) unsigned NOT NULL,
			month varchar(7) NOT NULL,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			allowances decimal(12,2) NOT NULL DEFAULT 0.00,
			deductions decimal(12,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'unpaid',
			paid_at datetime DEFAULT NULL,
			journal_entry_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY emp_month (employee_id, month),
			KEY month (month),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}quotations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quotation_no varchar(50) NOT NULL,
			contact_id bigint(20) unsigned NOT NULL,
			quotation_date date NOT NULL,
			valid_until date DEFAULT NULL,
			subtotal decimal(12,2) NOT NULL DEFAULT 0.00,
			tax_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			discount decimal(12,2) NOT NULL DEFAULT 0.00,
			total decimal(12,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'draft',
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY quotation_no (quotation_no),
			KEY contact_id (contact_id),
			KEY status (status),
			KEY quotation_date (quotation_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}quotation_items (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			quotation_id bigint(20) unsigned NOT NULL,
			description varchar(255) NOT NULL,
			quantity decimal(10,2) NOT NULL DEFAULT 1.00,
			unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
			tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
			total decimal(12,2) NOT NULL DEFAULT 0.00,
			PRIMARY KEY  (id),
			KEY quotation_id (quotation_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}sales (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sale_no varchar(50) NOT NULL,
			quotation_id bigint(20) unsigned DEFAULT NULL,
			contact_id bigint(20) unsigned NOT NULL,
			sale_date date NOT NULL,
			payment_status varchar(20) NOT NULL DEFAULT 'unpaid',
			payment_method varchar(50) DEFAULT NULL,
			subtotal decimal(12,2) NOT NULL DEFAULT 0.00,
			tax_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			discount decimal(12,2) NOT NULL DEFAULT 0.00,
			total decimal(12,2) NOT NULL DEFAULT 0.00,
			amount_paid decimal(12,2) NOT NULL DEFAULT 0.00,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY sale_no (sale_no),
			KEY quotation_id (quotation_id),
			KEY contact_id (contact_id),
			KEY payment_status (payment_status),
			KEY sale_date (sale_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$t}sale_items (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sale_id bigint(20) unsigned NOT NULL,
			description varchar(255) NOT NULL,
			quantity decimal(10,2) NOT NULL DEFAULT 1.00,
			unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
			tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
			total decimal(12,2) NOT NULL DEFAULT 0.00,
			PRIMARY KEY  (id),
			KEY sale_id (sale_id)
		) $charset;";

		foreach ( $sql as $q ) {
			dbDelta( $q );
		}

		// Default Chart of Accounts.
		$account_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t}accounts WHERE 1 = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $t is the plugin's fixed table-name constant, not user input.
		if ( ! $account_count ) {
			$default_accounts = array(
				array( '1001', 'Cash', 'asset' ),
				array( '1002', 'Bank Account', 'asset' ),
				array( '1003', 'Accounts Receivable', 'asset' ),
				array( '2001', 'Accounts Payable', 'liability' ),
				array( '2002', 'Tax Payable', 'liability' ),
				array( '3001', 'Owner Equity', 'equity' ),
				array( '4001', 'Sales Income', 'income' ),
				array( '4002', 'Service Income', 'income' ),
				array( '5001', 'Salary Expense', 'expense' ),
				array( '5002', 'Rent Expense', 'expense' ),
				array( '5003', 'Utilities Expense', 'expense' ),
				array( '5004', 'Office Supplies', 'expense' ),
				array( '5005', 'Marketing Expense', 'expense' ),
			);
			foreach ( $default_accounts as $account ) {
				$wpdb->insert(
					"{$t}accounts",
					array(
						'code' => $account[0],
						'name' => $account[1],
						'type' => $account[2],
					),
					array( '%s', '%s', '%s' )
				);
			}
		}

		// Default leave types.
		$lt_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t}leave_types WHERE 1 = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $t is the plugin's fixed table-name constant, not user input.
		if ( ! $lt_count ) {
			$default_leave_types = array(
				array( 'Annual Leave', 12 ),
				array( 'Sick Leave', 10 ),
				array( 'Casual Leave', 7 ),
				array( 'Maternity Leave', 90 ),
			);
			foreach ( $default_leave_types as $leave_type ) {
				$wpdb->insert(
					"{$t}leave_types",
					array(
						'name'          => $leave_type[0],
						'days_per_year' => $leave_type[1],
					),
					array( '%s', '%d' )
				);
			}
		}

		// Default fiscal year covering the current calendar year.
		$fy_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t}fiscal_years WHERE 1 = %d", 1 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $t is the plugin's fixed table-name constant, not user input.
		if ( ! $fy_count ) {
			$year = (int) date_i18n( 'Y' );
			$wpdb->insert(
				"{$t}fiscal_years",
				array(
					'name'       => sprintf( 'FY %d-%d', $year, $year + 1 ),
					'start_date' => $year . '-01-01',
					'end_date'   => $year . '-12-31',
					'is_active'  => 1,
				),
				array( '%s', '%s', '%s', '%d' )
			);
		}

		update_option( 'oby_mi_erp_version', OBY_MI_ERP_VERSION );
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
