<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_leave_type_form( $action ) {
	check_admin_referer( 'oby_mi_erp_leave_type_save' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	$data = array(
		'name'         => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'days_per_year'=> (int) sanitize_text_field( wp_unslash( $_POST['days_per_year'] ?? '' ) ),
		'is_active'    => sanitize_key( wp_unslash( $_POST['is_active'] ?? '' ) ) ? 1 : 0,
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Leave type name is required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'leave_types' );

	if ( 'update_leave_type' === $action ) {
		$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%d', '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Leave type updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%d', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Leave type created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_audit_log( 'save', 'leave_type', $entity_id, $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_leave_type() {
	check_admin_referer( 'oby_mi_erp_leave_type_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'leave_types' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'delete', 'leave_type', $id, 'Deleted leave type #' . $id );
	oby_mi_erp_redirect_notice( __( 'Leave type deleted.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_leave_request_form() {
	check_admin_referer( 'oby_mi_erp_leave_request_save' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	$employee_id = (int) sanitize_text_field( wp_unslash( $_POST['employee_id'] ?? '' ) );
	$leave_type  = (int) sanitize_text_field( wp_unslash( $_POST['leave_type_id'] ?? '' ) );
	$start       = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
	$end         = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
	$reason      = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $employee_id || ! $leave_type || ! $start || ! $end ) {
		oby_mi_erp_redirect_notice( __( 'Employee, leave type, start and end dates are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$total_days = (int) ceil( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1;
	if ( $total_days < 1 ) {
		oby_mi_erp_redirect_notice( __( 'End date must be after start date.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		oby_mi_erp_table( 'leave_requests' ),
		array(
			'employee_id'  => $employee_id,
			'leave_type_id'=> $leave_type,
			'start_date'   => $start,
			'end_date'     => $end,
			'total_days'   => $total_days,
			'reason'       => $reason,
			'status'       => 'pending',
		),
		array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	$entity_id = (int) $wpdb->insert_id;
	oby_mi_erp_audit_log( 'save', 'leave_request', $entity_id, 'New leave request' );
	oby_mi_erp_redirect_notice( __( 'Leave request submitted.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_leave_status( $status ) {
	check_admin_referer( 'oby_mi_erp_leave_status' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		oby_mi_erp_table( 'leave_requests' ),
		array( 'status' => $status, 'approved_by' => get_current_user_id() ),
		array( 'id' => $id ),
		array( '%s', '%d' ),
		array( '%d' )
	);
	oby_mi_erp_audit_log( 'approve', 'leave_request', $id, 'Leave request ' . $status );
	/* translators: %s: leave request status (approved or rejected). */
	oby_mi_erp_redirect_notice( sprintf( __( 'Leave request %s.', 'obydullah-micro-erp' ), $status ) );
}
