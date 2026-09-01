<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- derived value recomputed per request.
	$wpdb->prepare(
		"SELECT
			DATE_FORMAT(sale_date, %s) AS ym,
			DATE_FORMAT(sale_date, %s) AS month_label,
			COUNT(*) AS invoice_count,
			SUM(total) AS total_sales,
			SUM(amount_paid) AS total_paid
		FROM {$wpdb->prefix}oby_mi_erp_sales
		GROUP BY ym
		ORDER BY ym DESC
		LIMIT %d",
		'%Y-%m',
		'%Y-%m',
		12
	)
);

$oby_mi_erp_fiscal = oby_mi_erp_get_active_fiscal_year();
oby_mi_erp_print_admin_notice();
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Sales Reports', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php
	$oby_mi_erp_total_sales_all = oby_mi_erp_sum( $oby_mi_erp_rows, 'total_sales' );
	$oby_mi_erp_total_paid_all  = oby_mi_erp_sum( $oby_mi_erp_rows, 'total_paid' );
	$oby_mi_erp_invoice_count   = (int) oby_mi_erp_sum( $oby_mi_erp_rows, 'invoice_count' );
	$oby_mi_erp_pct_collected   = $oby_mi_erp_total_sales_all > 0 ? round( ( $oby_mi_erp_total_paid_all / $oby_mi_erp_total_sales_all ) * 100 ) : 0;

	$oby_mi_erp_report_stats = array(
		array(
			'key'   => 'sales',
			'label' => __( 'Total Sales (12 months)', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $oby_mi_erp_total_sales_all ),
			'sub'   => __( 'Gross invoiced amount', 'obydullah-micro-erp' ),
			'icon'  => 'chart-area',
			'bar'   => null,
		),
		array(
			'key'   => 'invoices',
			'label' => __( 'Invoices (12 months)', 'obydullah-micro-erp' ),
			'value' => number_format_i18n( $oby_mi_erp_invoice_count ),
			'sub'   => __( 'Sales orders created', 'obydullah-micro-erp' ),
			'icon'  => 'analytics',
			'bar'   => null,
		),
		array(
			'key'   => 'collected',
			'label' => __( 'Collected', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $oby_mi_erp_total_paid_all ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total sales collected. */
				__( '%d%% of total sales', 'obydullah-micro-erp' ),
				$oby_mi_erp_pct_collected
			),
			'icon'  => 'money-alt',
			'bar'   => $oby_mi_erp_pct_collected,
		),
		array(
			'key'   => 'fiscal',
			'label' => __( 'Active Fiscal Year', 'obydullah-micro-erp' ),
			'value' => $oby_mi_erp_fiscal ? esc_html( $oby_mi_erp_fiscal->name ) : '—',
			'sub'   => $oby_mi_erp_fiscal ? esc_html( $oby_mi_erp_fiscal->start_date . ' — ' . $oby_mi_erp_fiscal->end_date ) : '',
			'icon'  => 'calendar-alt',
			'bar'   => null,
		),
	);
	?>
	<div class="stat-cards">
		<?php foreach ( $oby_mi_erp_report_stats as $oby_mi_erp_stat ) : ?>
			<div class="stat-card stat-card--<?php echo esc_attr( $oby_mi_erp_stat['key'] ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $oby_mi_erp_stat['icon'] ); ?>"></span>
				</div>
				<div class="stat-body">
					<span class="stat-value"><?php echo esc_html( $oby_mi_erp_stat['value'] ); ?></span>
					<span class="stat-label"><?php echo esc_html( $oby_mi_erp_stat['label'] ); ?></span>
					<span class="stat-sub"><?php echo esc_html( $oby_mi_erp_stat['sub'] ); ?></span>
					<?php if ( null !== $oby_mi_erp_stat['bar'] ) : ?>
						<div class="stat-bar" role="presentation"><span style="width:<?php echo (int) $oby_mi_erp_stat['bar']; ?>%;"></span></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border mb-4">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Monthly Comparison', 'obydullah-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="160"><?php esc_html_e( 'Month', 'obydullah-micro-erp' ); ?></th>
								<th width="140" class="text-right"><?php esc_html_e( 'Invoice Count', 'obydullah-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Total Sales', 'obydullah-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Paid', 'obydullah-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Outstanding', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No sales recorded yet.', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php
							foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) :
								$oby_mi_erp_month_name  = date_i18n( 'F Y', strtotime( $oby_mi_erp_row->month_label . '-01' ) );
								$oby_mi_erp_outstanding = (float) $oby_mi_erp_row->total_sales - (float) $oby_mi_erp_row->total_paid;
								?>
								<tr>
									<td><strong><?php echo esc_html( $oby_mi_erp_month_name ); ?></strong></td>
									<td class="text-right"><?php echo (int) $oby_mi_erp_row->invoice_count; ?></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_row->total_sales ) ); ?></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_row->total_paid ) ); ?></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_outstanding ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 col-md-12">
			<div class="bg-light p-3 rounded shadow-sm border mb-4">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Account Balances (Top Level)', 'obydullah-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Account', 'obydullah-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Balance', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php foreach ( oby_mi_erp_get_account_balances_by_type() as $oby_mi_erp_type => $oby_mi_erp_balance ) : ?>
								<tr>
									<td><strong><?php echo esc_html( ucfirst( $oby_mi_erp_type ) ); ?></strong></td>
									<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_balance ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
