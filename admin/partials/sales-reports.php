<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
			DATE_FORMAT(sale_date, %s) AS ym,
			DATE_FORMAT(sale_date, %s) AS month_label,
			COUNT(*) AS invoice_count,
			SUM(total) AS total_sales,
			SUM(amount_paid) AS total_paid
		FROM {$wpdb->prefix}micro_erp_sales
		GROUP BY ym
		ORDER BY ym DESC
		LIMIT %d",
		'%Y-%m',
		'%Y-%m',
		12
	)
);

$fiscal = li_mi_erp_get_active_fiscal_year();
li_mi_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Sales Reports', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php
	$total_sales_all = li_mi_erp_sum( $rows, 'total_sales' );
	$total_paid_all  = li_mi_erp_sum( $rows, 'total_paid' );
	$invoice_count   = (int) li_mi_erp_sum( $rows, 'invoice_count' );
	$pct_collected   = $total_sales_all > 0 ? round( ( $total_paid_all / $total_sales_all ) * 100 ) : 0;

	$report_stats = array(
		array(
			'key'   => 'sales',
			'label' => __( 'Total Sales (12 months)', 'lime-micro-erp' ),
			'value' => li_mi_erp_format_money( $total_sales_all ),
			'sub'   => __( 'Gross invoiced amount', 'lime-micro-erp' ),
			'icon'  => 'chart-area',
			'bar'   => null,
		),
		array(
			'key'   => 'invoices',
			'label' => __( 'Invoices (12 months)', 'lime-micro-erp' ),
			'value' => number_format_i18n( $invoice_count ),
			'sub'   => __( 'Sales orders created', 'lime-micro-erp' ),
			'icon'  => 'analytics',
			'bar'   => null,
		),
		array(
			'key'   => 'collected',
			'label' => __( 'Collected', 'lime-micro-erp' ),
			'value' => li_mi_erp_format_money( $total_paid_all ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total sales collected. */
				__( '%d%% of total sales', 'lime-micro-erp' ),
				$pct_collected
			),
			'icon'  => 'money-alt',
			'bar'   => $pct_collected,
		),
		array(
			'key'   => 'fiscal',
			'label' => __( 'Active Fiscal Year', 'lime-micro-erp' ),
			'value' => $fiscal ? esc_html( $fiscal->name ) : '—',
			'sub'   => $fiscal ? esc_html( $fiscal->start_date . ' — ' . $fiscal->end_date ) : '',
			'icon'  => 'calendar-alt',
			'bar'   => null,
		),
	);
	?>
	<div class="stat-cards">
		<?php foreach ( $report_stats as $stat ) : ?>
			<div class="stat-card stat-card--<?php echo esc_attr( $stat['key'] ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $stat['icon'] ); ?>"></span>
				</div>
				<div class="stat-body">
					<span class="stat-value"><?php echo esc_html( $stat['value'] ); ?></span>
					<span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					<span class="stat-sub"><?php echo esc_html( $stat['sub'] ); ?></span>
					<?php if ( null !== $stat['bar'] ) : ?>
						<div class="stat-bar" role="presentation"><span style="width:<?php echo (int) $stat['bar']; ?>%;"></span></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border mb-4">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Monthly Comparison', 'lime-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="160"><?php esc_html_e( 'Month', 'lime-micro-erp' ); ?></th>
								<th width="140" class="text-right"><?php esc_html_e( 'Invoice Count', 'lime-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Total Sales', 'lime-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Paid', 'lime-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Outstanding', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No sales recorded yet.', 'lime-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) :
								$month_name = date_i18n( 'F Y', strtotime( $row->month_label . '-01' ) );
								$outstanding = (float) $row->total_sales - (float) $row->total_paid;
								?>
								<tr>
									<td><strong><?php echo esc_html( $month_name ); ?></strong></td>
									<td class="text-right"><?php echo (int) $row->invoice_count; ?></td>
									<td class="text-right"><?php echo esc_html( li_mi_erp_format_money( $row->total_sales ) ); ?></td>
									<td class="text-right"><?php echo esc_html( li_mi_erp_format_money( $row->total_paid ) ); ?></td>
									<td class="text-right"><?php echo esc_html( li_mi_erp_format_money( $outstanding ) ); ?></td>
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
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Account Balances (Top Level)', 'lime-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Account', 'lime-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Balance', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php foreach ( li_mi_erp_get_account_balances_by_type() as $type => $balance ) : ?>
								<tr>
									<td><strong><?php echo esc_html( ucfirst( $type ) ); ?></strong></td>
									<td class="text-right"><?php echo esc_html( li_mi_erp_format_money( $balance ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
