<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_journal_form() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_journal_save' );

	$date        = isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' );
	$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
	$accounts    = isset( $_POST['account_id'] ) ? array_map( 'intval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['account_id'] ) ) ) : array();
	$debits      = isset( $_POST['debit'] ) ? array_map( 'floatval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['debit'] ) ) ) : array();
	$credits     = isset( $_POST['credit'] ) ? array_map( 'floatval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['credit'] ) ) ) : array();
	$line_desc   = isset( $_POST['line_description'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['line_description'] ) ) : array();

	if ( ! $description ) {
		oby_mi_erp_redirect_notice( __( 'A description is required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$total_debit  = array_sum( $debits );
	$total_credit = array_sum( $credits );

	if ( $total_debit <= 0 || abs( $total_debit - $total_credit ) > 0.01 ) {
		oby_mi_erp_redirect_notice( __( 'Journal must be balanced and have at least one line.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$lines = array();
	foreach ( $accounts as $i => $account_id ) {
		if ( ! $account_id ) {
			continue;
		}
		$lines[] = array(
			'account_id'  => $account_id,
			'debit'       => isset( $debits[ $i ] ) ? $debits[ $i ] : 0,
			'credit'      => isset( $credits[ $i ] ) ? $credits[ $i ] : 0,
			'description' => isset( $line_desc[ $i ] ) ? $line_desc[ $i ] : '',
		);
	}

	$entry_id = oby_mi_erp_create_journal_entry( $date, $description, $lines, 'manual', 0 );
	oby_mi_erp_audit_log( 'save', 'journal', $entry_id, $description );
	oby_mi_erp_redirect_notice( __( 'Journal entry saved.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_transaction_form() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_journal_save' );

	$tx_mode     = sanitize_key( wp_unslash( $_POST['tx_mode'] ?? 'income' ) );
	$mode        = 'expense' === $tx_mode ? 'expense' : 'income';
	$date        = isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' );
	$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
	$amount      = (float) sanitize_text_field( wp_unslash( $_POST['amount'] ?? '' ) );
	$account_id  = (int) sanitize_text_field( wp_unslash( $_POST['account_id'] ?? '' ) );

	if ( ! $description || $amount <= 0 ) {
		oby_mi_erp_redirect_notice( __( 'A description and a valid amount are required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	if ( 'expense' === $mode ) {
		$acct     = $account_id ? $account_id : oby_mi_erp_default_account( 'expense', '5001' );
		$lines    = array(
			array(
				'account_id' => $acct,
				'debit'      => $amount,
				'credit'     => 0,
			),
			array(
				'account_id' => oby_mi_erp_default_account( 'asset', '1001' ),
				'debit'      => 0,
				'credit'     => $amount,
			),
		);
		$ref_type = 'expense';
	} else {
		$acct     = $account_id ? $account_id : oby_mi_erp_default_account( 'income', '4001' );
		$lines    = array(
			array(
				'account_id' => oby_mi_erp_default_account( 'asset', '1001' ),
				'debit'      => $amount,
				'credit'     => 0,
			),
			array(
				'account_id' => $acct,
				'debit'      => 0,
				'credit'     => $amount,
			),
		);
		$ref_type = 'income';
	}

	$entry_id = oby_mi_erp_create_journal_entry( $date, $description, $lines, $ref_type, 0 );

	do_action( 'oby_mi_erp_expense_created', $entry_id );
	oby_mi_erp_audit_log( 'save', 'journal', $entry_id, $description );
	oby_mi_erp_redirect_notice( __( 'Entry saved.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_delete_journal() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_journal_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'journal_lines' ), array( 'entry_id' => $id ), array( '%d' ) );
	$wpdb->delete( oby_mi_erp_table( 'journal_entries' ), array( 'id' => $id ), array( '%d' ) );
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'journal', $id, 'Deleted journal entry #' . $id );
	oby_mi_erp_redirect_notice( __( 'Journal entry deleted.', 'obydullah-micro-erp' ) );
}
