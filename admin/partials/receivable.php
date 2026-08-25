<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$search = micro_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, micro_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)",
			'paid',
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s",
			'paid'
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
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)
			ORDER BY s.sale_date ASC LIMIT %d OFFSET %d",
			'paid',
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s
			ORDER BY s.sale_date ASC LIMIT %d OFFSET %d",
			'paid',
			$per_page,
			$offset
		)
	);
}

// Grand totals across ALL unpaid invoices matching the filter (footer row must not change with paging).
if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(s.total),0) AS original, COALESCE(SUM(s.amount_paid),0) AS paid, COALESCE(SUM(s.total - s.amount_paid),0) AS balance
			FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)",
			'paid',
			$like,
			$like
		)
	);
} else {
	$totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(s.total),0) AS original, COALESCE(SUM(s.amount_paid),0) AS paid, COALESCE(SUM(s.total - s.amount_paid),0) AS balance
			FROM {$wpdb->prefix}micro_erp_sales s
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s",
			'paid'
		)
	);
}
$total_original = (float) ( $totals ? $totals->original : 0 );
$total_paid     = (float) ( $totals ? $totals->paid : 0 );
$total_balance  = (float) ( $totals ? $totals->balance : 0 );

micro_erp_print_admin_notice();

$back_url = micro_erp_admin_url( 'receivable' );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Receivable', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money owed to you by customers', 'lime-micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'receivable', __( 'Search Receivables', 'lime-micro-erp' ), __( 'Search by customer or invoice #...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Unpaid Invoices', 'lime-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?></th>
								<th width="120"><?php esc_html_e( 'Invoice/Sale #', 'lime-micro-erp' ); ?></th>
								<th width="110"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Original Amount', 'lime-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Paid', 'lime-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Balance', 'lime-micro-erp' ); ?></th>
								<th width="100"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
								<th width="160" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'Nothing owed to you. Nice!', 'lime-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) :
								$balance = (float) $row->total - (float) $row->amount_paid;
								$overdue = strtotime( $row->sale_date . ' +30 days' ) < time();
								?>
								<tr>
									<td><strong><?php echo esc_html( $row->customer ); ?></strong></td>
									<td><?php echo esc_html( $row->sale_no ); ?></td>
									<td><?php echo esc_html( $row->sale_date ); ?></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->total ) ); ?></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->amount_paid ) ); ?></td>
									<td class="text-right"><strong<?php echo $overdue ? ' style="color:#d63638;"' : ''; ?>><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></strong></td>
									<td><?php echo micro_erp_status_badge( $row->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
									<td class="text-right">
										<div class="pos-row-actions">
											<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a>
											<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array( 'pay' => $row->id ) ) ); ?>" class="pos-action pay"><?php esc_html_e( 'Record Payment', 'lime-micro-erp' ); ?></a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
							<tr class="total-row">
								<td colspan="3"><strong><?php esc_html_e( 'Total Receivable', 'lime-micro-erp' ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_original ) ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_paid ) ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_balance ) ); ?></strong></td>
								<td></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php micro_erp_render_pagination( 'receivable', $total_items, $per_page ); ?>

			</div>
		</div>
	</div>
</div>
