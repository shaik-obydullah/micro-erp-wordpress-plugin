<?php
/**
 * Shared helper functions used across the plugin's admin screens and form handlers:
 * table naming, object-cache wrappers, query-string accessors, money/badge
 * formatting, sequential document numbering, and journal-entry posting.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build a fully-prefixed plugin table name.
 *
 * @param string $name Table name without the plugin prefix, e.g. 'accounts'.
 * @return string Fully-prefixed table name, e.g. 'wp_oby_mi_erp_accounts'.
 */
function oby_mi_erp_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . OBY_MI_ERP_TABLE . $name;
}

/**
 * Set a value in the plugin's object-cache group and remember its key
 * so it can be flushed reliably, even on WordPress < 6.1.
 *
 * @param string $key   Cache key.
 * @param mixed  $value Value to store.
 */
function oby_mi_erp_cache_set( $key, $value ) {
	$keys   = (array) wp_cache_get( 'oby_mi_erp_keys', 'oby_mi_erp' );
	$keys[] = $key;
	wp_cache_set( $key, $value, 'oby_mi_erp' );
	wp_cache_set( 'oby_mi_erp_keys', array_unique( $keys ), 'oby_mi_erp' );
}

/**
 * Flush all cached micro-ERP lookups after a write.
 */
function oby_mi_erp_flush_cache() {
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'oby_mi_erp' );
		return;
	}

	$keys = (array) wp_cache_get( 'oby_mi_erp_keys', 'oby_mi_erp' );
	foreach ( $keys as $key ) {
		wp_cache_delete( $key, 'oby_mi_erp' );
	}
	wp_cache_delete( 'oby_mi_erp_keys', 'oby_mi_erp' );
}

/**
 * Centralized read-only $_GET accessors for admin list filters.
 *
 * These query vars drive search boxes, pagination and view state on
 * manage_options-gated admin screens only. They carry no side effects, so
 * nonce verification is intentionally not required; all access is funneled
 * through these helpers to keep that decision documented in one place.
 *
 * @param string $key Query var name.
 * @return bool Whether the query var is present.
 */
function oby_mi_erp_query_has( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] );
}

/**
 * Read and sanitize a text $_GET query var. See oby_mi_erp_query_has() docblock.
 *
 * @param string $key           Query var name.
 * @param string $default_value Value to return when the query var is absent.
 * @return string Sanitized value, or $default_value.
 */
function oby_mi_erp_query_text( $key, $default_value = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default_value;
}

/**
 * Read and sanitize a $_GET query var as a key (e.g. a status/type slug). See oby_mi_erp_query_has() docblock.
 *
 * @param string $key           Query var name.
 * @param string $default_value Value to return when the query var is absent.
 * @return string Sanitized key, or $default_value.
 */
function oby_mi_erp_query_key( $key, $default_value = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : $default_value;
}

/**
 * Read a $_GET query var as a non-negative integer. See oby_mi_erp_query_has() docblock.
 *
 * @param string $key           Query var name.
 * @param int    $default_value Value to return when the query var is absent.
 * @return int Sanitized integer, or $default_value.
 */
function oby_mi_erp_query_int( $key, $default_value = 0 ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? absint( wp_unslash( $_GET[ $key ] ) ) : $default_value;
}

/**
 * Build a plugin admin URL without re-encoding slashes in the page slug.
 * Unlike chained add_query_arg() calls, this never turns "/" into "%2F".
 *
 * @param string $page Plugin page slug, e.g. 'accounts'.
 * @param array  $args Extra query args to merge in.
 * @return string Fully-built admin URL.
 */
function oby_mi_erp_admin_url( $page, $args = array() ) {
	$args = array_merge( array( 'page' => 'oby-mi-erp/' . $page ), $args );

	$pairs = array();
	foreach ( $args as $key => $value ) {
		if ( null === $value || false === $value || '' === $value ) {
			continue;
		}
		$pairs[] = str_replace( '%2F', '/', rawurlencode( (string) $key ) ) . '=' . str_replace( '%2F', '/', rawurlencode( (string) $value ) );
	}

	return admin_url( 'admin.php' ) . '?' . implode( '&', $pairs );
}

/**
 * Get the plugin's configured currency symbol.
 *
 * @return string Currency symbol, defaulting to '$'.
 */
function oby_mi_erp_get_currency_symbol() {
	return get_option( 'oby_mi_erp_currency_symbol', '$' );
}

/**
 * Format an amount for display with the plugin's currency symbol.
 *
 * @param float $amount Amount to format.
 * @return string Formatted amount, e.g. "$1,234.50".
 */
function oby_mi_erp_format_money( $amount ) {
	$symbol = oby_mi_erp_get_currency_symbol();
	$value  = number_format( (float) $amount, 2 );
	return $symbol . $value;
}

/**
 * Get the currently active fiscal year row, cached.
 *
 * @return object|null Fiscal year row, or null if none is active.
 */
function oby_mi_erp_get_active_fiscal_year() {
	$cache_key = 'oby_mi_erp_active_fiscal_year';
	$fy        = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $fy ) {
		return $fy;
	}

	global $wpdb;
	$fy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_fiscal_years WHERE is_active = %d ORDER BY id DESC LIMIT 1", 1 ) );

	oby_mi_erp_cache_set( $cache_key, $fy );
	return $fy;
}

/**
 * Get the ID of the currently active fiscal year.
 *
 * @return int Fiscal year ID, or 0 if none is active.
 */
function oby_mi_erp_get_fiscal_year_id() {
	$fy = oby_mi_erp_get_active_fiscal_year();
	return $fy ? (int) $fy->id : 0;
}

/**
 * Get a plugin setting value, cached.
 *
 * @param string $key           Setting key.
 * @param string $default_value Value to return when the setting is unset.
 * @return string Setting value, or $default_value.
 */
function oby_mi_erp_get_setting( $key, $default_value = '' ) {
	$cache_key = 'oby_mi_erp_setting_' . $key;
	$cached    = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->prefix}oby_mi_erp_settings WHERE option_key = %s", $key ) );
	$val = null !== $val ? $val : $default_value;

	oby_mi_erp_cache_set( $cache_key, $val );
	return $val;
}

/**
 * Save (insert or update) a plugin setting value and invalidate its cache.
 *
 * @param string $key   Setting key.
 * @param string $value Setting value.
 * @return void
 */
function oby_mi_erp_set_setting( $key, $value ) {
	global $wpdb;
	$table = oby_mi_erp_table( 'settings' );
	$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE option_key = %s", $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- upsert existence check inside a write; the setting cache is invalidated below regardless; $table is a fixed plugin table name, not user input.
	if ( $found ) {
		$wpdb->update( $table, array( 'option_value' => $value ), array( 'option_key' => $key ), array( '%s' ), array( '%s' ) );
	} else {
		$wpdb->insert(
			$table,
			array(
				'option_key'   => $key,
				'option_value' => $value,
			),
			array( '%s', '%s' )
		);
	}
	wp_cache_delete( 'oby_mi_erp_setting_' . $key, 'oby_mi_erp' );
}

/**
 * Record an audit-log entry for a plugin data change.
 *
 * @param string $action      Action performed, e.g. 'save', 'delete', 'status'.
 * @param string $entity_type Entity type, e.g. 'account', 'quotation'.
 * @param int    $entity_id   Affected entity's ID.
 * @param string $description Human-readable description of the change.
 * @return void
 */
function oby_mi_erp_audit_log( $action, $entity_type, $entity_id, $description = '' ) {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) && ! is_user_logged_in() ) {
		$user_id = 0;
	} else {
		$user_id = get_current_user_id();
	}
	$wpdb->insert(
		oby_mi_erp_table( 'audit_log' ),
		array(
			'user_id'     => $user_id,
			'action'      => $action,
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'description' => $description,
			'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		),
		array( '%d', '%s', '%s', '%d', '%s', '%s' )
	);
}

/**
 * Generate the next sequential employee ID, e.g. "EMP-004".
 *
 * @return string Next employee ID.
 */
function oby_mi_erp_next_employee_id() {
	global $wpdb;
	$max = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(SUBSTRING(employee_id, %d) AS UNSIGNED)) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE employee_id LIKE %s", 5, 'EMP-%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- MUST return a fresh value every call to produce a unique employee number; caching would generate duplicates.
	return 'EMP-' . str_pad( $max + 1, 3, '0', STR_PAD_LEFT );
}

/**
 * Generate the next sequential, year-scoped document number for a table/column,
 * e.g. "QUO-2026-0007".
 *
 * @param string $table  Fully-prefixed table name to scan.
 * @param string $column Column holding the existing document numbers.
 * @param string $prefix Document number prefix, e.g. 'QUO-'.
 * @return string Next document number.
 */
function oby_mi_erp_next_number( $table, $column, $prefix ) {
	global $wpdb;
	$year = current_time( 'Y' );
	$max  = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching -- MUST return a fresh value every call to produce a unique sequential number; caching would generate duplicates.
		$wpdb->prepare(
			"SELECT MAX(CAST(SUBSTRING({$column}, %d) AS UNSIGNED)) FROM {$table} WHERE {$column} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted internal identifiers
			strlen( $prefix . $year . '-' ) + 1,
			$prefix . $year . '-%'
		)
	);
	return $prefix . $year . '-' . str_pad( $max + 1, 4, '0', STR_PAD_LEFT );
}

/**
 * Generate the next sequential quotation number.
 *
 * @return string Next quotation number.
 */
function oby_mi_erp_next_quotation_no() {
	return oby_mi_erp_next_number( oby_mi_erp_table( 'quotations' ), 'quotation_no', 'QUO-' );
}

/**
 * Generate the next sequential sale number.
 *
 * @return string Next sale number.
 */
function oby_mi_erp_next_sale_no() {
	return oby_mi_erp_next_number( oby_mi_erp_table( 'sales' ), 'sale_no', 'SALE-' );
}

/**
 * Queue an admin notice to display after the next redirect.
 *
 * @param string $message Notice text.
 * @param string $type    Notice type: 'success' or 'error'.
 * @return void
 */
function oby_mi_erp_redirect_notice( $message, $type = 'success' ) {
	set_transient(
		'oby_mi_erp_admin_notice',
		array(
			'message' => $message,
			'type'    => $type,
		),
		30
	);
}

/**
 * Print and clear the admin notice queued by oby_mi_erp_redirect_notice(), if any.
 *
 * @return void
 */
function oby_mi_erp_print_admin_notice() {
	$notice = get_transient( 'oby_mi_erp_admin_notice' );
	if ( $notice ) {
		delete_transient( 'oby_mi_erp_admin_notice' );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $notice['type'] ),
			esc_html( $notice['message'] )
		);
	}
}

/**
 * Verify a request nonce, halting with wp_die() on failure.
 *
 * @param string $action Nonce action name to verify against.
 * @param string $arg    $_POST key holding the nonce value.
 * @return void
 */
function oby_mi_erp_verify_nonce( $action, $arg = '_wpnonce' ) {
	// Verify nonce - sanitize the input first.
	$nonce = sanitize_text_field( wp_unslash( $_POST[ $arg ] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, $action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'obydullah-micro-erp' ) );
	}
}

/**
 * Get all accounts, optionally filtered by type, cached.
 *
 * @param string $type Account type to filter by, e.g. 'asset'; empty for all.
 * @return array Account rows ordered by code.
 */
function oby_mi_erp_get_accounts( $type = '' ) {
	$cache_key = $type ? 'oby_mi_erp_accounts_' . $type : 'oby_mi_erp_accounts_all';
	$accounts  = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $accounts ) {
		return $accounts;
	}

	global $wpdb;
	if ( $type ) {
		$accounts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s ORDER BY code ASC", $type ) );
	} else {
		$accounts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts ORDER BY code ASC" );
	}

	oby_mi_erp_cache_set( $cache_key, $accounts );
	return $accounts;
}

/**
 * Compute an account's current balance from its journal lines, cached.
 *
 * Asset and expense accounts carry a normal debit balance (debit - credit);
 * all other account types carry a normal credit balance (credit - debit).
 *
 * @param int $account_id Account ID.
 * @return float Account balance.
 */
function oby_mi_erp_account_balance( $account_id ) {
	$cache_key = 'oby_mi_erp_account_balance_' . (int) $account_id;
	$balance   = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $balance ) {
		return $balance;
	}

	global $wpdb;
	$account_id = (int) $account_id;
	$table      = oby_mi_erp_table( 'journal_lines' );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed plugin table name, not user input; the actual value is placeholder-bound below.
	$debit = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(debit),0) FROM {$table} WHERE account_id = %d", $account_id ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed plugin table name, not user input; the actual value is placeholder-bound below.
	$credit = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit),0) FROM {$table} WHERE account_id = %d", $account_id ) );

	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE id = %d", $account_id ) );
	if ( ! $account ) {
		return 0;
	}

	$normal_debit = in_array( $account->type, array( 'asset', 'expense' ), true );
	$balance      = $normal_debit ? $debit - $credit : $credit - $debit;

	oby_mi_erp_cache_set( $cache_key, $balance );
	return $balance;
}

/**
 * Get the total posted income across all fiscal years, cached.
 *
 * @return float Total income.
 */
function oby_mi_erp_total_income() {
	$cache_key = 'oby_mi_erp_total_income';
	$total     = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $total ) {
		return (float) $total;
	}

	global $wpdb;
	$total = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT SUM(credit) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l INNER JOIN {$wpdb->prefix}oby_mi_erp_accounts a ON a.id = l.account_id WHERE a.type = %s",
			'income'
		)
	);

	oby_mi_erp_cache_set( $cache_key, $total );
	return $total;
}

/**
 * Get the total posted expense across all fiscal years, cached.
 *
 * @return float Total expense.
 */
function oby_mi_erp_total_expense() {
	$cache_key = 'oby_mi_erp_total_expense';
	$total     = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $total ) {
		return (float) $total;
	}

	global $wpdb;
	$total = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT SUM(debit) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l INNER JOIN {$wpdb->prefix}oby_mi_erp_accounts a ON a.id = l.account_id WHERE a.type = %s",
			'expense'
		)
	);

	oby_mi_erp_cache_set( $cache_key, $total );
	return $total;
}

/**
 * Look up a contact's name by ID, cached.
 *
 * @param int $id Contact ID.
 * @return string Contact name, or an em dash placeholder if not found.
 */
function oby_mi_erp_contact_name( $id ) {
	$cache_key = 'oby_mi_erp_contact_name_' . (int) $id;
	$name      = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $name ) {
		return $name;
	}

	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE id = %d", $id ) );
	$name = $name ? $name : '—';

	oby_mi_erp_cache_set( $cache_key, $name );
	return $name;
}

/**
 * Look up an employee's name by ID, cached.
 *
 * @param int $id Employee ID.
 * @return string Employee name, or an em dash placeholder if not found.
 */
function oby_mi_erp_employee_name( $id ) {
	$cache_key = 'oby_mi_erp_employee_name_' . (int) $id;
	$name      = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $name ) {
		return $name;
	}

	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_employees WHERE id = %d", $id ) );
	$name = $name ? $name : '—';

	oby_mi_erp_cache_set( $cache_key, $name );
	return $name;
}

/**
 * Look up a department's name by ID, cached.
 *
 * @param int $id Department ID.
 * @return string Department name, or an em dash placeholder if not found.
 */
function oby_mi_erp_department_name( $id ) {
	$cache_key = 'oby_mi_erp_department_name_' . (int) $id;
	$name      = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $name ) {
		return $name;
	}

	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_departments WHERE id = %d", $id ) );
	$name = $name ? $name : '—';

	oby_mi_erp_cache_set( $cache_key, $name );
	return $name;
}

/**
 * Look up a leave type's name by ID, cached.
 *
 * @param int $id Leave type ID.
 * @return string Leave type name, or an em dash placeholder if not found.
 */
function oby_mi_erp_leave_type_name( $id ) {
	$cache_key = 'oby_mi_erp_leave_type_name_' . (int) $id;
	$name      = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $name ) {
		return $name;
	}

	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_leave_types WHERE id = %d", $id ) );
	$name = $name ? $name : '—';

	oby_mi_erp_cache_set( $cache_key, $name );
	return $name;
}

/**
 * Render a status badge <span> for a value, using a value-to-CSS-class map.
 *
 * @param string $value Value to display, e.g. a status slug.
 * @param array  $map   Map of value => CSS class suffix.
 * @return string HTML for the badge.
 */
function oby_mi_erp_badge( $value, $map = array() ) {
	$class = isset( $map[ $value ] ) ? $map[ $value ] : 'status-neutral';
	return '<span class="status-badge ' . esc_attr( $class ) . '">' . esc_html( ucfirst( $value ) ) . '</span>';
}

/**
 * Render a status badge for any of the plugin's various entity status values.
 *
 * @param string $status Status slug, e.g. 'active', 'paid', 'pending'.
 * @return string HTML for the badge.
 */
function oby_mi_erp_status_badge( $status ) {
	$map = array(
		'active'        => 'status-active',
		'inactive'      => 'status-inactive',
		'terminated'    => 'status-inactive',
		'draft'         => 'status-neutral',
		'sent'          => 'status-info',
		'accepted'      => 'status-active',
		'rejected'      => 'status-inactive',
		'converted'     => 'status-info',
		'paid'          => 'status-active',
		'unpaid'        => 'status-inactive',
		'partial'       => 'status-warning',
		'pending'       => 'status-warning',
		'approved'      => 'status-active',
		'present'       => 'status-active',
		'absent'        => 'status-inactive',
		'late'          => 'status-warning',
		'approved_half' => 'status-info',
	);
	return oby_mi_erp_badge( $status, $map );
}

/**
 * Render a status badge for a contact type.
 *
 * @param string $type Contact type: 'customer', 'vendor', or 'supplier'.
 * @return string HTML for the badge.
 */
function oby_mi_erp_contact_type_badge( $type ) {
	$map = array(
		'customer' => 'status-info',
		'vendor'   => 'status-neutral',
		'supplier' => 'status-warning',
	);
	return oby_mi_erp_badge( $type, $map );
}

/**
 * Post a double-entry journal entry with its debit/credit lines.
 *
 * @param string      $date           Entry date (Y-m-d).
 * @param string      $description    Entry description.
 * @param array[]     $lines          Line rows, each with 'account_id', 'debit', 'credit',
 *                                    and optionally 'description'.
 * @param string|null $reference_type Source document type, e.g. 'sale'.
 * @param int|null    $reference_id   Source document ID.
 * @return int Newly created journal entry ID, or 0 on failure.
 */
function oby_mi_erp_create_journal_entry( $date, $description, $lines, $reference_type = null, $reference_id = null ) {
	global $wpdb;

	$fiscal_year_id = oby_mi_erp_get_fiscal_year_id();

	$wpdb->insert(
		oby_mi_erp_table( 'journal_entries' ),
		array(
			'entry_date'     => $date,
			'reference_type' => $reference_type,
			'reference_id'   => $reference_id,
			'description'    => $description,
			'fiscal_year_id' => $fiscal_year_id,
			'created_by'     => get_current_user_id(),
		),
		array( '%s', '%s', '%d', '%s', '%d', '%d' )
	);

	$entry_id = (int) $wpdb->insert_id;

	if ( ! $entry_id ) {
		return 0;
	}

	foreach ( $lines as $line ) {
		$wpdb->insert(
			oby_mi_erp_table( 'journal_lines' ),
			array(
				'entry_id'    => $entry_id,
				'account_id'  => (int) $line['account_id'],
				'debit'       => (float) $line['debit'],
				'credit'      => (float) $line['credit'],
				'description' => isset( $line['description'] ) ? $line['description'] : null,
			),
			array( '%d', '%d', '%f', '%f', '%s' )
		);
	}

	oby_mi_erp_flush_cache();

	return $entry_id;
}

/**
 * Post the accounting entry for a sale: debit Accounts Receivable, credit Sales Income.
 *
 * @param object $sale Sale row.
 * @return int Newly created journal entry ID, or 0 if $sale is invalid.
 */
function oby_mi_erp_create_sale_journal( $sale ) {
	if ( ! $sale || empty( $sale->id ) ) {
		return 0;
	}

	$ar_account     = oby_mi_erp_default_account( 'asset', '1003' );
	$income_account = oby_mi_erp_default_account( 'income', '4001' );

	return oby_mi_erp_create_journal_entry(
		$sale->sale_date,
		sprintf( 'Sale - %s (%s)', $sale->sale_no, oby_mi_erp_contact_name( $sale->contact_id ) ),
		array(
			array(
				'account_id' => $ar_account,
				'debit'      => (float) $sale->total,
				'credit'     => 0,
			),
			array(
				'account_id' => $income_account,
				'debit'      => 0,
				'credit'     => (float) $sale->total,
			),
		),
		'sale',
		(int) $sale->id
	);
}

/**
 * Resolve the default account to post to for a given account type, cached.
 *
 * Tries, in order: an active account of $type with $fallback_code, any active
 * account of $type, then any account with $fallback_code regardless of status.
 *
 * @param string $type          Account type, e.g. 'asset'.
 * @param string $fallback_code Preferred account code, e.g. '1003'.
 * @return int Account ID, or 0 if none could be resolved.
 */
function oby_mi_erp_default_account( $type, $fallback_code ) {
	$cache_key  = 'oby_mi_erp_default_account_' . $type . '_' . $fallback_code;
	$account_id = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $account_id ) {
		return (int) $account_id;
	}

	global $wpdb;
	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s AND code = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type, $fallback_code ) );
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type ) );
	}
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code = %s LIMIT 1", $fallback_code ) );
	}

	$account_id = $account ? (int) $account->id : 0;
	oby_mi_erp_cache_set( $cache_key, $account_id );
	return $account_id;
}

/**
 * Sum a numeric column across an array of row objects.
 *
 * @param object[] $rows Row objects, e.g. from $wpdb->get_results().
 * @param string   $key  Property name to sum.
 * @return float Sum of the column.
 */
function oby_mi_erp_sum( $rows, $key ) {
	$total = 0;
	foreach ( (array) $rows as $row ) {
		if ( isset( $row->{$key} ) ) {
			$total += (float) $row->{$key};
		}
	}
	return $total;
}

/**
 * Get total balances grouped by account type (asset, liability, equity, income,
 * expense), cached. Used to render the balance sheet / dashboard summary.
 *
 * @return array Map of account type => total balance.
 */
function oby_mi_erp_get_account_balances_by_type() {
	$cache_key = 'oby_mi_erp_account_balances_by_type';
	$types     = wp_cache_get( $cache_key, 'oby_mi_erp' );
	if ( false !== $types ) {
		return $types;
	}

	global $wpdb;
	$accounts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts ORDER BY id ASC" );
	$types    = array(
		'asset'     => 0,
		'liability' => 0,
		'equity'    => 0,
		'income'    => 0,
		'expense'   => 0,
	);

	foreach ( $accounts as $account ) {
		if ( ! isset( $types[ $account->type ] ) ) {
			$types[ $account->type ] = 0;
		}
		$types[ $account->type ] += oby_mi_erp_account_balance( $account->id );
	}

	oby_mi_erp_cache_set( $cache_key, $types );

	return $types;
}

/**
 * Render the shared search bar used by plugin list tables.
 * Preserves any extra query args as hidden inputs so existing filters survive a new search.
 *
 * @param string $page_slug Plugin page slug (e.g. 'journal').
 * @param string $label     Field label.
 * @param string $placeholder Input placeholder text.
 * @param array  $hidden    Extra args to persist (e.g. array( 'type' => 'asset' )).
 * @param string $current   Current search term.
 * @param bool   $inline    True to render a borderless variant for embedding inside an existing toolbar.
 */
function oby_mi_erp_render_search_bar( $page_slug, $label, $placeholder, $hidden = array(), $current = '', $inline = false ) {
	$form_class = $inline ? 'inline-search' : 'search-section mb-3';
	?>
<form method="get" action="" class="<?php echo esc_attr( $form_class ); ?>">
	<input type="hidden" name="page" value="oby-mi-erp/<?php echo esc_attr( $page_slug ); ?>">
	<?php
	foreach ( $hidden as $h_key => $h_val ) :
		if ( '' === $h_val || null === $h_val ) {
			continue;
		}
		?>
	<input type="hidden" name="<?php echo esc_attr( $h_key ); ?>" value="<?php echo esc_attr( (string) $h_val ); ?>">
	<?php endforeach; ?>
	<?php if ( $inline ) : ?>
	<label for="s-<?php echo esc_attr( $page_slug ); ?>"
		class="form-label mb-0"><?php echo esc_html( $label ); ?></label>
	<input type="text" name="s" id="s-<?php echo esc_attr( $page_slug ); ?>"
		class="form-control form-control-sm search-field" placeholder="<?php echo esc_attr( $placeholder ); ?>"
		value="<?php echo esc_attr( $current ); ?>">
	<button type="submit" id="search-button"
		class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>
		<?php if ( $current ) : ?>
	<a href="<?php echo esc_url( oby_mi_erp_admin_url( $page_slug, $hidden ) ); ?>"
		class="btn-secondary"><?php esc_html_e( 'Clear', 'obydullah-micro-erp' ); ?></a>
	<?php endif; ?>
	<?php else : ?>
	<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
		<label for="s-<?php echo esc_attr( $page_slug ); ?>"
			class="form-label mb-0"><?php echo esc_html( $label ); ?></label>
		<input type="text" name="s" id="s-<?php echo esc_attr( $page_slug ); ?>"
			class="form-control form-control-sm search-field" placeholder="<?php echo esc_attr( $placeholder ); ?>"
			value="<?php echo esc_attr( $current ); ?>">
		<button type="submit" id="search-button"
			class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>
		<?php if ( $current ) : ?>
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( $page_slug, $hidden ) ); ?>"
			class="btn-secondary"><?php esc_html_e( 'Clear', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</form>
	<?php
}

/**
 * Render a pagination bar for plugin list tables.
 * Styled via the bundled .tablenav-pages CSS; hidden when everything fits on one page.
 *
 * @param string $page_slug   Plugin page slug (e.g. 'contacts').
 * @param int    $total_items Total rows matching the current query.
 * @param int    $per_page    Rows shown per page.
 */
function oby_mi_erp_render_pagination( $page_slug, $total_items, $per_page = 20 ) {
	$total_items = (int) $total_items;
	$per_page    = max( 1, (int) $per_page );

	if ( $total_items <= $per_page ) {
		return;
	}

	$total_pages = (int) ceil( $total_items / $per_page );
	$paged       = oby_mi_erp_query_int( 'paged', 1 );
	$paged       = min( max( 1, $paged ), $total_pages );

	// Preserve known filter args across page links.
	$filter_keys = array( 's', 'type', 'status', 'department_id', 'month', 'date' );
	$args        = array();
	foreach ( $filter_keys as $key ) {
		if ( oby_mi_erp_query_has( $key ) && '' !== oby_mi_erp_query_text( $key ) ) {
			$args[ $key ] = oby_mi_erp_query_text( $key );
		}
	}

	$page_url = function ( $n ) use ( $page_slug, $args ) {
		return oby_mi_erp_admin_url( $page_slug, array_merge( $args, array( 'paged' => $n > 1 ? $n : '' ) ) );
	};

	$out = '<div class="tablenav-pages">';
	/* translators: %s: number of items. */
	$out .= '<span class="displaying-num">' . esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'obydullah-micro-erp' ), number_format_i18n( $total_items ) ) ) . '</span>';
	$out .= '<div class="pagination-links">';

	// First / Prev.
	if ( $paged > 1 ) {
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( 1 ) ) . '" aria-label="' . esc_attr__( 'First page', 'obydullah-micro-erp' ) . '">«</a>';
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $paged - 1 ) ) . '" aria-label="' . esc_attr__( 'Previous page', 'obydullah-micro-erp' ) . '">‹</a>';
	} else {
		$out .= '<span class="btn btn-dark btn-disabled">«</span><span class="btn btn-dark btn-disabled">‹</span>';
	}

	// Page number window: up to five numbers centred on the current one.
	$start = max( 1, $paged - 2 );
	$end   = min( $total_pages, $paged + 2 );

	if ( $start > 1 ) {
		$out .= '<a class="btn btn-white" href="' . esc_url( $page_url( 1 ) ) . '">1</a>';
		if ( $start > 2 ) {
			$out .= '<span class="tablenav-dots">…</span>';
		}
	}
	for ( $i = $start; $i <= $end; $i++ ) {
		if ( $i === $paged ) {
			$out .= '<span class="btn current-page" aria-current="page">' . (int) $i . '</span>';
		} else {
			$out .= '<a class="btn btn-white" href="' . esc_url( $page_url( $i ) ) . '">' . (int) $i . '</a>';
		}
	}
	if ( $end < $total_pages ) {
		if ( $end < $total_pages - 1 ) {
			$out .= '<span class="tablenav-dots">…</span>';
		}
		$out .= '<a class="btn btn-white" href="' . esc_url( $page_url( $total_pages ) ) . '">' . (int) $total_pages . '</a>';
	}

	// Next / Last.
	if ( $paged < $total_pages ) {
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $paged + 1 ) ) . '" aria-label="' . esc_attr__( 'Next page', 'obydullah-micro-erp' ) . '">›</a>';
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $total_pages ) ) . '" aria-label="' . esc_attr__( 'Last page', 'obydullah-micro-erp' ) . '">»</a>';
	} else {
		$out .= '<span class="btn btn-dark btn-disabled">›</span><span class="btn btn-dark btn-disabled">»</span>';
	}

	$out .= '</div></div>';

	echo wp_kses_post( $out ); // phpcs:ignore WordPress.Security.EscapeOutput -- all values escaped above.
}
