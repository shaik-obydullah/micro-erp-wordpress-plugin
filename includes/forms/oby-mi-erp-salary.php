<?php
/**
 * Form handler that runs a monthly salary payment batch for all active employees.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Record a salary payment for one employee (or all active employees) for a
 * given month, and post the matching salary-expense journal entry.
 *
 * @return void
 */
function oby_mi_erp_handle_salary_paid() {
	check_admin_referer( 'oby_mi_erp_salary_paid' );

	$month = sanitize_text_field( wp_unslash( $_POST['month'] ?? current_time( 'Y-m' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
		oby_mi_erp_redirect_notice( __( 'Invalid month.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$employees = array();

	$employee_id = (int) sanitize_text_field( wp_unslash( $_POST['employee_id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	if ( $employee_id ) {
		$employees[] = $employee_id;
	} else {
		$employees = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}oby_mi_erp_employees WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
	}

	$spt   = oby_mi_erp_table( 'salary_payments' );
	$count = 0;

	foreach ( $employees as $employee_id ) {
		$emp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE id = %d", $employee_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
		if ( ! $emp ) {
			continue;
		}

		$base       = (float) $emp->basic_salary;
		$allowances = isset( $_POST['allowances'][ $employee_id ] ) ? (float) sanitize_text_field( wp_unslash( $_POST['allowances'][ $employee_id ] ) ) : 0;
		$deductions = isset( $_POST['deductions'][ $employee_id ] ) ? (float) sanitize_text_field( wp_unslash( $_POST['deductions'][ $employee_id ] ) ) : 0;
		$amount     = $base + $allowances - $deductions;

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$spt} WHERE employee_id = %d AND month = %s", $employee_id, $month ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $spt is a fixed plugin table name, not user input; the actual values are placeholder-bound above.

		$data = array(
			'employee_id' => $employee_id,
			'month'       => $month,
			'amount'      => $amount,
			'allowances'  => $allowances,
			'deductions'  => $deductions,
			'status'      => 'paid',
			'paid_at'     => current_time( 'mysql' ),
		);

		if ( $existing ) {
			$wpdb->update( $spt, $data, array( 'id' => $existing ), array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
			$payment_id = (int) $existing;
		} else {
			$wpdb->insert( $spt, $data, array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
			$payment_id = (int) $wpdb->insert_id;
		}

		$expense_account = oby_mi_erp_default_account( 'expense', '5001' );
		$cash_account    = oby_mi_erp_default_account( 'asset', '1001' );

		$entry_id = oby_mi_erp_create_journal_entry(
			current_time( 'Y-m-d' ),
			sprintf( 'Salary Payment - %s (%s)', $month, $emp->name ),
			array(
				array(
					'account_id' => $expense_account,
					'debit'      => $amount,
					'credit'     => 0,
				),
				array(
					'account_id' => $cash_account,
					'debit'      => 0,
					'credit'     => $amount,
				),
			),
			'salary_payment',
			$payment_id
		);

		$wpdb->update( $spt, array( 'journal_entry_id' => $entry_id ), array( 'id' => $payment_id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.

		do_action( 'oby_mi_erp_salary_paid', $payment_id, $employee_id, $month, $amount );
		++$count;
	}

	oby_mi_erp_audit_log( 'salary_paid', 'salary', 0, 'Marked ' . $count . ' salary payment(s) paid for ' . $month );
	/* translators: %d: number of salary payments marked as paid. */
	oby_mi_erp_redirect_notice( sprintf( __( '%d salary payment(s) marked as paid.', 'obydullah-micro-erp' ), $count ) );
}
