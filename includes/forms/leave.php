<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_leave_type_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_leave_type_save' );

	$data = array(
		'name'         => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
		'days_per_year'=> isset( $_POST['days_per_year'] ) ? (int) $_POST['days_per_year'] : 0,
		'is_active'    => isset( $_POST['is_active'] ) ? 1 : 0,
	);

	if ( ! $data['name'] ) {
		micro_erp_redirect_notice( __( 'Leave type name is required.', 'micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'leave_types' );

	if ( 'update_leave_type' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ) );
		$entity_id = $id;
		$message   = __( 'Leave type updated.', 'micro-erp' );
	} else {
		$wpdb->insert( $table, $data );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Leave type created.', 'micro-erp' );
	}

	micro_erp_audit_log( 'save', 'leave_type', $entity_id, $data['name'] );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_leave_type() {
	micro_erp_verify_nonce( 'micro_erp_leave_type_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'leave_types' ), array( 'id' => $id ) );
	micro_erp_audit_log( 'delete', 'leave_type', $id, 'Deleted leave type #' . $id );
	micro_erp_redirect_notice( __( 'Leave type deleted.', 'micro-erp' ) );
}

function micro_erp_handle_leave_request_form() {
	micro_erp_verify_nonce( 'micro_erp_leave_request_save' );

	$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
	$leave_type  = isset( $_POST['leave_type_id'] ) ? (int) $_POST['leave_type_id'] : 0;
	$start       = sanitize_text_field( wp_unslash( $_POST['start_date'] ) );
	$end         = sanitize_text_field( wp_unslash( $_POST['end_date'] ) );
	$reason      = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

	if ( ! $employee_id || ! $leave_type || ! $start || ! $end ) {
		micro_erp_redirect_notice( __( 'Employee, leave type, start and end dates are required.', 'micro-erp' ), 'error' );
		return;
	}

	$total_days = (int) ceil( ( strtotime( $end ) - strtotime( $start ) ) / DAY_IN_SECONDS ) + 1;
	if ( $total_days < 1 ) {
		micro_erp_redirect_notice( __( 'End date must be after start date.', 'micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$wpdb->insert(
		micro_erp_table( 'leave_requests' ),
		array(
			'employee_id'  => $employee_id,
			'leave_type_id'=> $leave_type,
			'start_date'   => $start,
			'end_date'     => $end,
			'total_days'   => $total_days,
			'reason'       => $reason,
			'status'       => 'pending',
		)
	);

	$entity_id = (int) $wpdb->insert_id;
	micro_erp_audit_log( 'save', 'leave_request', $entity_id, 'New leave request' );
	micro_erp_redirect_notice( __( 'Leave request submitted.', 'micro-erp' ) );
}

function micro_erp_handle_leave_status( $status ) {
	micro_erp_verify_nonce( 'micro_erp_leave_status' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->update(
		micro_erp_table( 'leave_requests' ),
		array( 'status' => $status, 'approved_by' => get_current_user_id() ),
		array( 'id' => $id )
	);
	micro_erp_audit_log( 'approve', 'leave_request', $id, 'Leave request ' . $status );
	micro_erp_redirect_notice( sprintf( __( 'Leave request %s.', 'micro-erp' ), $status ) );
}
