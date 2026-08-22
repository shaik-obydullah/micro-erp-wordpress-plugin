<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function micro_erp_table( $name ) {
	return MICRO_ERP_TABLE . $name;
}

/**
 * Build a plugin admin URL without re-encoding slashes in the page slug.
 * Unlike chained add_query_arg() calls, this never turns "/" into "%2F".
 */
function micro_erp_admin_url( $page, $args = array() ) {
	$args = array_merge( array( 'page' => 'micro-erp/' . $page ), $args );

	$pairs = array();
	foreach ( $args as $key => $value ) {
		if ( null === $value || false === $value || '' === $value ) {
			continue;
		}
		$pairs[] = str_replace( '%2F', '/', rawurlencode( (string) $key ) ) . '=' . str_replace( '%2F', '/', rawurlencode( (string) $value ) );
	}

	return admin_url( 'admin.php' ) . '?' . implode( '&', $pairs );
}

function micro_erp_get_currency_symbol() {
	return get_option( 'micro_erp_currency_symbol', '$' );
}

function micro_erp_format_money( $amount ) {
	$symbol = micro_erp_get_currency_symbol();
	$value  = number_format( (float) $amount, 2 );
	return $symbol . $value;
}

function micro_erp_get_active_fiscal_year() {
	global $wpdb;
	return $wpdb->get_row( "SELECT * FROM " . micro_erp_table( 'fiscal_years' ) . " WHERE is_active = 1 ORDER BY id DESC LIMIT 1" );
}

function micro_erp_get_fiscal_year_id() {
	$fy = micro_erp_get_active_fiscal_year();
	return $fy ? (int) $fy->id : 0;
}

function micro_erp_get_setting( $key, $default = '' ) {
	global $wpdb;
	$val = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM " . micro_erp_table( 'settings' ) . " WHERE option_key = %s", $key ) );
	return null !== $val ? $val : $default;
}

function micro_erp_set_setting( $key, $value ) {
	global $wpdb;
	$table = micro_erp_table( 'settings' );
	$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE option_key = %s", $key ) );
	if ( $found ) {
		$wpdb->update( $table, array( 'option_value' => $value ), array( 'option_key' => $key ) );
	} else {
		$wpdb->insert( $table, array( 'option_key' => $key, 'option_value' => $value ) );
	}
}

function micro_erp_audit_log( $action, $entity_type, $entity_id, $description = '' ) {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) && ! is_user_logged_in() ) {
		$user_id = 0;
	} else {
		$user_id = get_current_user_id();
	}
	$wpdb->insert(
		micro_erp_table( 'audit_log' ),
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

function micro_erp_next_employee_id() {
	global $wpdb;
	$max = (int) $wpdb->get_var( "SELECT MAX(CAST(SUBSTRING(employee_id, 5) AS UNSIGNED)) FROM " . micro_erp_table( 'employees' ) . " WHERE employee_id LIKE 'EMP-%'" );
	return 'EMP-' . str_pad( $max + 1, 3, '0', STR_PAD_LEFT );
}

function micro_erp_next_number( $table, $column, $prefix ) {
	global $wpdb;
	$year = current_time( 'Y' );
	$max  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(SUBSTRING({$column}, %d) AS UNSIGNED)) FROM " . $table . " WHERE {$column} LIKE %s", strlen( $prefix . $year . '-' ) + 1, $prefix . $year . '-%' ) );
	return $prefix . $year . '-' . str_pad( $max + 1, 4, '0', STR_PAD_LEFT );
}

function micro_erp_next_quotation_no() {
	return micro_erp_next_number( micro_erp_table( 'quotations' ), 'quotation_no', 'QUO-' );
}

function micro_erp_next_sale_no() {
	return micro_erp_next_number( micro_erp_table( 'sales' ), 'sale_no', 'SALE-' );
}

function micro_erp_redirect_notice( $message, $type = 'success' ) {
	set_transient( 'micro_erp_admin_notice', array( 'message' => $message, 'type' => $type ), 30 );
}

function micro_erp_print_admin_notice() {
	$notice = get_transient( 'micro_erp_admin_notice' );
	if ( $notice ) {
		delete_transient( 'micro_erp_admin_notice' );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $notice['type'] ),
			esc_html( $notice['message'] )
		);
	}
}

function micro_erp_verify_nonce( $action, $arg = '_wpnonce' ) {
	if ( ! isset( $_POST[ $arg ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $arg ] ) ), $action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'micro-erp' ) );
	}
}

function micro_erp_get_accounts( $type = '' ) {
	global $wpdb;
	if ( $type ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE type = %s ORDER BY code ASC", $type ) );
	}
	return $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " ORDER BY code ASC" );
}

function micro_erp_account_balance( $account_id ) {
	global $wpdb;
	$account_id = (int) $account_id;
	$table      = micro_erp_table( 'journal_lines' );
	$debit      = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(debit),0) FROM {$table} WHERE account_id = %d", $account_id ) );
	$credit     = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit),0) FROM {$table} WHERE account_id = %d", $account_id ) );

	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE id = %d", $account_id ) );
	if ( ! $account ) {
		return 0;
	}

	$normal_debit = in_array( $account->type, array( 'asset', 'expense' ), true );
	if ( $normal_debit ) {
		return $debit - $credit;
	}
	return $credit - $debit;
}

function micro_erp_total_income() {
	global $wpdb;
	$t = micro_erp_table( 'journal_lines' );
	return (float) $wpdb->get_var(
		"SELECT SUM(credit) FROM {$t} l INNER JOIN " . micro_erp_table( 'accounts' ) . " a ON a.id = l.account_id WHERE a.type = 'income'"
	);
}

function micro_erp_total_expense() {
	global $wpdb;
	$t = micro_erp_table( 'journal_lines' );
	return (float) $wpdb->get_var(
		"SELECT SUM(debit) FROM {$t} l INNER JOIN " . micro_erp_table( 'accounts' ) . " a ON a.id = l.account_id WHERE a.type = 'expense'"
	);
}

function micro_erp_contact_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM " . micro_erp_table( 'contacts' ) . " WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function micro_erp_employee_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM " . micro_erp_table( 'employees' ) . " WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function micro_erp_department_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM " . micro_erp_table( 'departments' ) . " WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function micro_erp_leave_type_name( $id ) {
	global $wpdb;
	$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM " . micro_erp_table( 'leave_types' ) . " WHERE id = %d", $id ) );
	return $name ? $name : '—';
}

function micro_erp_badge( $value, $map = array() ) {
	$class = isset( $map[ $value ] ) ? $map[ $value ] : 'status-neutral';
	return '<span class="status-badge ' . esc_attr( $class ) . '">' . esc_html( ucfirst( $value ) ) . '</span>';
}

function micro_erp_status_badge( $status ) {
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
	return micro_erp_badge( $status, $map );
}

function micro_erp_contact_type_badge( $type ) {
	$map = array(
		'customer' => 'status-info',
		'vendor'   => 'status-neutral',
		'supplier' => 'status-warning',
	);
	return micro_erp_badge( $type, $map );
}

function micro_erp_create_journal_entry( $date, $description, $lines, $reference_type = null, $reference_id = null ) {
	global $wpdb;

	$fiscal_year_id = micro_erp_get_fiscal_year_id();

	$wpdb->insert(
		micro_erp_table( 'journal_entries' ),
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

	foreach ( $lines as $line ) {
		$wpdb->insert(
			micro_erp_table( 'journal_lines' ),
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

function micro_erp_create_sale_journal( $sale ) {
	$ar_account     = micro_erp_default_account( 'asset', '1003' );
	$income_account = micro_erp_default_account( 'income', '4001' );

	return micro_erp_create_journal_entry(
		$sale->sale_date,
		sprintf( 'Sale - %s (%s)', $sale->sale_no, micro_erp_contact_name( $sale->contact_id ) ),
		array(
			array( 'account_id' => $ar_account, 'debit' => (float) $sale->total, 'credit' => 0 ),
			array( 'account_id' => $income_account, 'debit' => 0, 'credit' => (float) $sale->total ),
		),
		'sale',
		(int) $sale->id
	);
}

function micro_erp_default_account( $type, $fallback_code ) {
	global $wpdb;
	$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE type = %s AND code = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type, $fallback_code ) );
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE type = %s AND is_active = 1 ORDER BY id ASC LIMIT 1", $type ) );
	}
	if ( ! $account ) {
		$account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE code = %s LIMIT 1", $fallback_code ) );
	}
	return $account ? (int) $account->id : 0;
}

function micro_erp_sum( $rows, $key ) {
	$total = 0;
	foreach ( (array) $rows as $row ) {
		if ( isset( $row->{$key} ) ) {
			$total += (float) $row->{$key};
		}
	}
	return $total;
}

function micro_erp_get_account_balances_by_type() {
	global $wpdb;
	$accounts = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " ORDER BY id ASC" );
	$types    = array( 'asset' => 0, 'liability' => 0, 'equity' => 0, 'income' => 0, 'expense' => 0 );

	foreach ( $accounts as $account ) {
		if ( ! isset( $types[ $account->type ] ) ) {
			$types[ $account->type ] = 0;
		}
		$types[ $account->type ] += micro_erp_account_balance( $account->id );
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
function micro_erp_render_search_bar( $page_slug, $label, $placeholder, $hidden = array(), $current = '', $inline = false ) {
	$form_class = $inline ? 'inline-search' : 'search-section mb-3';
	?>
	<form method="get" action="" class="<?php echo esc_attr( $form_class ); ?>">
		<input type="hidden" name="page" value="micro-erp/<?php echo esc_attr( $page_slug ); ?>">
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
			<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
			<?php if ( $current ) : ?>
				<a href="<?php echo esc_url( micro_erp_admin_url( $page_slug, $hidden ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Clear', 'micro-erp' ); ?></a>
			<?php endif; ?>
		<?php else : ?>
			<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
				<label for="s-<?php echo esc_attr( $page_slug ); ?>" class="form-label mb-0"><?php echo esc_html( $label ); ?></label>
				<input type="text" name="s" id="s-<?php echo esc_attr( $page_slug ); ?>" class="form-control form-control-sm search-field" placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $current ); ?>">
				<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
				<?php if ( $current ) : ?>
					<a href="<?php echo esc_url( micro_erp_admin_url( $page_slug, $hidden ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Clear', 'micro-erp' ); ?></a>
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
function micro_erp_render_pagination( $page_slug, $total_items, $per_page = 20 ) {
	$total_items = (int) $total_items;
	$per_page    = max( 1, (int) $per_page );

	if ( $total_items <= $per_page ) {
		return;
	}

	$total_pages = (int) ceil( $total_items / $per_page );
	$paged       = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1;
	$paged       = min( max( 1, $paged ), $total_pages );

	// Preserve known filter args across page links.
	$filter_keys = array( 's', 'type', 'status', 'department_id', 'month', 'date' );
	$args        = array();
	foreach ( $filter_keys as $key ) {
		if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) {
			$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
		}
	}

	$page_url = function ( $n ) use ( $page_slug, $args ) {
		return micro_erp_admin_url( $page_slug, array_merge( $args, array( 'paged' => $n > 1 ? $n : '' ) ) );
	};

	$out  = '<div class="tablenav-pages">';
	$out .= '<span class="displaying-num">' . esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'micro-erp' ), number_format_i18n( $total_items ) ) ) . '</span>';
	$out .= '<div class="pagination-links">';

	// First / Prev.
	if ( $paged > 1 ) {
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( 1 ) ) . '" aria-label="' . esc_attr__( 'First page', 'micro-erp' ) . '">«</a>';
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $paged - 1 ) ) . '" aria-label="' . esc_attr__( 'Previous page', 'micro-erp' ) . '">‹</a>';
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
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $paged + 1 ) ) . '" aria-label="' . esc_attr__( 'Next page', 'micro-erp' ) . '">›</a>';
		$out .= '<a class="btn btn-dark" href="' . esc_url( $page_url( $total_pages ) ) . '" aria-label="' . esc_attr__( 'Last page', 'micro-erp' ) . '">»</a>';
	} else {
		$out .= '<span class="btn btn-dark btn-disabled">›</span><span class="btn btn-dark btn-disabled">»</span>';
	}

	$out .= '</div></div>';

	echo wp_kses_post( $out ); // phpcs:ignore WordPress.Security.EscapeOutput -- all values escaped above.
}
