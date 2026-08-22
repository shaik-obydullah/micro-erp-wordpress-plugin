<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_employee_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_employee_save' );

	$data = array(
		'employee_id'   => isset( $_POST['employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ) : '',
		'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'email'         => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'         => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'department_id' => isset( $_POST['department_id'] ) ? (int) $_POST['department_id'] : 0,
		'designation'   => isset( $_POST['designation'] ) ? sanitize_text_field( wp_unslash( $_POST['designation'] ) ) : '',
		'date_of_join'  => isset( $_POST['date_of_join'] ) ? sanitize_text_field( wp_unslash( $_POST['date_of_join'] ) ) : '',
		'date_of_birth' => isset( $_POST['date_of_birth'] ) ? sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ) ) : '',
		'gender'        => isset( $_POST['gender'] ) ? sanitize_key( wp_unslash( $_POST['gender'] ) ) : '',
		'address'       => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
		'basic_salary'  => isset( $_POST['basic_salary'] ) ? (float) $_POST['basic_salary'] : 0,
		'status'        => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
	);

	if ( ! $data['employee_id'] || ! $data['name'] ) {
		micro_erp_redirect_notice( __( 'Employee ID and name are required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'employees' );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE employee_id = %s AND id != %d", $data['employee_id'], isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 ) );
	if ( $exists ) {
		micro_erp_redirect_notice( __( 'An employee with that ID already exists.', 'lime-micro-erp' ), 'error' );
		return;
	}

	if ( 'update_employee' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Employee updated.', 'lime-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Employee created.', 'lime-micro-erp' );
	}

	micro_erp_audit_log( 'save', 'employee', $entity_id, $data['employee_id'] . ' - ' . $data['name'] );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_employee() {
	micro_erp_verify_nonce( 'micro_erp_employee_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'employees' ), array( 'id' => $id ), array( '%d' ) );
	micro_erp_audit_log( 'delete', 'employee', $id, 'Deleted employee #' . $id );
	micro_erp_redirect_notice( __( 'Employee deleted.', 'lime-micro-erp' ) );
}
