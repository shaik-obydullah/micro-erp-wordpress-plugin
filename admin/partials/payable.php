<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Payable = amounts owed to vendors, tracked via the Accounts Payable account.
$ap_account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_accounts WHERE code = %s LIMIT %d", '2001', 1 ) );

$rows = array();
$total_items = 0;
$total = 0;
if ( $ap_account ) {
	$search = micro_erp_query_text( 's' );

	$per_page = 20;
	$paged    = max( 1, micro_erp_query_int( 'paged', 1 ) );

	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$total_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s",
				$ap_account->id,
				$like
			)
		);
	} else {
		$total_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0",
				$ap_account->id
			)
		);
	}

	$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	$paged       = min( $paged, $total_pages );
	$offset      = ( $paged - 1 ) * $per_page;

	// Grand total across ALL payable entries (footer row must not change with paging).
	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$total = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(l.credit),0) FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s",
				$ap_account->id,
				$like
			)
		);
	} else {
		$total = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(l.credit),0) FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0",
				$ap_account->id
			)
		);
	}

	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
				FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s
				ORDER BY j.entry_date DESC LIMIT %d OFFSET %d",
				$ap_account->id,
				$like,
				$per_page,
				$offset
			)
		);
	} else {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
				FROM {$wpdb->prefix}micro_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}micro_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0
				ORDER BY j.entry_date DESC LIMIT %d OFFSET %d",
				$ap_account->id,
				$per_page,
				$offset
			)
		);
	}
} else {
	$search = '';
}
micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Payable', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money you owe to vendors and suppliers', 'lime-micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'payable', __( 'Search Payables', 'lime-micro-erp' ), __( 'Search by description...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Outstanding Payables', 'lime-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="110"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
								<th width="90"><?php esc_html_e( 'Reference', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Payable', 'lime-micro-erp' ); ?></th>
								<th width="110" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No payables recorded.', 'lime-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row->entry_date ); ?></td>
									<td>JE-<?php echo (int) $row->entry_id; ?></td>
									<td><?php echo esc_html( $row->description ); ?></td>
									<td class="text-right"><strong style="color:#d63638;"><?php echo esc_html( micro_erp_format_money( $row->payable_amount ) ); ?></strong></td>
									<td class="text-right"><a href="<?php echo esc_url( micro_erp_admin_url( 'journal', array( 'view' => $row->entry_id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a></td>
								</tr>
							<?php endforeach; ?>
							<tr class="total-row">
								<td colspan="3"><strong><?php esc_html_e( 'Total Payable', 'lime-micro-erp' ); ?></strong></td>
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
