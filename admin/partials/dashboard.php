<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$income    = micro_erp_total_income();
$expense   = micro_erp_total_expense();
$receivable = (float) $wpdb->get_var( "SELECT COALESCE(SUM(total - amount_paid),0) FROM " . micro_erp_table( 'sales' ) . " WHERE payment_status != 'paid'" );
$payable   = (float) $wpdb->get_var( "SELECT COALESCE(SUM(credit - debit),0) FROM " . micro_erp_table( 'journal_lines' ) . " WHERE account_id = (SELECT id FROM " . micro_erp_table( 'accounts' ) . " WHERE code = '2001' LIMIT 1)" );

$recent_sales = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'sales' ) . " ORDER BY id DESC LIMIT 5" );
$pending_quo  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . micro_erp_table( 'quotations' ) . " WHERE status IN ('draft','sent')" );
$pending_leave= (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . micro_erp_table( 'leave_requests' ) . " WHERE status = 'pending'" );
$unpaid_sales = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . micro_erp_table( 'sales' ) . " WHERE payment_status != 'paid'" );
$recent_emp   = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'employees' ) . " ORDER BY id DESC LIMIT 5" );
$recent_jrnl  = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'journal_entries' ) . " ORDER BY id DESC LIMIT 5" );
$fy           = micro_erp_get_active_fiscal_year();

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Dashboard', 'micro-erp' ); ?></h1>

	<?php if ( $fy ) : ?>
		<p class="micro-erp-fy-label"><?php esc_html_e( 'Active Fiscal Year:', 'micro-erp' ); ?> <strong><?php echo esc_html( $fy->name ); ?></strong></p>
	<?php endif; ?>

	<div class="kpi-grid">
		<div class="kpi-card income">
			<div class="kpi-label"><?php esc_html_e( 'Total Income', 'micro-erp' ); ?></div>
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( $income ) ); ?></div>
		</div>
		<div class="kpi-card expense">
			<div class="kpi-label"><?php esc_html_e( 'Total Expense', 'micro-erp' ); ?></div>
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( $expense ) ); ?></div>
		</div>
		<div class="kpi-card receivable">
			<div class="kpi-label"><?php esc_html_e( 'Total Receivable', 'micro-erp' ); ?></div>
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( $receivable ) ); ?></div>
		</div>
		<div class="kpi-card payable">
			<div class="kpi-label"><?php esc_html_e( 'Total Payable', 'micro-erp' ); ?></div>
			<div class="kpi-value"><?php echo esc_html( micro_erp_format_money( $payable ) ); ?></div>
		</div>
	</div>

	<div class="dashboard-grid">
		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Recent Sales', 'micro-erp' ); ?></div>
			<div class="card-body">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Sale #', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Amount', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_sales ) ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No sales yet.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $recent_sales as $sale ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $sale->sale_no ); ?></strong></td>
								<td><?php echo esc_html( micro_erp_contact_name( $sale->contact_id ) ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $sale->total ) ); ?></td>
								<td><?php echo micro_erp_status_badge( $sale->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td><?php echo esc_html( $sale->sale_date ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Pending Actions', 'micro-erp' ); ?></div>
			<div class="card-body">
				<table>
					<tbody>
						<tr>
							<td><span class="badge badge-warning"><?php echo (int) $pending_quo; ?></span> <?php esc_html_e( 'Pending Quotations', 'micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="badge badge-warning"><?php echo (int) $pending_leave; ?></span> <?php esc_html_e( 'Leave Requests', 'micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="badge badge-inactive"><?php echo (int) $unpaid_sales; ?></span> <?php esc_html_e( 'Unpaid Invoices', 'micro-erp' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Recent Employees', 'micro-erp' ); ?></div>
			<div class="card-body">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_emp ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No employees yet.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $recent_emp as $emp ) : ?>
							<tr>
								<td><?php echo esc_html( $emp->employee_id ); ?></td>
								<td><?php echo esc_html( $emp->name ); ?></td>
								<td><?php echo esc_html( micro_erp_department_name( $emp->department_id ) ); ?></td>
								<td><?php echo micro_erp_status_badge( $emp->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Recent Journal Entries', 'micro-erp' ); ?></div>
			<div class="card-body">
				<table>
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_jrnl ) ) : ?>
							<tr><td colspan="3"><?php esc_html_e( 'No journal entries yet.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $recent_jrnl as $je ) : ?>
							<tr>
								<td>JE-<?php echo (int) $je->id; ?></td>
								<td><?php echo esc_html( $je->description ); ?></td>
								<td><?php echo esc_html( $je->entry_date ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
