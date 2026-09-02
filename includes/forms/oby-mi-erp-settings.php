<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_settings_form() {
	check_admin_referer( 'oby_mi_erp_settings_save' );

	$settings = array(
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'company_name'            => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
		'currency_symbol'         => sanitize_text_field( wp_unslash( $_POST['currency_symbol'] ?? '$' ) ),
		'default_tax_rate'        => (float) sanitize_text_field( wp_unslash( $_POST['default_tax_rate'] ?? '' ) ),
		'default_income_account'  => (int) sanitize_text_field( wp_unslash( $_POST['default_income_account'] ?? '' ) ),
		'default_expense_account' => (int) sanitize_text_field( wp_unslash( $_POST['default_expense_account'] ?? '' ) ),
		'cash_account'            => (int) sanitize_text_field( wp_unslash( $_POST['cash_account'] ?? '' ) ),
		'module_accounting'       => sanitize_key( wp_unslash( $_POST['module_accounting'] ?? '' ) ) ? 1 : 0,
		'module_hrm'              => sanitize_key( wp_unslash( $_POST['module_hrm'] ?? '' ) ) ? 1 : 0,
		'module_sales'            => sanitize_key( wp_unslash( $_POST['module_sales'] ?? '' ) ) ? 1 : 0,
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	);

	foreach ( $settings as $key => $value ) {
		oby_mi_erp_set_setting( $key, $value );
	}

	oby_mi_erp_audit_log( 'save', 'settings', 0, 'Updated plugin settings' );
	oby_mi_erp_redirect_notice( __( 'Settings saved.', 'obydullah-micro-erp' ) );
}
