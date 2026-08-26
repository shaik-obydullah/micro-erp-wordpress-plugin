<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_settings_form() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_settings_save' );

	$settings = array(
		'company_name'            => isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '',
		'currency_symbol'         => isset( $_POST['currency_symbol'] ) ? sanitize_text_field( wp_unslash( $_POST['currency_symbol'] ) ) : '$',
		'default_tax_rate'        => isset( $_POST['default_tax_rate'] ) ? (float) $_POST['default_tax_rate'] : 0,
		'default_income_account'  => isset( $_POST['default_income_account'] ) ? (int) $_POST['default_income_account'] : 0,
		'default_expense_account' => isset( $_POST['default_expense_account'] ) ? (int) $_POST['default_expense_account'] : 0,
		'cash_account'            => isset( $_POST['cash_account'] ) ? (int) $_POST['cash_account'] : 0,
		'module_accounting'       => isset( $_POST['module_accounting'] ) ? 1 : 0,
		'module_hrm'              => isset( $_POST['module_hrm'] ) ? 1 : 0,
		'module_sales'            => isset( $_POST['module_sales'] ) ? 1 : 0,
	);

	foreach ( $settings as $key => $value ) {
		oby_mi_erp_set_setting( $key, $value );
	}

	oby_mi_erp_audit_log( 'save', 'settings', 0, 'Updated plugin settings' );
	oby_mi_erp_redirect_notice( __( 'Settings saved.', 'obydullah-micro-erp' ) );
}
