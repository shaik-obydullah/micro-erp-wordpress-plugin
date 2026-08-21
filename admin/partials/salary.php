<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$month = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : current_time( 'Y-m' );
if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
	$month = current_time( 'Y-m' );
}

$year  = (int) substr( $month, 0, 4 );
$mnum  = (int) substr( $month, 5, 2 );

$prev = date( 'Y-m', mktime( 0, 0, 0, $mnum - 1, 1, $year ) );
$next = date( 'Y-m', mktime( 0, 0, 0, $mnum + 1, 1, $year ) );

$employees = $wpdb->get_results(
	"SELECT e.*, d.name AS department_name FROM " . micro_erp_table( 'employees' ) . " e
	LEFT JOIN " . micro_erp_table( 'departments' ) . " d ON d.id = e.department_id
	ORDER BY e.employee_id ASC"
);

$payments = array();
if ( $employees ) {
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'salary_payments' ) . " WHERE month = %s", $month ) );
	foreach ( $rows as $r ) {
		$payments[ $r->employee_id ] = $r;
	}
}

$total_salary = 0;
$total_paid   = 0;
$total_unpaid = 0;

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => $month ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Salary', 'micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<div class="month-nav mt-3">
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => $prev ), admin_url( 'admin.php' ) ) ); ?>" class="btn-secondary">← <?php esc_html_e( 'Previous', 'micro-erp' ); ?></a>
		<strong><?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) ) ); ?></strong>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => $next ), admin_url( 'admin.php' ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Next', 'micro-erp' ); ?> →</a>
		<span style="margin-left:auto;"></span>
		<form method="post" action="" class="inline-form">
			<?php wp_nonce_field( 'micro_erp_salary_paid' ); ?>
			<input type="hidden" name="micro_erp_action" value="mark_salary_paid">
			<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
			<button type="submit" class="btn-success"><?php esc_html_e( 'Mark All Paid', 'micro-erp' ); ?></button>
		</form>
	</div>

	<div class="row mt-3 mb-3">
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-info">
				<h4><?php echo count( $employees ); ?></h4>
				<p><?php esc_html_e( 'Total Employees', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-primary">
				<h4><?php echo esc_html( micro_erp_format_money( $total_salary ) ); ?></h4>
				<p><?php esc_html_e( 'Total Salary', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-success">
				<h4><?php echo esc_html( micro_erp_format_money( $total_paid ) ); ?></h4>
				<p><?php esc_html_e( 'Paid', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-danger">
				<h4><?php echo esc_html( micro_erp_format_money( $total_unpaid ) ); ?></h4>
				<p><?php esc_html_e( 'Unpaid', 'micro-erp' ); ?></p>
			</div>
		</div>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'micro_erp_salary_paid' ); ?>
		<input type="hidden" name="micro_erp_action" value="mark_salary_paid">
		<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
		<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Salary Sheet', 'micro-erp' ); ?> — <?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) ) ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Basic Salary', 'micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Allowances', 'micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Deductions', 'micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Net Pay', 'micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
									<th width="140" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $employees ) ) : ?>
									<tr><td colspan="9" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $employees as $emp ) :
									$payment   = isset( $payments[ $emp->id ] ) ? $payments[ $emp->id ] : null;
									$basic     = (float) $emp->basic_salary;
									$allow     = $payment ? (float) $payment->allowances : 0;
									$deduct    = $payment ? (float) $payment->deductions : 0;
									$net       = $basic + $allow - $deduct;
									$is_paid   = $payment && 'paid' === $payment->status;

									$total_salary += $net;
									if ( $is_paid ) {
										$total_paid += $net;
									} else {
										$total_unpaid += $net;
									}
									?>
									<tr>
										<td><?php echo esc_html( $emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $emp->name ); ?></strong></td>
										<td><?php echo esc_html( $emp->department_name ); ?></td>
										<td class="text-right"><?php echo esc_html( micro_erp_format_money( $basic ) ); ?></td>
										<td><input type="number" name="allowances[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $allow ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
										<td><input type="number" name="deductions[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $deduct ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
										<td class="text-right fw-bold"><?php echo esc_html( micro_erp_format_money( $net ) ); ?></td>
										<td><?php echo micro_erp_status_badge( $is_paid ? 'paid' : 'unpaid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td class="text-right">
											<?php if ( ! $is_paid ) : ?>
												<button type="submit" name="employee_id" value="<?php echo (int) $emp->id; ?>" class="btn-success"><?php esc_html_e( 'Mark Paid', 'micro-erp' ); ?></button>
											<?php else : ?>
												<em class="text-muted fs-6"><?php echo esc_html( gmdate( 'M d, Y', strtotime( $payment->paid_at ) ) ); ?></em>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
