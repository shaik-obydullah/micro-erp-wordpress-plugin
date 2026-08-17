<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_department_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_department_save' );

	$data = array(
		'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
		'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		'status'      => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
	);

	if ( ! $data['name'] ) {
		micro_erp_redirect_notice( __( 'Department name is required.', 'micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'departments' );

	if ( 'update_department' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ) );
		$entity_id = $id;
		$message   = __( 'Department updated.', 'micro-erp' );
	} else {
		$wpdb->insert( $table, $data );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Department created.', 'micro-erp' );
	}

	micro_erp_audit_log( 'save', 'department', $entity_id, $data['name'] );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_department() {
	micro_erp_verify_nonce( 'micro_erp_department_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . micro_erp_table( 'employees' ) . " WHERE department_id = %d", $id ) );
	if ( $used ) {
		micro_erp_redirect_notice( __( 'This department has employees and cannot be deleted.', 'micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( micro_erp_table( 'departments' ), array( 'id' => $id ) );
	micro_erp_audit_log( 'delete', 'department', $id, 'Deleted department #' . $id );
	micro_erp_redirect_notice( __( 'Department deleted.', 'micro-erp' ) );
}
