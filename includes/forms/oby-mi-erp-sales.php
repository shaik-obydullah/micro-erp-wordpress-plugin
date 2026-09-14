<?php
/**
 * Form handlers for saving a sale and posting its accounting entries.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save (create or update) a sale and its line items, posting the sale journal
 * entry when a new sale is created.
 *
 * @return void
 */
function oby_mi_erp_handle_sale_form() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_sale_save' );

	global $wpdb;
	list( $entity_id, $created ) = oby_mi_erp_save_quote_sale(
		'SALE-',
		oby_mi_erp_table( 'sales' ),
		oby_mi_erp_table( 'sale_items' ),
		'sale_id',
		'sale'
	);

	// Credit sale: Dr Accounts Receivable / Cr Sales Income.
	if ( $created ) {
		$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales WHERE id = %d", $entity_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
		oby_mi_erp_create_sale_journal( $sale );

		do_action( 'oby_mi_erp_sale_created', $entity_id );
	}

	oby_mi_erp_redirect_notice( $created ? __( 'Sale created.', 'obydullah-micro-erp' ) : __( 'Sale updated.', 'obydullah-micro-erp' ) );
}

/**
 * Delete a sale and its line items, named by $_POST['id'].
 *
 * @return void
 */
function oby_mi_erp_handle_delete_sale() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_sale_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via oby_mi_erp_verify_nonce() above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'sale_items' ), array( 'sale_id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	$wpdb->delete( oby_mi_erp_table( 'sales' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'delete', 'sale', $id, 'Deleted sale #' . $id );
	oby_mi_erp_redirect_notice( __( 'Sale deleted.', 'obydullah-micro-erp' ) );
}

/**
 * Record a payment against a sale from $_POST and post the matching cash journal entry.
 *
 * @return void
 */
function oby_mi_erp_handle_record_payment() {
	check_admin_referer( 'oby_mi_erp_payment_save' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	$sale_id = (int) sanitize_text_field( wp_unslash( $_POST['sale_id'] ?? '' ) );
	$amount  = (float) sanitize_text_field( wp_unslash( $_POST['amount'] ?? '' ) );
	$method  = sanitize_key( wp_unslash( $_POST['method'] ?? 'cash' ) );
	$ref     = sanitize_text_field( wp_unslash( $_POST['reference'] ?? '' ) );
	$deposit = (int) sanitize_text_field( wp_unslash( $_POST['deposit_to'] ?? '' ) );
	$notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	global $wpdb;
	$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales WHERE id = %d", $sale_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
	if ( ! $sale ) {
		oby_mi_erp_redirect_notice( __( 'Sale not found.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$balance = (float) $sale->total - (float) $sale->amount_paid;
	if ( $amount <= 0 || $amount > $balance + 0.01 ) {
		oby_mi_erp_redirect_notice( __( 'Payment amount is invalid.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$new_paid = (float) $sale->amount_paid + $amount;
	$status   = 'partial';
	if ( $new_paid >= (float) $sale->total - 0.01 ) {
		$status = 'paid';
	}

	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		oby_mi_erp_table( 'sales' ),
		array(
			'amount_paid'    => $new_paid,
			'payment_status' => $status,
			'payment_method' => $method,
		),
		array( 'id' => $sale_id ),
		array( '%f', '%s', '%s' ),
		array( '%d' )
	);

	if ( ! $deposit ) {
		$deposit = oby_mi_erp_default_account( 'asset', '1001' );
	}
	$ar_account = oby_mi_erp_default_account( 'asset', '1003' );

	$entry_id = oby_mi_erp_create_journal_entry(
		current_time( 'Y-m-d' ),
		sprintf( 'Sale Payment - %s (%s)', $sale->sale_no, $ref ),
		array(
			array(
				'account_id' => $deposit,
				'debit'      => $amount,
				'credit'     => 0,
			),
			array(
				'account_id' => $ar_account,
				'debit'      => 0,
				'credit'     => $amount,
			),
		),
		'sale_payment',
		$sale_id
	);

	do_action( 'oby_mi_erp_sale_payment_received', $sale_id, $amount );
	oby_mi_erp_audit_log( 'payment', 'sale', $sale_id, 'Received ' . $amount . ' payment on ' . $sale->sale_no );
	oby_mi_erp_redirect_notice( __( 'Payment recorded.', 'obydullah-micro-erp' ) );
}
