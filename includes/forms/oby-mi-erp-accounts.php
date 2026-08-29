<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_account_form( $action ) {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_account_save' );

	$id        = isset( $_POST['id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0;
	$parent_id = isset( $_POST['parent_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['parent_id'] ) ) : 0;

	$data = array(
		'code'      => isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '',
		'name'      => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'type'      => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'asset',
		'parent_id' => $parent_id ? $parent_id : null,
		'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
	);

	if ( ! $data['code'] || ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Code and name are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'accounts' );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE code = %s AND id != %d", $data['code'], $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed plugin table name, not user input; the actual values are placeholder-bound above.
	if ( $exists ) {
		oby_mi_erp_redirect_notice( __( 'An account with that code already exists.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	if ( 'update_account' === $action ) {
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%d', '%d' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Account updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%d', '%d' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Account created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_flush_cache();

	oby_mi_erp_audit_log( 'save', 'account', $entity_id, $data['code'] . ' - ' . $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_account() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_account_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );

	global $wpdb;
	$table       = oby_mi_erp_table( 'accounts' );
	$lines_table = oby_mi_erp_table( 'journal_lines' );

	$used = $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $lines_table is a fixed plugin table name, not user input; the actual value is placeholder-bound below.
			"SELECT COUNT(*) FROM {$lines_table} WHERE account_id = %d",
			$id
		)
	);
	if ( $used ) {
		oby_mi_erp_redirect_notice( __( 'This account is used by journal entries and cannot be deleted.', 'obydullah-micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'account', $id, 'Deleted account #' . $id );
	oby_mi_erp_redirect_notice( __( 'Account deleted.', 'obydullah-micro-erp' ) );
}
