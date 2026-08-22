<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Payable = amounts owed to vendors, tracked via the Accounts Payable account.
$ap_account = $wpdb->get_row( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE code = '2001' LIMIT 1" );

$rows = array();
$total_items = 0;
$total = 0;
if ( $ap_account ) {
	$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$search_where = '';
	if ( $search ) {
		$like         = '%' . $wpdb->esc_like( $search ) . '%';
		$search_where = $wpdb->prepare( ' AND j.description LIKE %s', $like ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	$per_page    = 20;
	$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM " . micro_erp_table( 'journal_lines' ) . " l
			INNER JOIN " . micro_erp_table( 'journal_entries' ) . " j ON j.id = l.entry_id
			WHERE l.account_id = %d AND l.credit > 0{$search_where}",
			$ap_account->id
		)
	);
	$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	$paged       = min( $paged, $total_pages );
	$offset      = ( $paged - 1 ) * $per_page;

	// Grand total across ALL payable entries (footer row must not change with paging).
	$total = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(l.credit),0) FROM " . micro_erp_table( 'journal_lines' ) . " l
			INNER JOIN " . micro_erp_table( 'journal_entries' ) . " j ON j.id = l.entry_id
			WHERE l.account_id = %d AND l.credit > 0{$search_where}",
			$ap_account->id
		)
	);

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
			FROM " . micro_erp_table( 'journal_lines' ) . " l
			INNER JOIN " . micro_erp_table( 'journal_entries' ) . " j ON j.id = l.entry_id
			WHERE l.account_id = %d AND l.credit > 0{$search_where}
			ORDER BY j.entry_date DESC LIMIT {$per_page} OFFSET {$offset}",
			$ap_account->id
		)
	);
} else {
	$search = '';
}
micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Payable', 'micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money you owe to vendors and suppliers', 'micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'payable', __( 'Search Payables', 'micro-erp' ), __( 'Search by description...', 'micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Outstanding Payables', 'micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="110"><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
								<th width="90"><?php esc_html_e( 'Reference', 'micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Payable', 'micro-erp' ); ?></th>
								<th width="110" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No payables recorded.', 'micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row->entry_date ); ?></td>
									<td>JE-<?php echo (int) $row->entry_id; ?></td>
									<td><?php echo esc_html( $row->description ); ?></td>
									<td class="text-right"><strong style="color:#d63638;"><?php echo esc_html( micro_erp_format_money( $row->payable_amount ) ); ?></strong></td>
									<td class="text-right"><a href="<?php echo esc_url( micro_erp_admin_url( 'journal', array( 'view' => $row->entry_id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a></td>
								</tr>
							<?php endforeach; ?>
							<tr class="total-row">
								<td colspan="3"><strong><?php esc_html_e( 'Total Payable', 'micro-erp' ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total ) ); ?></strong></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php micro_erp_render_pagination( 'payable', $total_items, $per_page ); ?>

			</div>
		</div>
	</div>
</div>
