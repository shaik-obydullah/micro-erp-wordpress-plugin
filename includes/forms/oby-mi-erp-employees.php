<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_employee_form( $action ) {
	check_admin_referer( 'oby_mi_erp_employee_save' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by check_admin_referer() above.
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );

	$data = array(
		'employee_id'   => sanitize_text_field( wp_unslash( $_POST['employee_id'] ?? '' ) ),
		'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'email'         => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'phone'         => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
		'department_id' => (int) sanitize_text_field( wp_unslash( $_POST['department_id'] ?? '' ) ),
		'designation'   => sanitize_text_field( wp_unslash( $_POST['designation'] ?? '' ) ),
		'date_of_join'  => sanitize_text_field( wp_unslash( $_POST['date_of_join'] ?? '' ) ),
		'date_of_birth' => sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ?? '' ) ),
		'gender'        => sanitize_key( wp_unslash( $_POST['gender'] ?? '' ) ),
		'address'       => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
		'basic_salary'  => (float) sanitize_text_field( wp_unslash( $_POST['basic_salary'] ?? '' ) ),
		'status'        => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $data['employee_id'] || ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Employee ID and name are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'employees' );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE employee_id = %s AND id != %d", $data['employee_id'], $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- single-row lookup gating a write flow; caches are flushed downstream; table/column name comes from a fixed internal constant.
	if ( $exists ) {
		oby_mi_erp_redirect_notice( __( 'An employee with that ID already exists.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	if ( 'update_employee' === $action ) {
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Employee updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Employee created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_flush_cache();

	oby_mi_erp_audit_log( 'save', 'employee', $entity_id, $data['employee_id'] . ' - ' . $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_employee() {
	check_admin_referer( 'oby_mi_erp_employee_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'employees' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'employee', $id, 'Deleted employee #' . $id );
	oby_mi_erp_redirect_notice( __( 'Employee deleted.', 'obydullah-micro-erp' ) );
}