<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oby_mi_erp_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . OBY_MI_ERP_TABLE . $name;
}

/**
 * Centralized read-only $_GET accessors for admin list filters.
 *
 * These query vars drive search boxes, pagination and view state on
 * manage_options-gated admin screens only. They carry no side effects, so
 * nonce verification is intentionally not required; all access is funneled
 * through these helpers to keep that decision documented in one place.
 */

function oby_mi_erp_query_has( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] );
}

function oby_mi_erp_query_text( $key, $default = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default;
}

function oby_mi_erp_query_key( $key, $default = '' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : $default;
}

function oby_mi_erp_query_int( $key, $default = 0 ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter, see docblock above.
	return isset( $_GET[ $key ] ) ? absint( wp_unslash( $_GET[ $key ] ) ) : $default;
}

/**
 * Build a plugin admin URL without re-encoding slashes in the page slug.
 * Unlike chained add_query_arg() calls, this never turns "/" into "%2F".
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

function oby_mi_erp_get_currency_symbol() {
	return get_option( 'oby_mi_erp_currency_symbol', '$' );
}

function oby_mi_erp_format_money( $amount ) {
	$symbol = oby_mi_erp_get_currency_symbol();
	$value  = number_format( (float) $amount, 2 );
	return $symbol . $value;
}

function oby_mi_erp_get_active_fiscal_year() {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_fiscal_years WHERE is_active = %d ORDER BY id DESC LIMIT 1", 1 ) );
}

function oby_mi_erp_get_fiscal_year_id() {
	$fy = oby_mi_erp_get_active_fiscal_year();
	return $fy ? (int) $fy->id : 0;
}

function oby_mi_erp_get_setting( $key, $default = '' ) {
	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->prefix}oby_mi_erp_settings WHERE option_key = %s", $key ) );
	return null !== $val ? $val : $default;
}

function oby_mi_erp_set_setting( $key, $value ) {
	global $wpdb;
	$table = oby_mi_erp_table( 'settings' );
	$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE option_key = %s", $key ) );
	if ( $found ) {
		$wpdb->update( $table, array( 'option_value' => $value ), array( 'option_key' => $key ), array( '%s' ), array( '%s' ) );
	} else {
		$wpdb->insert( $table, array( 'option_key' => $key, 'option_value' => $value ), array( '%s', '%s' ) );
	}
}

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
			'user_id'      => $user_id,
			'action'       => $action,
			'entity_type'  => $entity_type,
			'entity_id'    => $entity_id,
			'description'  => $description,
			'ip_address'   => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		),
		array( '%d', '%s', '%s', '%d', '%s', '%s' )
	);
}

function oby_mi_erp_next_employee_id() {
	global $wpdb;
	$max = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(SUBSTRING(employee_id, %d) AS UNSIGNED)) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE employee_id LIKE %s", 5, 'EMP-%' ) );
	return 'EMP-' . str_pad( $max + 1, 3, '0', STR_PAD_LEFT );
}

function oby_mi_erp_next_number( $table, $column, $prefix ) {
	global $wpdb;
	$year = current_time( 'Y' );
	$max  = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(CAST(SUBSTRING({$column}, %d) AS UNSIGNED)) FROM {$table} WHERE {$column} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted internal identifiers
			strlen( $prefix . $year . '-' ) + 1,
			$prefix . $year . '-%'
		)
	);
	return $prefix . $year . '-' . str_pad( $max + 1, 4, '0', STR_PAD_LEFT );
}

function oby_mi_erp_next_quotation_no() {
	return oby_mi_erp_next_number( oby_mi_erp_table( 'quotations' ), 'quotation_no', 'QUO-' );
}

function oby_mi_erp_next_sale_no() {
	return oby_mi_erp_next_number( oby_mi_erp_table( 'sales' ), 'sale_no', 'SALE-' );
}

function oby_mi_erp_redirect_notice( $message, $type = 'success' ) {
	set_transient( 'oby_mi_erp_admin_notice', array( 'message' => $message, 'type' => $type ), 30 );
}

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

function oby_mi_erp_verify_nonce( $action, $arg = '_wpnonce' ) {
	if ( ! isset( $_POST[ $arg ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $arg ] ) ), $action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'obydullah-micro-erp' ) );
	}
}

function oby_mi_erp_get_accounts( $type = '' ) {
	global $wpdb;
	if ( $type ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s ORDER BY code ASC", $type ) );
	}
	return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts ORDER BY code ASC" );
}

function oby_mi_erp_account_balance( $account_id ) {
	global $wpdb;
	$account_id = (int) $account_id;
	$table      = oby_mi_erp_table( 'journal_lines' );
	$debit      = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(debit),0) FROM {$table} WHERE account_id = %d", $account_id ) );
	$credit     = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit),0) FROM {$table} WHERE account_id = %d", $account_id ) );

	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE id = %d", $account_id ) );
	if ( ! $account ) {
		return 0;
	}

	$normal_debit = in_array( $account->type, array( 'asset', 'expense' ), true );
	if ( $normal_debit ) {
		return $debit - $credit;
	}
	return $credit - $debit;
}

function oby_mi_erp_total_income() {
	global $wpdb;
	return (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT SUM(credit) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l INNER JOIN {$wpdb->prefix}oby_mi_erp_accounts a ON a.id = l.account_id WHERE a.type = %s",
			'income'
		)
	);
}

function oby_mi_erp_total_expense() {
	global $wpdb;
	return (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT SUM(debit) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l INNER JOIN {$wpdb->prefix}oby_mi_erp_accounts a ON a.id = l.account_id WHERE a.type = %s",
			'expense'
		)
	);
}

function oby_mi_erp_contact_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function oby_mi_erp_employee_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_employees WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function oby_mi_erp_department_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_departments WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function oby_mi_erp_leave_type_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}oby_mi_erp_leave_types WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function oby_mi_erp_badge( $value, $map = array() ) {
	$class = isset( $map[ $value ] ) ? $map[ $value ] : 'status-neutral';
	return '<span class="status-badge ' . esc_attr( $class ) . '">' . esc_html( ucfirst( $value ) ) . '</span>';
}

function oby_mi_erp_status_badge( $status ) {
	$map = array(
		'active'    => 'status-active',
		'inactive'  => 'status-inactive',
		'terminated'=> 'status-inactive',
		'draft'     => 'status-neutral',
		'sent'      => 'status-info',
		'accepted'  => 'status-active',
		'rejected'  => 'status-inactive',
		'converted' => 'status-info',
		'paid'      => 'status-active',
		'unpaid'    => 'status-inactive',
		'partial'   => 'status-warning',
		'pending'   => 'status-warning',
		'approved'  => 'status-active',
		'present'   => 'status-active',
		'absent'    => 'status-inactive',
		'late'      => 'status-warning',
		'approved_half' => 'status-info',
	);
	return oby_mi_erp_badge( $status, $map );
}

function oby_mi_erp_contact_type_badge( $type ) {
	$map = array(
		'customer' => 'status-info',
		'vendor'   => 'status-neutral',
		'supplier' => 'status-warning',
	);
	return oby_mi_erp_badge( $type, $map );
}

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

	return $entry_id;
}

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
			array( 'account_id' => $ar_account, 'debit' => (float) $sale->total, 'credit' => 0 ),
			array( 'account_id' => $income_account, 'debit' => 0, 'credit' => (float) $sale->total ),
		),
		'sale',
		(int) $sale->id
	);
}

function oby_mi_erp_default_account( $type, $fallback_code ) {
	global $wpdb;
	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s AND code = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type, $fallback_code ) );
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE type = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type ) );
	}
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code = %s LIMIT 1", $fallback_code ) );
	}
	return $account ? (int) $account->id : 0;
}

function oby_mi_erp_sum( $rows, $key ) {
	$total = 0;
	foreach ( (array) $rows as $row ) {
		if ( isset( $row->{$key} ) ) {
			$total += (float) $row->{$key};
		}
	}
	return $total;
}

function oby_mi_erp_get_account_balances_by_type() {
	global $wpdb;
	$accounts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts ORDER BY id ASC" );
	$types    = array( 'asset' => 0, 'liability' => 0, 'equity' => 0, 'income' => 0, 'expense' => 0 );

	foreach ( $accounts as $account ) {
		if ( ! isset( $types[ $account->type ] ) ) {
			$types[ $account->type ] = 0;
		}
		$types[ $account->type ] += oby_mi_erp_account_balance( $account->id );
	}

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
		<?php foreach ( $hidden as $h_key => $h_val ) :
			if ( '' === $h_val || null === $h_val ) {
				continue;
			}
			?>
			<input type="hidden" name="<?php echo esc_attr( $h_key ); ?>" value="<?php echo esc_attr( (string) $h_val ); ?>">
		<?php endforeach; ?>
		<?php if ( $inline ) : ?>
			<label for="s-<?php echo esc_attr( $page_slug ); ?>" class="form-label mb-0"><?php echo esc_html( $label ); ?></label>
			<input type="text" name="s" id="s-<?php echo esc_attr( $page_slug ); ?>" class="form-control form-control-sm search-field" placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $current ); ?>">
			<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>
			<?php if ( $current ) : ?>
				<a href="<?php echo esc_url( oby_mi_erp_admin_url( $page_slug, $hidden ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Clear', 'obydullah-micro-erp' ); ?></a>
			<?php endif; ?>
		<?php else : ?>
			<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
				<label for="s-<?php echo esc_attr( $page_slug ); ?>" class="form-label mb-0"><?php echo esc_html( $label ); ?></label>
				<input type="text" name="s" id="s-<?php echo esc_attr( $page_slug ); ?>" class="form-control form-control-sm search-field" placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $current ); ?>">
				<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>
				<?php if ( $current ) : ?>
					<a href="<?php echo esc_url( oby_mi_erp_admin_url( $page_slug, $hidden ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Clear', 'obydullah-micro-erp' ); ?></a>
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

	$out  = '<div class="tablenav-pages">';
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
