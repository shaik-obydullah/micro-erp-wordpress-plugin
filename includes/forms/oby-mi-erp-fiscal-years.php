<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_fiscal_year_form() {
	check_admin_referer( 'oby_mi_erp_fiscal_year_save' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$start_date = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
	$end_date   = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
	$id         = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $name || ! $start_date || ! $end_date ) {
		oby_mi_erp_redirect_notice( __( 'Name, start date and end date are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'fiscal_years' );
	$data  = array(
		'name'       => $name,
		'start_date' => $start_date,
		'end_date'   => $end_date,
	);

	if ( $id ) {
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Fiscal year updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data + array( 'is_active' => 0 ), array( '%s', '%s', '%s', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Fiscal year created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_audit_log( 'save', 'fiscal_year', $entity_id, $name );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_fiscal_year() {
	check_admin_referer( 'oby_mi_erp_fiscal_year_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'fiscal_years' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'delete', 'fiscal_year', $id, 'Deleted fiscal year #' . $id );
	oby_mi_erp_redirect_notice( __( 'Fiscal year deleted.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_activate_fiscal_year() {
	check_admin_referer( 'oby_mi_erp_fiscal_year_activate' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$table = oby_mi_erp_table( 'fiscal_years' );
	$wpdb->update( $table, array( 'is_active' => 0 ), null, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	$wpdb->update( $table, array( 'is_active' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'activate', 'fiscal_year', $id, 'Activated fiscal year #' . $id );
	oby_mi_erp_redirect_notice( __( 'Fiscal year activated.', 'obydullah-micro-erp' ) );
}
