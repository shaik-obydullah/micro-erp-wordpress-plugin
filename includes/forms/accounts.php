<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_account_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_account_save' );

	$data = array(
		'code'      => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '',
		'name'      => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'type'      => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'asset',
		'parent_id' => isset( $_POST['parent_id'] ) && $_POST['parent_id'] ? (int) wp_unslash( $_POST['parent_id'] ) : null,
		'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
	);

	if ( ! $data['code'] || ! $data['name'] ) {
		micro_erp_redirect_notice( __( 'Code and name are required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'accounts' );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE code = %s AND id != %d", $data['code'], isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 ) );
	if ( $exists ) {
		micro_erp_redirect_notice( __( 'An account with that code already exists.', 'lime-micro-erp' ), 'error' );
		return;
	}

	if ( 'update_account' === $action ) {
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%d', '%d' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Account updated.', 'lime-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%d', '%d' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Account created.', 'lime-micro-erp' );
	}

	micro_erp_audit_log( 'save', 'account', $entity_id, $data['code'] . ' - ' . $data['name'] );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_account() {
	micro_erp_verify_nonce( 'micro_erp_account_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$table = micro_erp_table( 'accounts' );
	$used  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . micro_erp_table( 'journal_lines' ) . " WHERE account_id = %d", $id ) );
	if ( $used ) {
		micro_erp_redirect_notice( __( 'This account is used by journal entries and cannot be deleted.', 'lime-micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	micro_erp_audit_log( 'delete', 'account', $id, 'Deleted account #' . $id );
	micro_erp_redirect_notice( __( 'Account deleted.', 'lime-micro-erp' ) );
}
