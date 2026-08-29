<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)",
			'paid',
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s",
			'paid'
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)
			ORDER BY s.sale_date ASC LIMIT %d OFFSET %d",
			'paid',
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s
			ORDER BY s.sale_date ASC LIMIT %d OFFSET %d",
			'paid',
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

// Grand totals across ALL unpaid invoices matching the filter (footer row must not change with paging).
if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(s.total),0) AS original, COALESCE(SUM(s.amount_paid),0) AS paid, COALESCE(SUM(s.total - s.amount_paid),0) AS balance
			FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s AND (s.sale_no LIKE %s OR c.name LIKE %s)",
			'paid',
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(s.total),0) AS original, COALESCE(SUM(s.amount_paid),0) AS paid, COALESCE(SUM(s.total - s.amount_paid),0) AS balance
			FROM {$wpdb->prefix}oby_mi_erp_sales s
			INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id
			WHERE s.payment_status != %s",
			'paid'
		)
	);
}
$oby_mi_erp_total_original = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->original : 0 );
$oby_mi_erp_total_paid     = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->paid : 0 );
$oby_mi_erp_total_balance  = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->balance : 0 );

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'receivable' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Receivable', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money owed to you by customers', 'obydullah-micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'receivable', __( 'Search Receivables', 'obydullah-micro-erp' ), __( 'Search by customer or invoice #...', 'obydullah-micro-erp' ), array(), $oby_mi_erp_search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Unpaid Invoices', 'obydullah-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Customer', 'obydullah-micro-erp' ); ?></th>
								<th width="120"><?php esc_html_e( 'Invoice/Sale #', 'obydullah-micro-erp' ); ?></th>
								<th width="110"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Original Amount', 'obydullah-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Paid', 'obydullah-micro-erp' ); ?></th>
								<th width="130" class="text-right"><?php esc_html_e( 'Balance', 'obydullah-micro-erp' ); ?></th>
								<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
								<th width="160" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
								<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'Nothing owed to you. Nice!', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) :
								$oby_mi_erp_balance = (float) $oby_mi_erp_row->total - (float) $oby_mi_erp_row->amount_paid;
								$oby_mi_erp_overdue = strtotime( $oby_mi_erp_row->sale_date . ' +30 days' ) < time();
								?>
								<tr>
									<td><strong><?php echo esc_html( $oby_mi_erp_row->customer ); ?></strong></td>
									<td><?php echo esc_html( $oby_mi_erp_row->sale_no ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_row->sale_date ); ?></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_row->total ) ); ?></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_row->amount_paid ) ); ?></td>
									<td class="text-right"><strong<?php echo $oby_mi_erp_overdue ? ' style="color:#d63638;"' : ''; ?>><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_balance ) ); ?></strong></td>
									<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_row->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
									<td class="text-right">
										<div class="pos-row-actions">
											<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array( 'edit' => $oby_mi_erp_row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a>
											<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array( 'pay' => $oby_mi_erp_row->id ) ) ); ?>" class="pos-action pay"><?php esc_html_e( 'Record Payment', 'obydullah-micro-erp' ); ?></a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
							<tr class="total-row">
								<td colspan="3"><strong><?php esc_html_e( 'Total Receivable', 'obydullah-micro-erp' ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_total_original ) ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_total_paid ) ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_total_balance ) ); ?></strong></td>
								<td></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php oby_mi_erp_render_pagination( 'receivable', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

			</div>
		</div>
	</div>
</div>
