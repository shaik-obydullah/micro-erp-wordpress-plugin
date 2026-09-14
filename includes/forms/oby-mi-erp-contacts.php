<?php
/**
 * Form handlers for creating, updating, and deleting contacts (customers and suppliers).
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save (create or update) a contact (customer/supplier) from $_POST.
 *
 * @param string $action 'update_contact' to update the existing row named by $_POST['id'], otherwise create a new one.
 * @return void
 */
function oby_mi_erp_handle_contact_form( $action ) {
	check_admin_referer( 'oby_mi_erp_contact_save' );

	$data = array(
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		'type'    => sanitize_key( wp_unslash( $_POST['type'] ?? 'customer' ) ),
		'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'email'   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'phone'   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
		'address' => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
		'company' => sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ),
		'tax_id'  => sanitize_text_field( wp_unslash( $_POST['tax_id'] ?? '' ) ),
		'status'  => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	);

	if ( ! $data['name'] ) {
		oby_mi_erp_redirect_notice( __( 'Contact name is required.', 'obydullah-micro-erp' ), 'error' );
		return;
	}

	global $wpdb;
	$table = oby_mi_erp_table( 'contacts' );

	if ( 'update_contact' === $action ) {
		$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.
		$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
		$entity_id = $id;
		$message   = __( 'Contact updated.', 'obydullah-micro-erp' );
	} else {
		$wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- write path.
		$entity_id = (int) $wpdb->insert_id;
		$message   = __( 'Contact created.', 'obydullah-micro-erp' );
	}

	oby_mi_erp_flush_cache();

	oby_mi_erp_audit_log( 'save', 'contact', $entity_id, $data['name'] );
	oby_mi_erp_redirect_notice( $message );
}

/**
 * Delete a contact named by $_POST['id'].
 *
 * @return void
 */
function oby_mi_erp_handle_delete_contact() {
	check_admin_referer( 'oby_mi_erp_contact_delete' );
	$id = (int) sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified via check_admin_referer() above.

	global $wpdb;
	$wpdb->delete( oby_mi_erp_table( 'contacts' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- write path.
	oby_mi_erp_flush_cache();
	oby_mi_erp_audit_log( 'delete', 'contact', $id, 'Deleted contact #' . $id );
	oby_mi_erp_redirect_notice( __( 'Contact deleted.', 'obydullah-micro-erp' ) );
}
