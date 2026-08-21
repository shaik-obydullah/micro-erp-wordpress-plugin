<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$sales_tbl = micro_erp_table( 'sales' );
$rows      = $wpdb->get_results(
	"SELECT DATE_FORMAT(sale_date, '%Y-%m') AS ym, DATE_FORMAT(sale_date, '%Y-%m') AS month_label,
	        COUNT(*) AS invoice_count, SUM(total) AS total_sales, SUM(amount_paid) AS total_paid
	 FROM {$sales_tbl} GROUP BY ym ORDER BY ym DESC LIMIT 12"
);

$fiscal = micro_erp_get_active_fiscal_year();
micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Sales Reports', 'micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<div class="row mt-3">
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-primary">
				<h4><?php echo esc_html( micro_erp_format_money( micro_erp_sum( $rows, 'total_sales' ) ) ); ?></h4>
				<p><?php esc_html_e( 'Total Sales (12 months)', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-info">
				<h4><?php echo esc_html( micro_erp_sum( $rows, 'invoice_count' ) ); ?></h4>
				<p><?php esc_html_e( 'Invoices (12 months)', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-success">
				<h4><?php echo esc_html( micro_erp_format_money( micro_erp_sum( $rows, 'total_paid' ) ) ); ?></h4>
				<p><?php esc_html_e( 'Collected', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-warning">
				<h4><?php echo $fiscal ? esc_html( $fiscal->name ) : '—'; ?></h4>
				<p><?php esc_html_e( 'Active Fiscal Year', 'micro-erp' ); ?></p>
			</div>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border mb-4">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Monthly Comparison', 'micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="160"><?php esc_html_e( 'Month', 'micro-erp' ); ?></th>
								<th width="140" class="text-right"><?php esc_html_e( 'Invoice Count', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Total Sales', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Paid', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Outstanding', 'micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No sales recorded yet.', 'micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) :
								$month_name = date_i18n( 'F Y', strtotime( $row->month_label . '-01' ) );
								$outstanding = (float) $row->total_sales - (float) $row->total_paid;
								?>
								<tr>
									<td><strong><?php echo esc_html( $month_name ); ?></strong></td>
									<td class="text-right"><?php echo (int) $row->invoice_count; ?></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->total_sales ) ); ?></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->total_paid ) ); ?></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $outstanding ) ); ?></td>
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
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Account Balances (Top Level)', 'micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Account', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Balance', 'micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php foreach ( micro_erp_get_account_balances_by_type() as $type => $balance ) : ?>
								<tr>
									<td><strong><?php echo esc_html( ucfirst( $type ) ); ?></strong></td>
									<td class="text-right"><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
