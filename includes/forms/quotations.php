<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_save_quote_sale( $prefix, $table_main, $table_items, $item_col, $type ) {
	global $wpdb;

	$contact_id = isset( $_POST['contact_id'] ) ? (int) $_POST['contact_id'] : 0;
	$date       = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : current_time( 'Y-m-d' );
	$valid_until = isset( $_POST['valid_until'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_until'] ) ) : '';
	$valid_until = '' !== $valid_until ? $valid_until : null;
	$discount   = isset( $_POST['discount'] ) ? (float) $_POST['discount'] : 0;
	$notes      = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
	$update_id  = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$send       = isset( $_POST['save_and_send'] );

	$descriptions = isset( $_POST['item_description'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['item_description'] ) ) : array();
	$quantities   = isset( $_POST['item_quantity'] ) ? array_map( 'floatval', (array) wp_unslash( $_POST['item_quantity'] ) ) : array();
	$prices       = isset( $_POST['item_price'] ) ? array_map( 'floatval', (array) wp_unslash( $_POST['item_price'] ) ) : array();
	$tax_rates    = isset( $_POST['item_tax'] ) ? array_map( 'floatval', (array) wp_unslash( $_POST['item_tax'] ) ) : array();

	if ( ! $contact_id || empty( $descriptions ) ) {
		micro_erp_redirect_notice( __( 'A contact and at least one item are required.', 'lime-micro-erp' ), 'error' );
		return;
	}

	$subtotal = 0;
	$tax      = 0;
	$items    = array();

	foreach ( $descriptions as $i => $desc ) {
		if ( ! $desc ) {
			continue;
		}
		$qty     = isset( $quantities[ $i ] ) ? $quantities[ $i ] : 1;
		$price   = isset( $prices[ $i ] ) ? $prices[ $i ] : 0;
		$rate    = isset( $tax_rates[ $i ] ) ? $tax_rates[ $i ] : 0;
		$line    = $qty * $price;
		$line_tx = $line * $rate / 100;

		$subtotal += $line;
		$tax      += $line_tx;

		$items[] = array(
			'description' => $desc,
			'quantity'    => $qty,
			'unit_price'  => $price,
			'tax_rate'    => $rate,
			'total'       => $line + $line_tx,
		);
	}

	$total = $subtotal + $tax - $discount;

	$data = array(
		'contact_id' => $contact_id,
		'date'       => $date,
		'subtotal'   => $subtotal,
		'tax_amount' => $tax,
		'discount'   => $discount,
		'total'      => $total,
		'notes'      => $notes,
	);

	if ( 'quotation' === $type ) {
		$data['quotation_no'] = $update_id ? $wpdb->get_var( $wpdb->prepare( "SELECT quotation_no FROM {$table_main} WHERE id = %d", $update_id ) ) : micro_erp_next_quotation_no();
		$data['quotation_date'] = $date;
		$data['valid_until'] = $valid_until;
		unset( $data['date'] );
		$formats = array( '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s' );
	} else {
		$data['sale_no'] = $update_id ? $wpdb->get_var( $wpdb->prepare( "SELECT sale_no FROM {$table_main} WHERE id = %d", $update_id ) ) : micro_erp_next_sale_no();
		$data['sale_date'] = $date;
		unset( $data['date'] );
		$formats = array( '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s' );
	}

	if ( $update_id ) {
		$wpdb->update( $table_main, $data, array( 'id' => $update_id ), $formats, array( '%d' ) );
		$entity_id = $update_id;
		$created   = false;
	} else {
		$data['created_by'] = get_current_user_id();
	if ( 'quotation' === $type ) {
			$data['status'] = $send ? 'sent' : 'draft';
			$formats[] = '%d';
			$formats[] = '%s';
		} else {
			$data['payment_status'] = 'unpaid';
			$data['amount_paid']    = 0;
			$formats[] = '%d';
			$formats[] = '%s';
			$formats[] = '%f';
		}
		$wpdb->insert( $table_main, $data, $formats );
		$entity_id = (int) $wpdb->insert_id;
		$created   = true;
	}

	$wpdb->delete( $table_items, array( $item_col => $entity_id ), array( '%d' ) );
	foreach ( $items as $item ) {
		$item[ $item_col ] = $entity_id;
		$wpdb->insert( $table_items, $item, array( '%s', '%f', '%f', '%f', '%f', '%d' ) );
	}

	micro_erp_audit_log( 'save', $type, $entity_id, 'Saved ' . $type . ' #' . $entity_id );
	return array( $entity_id, $created );
}

function micro_erp_handle_quotation_form( $action ) {
	micro_erp_verify_nonce( 'micro_erp_quotation_save' );
	list( $entity_id, $created ) = micro_erp_save_quote_sale(
		'QUO-',
		micro_erp_table( 'quotations' ),
		micro_erp_table( 'quotation_items' ),
		'quotation_id',
		'quotation'
	);
	micro_erp_redirect_notice( $created ? __( 'Quotation created.', 'lime-micro-erp' ) : __( 'Quotation updated.', 'lime-micro-erp' ) );
}

function micro_erp_handle_delete_quotation() {
	micro_erp_verify_nonce( 'micro_erp_quotation_delete' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$wpdb->delete( micro_erp_table( 'quotation_items' ), array( 'quotation_id' => $id ), array( '%d' ) );
	$wpdb->delete( micro_erp_table( 'quotations' ), array( 'id' => $id ), array( '%d' ) );
	micro_erp_audit_log( 'delete', 'quotation', $id, 'Deleted quotation #' . $id );
	micro_erp_redirect_notice( __( 'Quotation deleted.', 'lime-micro-erp' ) );
}

function micro_erp_handle_quotation_status() {
	micro_erp_verify_nonce( 'micro_erp_quotation_status' );
	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'draft';

	global $wpdb;
	$wpdb->update( micro_erp_table( 'quotations' ), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	micro_erp_audit_log( 'status', 'quotation', $id, 'Quotation status -> ' . $status );
	micro_erp_redirect_notice( __( 'Quotation status updated.', 'lime-micro-erp' ) );
}

function micro_erp_handle_convert_quotation() {
	micro_erp_verify_nonce( 'micro_erp_quotation_convert' );
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	global $wpdb;
	$q = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_quotations WHERE id = %d", $id ) );
	if ( ! $q ) {
		micro_erp_redirect_notice( __( 'Quotation not found.', 'lime-micro-erp' ), 'error' );
		return;
	}

	$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_quotation_items WHERE quotation_id = %d", $id ) );

	$sale_no = micro_erp_next_sale_no();
	$wpdb->insert(
		micro_erp_table( 'sales' ),
		array(
			'sale_no'       => $sale_no,
			'quotation_id'  => $id,
			'contact_id'    => $q->contact_id,
			'sale_date'     => current_time( 'Y-m-d' ),
			'payment_status'=> 'unpaid',
			'subtotal'      => $q->subtotal,
			'tax_amount'    => $q->tax_amount,
			'discount'      => $q->discount,
			'total'         => $q->total,
			'amount_paid'   => 0,
			'notes'         => $q->notes,
			'created_by'    => get_current_user_id(),
		),
		array( '%s', '%d', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%d' )
	);
	$sale_id = (int) $wpdb->insert_id;

	foreach ( $items as $item ) {
		$wpdb->insert(
			micro_erp_table( 'sale_items' ),
			array(
				'sale_id'     => $sale_id,
				'description' => $item->description,
				'quantity'    => $item->quantity,
				'unit_price'  => $item->unit_price,
				'tax_rate'    => $item->tax_rate,
				'total'       => $item->total,
			),
			array( '%d', '%s', '%f', '%f', '%f', '%f' )
		);
	}

	$wpdb->update( micro_erp_table( 'quotations' ), array( 'status' => 'converted' ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );

	$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_sales WHERE id = %d", $sale_id ) );
	micro_erp_create_sale_journal( $sale );

	do_action( 'micro_erp_quotation_converted', $id, $sale_id );
	micro_erp_audit_log( 'convert', 'quotation', $id, 'Converted quotation to sale ' . $sale_no );
	micro_erp_redirect_notice( __( 'Quotation converted to sale.', 'lime-micro-erp' ) );
}
