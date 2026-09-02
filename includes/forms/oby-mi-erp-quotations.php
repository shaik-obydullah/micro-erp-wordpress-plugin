<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_save_quote_sale( $prefix, $table_main, $table_items, $item_col, $type ) {
	global $wpdb;

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by check_admin_referer() in the calling form handler.
	$contact_id = (int) sanitize_text_field( wp_unslash( $_POST['contact_id'] ?? '' ) );
	$date       = sanitize_text_field( wp_unslash( $_POST['date'] ?? current_time( 'Y-m-d' ) ) );
	$valid_until = sanitize_text_field( wp_unslash( $_POST['valid_until'] ?? '' ) );
	$valid_until = '' !== $valid_until ? $valid_until : null;
	$discount   = (float) sanitize_text_field( wp_unslash( $_POST['discount'] ?? '' ) );
	$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
	$update_id  = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
	$send       = (bool) sanitize_key( wp_unslash( $_POST['save_and_send'] ?? '' ) );

	$descriptions = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['item_description'] ?? array() ) );
	$quantities   = array_map( 'floatval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['item_quantity'] ?? array() ) ) );
	$prices       = array_map( 'floatval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['item_price'] ?? array() ) ) );
	$tax_rates    = array_map( 'floatval', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['item_tax'] ?? array() ) ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( ! $contact_id || empty( $descriptions ) ) {
		oby_mi_erp_redirect_notice( __( 'A contact and at least one item are required.', 'obydullah-micro-erp' ), 'error' );
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
		$data['quotation_no'] = $update_id ? $wpdb->get_var( $wpdb->prepare( "SELECT quotation_no FROM {$table_main} WHERE id = %d", $update_id ) ) : oby_mi_erp_next_quotation_no(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from a fixed internal constant; lookup for the edit flow only.
		$data['quotation_date'] = $date;
		$data['valid_until'] = $valid_until;
		unset( $data['date'] );
		$formats = array( '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s' );
	} else {
		$data['sale_no'] = $update_id ? $wpdb->get_var( $wpdb->prepare( "SELECT sale_no FROM {$table_main} WHERE id = %d", $update_id ) ) : oby_mi_erp_next_sale_no(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from a fixed internal constant; lookup for the edit flow only.
		$data['sale_date'] = $date;
		unset( $data['date'] );
		$formats = array( '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s' );
	}

	if ( $update_id ) {
		$wpdb->update( $table_main, $data, array( 'id' => $update_id ), $formats, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- update flow writes and flushes caches via the shared save routine.
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
		$wpdb->insert( $table_main, $data, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$created   = true;
	}

	$wpdb->delete( $table_items, array( $item_col => $entity_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	foreach ( $items as $item ) {
		$item[ $item_col ] = $entity_id;
		$wpdb->insert( $table_items, $item, array( '%s', '%f', '%f', '%f', '%f', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
	}

	oby_mi_erp_audit_log( 'save', $type, $entity_id, 'Saved ' . $type . ' #' . $entity_id );
	return array( $entity_id, $created );
}

function oby_mi_erp_handle_quotation_form( $action ) {
	check_admin_referer( 'oby_mi_erp_quotation_save' );
	list( $entity_id, $created ) = oby_mi_erp_save_quote_sale(
		'QUO-',
		oby_mi_erp_table( 'quotations' ),
		oby_mi_erp_table( 'quotation_items' ),
		'quotation_id',
		'quotation'
	);
	oby_mi_erp_redirect_notice( $created ? __( 'Quotation created.', 'obydullah-micro-erp' ) : __( 'Quotation updated.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_delete_quotation() {
	check_admin_referer( 'oby_mi_erp_quotation_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'quotation_items' ), array( 'quotation_id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	$wpdb->delete( oby_mi_erp_table( 'quotations' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'delete', 'quotation', $id, 'Deleted quotation #' . $id );
	oby_mi_erp_redirect_notice( __( 'Quotation deleted.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_quotation_status() {
	check_admin_referer( 'oby_mi_erp_quotation_status' );
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified above.
	$id     = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
	$status = sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	global $wpdb;
	$wpdb->update( oby_mi_erp_table( 'quotations' ), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_audit_log( 'status', 'quotation', $id, 'Quotation status -> ' . $status );
	oby_mi_erp_redirect_notice( __( 'Quotation status updated.', 'obydullah-micro-erp' ) );
}

function oby_mi_erp_handle_convert_quotation() {
	check_admin_referer( 'oby_mi_erp_quotation_convert' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.

	global $wpdb;
	$q = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_quotations WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; the affected rows' caches are flushed by the journal creation below.
	if ( ! $q ) {
		oby_mi_erp_redirect_notice( __( 'Quotation not found.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_quotation_items WHERE quotation_id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reads feeding the write flow below; caches flushed downstream.

	$sale_no = oby_mi_erp_next_sale_no();
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		oby_mi_erp_table( 'sales' ),
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
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
			oby_mi_erp_table( 'sale_items' ),
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

	$wpdb->update( oby_mi_erp_table( 'quotations' ), array( 'status' => 'converted' ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.

	$sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales WHERE id = %d", $sale_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- read feeding the write flow; cache flushed by the journal creation below.
	oby_mi_erp_create_sale_journal( $sale );

	do_action( 'oby_mi_erp_quotation_converted', $id, $sale_id );
	oby_mi_erp_audit_log( 'convert', 'quotation', $id, 'Converted quotation to sale ' . $sale_no );
	oby_mi_erp_redirect_notice( __( 'Quotation converted to sale.', 'obydullah-micro-erp' ) );
}
