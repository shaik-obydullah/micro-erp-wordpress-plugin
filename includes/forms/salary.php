<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_salary_paid() {
	micro_erp_verify_nonce( 'micro_erp_salary_paid' );

	$month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : current_time( 'Y-m' );
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
		micro_erp_redirect_notice( __( 'Invalid month.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$employees = array();

	if ( isset( $_POST['employee_id'] ) && (int) $_POST['employee_id'] ) {
		$employees[] = (int) $_POST['employee_id'];
	} else {
		$employees = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}micro_erp_employees WHERE status = 'active'" );
	}

	$spt   = micro_erp_table( 'salary_payments' );
	$count = 0;

	foreach ( $employees as $employee_id ) {
		$emp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_employees WHERE id = %d", $employee_id ) );
		if ( ! $emp ) {
			continue;
		}

		$base        = (float) $emp->basic_salary;
		$allowances  = isset( $_POST['allowances'][ $employee_id ] ) ? (float) $_POST['allowances'][ $employee_id ] : 0;
		$deductions  = isset( $_POST['deductions'][ $employee_id ] ) ? (float) $_POST['deductions'][ $employee_id ] : 0;
		$amount      = $base + $allowances - $deductions;

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$spt} WHERE employee_id = %d AND month = %s", $employee_id, $month ) );

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
			$wpdb->update( $spt, $data, array( 'id' => $existing ), array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' ), array( '%d' ) );
			$payment_id = (int) $existing;
		} else {
			$wpdb->insert( $spt, $data, array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' ) );
			$payment_id = (int) $wpdb->insert_id;
		}

		$expense_account = micro_erp_default_account( 'expense', '5001' );
		$cash_account    = micro_erp_default_account( 'asset', '1001' );

		$entry_id = micro_erp_create_journal_entry(
			current_time( 'Y-m-d' ),
			sprintf( 'Salary Payment - %s (%s)', $month, $emp->name ),
			array(
				array( 'account_id' => $expense_account, 'debit' => $amount, 'credit' => 0 ),
				array( 'account_id' => $cash_account, 'debit' => 0, 'credit' => $amount ),
			),
			'salary_payment',
			$payment_id
		);

		$wpdb->update( $spt, array( 'journal_entry_id' => $entry_id ), array( 'id' => $payment_id ), array( '%d' ), array( '%d' ) );

		do_action( 'micro_erp_salary_paid', $payment_id, $employee_id, $month, $amount );
		$count++;
	}

	micro_erp_audit_log( 'salary_paid', 'salary', 0, 'Marked ' . $count . ' salary payment(s) paid for ' . $month );
	/* translators: %d: number of salary payments marked as paid. */
	micro_erp_redirect_notice( sprintf( __( '%d salary payment(s) marked as paid.', 'lime-micro-erp' ), $count ) );
}
