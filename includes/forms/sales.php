<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_sale_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_sale_save' );

	global $wpdb;
	list( $entity_id, $created ) = micro_erp_save_quote_sale(
		'SALE-',
		micro_erp_table( 'sales' ),
		micro_erp_table( 'sale_items' ),
		'sale_id',
		'sale'
	);

	// Credit sale: Dr Accounts Receivable / Cr Sales Income.
	if ( $created ) {
		$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sales' ) . " WHERE id = %d", $entity_id ) );
		micro_erp_create_sale_journal( $sale );

		do_action( 'micro_erp_sale_created', $entity_id );
	}

	micro_erp_redirect_notice( $created ? __( 'Sale created.', 'lime-micro-erp' ) : __( 'Sale updated.', 'lime-micro-erp' ) );
}

function micro_erp_handle_delete_sale() {
	micro_erp_verify_nonce( 'micro_erp_sale_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'sale_items' ), array( 'sale_id' => $id ), array( '%d' ) );
	$wpdb->delete( micro_erp_table( 'sales' ), array( 'id' => $id ), array( '%d' ) );
	micro_erp_audit_log( 'delete', 'sale', $id, 'Deleted sale #' . $id );
	micro_erp_redirect_notice( __( 'Sale deleted.', 'lime-micro-erp' ) );
}

function micro_erp_handle_record_payment() {
	micro_erp_verify_nonce( 'micro_erp_payment_save' );

	$sale_id = isset( $_POST['sale_id'] ) ? (int) $_POST['sale_id'] : 0;
	$amount  = isset( $_POST['amount'] ) ? (float) $_POST['amount'] : 0;
	$method  = isset( $_POST['method'] ) ? sanitize_key( wp_unslash( $_POST['method'] ) ) : 'cash';
	$ref     = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
	$deposit = isset( $_POST['deposit_to'] ) ? (int) $_POST['deposit_to'] : 0;
	$notes   = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

	global $wpdb;
	$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sales' ) . " WHERE id = %d", $sale_id ) );
	if ( ! $sale ) {
		micro_erp_redirect_notice( __( 'Sale not found.', 'lime-micro-erp' ), 'error' );
		return;
	}

	$balance = (float) $sale->total - (float) $sale->amount_paid;
	if ( $amount <= 0 || $amount > $balance + 0.01 ) {
		micro_erp_redirect_notice( __( 'Payment amount is invalid.', 'lime-micro-erp' ), 'error' );
		return;
	}

	$new_paid = (float) $sale->amount_paid + $amount;
	$status   = 'partial';
	if ( $new_paid >= (float) $sale->total - 0.01 ) {
		$status = 'paid';
	}

	$wpdb->update(
		micro_erp_table( 'sales' ),
		array(
			'amount_paid'   => $new_paid,
			'payment_status' => $status,
			'payment_method' => $method,
		),
		array( 'id' => $sale_id ),
		array( '%f', '%s', '%s' ),
		array( '%d' )
	);

	if ( ! $deposit ) {
		$deposit = micro_erp_default_account( 'asset', '1001' );
	}
	$ar_account = micro_erp_default_account( 'asset', '1003' );

	$entry_id = micro_erp_create_journal_entry(
		current_time( 'Y-m-d' ),
		sprintf( 'Sale Payment - %s (%s)', $sale->sale_no, $ref ),
		array(
			array( 'account_id' => $deposit, 'debit' => $amount, 'credit' => 0 ),
			array( 'account_id' => $ar_account, 'debit' => 0, 'credit' => $amount ),
		),
		'sale_payment',
		$sale_id
	);

	do_action( 'micro_erp_sale_payment_received', $sale_id, $amount );
	micro_erp_audit_log( 'payment', 'sale', $sale_id, 'Received ' . $amount . ' payment on ' . $sale->sale_no );
	micro_erp_redirect_notice( __( 'Payment recorded.', 'lime-micro-erp' ) );
}
