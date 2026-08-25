<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$income    = li_mi_erp_total_income();
$expense   = li_mi_erp_total_expense();
$receivable = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total - amount_paid),0) FROM {$wpdb->prefix}micro_erp_sales WHERE payment_status != %s", 'paid' ) );
$payable   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit - debit),0) FROM {$wpdb->prefix}micro_erp_journal_lines WHERE account_id = (SELECT id FROM {$wpdb->prefix}micro_erp_accounts WHERE code = %s LIMIT 1)", '2001' ) );

$recent_sales = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_sales ORDER BY id DESC LIMIT %d", 5 ) );
$pending_quo  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_quotations WHERE status IN (%s,%s)", 'draft', 'sent' ) );
$pending_leave= (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_leave_requests WHERE status = %s", 'pending' ) );
$unpaid_sales = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_sales WHERE payment_status != %s", 'paid' ) );
$recent_emp   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_employees ORDER BY id DESC LIMIT %d", 5 ) );
$recent_jrnl  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_journal_entries ORDER BY id DESC LIMIT %d", 5 ) );
$fy           = li_mi_erp_get_active_fiscal_year();

li_mi_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Dashboard', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $fy ) : ?>
		<p class="micro-erp-fy-label"><?php esc_html_e( 'Active Fiscal Year:', 'lime-micro-erp' ); ?> <strong><?php echo esc_html( $fy->name ); ?></strong></p>
	<?php endif; ?>

	<!-- Main Metrics Grid -->
	<div class="row mb-4">
		<!-- Total Income -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Income', 'lime-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-success mb-0">
					<?php echo esc_html( li_mi_erp_format_money( $income ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'All time revenue', 'lime-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Expense -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Expense', 'lime-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-danger mb-0">
					<?php echo esc_html( li_mi_erp_format_money( $expense ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'All time expenses', 'lime-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Receivable -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Receivable', 'lime-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-info mb-0">
					<?php echo esc_html( li_mi_erp_format_money( $receivable ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'Money owed to you', 'lime-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Payable -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-warning">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Payable', 'lime-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-warning mb-0">
					<?php echo esc_html( li_mi_erp_format_money( $payable ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'Money you owe', 'lime-micro-erp' ); ?></small>
			</div>
		</div>
	</div>

	<!-- Recent Sales -->
	<div class="row mt-4">
		<div class="col-lg-12 col-md-12 col-sm-12 col-12">
			<div class="bg-light p-4 rounded shadow-sm">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Recent Sales', 'lime-micro-erp' ); ?>
					</h3>
				</div>

				<?php if ( ! empty( $recent_sales ) ) : ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Sale #', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?></th>
									<th class="text-right"><?php esc_html_e( 'Amount', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_sales as $sale ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $sale->sale_no ); ?></strong></td>
										<td><?php echo esc_html( li_mi_erp_contact_name( $sale->contact_id ) ); ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( li_mi_erp_format_money( $sale->total ) ); ?></td>
										<td><?php echo li_mi_erp_status_badge( $sale->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td><?php echo esc_html( $sale->sale_date ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="text-center py-5">
						<p class="mb-0 text-muted">
							<?php esc_html_e( 'No sales yet.', 'lime-micro-erp' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Pending Actions & Recent Employees -->
	<div class="row mt-4">
		<div class="col-lg-6 col-md-12 mb-3">
			<div class="bg-light p-4 rounded shadow-sm h-100">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Pending Actions', 'lime-micro-erp' ); ?>
					</h3>
				</div>
				<table class="table table-hover mb-0">
					<tbody>
						<tr>
							<td><span class="status-badge status-warning"><?php echo (int) $pending_quo; ?></span> <?php esc_html_e( 'Pending Quotations', 'lime-micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="status-badge status-warning"><?php echo (int) $pending_leave; ?></span> <?php esc_html_e( 'Leave Requests', 'lime-micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="status-badge status-inactive"><?php echo (int) $unpaid_sales; ?></span> <?php esc_html_e( 'Unpaid Invoices', 'lime-micro-erp' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="col-lg-6 col-md-12 mb-3">
			<div class="bg-light p-4 rounded shadow-sm h-100">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Recent Employees', 'lime-micro-erp' ); ?>
					</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'ID', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Department', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent_emp ) ) : ?>
								<tr><td colspan="4"><?php esc_html_e( 'No employees yet.', 'lime-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $recent_emp as $emp ) : ?>
								<tr>
									<td><?php echo esc_html( $emp->employee_id ); ?></td>
									<td><?php echo esc_html( $emp->name ); ?></td>
									<td><?php echo esc_html( li_mi_erp_department_name( $emp->department_id ) ); ?></td>
									<td><?php echo li_mi_erp_status_badge( $emp->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Journal Entries -->
	<div class="row mt-4">
		<div class="col-lg-12 col-md-12 col-sm-12 col-12">
			<div class="bg-light p-4 rounded shadow-sm">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Recent Journal Entries', 'lime-micro-erp' ); ?>
					</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr class="bg-primary text-white">
								<th>#</th>
								<th><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent_jrnl ) ) : ?>
								<tr><td colspan="3"><?php esc_html_e( 'No journal entries yet.', 'lime-micro-erp' ); ?></td></tr>
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
</div>
