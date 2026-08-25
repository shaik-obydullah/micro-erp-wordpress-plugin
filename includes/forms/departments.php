<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function li_mi_erp_handle_department_form( $action ) {
	li_mi_erp_verify_nonce( 'li_mi_erp_department_save' );

	$data = array(
		'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		'status'      => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
	);

	if ( ! $data['name'] ) {
		li_mi_erp_redirect_notice( __( 'Department name is required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = li_mi_erp_table( 'departments' );

	if ( 'update_department' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Department updated.', 'lime-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Department created.', 'lime-micro-erp' );
	}

	li_mi_erp_audit_log( 'save', 'department', $entity_id, $data['name'] );
	li_mi_erp_redirect_notice( $message );
}

function li_mi_erp_handle_delete_department() {
	li_mi_erp_verify_nonce( 'li_mi_erp_department_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_employees WHERE department_id = %d", $id ) );
	if ( $used ) {
		li_mi_erp_redirect_notice( __( 'This department has employees and cannot be deleted.', 'lime-micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( li_mi_erp_table( 'departments' ), array( 'id' => $id ), array( '%d' ) );
	li_mi_erp_audit_log( 'delete', 'department', $id, 'Deleted department #' . $id );
	li_mi_erp_redirect_notice( __( 'Department deleted.', 'lime-micro-erp' ) );
}
