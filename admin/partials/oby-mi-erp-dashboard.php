<?php
/**
 * Renders the plugin's main Dashboard overview screen.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_income     = oby_mi_erp_total_income();
$oby_mi_erp_expense    = oby_mi_erp_total_expense();
$oby_mi_erp_receivable = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total - amount_paid),0) FROM {$wpdb->prefix}oby_mi_erp_sales WHERE payment_status != %s", 'paid' ) );
$oby_mi_erp_payable    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit - debit),0) FROM {$wpdb->prefix}oby_mi_erp_journal_lines WHERE account_id = (SELECT id FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code = %s LIMIT 1)", '2001' ) );

$oby_mi_erp_recent_sales  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales ORDER BY id DESC LIMIT %d", 5 ) );
$oby_mi_erp_pending_quo   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_quotations WHERE status IN (%s,%s)", 'draft', 'sent' ) );
$oby_mi_erp_pending_leave = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_leave_requests WHERE status = %s", 'pending' ) );
$oby_mi_erp_unpaid_sales  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales WHERE payment_status != %s", 'paid' ) );
$oby_mi_erp_recent_emp    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees ORDER BY id DESC LIMIT %d", 5 ) );
$oby_mi_erp_recent_jrnl   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_journal_entries ORDER BY id DESC LIMIT %d", 5 ) );
$oby_mi_erp_fy            = oby_mi_erp_get_active_fiscal_year();

oby_mi_erp_print_admin_notice();
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Dashboard', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_fy ) : ?>
		<p class="oby-mi-erp-fy-label"><?php esc_html_e( 'Active Fiscal Year:', 'obydullah-micro-erp' ); ?> <strong><?php echo esc_html( $oby_mi_erp_fy->name ); ?></strong></p>
	<?php endif; ?>

	<!-- Main Metrics Grid -->
	<div class="row mb-4">
		<!-- Total Income -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Income', 'obydullah-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-success mb-0">
					<?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_income ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'All time revenue', 'obydullah-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Expense -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Expense', 'obydullah-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-danger mb-0">
					<?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_expense ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'All time expenses', 'obydullah-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Receivable -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Receivable', 'obydullah-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-info mb-0">
					<?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_receivable ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'Money owed to you', 'obydullah-micro-erp' ); ?></small>
			</div>
		</div>

		<!-- Total Payable -->
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-warning">
				<h3 class="fs-6 fw-normal text-muted mb-2">
					<?php esc_html_e( 'Total Payable', 'obydullah-micro-erp' ); ?>
				</h3>
				<p class="summary-number text-warning mb-0">
					<?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_payable ) ); ?>
				</p>
				<small class="text-muted"><?php esc_html_e( 'Money you owe', 'obydullah-micro-erp' ); ?></small>
			</div>
		</div>
	</div>

	<!-- Recent Sales -->
	<div class="row mt-4">
		<div class="col-lg-12 col-md-12 col-sm-12 col-12">
			<div class="bg-light p-4 rounded shadow-sm">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Recent Sales', 'obydullah-micro-erp' ); ?>
					</h3>
				</div>

				<?php if ( ! empty( $oby_mi_erp_recent_sales ) ) : ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Sale #', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'obydullah-micro-erp' ); ?></th>
									<th class="text-right"><?php esc_html_e( 'Amount', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $oby_mi_erp_recent_sales as $oby_mi_erp_sale ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $oby_mi_erp_sale->sale_no ); ?></strong></td>
										<td><?php echo esc_html( oby_mi_erp_contact_name( $oby_mi_erp_sale->contact_id ) ); ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_sale->total ) ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_sale->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td><?php echo esc_html( $oby_mi_erp_sale->sale_date ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="text-center py-5">
						<p class="mb-0 text-muted">
							<?php esc_html_e( 'No sales yet.', 'obydullah-micro-erp' ); ?>
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
						<?php esc_html_e( 'Pending Actions', 'obydullah-micro-erp' ); ?>
					</h3>
				</div>
				<table class="table table-hover mb-0">
					<tbody>
						<tr>
							<td><span class="status-badge status-warning"><?php echo (int) $oby_mi_erp_pending_quo; ?></span> <?php esc_html_e( 'Pending Quotations', 'obydullah-micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="status-badge status-warning"><?php echo (int) $oby_mi_erp_pending_leave; ?></span> <?php esc_html_e( 'Leave Requests', 'obydullah-micro-erp' ); ?></td>
						</tr>
						<tr>
							<td><span class="status-badge status-inactive"><?php echo (int) $oby_mi_erp_unpaid_sales; ?></span> <?php esc_html_e( 'Unpaid Invoices', 'obydullah-micro-erp' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="col-lg-6 col-md-12 mb-3">
			<div class="bg-light p-4 rounded shadow-sm h-100">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<h3 class="fs-6 fw-semibold mb-0">
						<?php esc_html_e( 'Recent Employees', 'obydullah-micro-erp' ); ?>
					</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'ID', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Department', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $oby_mi_erp_recent_emp ) ) : ?>
								<tr><td colspan="4"><?php esc_html_e( 'No employees yet.', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $oby_mi_erp_recent_emp as $oby_mi_erp_emp ) : ?>
								<tr>
									<td><?php echo esc_html( $oby_mi_erp_emp->employee_id ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_emp->name ); ?></td>
									<td><?php echo esc_html( oby_mi_erp_department_name( $oby_mi_erp_emp->department_id ) ); ?></td>
									<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_emp->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
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
						<?php esc_html_e( 'Recent Journal Entries', 'obydullah-micro-erp' ); ?>
					</h3>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr class="bg-primary text-white">
								<th>#</th>
								<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $oby_mi_erp_recent_jrnl ) ) : ?>
								<tr><td colspan="3"><?php esc_html_e( 'No journal entries yet.', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $oby_mi_erp_recent_jrnl as $oby_mi_erp_je ) : ?>
								<tr>
									<td>JE-<?php echo (int) $oby_mi_erp_je->id; ?></td>
									<td><?php echo esc_html( $oby_mi_erp_je->description ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_je->entry_date ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
