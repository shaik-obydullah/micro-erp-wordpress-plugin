<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function li_mi_erp_handle_journal_form() {
	li_mi_erp_verify_nonce( 'li_mi_erp_journal_save' );

	$date        = isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' );
	$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
	$accounts    = isset( $_POST['account_id'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['account_id'] ) ) : array();
	$debits      = isset( $_POST['debit'] ) ? array_map( 'floatval', (array) wp_unslash( $_POST['debit'] ) ) : array();
	$credits     = isset( $_POST['credit'] ) ? array_map( 'floatval', (array) wp_unslash( $_POST['credit'] ) ) : array();
	$line_desc   = isset( $_POST['line_description'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['line_description'] ) ) : array();

	if ( ! $description ) {
		li_mi_erp_redirect_notice( __( 'A description is required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	$total_debit  = array_sum( $debits );
	$total_credit = array_sum( $credits );

	if ( $total_debit <= 0 || abs( $total_debit - $total_credit ) > 0.01 ) {
		li_mi_erp_redirect_notice( __( 'Journal must be balanced and have at least one line.', 'lime-micro-erp' ), 'error' );
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

	$entry_id = li_mi_erp_create_journal_entry( $date, $description, $lines, 'manual', 0 );
	li_mi_erp_audit_log( 'save', 'journal', $entry_id, $description );
	li_mi_erp_redirect_notice( __( 'Journal entry saved.', 'lime-micro-erp' ) );
}

function li_mi_erp_handle_transaction_form() {
	li_mi_erp_verify_nonce( 'li_mi_erp_journal_save' );

	$mode        = isset( $_POST['tx_mode'] ) && 'expense' === $_POST['tx_mode'] ? 'expense' : 'income';
	$date        = isset( $_POST['entry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_date'] ) ) : current_time( 'Y-m-d' );
	$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
	$amount      = isset( $_POST['amount'] ) ? (float) $_POST['amount'] : 0;
	$account_id  = isset( $_POST['account_id'] ) ? (int) $_POST['account_id'] : 0;

	if ( ! $description || $amount <= 0 ) {
		li_mi_erp_redirect_notice( __( 'A description and a valid amount are required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	if ( 'expense' === $mode ) {
		$acct  = $account_id ? $account_id : li_mi_erp_default_account( 'expense', '5001' );
		$lines = array(
			array( 'account_id' => $acct, 'debit' => $amount, 'credit' => 0 ),
			array( 'account_id' => li_mi_erp_default_account( 'asset', '1001' ), 'debit' => 0, 'credit' => $amount ),
		);
		$ref_type = 'expense';
	} else {
		$acct  = $account_id ? $account_id : li_mi_erp_default_account( 'income', '4001' );
		$lines = array(
			array( 'account_id' => li_mi_erp_default_account( 'asset', '1001' ), 'debit' => $amount, 'credit' => 0 ),
			array( 'account_id' => $acct, 'debit' => 0, 'credit' => $amount ),
		);
		$ref_type = 'income';
	}

	$entry_id = li_mi_erp_create_journal_entry( $date, $description, $lines, $ref_type, 0 );

	do_action( 'li_mi_erp_expense_created', $entry_id );
	li_mi_erp_audit_log( 'save', 'journal', $entry_id, $description );
	li_mi_erp_redirect_notice( __( 'Entry saved.', 'lime-micro-erp' ) );
}

function li_mi_erp_handle_delete_journal() {
	li_mi_erp_verify_nonce( 'li_mi_erp_journal_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( li_mi_erp_table( 'journal_lines' ), array( 'entry_id' => $id ), array( '%d' ) );
	$wpdb->delete( li_mi_erp_table( 'journal_entries' ), array( 'id' => $id ), array( '%d' ) );
	li_mi_erp_audit_log( 'delete', 'journal', $id, 'Deleted journal entry #' . $id );
	li_mi_erp_redirect_notice( __( 'Journal entry deleted.', 'lime-micro-erp' ) );
}
