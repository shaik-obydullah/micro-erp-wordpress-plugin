<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_handle_attendance_form() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_attendance_save' );

	$employees = isset( $_POST['attendance'] ) ? (array) wp_unslash( $_POST['attendance'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every row value is sanitized individually in the loop below.
	$date      = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : current_time( 'Y-m-d' );

	if ( ! $date ) {
		oby_mi_erp_redirect_notice( __( 'A date is required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'attendance' );

	foreach ( $employees as $employee_id => $row ) {
		$employee_id = (int) $employee_id;
		$status      = isset( $row['status'] ) ? sanitize_key( $row['status'] ) : 'present';
		$check_in    = isset( $row['check_in'] ) && $row['check_in'] ? sanitize_text_field( $row['check_in'] ) : null;
		$check_out   = isset( $row['check_out'] ) && $row['check_out'] ? sanitize_text_field( $row['check_out'] ) : null;
		$notes       = isset( $row['notes'] ) ? sanitize_textarea_field( $row['notes'] ) : '';

		$hours = null;
		if ( $check_in && $check_out ) {
			$hours = round( ( strtotime( $check_out ) - strtotime( $check_in ) ) / 3600, 2 );
			if ( $hours < 0 ) {
				$hours += 24;
			}
		}

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE employee_id = %d AND date = %s", $employee_id, $date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed plugin table name, not user input; the actual values are placeholder-bound above.
		$data     = array(
			'employee_id'  => $employee_id,
			'date'         => $date,
			'check_in'     => $check_in,
			'check_out'    => $check_out,
			'status'       => $status,
			'hours_worked' => $hours,
			'notes'        => $notes,
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing ), array( '%d', '%s', '%s', '%s', '%s', '%f', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%s', '%s', '%f', '%s' ) );
		}
	}

	oby_mi_erp_audit_log( 'save', 'attendance', 0, 'Saved attendance for ' . $date );
	oby_mi_erp_redirect_notice( __( 'Attendance saved.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_delete_attendance() {
	oby_mi_erp_verify_nonce( 'oby_mi_erp_attendance_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'attendance' ), array( 'id' => $id ), array( '%d' ) );
	oby_mi_erp_audit_log( 'delete', 'attendance', $id, 'Deleted attendance #' . $id );
	oby_mi_erp_redirect_notice( __( 'Attendance record deleted.', 'obydullah-micro-erp' ) );
}
