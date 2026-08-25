<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tx_mode   = ( 'expense' === $tx_mode ) ? 'expense' : 'income';
$tx_page   = 'expense' === $tx_mode ? 'expenses' : 'income'; // Real menu slug (plural for expenses).
$label   = 'expense' === $tx_mode ? __( 'Expenses', 'lime-micro-erp' ) : __( 'Income', 'lime-micro-erp' );
$add_btn = 'expense' === $tx_mode ? __( '+ Add Expense', 'lime-micro-erp' ) : __( '+ Add Income', 'lime-micro-erp' );
$acct_type = 'expense' === $tx_mode ? 'expense' : 'income';

global $wpdb;
$accounts = li_mi_erp_get_accounts( $acct_type );

$per_page = 20;
$paged    = max( 1, li_mi_erp_query_int( 'paged', 1 ) );
$search   = li_mi_erp_query_text( 's' );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT j.id)
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s AND (j.description LIKE %s OR a.name LIKE %s OR a.code LIKE %s)",
			$acct_type,
			$like,
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT j.id)
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s",
			$acct_type
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT j.*, l.debit, l.credit, l.account_id, a.code AS account_code, a.name AS account_name
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s AND (j.description LIKE %s OR a.name LIKE %s OR a.code LIKE %s)
			ORDER BY j.entry_date DESC, j.id DESC LIMIT %d OFFSET %d",
			$acct_type,
			$like,
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT j.*, l.debit, l.credit, l.account_id, a.code AS account_code, a.name AS account_name
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s
			ORDER BY j.entry_date DESC, j.id DESC LIMIT %d OFFSET %d",
			$acct_type,
			$per_page,
			$offset
		)
	);
}

// Grand total across ALL matching entries (footer row must not change with paging).
// Both columns are summed and the mode picks one in PHP — no dynamic column names in SQL.
if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$sums = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(l.debit),0) AS debit_total, COALESCE(SUM(l.credit),0) AS credit_total
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s AND (j.description LIKE %s OR a.name LIKE %s OR a.code LIKE %s)",
			$acct_type,
			$like,
			$like,
			$like
		)
	);
} else {
	$sums = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(l.debit),0) AS debit_total, COALESCE(SUM(l.credit),0) AS credit_total
			FROM {$wpdb->prefix}micro_erp_journal_entries j
			INNER JOIN {$wpdb->prefix}micro_erp_journal_lines l ON l.entry_id = j.id
			INNER JOIN {$wpdb->prefix}micro_erp_accounts a ON a.id = l.account_id
			WHERE a.type = %s",
			$acct_type
		)
	);
}

$grand_total = 'expense' === $tx_mode ? (float) $sums->debit_total : (float) $sums->credit_total;

li_mi_erp_print_admin_notice();

$back_url = li_mi_erp_admin_url( $tx_page );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo esc_html( $label ); ?>
		<?php if ( ! li_mi_erp_query_has( 'new' ) ) : ?>
			<a href="<?php echo esc_url( li_mi_erp_admin_url( $tx_page, array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php echo esc_html( $add_btn ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( li_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 class="mb-3 mt-1"><?php echo esc_html( $add_btn ); ?></h2>
					<form method="post" action="">
						<?php wp_nonce_field( 'li_mi_erp_journal_save' ); ?>
						<input type="hidden" name="li_mi_erp_action" value="save_transaction">
						<input type="hidden" name="tx_mode" value="<?php echo esc_attr( $tx_mode ); ?>">
						<input type="hidden" name="li_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

						<div class="mb-3">
							<label for="entry_date" class="form-label"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="entry_date" id="entry_date" class="form-control" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="description" class="form-label"><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="description" id="description" class="form-control" required>
						</div>

						<div class="mb-3">
							<label for="amount" class="form-label"><?php esc_html_e( 'Amount', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required>
						</div>

						<div class="mb-3">
							<label for="account" class="form-label"><?php esc_html_e( 'Account', 'lime-micro-erp' ); ?></label>
							<select name="account_id" id="account" class="form-control">
								<option value="0"><?php esc_html_e( '— Default —', 'lime-micro-erp' ); ?></option>
								<?php foreach ( $accounts as $acct ) : ?>
									<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save', 'lime-micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

		<div class="row mt-3">
		<div class="col-lg-12">
			<?php li_mi_erp_render_search_bar( $tx_page, __( 'Search Entries', 'lime-micro-erp' ), __( 'Search by description or account...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php echo esc_html( $label ); ?> <?php esc_html_e( 'Entries', 'lime-micro-erp' ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="110"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Account', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Source', 'lime-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Amount', 'lime-micro-erp' ); ?></th>
									<th width="90" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
								$total = 0;
								if ( empty( $rows ) ) :
									?>
									<tr><td colspan="6" class="text-center p-4"><?php esc_html_e( 'No entries yet.', 'lime-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) :
									$amount = 'expense' === $tx_mode ? (float) $row->debit : (float) $row->credit;
									$total += $amount;
									?>
									<tr>
										<td><?php echo esc_html( $row->entry_date ); ?></td>
										<td><?php echo esc_html( $row->description ); ?></td>
										<td><?php echo esc_html( $row->account_code . ' - ' . $row->account_name ); ?></td>
										<td><?php echo esc_html( $row->reference_type ? strtoupper( $row->reference_type ) : '—' ); ?></td>
										<td class="text-right"><strong style="color: <?php echo 'expense' === $tx_mode ? '#d63638' : '#00a32a'; ?>;"><?php echo esc_html( li_mi_erp_format_money( $amount ) ); ?></strong></td>
										<td class="text-right"><a href="<?php echo esc_url( li_mi_erp_admin_url( 'journal', array( 'view' => $row->id, 'from' => $tx_mode ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a></td>
									</tr>
								<?php endforeach; ?>
								<tr class="total-row">
									<td colspan="4"><strong><?php esc_html_e( 'Total', 'lime-micro-erp' ); ?></strong></td>
									<td class="text-right"><strong><?php echo esc_html( li_mi_erp_format_money( $grand_total ) ); ?></strong></td>
									<td></td>
								</tr>
							</tbody>
						</table>
					</div>

					<?php li_mi_erp_render_pagination( $tx_page, $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
