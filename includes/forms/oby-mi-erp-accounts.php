<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_account_form( $action ) {
	check_admin_referer( 'oby_mi_erp_account_save' );

	$id        = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	$parent_id = (int) sanitize_text_field( wp_unslash( $_POST['parent_id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	$data = array(
		'code'      => sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'name'      => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'type'      => sanitize_key( wp_unslash( $_POST['type'] ?? 'asset' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'parent_id' => $parent_id ? $parent_id : null,
		'is_active' => sanitize_key( wp_unslash( $_POST['is_active'] ?? '' ) ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	);

	if ( ! $data['code'] || ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Code and name are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'accounts' );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE code = %s AND id != %d", $data['code'], $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- single-row lookup gating a write flow; caches are flushed downstream; table/column name comes from a fixed internal constant.
	if ( $exists ) {
		oby_mi_erp_redirect_notice( __( 'An account with that code already exists.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	if ( 'update_account' === $action ) {
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%d', '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Account updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%d', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Account created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_flush_cache();

	oby_mi_erp_audit_log( 'save', 'account', $entity_id, $data['code'] . ' - ' . $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

function oby_mi_erp_handle_delete_account() {
	check_admin_referer( 'oby_mi_erp_account_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$table       = oby_mi_erp_table( 'accounts' );
	$lines_table = oby_mi_erp_table( 'journal_lines' );

	$used = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- single-row lookup gating a write flow; caches are flushed downstream.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$lines_table} WHERE account_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table/column name comes from a fixed internal constant.
			$id
		)
	);
	if ( $used ) {
		oby_mi_erp_redirect_notice( __( 'This account is used by journal entries and cannot be deleted.', 'obydullah-micro-erp' ), 'error' );
		return;
	}
	$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'account', $id, 'Deleted account #' . $id );
	oby_mi_erp_redirect_notice( __( 'Account deleted.', 'obydullah-micro-erp' ) );
}
