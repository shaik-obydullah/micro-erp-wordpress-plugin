<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_department_form( $action ) {
	check_admin_referer( 'oby_mi_erp_department_save' );

	$data = array(
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
		'status'      => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	);

	if ( ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Department name is required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'departments' );

	if ( 'update_department' === $action ) {
		$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Department updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Department created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_flush_cache();

	oby_mi_erp_audit_log( 'save', 'department', $entity_id, $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_department() {
	check_admin_referer( 'oby_mi_erp_department_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE department_id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- single-row lookup gating a write flow; caches are flushed downstream; table/column name comes from a fixed internal constant.
	if ( $used ) {
		oby_mi_erp_redirect_notice( __( 'This department has employees and cannot be deleted.', 'obydullah-micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( oby_mi_erp_table( 'departments' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'department', $id, 'Deleted department #' . $id );
	oby_mi_erp_redirect_notice( __( 'Department deleted.', 'obydullah-micro-erp' ) );
}
