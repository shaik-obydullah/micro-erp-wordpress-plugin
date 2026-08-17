<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_contact_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_contact_save' );

	$data = array(
		'type'    => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'customer',
		'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
		'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'address' => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
		'company' => isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '',
		'tax_id'  => isset( $_POST['tax_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_id'] ) ) : '',
		'status'  => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
	);

	if ( ! $data['name'] ) {
		micro_erp_redirect_notice( __( 'Contact name is required.', 'micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'contacts' );

	if ( 'update_contact' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ) );
		$entity_id = $id;
		$message   = __( 'Contact updated.', 'micro-erp' );
	} else {
		$wpdb->insert( $table, $data );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Contact created.', 'micro-erp' );
	}

	micro_erp_audit_log( 'save', 'contact', $entity_id, $data['name'] );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_contact() {
	micro_erp_verify_nonce( 'micro_erp_contact_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'contacts' ), array( 'id' => $id ) );
	micro_erp_audit_log( 'delete', 'contact', $id, 'Deleted contact #' . $id );
	micro_erp_redirect_notice( __( 'Contact deleted.', 'micro-erp' ) );
}
