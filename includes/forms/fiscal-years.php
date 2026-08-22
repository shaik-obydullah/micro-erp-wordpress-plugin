<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_handle_fiscal_year_form() {
	micro_erp_verify_nonce( 'micro_erp_fiscal_year_save' );

	$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
	$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
	$id         = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	if ( ! $name || ! $start_date || ! $end_date ) {
		micro_erp_redirect_notice( __( 'Name, start date and end date are required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = micro_erp_table( 'fiscal_years' );
	$data  = array(
		'name'       => $name,
		'start_date' => $start_date,
		'end_date'   => $end_date,
	);

	if ( $id ) {
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) );
		$entity_id = $id;
		$message   = __( 'Fiscal year updated.', 'lime-micro-erp' );
	} else {
		$wpdb->insert( $table, $data + array( 'is_active' => 0 ), array( '%s', '%s', '%s', '%d' ) );
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Fiscal year created.', 'lime-micro-erp' );
	}

	micro_erp_audit_log( 'save', 'fiscal_year', $entity_id, $name );
	micro_erp_redirect_notice( $message );
}

function micro_erp_handle_delete_fiscal_year() {
	micro_erp_verify_nonce( 'micro_erp_fiscal_year_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'fiscal_years' ), array( 'id' => $id ), array( '%d' ) );
	micro_erp_audit_log( 'delete', 'fiscal_year', $id, 'Deleted fiscal year #' . $id );
	micro_erp_redirect_notice( __( 'Fiscal year deleted.', 'lime-micro-erp' ) );
}

function micro_erp_handle_activate_fiscal_year() {
	micro_erp_verify_nonce( 'micro_erp_fiscal_year_activate' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$table = micro_erp_table( 'fiscal_years' );
	$wpdb->query( "UPDATE {$table} SET is_active = 0" );
	$wpdb->update( $table, array( 'is_active' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	micro_erp_audit_log( 'activate', 'fiscal_year', $id, 'Activated fiscal year #' . $id );
	micro_erp_redirect_notice( __( 'Fiscal year activated.', 'lime-micro-erp' ) );
}
