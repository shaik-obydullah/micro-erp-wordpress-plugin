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
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Sales Reports', 'micro-erp' ); ?></h1>

	<div class="kpi-grid">
		<div class="card kpi">
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( micro_erp_sum( $rows, 'total_sales' ) ) ); ?></div>
			<div class="kpi-label"><?php esc_html_e( 'Total Sales (12 months)', 'micro-erp' ); ?></div>
		</div>
		<div class="card kpi">
			<div class="kpi-value"><?php echo esc_html( micro_erp_sum( $rows, 'invoice_count' ) ); ?></div>
			<div class="kpi-label"><?php esc_html_e( 'Invoices (12 months)', 'micro-erp' ); ?></div>
		</div>
		<div class="card kpi">
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( micro_erp_sum( $rows, 'total_paid' ) ) ); ?></div>
			<div class="kpi-label"><?php esc_html_e( 'Collected', 'micro-erp' ); ?></div>
		</div>
		<div class="card kpi">
			<div class="kpi-value"><?php echo $fiscal ? esc_html( $fiscal->name ) : '—'; ?></div>
			<div class="kpi-label"><?php esc_html_e( 'Active Fiscal Year', 'micro-erp' ); ?></div>
		</div>
	</div>

	<div class="card">
		<div class="card-header"><?php esc_html_e( 'Monthly Comparison', 'micro-erp' ); ?></div>
		<div class="card-body" style="padding: 0;">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Month', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Invoice Count', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Total Sales', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Amount Paid', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Outstanding', 'micro-erp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No sales recorded yet.', 'micro-erp' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) :
						$month_name = date_i18n( 'F Y', strtotime( $row->month_label . '-01' ) );
						$outstanding = (float) $row->total_sales - (float) $row->total_paid;
						?>
						<tr>
							<td><?php echo esc_html( $month_name ); ?></td>
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

	<div class="card">
		<div class="card-header"><?php esc_html_e( 'Account Balances (Top Level)', 'micro-erp' ); ?></div>
		<div class="card-body" style="padding: 0;">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Account', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Balance', 'micro-erp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( micro_erp_get_account_balances_by_type() as $type => $balance ) : ?>
						<tr>
							<td><?php echo esc_html( ucfirst( $type ) ); ?></td>
							<td class="text-right"><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
