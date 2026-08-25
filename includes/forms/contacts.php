<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function li_mi_erp_handle_contact_form( $action ) {
	li_mi_erp_verify_nonce( 'li_mi_erp_contact_save' );

	$data = array(
		'type'    => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'customer',
		'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'address' => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
		'company' => isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '',
		'tax_id'  => isset( $_POST['tax_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_id'] ) ) : '',
		'status'  => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
	);

	if ( ! $data['name'] ) {
		li_mi_erp_redirect_notice( __( 'Contact name is required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = li_mi_erp_table( 'contacts' );

	if ( 'update_contact' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Contact updated.', 'lime-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Contact created.', 'lime-micro-erp' );
	}

	li_mi_erp_audit_log( 'save', 'contact', $entity_id, $data['name'] );
	li_mi_erp_redirect_notice( $message );
}

function li_mi_erp_handle_delete_contact() {
	li_mi_erp_verify_nonce( 'li_mi_erp_contact_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( li_mi_erp_table( 'contacts' ), array( 'id' => $id ), array( '%d' ) );
	li_mi_erp_audit_log( 'delete', 'contact', $id, 'Deleted contact #' . $id );
	li_mi_erp_redirect_notice( __( 'Contact deleted.', 'lime-micro-erp' ) );
}
