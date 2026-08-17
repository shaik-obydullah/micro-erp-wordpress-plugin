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
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Salary', 'micro-erp' ); ?></h1>

	<div class="month-nav">
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => $prev ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-primary btn-sm">← <?php esc_html_e( 'Previous', 'micro-erp' ); ?></a>
		<strong><?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) ) ); ?></strong>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => $next ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Next', 'micro-erp' ); ?> →</a>
		<span style="margin-left:auto;"></span>
		<form method="post" action="" class="inline-form">
			<?php wp_nonce_field( 'micro_erp_salary_paid' ); ?>
			<input type="hidden" name="micro_erp_action" value="mark_salary_paid">
			<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
			<button type="submit" class="btn btn-success"><?php esc_html_e( 'Mark All Paid', 'micro-erp' ); ?></button>
		</form>
	</div>

	<div class="summary-box">
		<div class="summary-item"><div class="label"><?php esc_html_e( 'Total Employees', 'micro-erp' ); ?></div><div class="value"><?php echo count( $employees ); ?></div></div>
		<div class="summary-item"><div class="label"><?php esc_html_e( 'Total Salary', 'micro-erp' ); ?></div><div class="value" style="color:#2271b1;"><?php echo esc_html( micro_erp_format_money( $total_salary ) ); ?></div></div>
		<div class="summary-item"><div class="label"><?php esc_html_e( 'Paid', 'micro-erp' ); ?></div><div class="value" style="color:#00a32a;"><?php echo esc_html( micro_erp_format_money( $total_paid ) ); ?></div></div>
		<div class="summary-item"><div class="label"><?php esc_html_e( 'Unpaid', 'micro-erp' ); ?></div><div class="value" style="color:#d63638;"><?php echo esc_html( micro_erp_format_money( $total_unpaid ) ); ?></div></div>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'micro_erp_salary_paid' ); ?>
		<input type="hidden" name="micro_erp_action" value="mark_salary_paid">
		<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
		<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Emp ID', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Basic Salary', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Allowances', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Deductions', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Net Pay', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $employees ) ) : ?>
							<tr><td colspan="9"><?php esc_html_e( 'Add employees first.', 'micro-erp' ); ?></td></tr>
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
								<td class="text-right"><input type="number" name="allowances[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $allow ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
								<td class="text-right"><input type="number" name="deductions[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $deduct ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $net ) ); ?></strong></td>
								<td><?php echo micro_erp_status_badge( $is_paid ? 'paid' : 'unpaid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<?php if ( ! $is_paid ) : ?>
										<button type="submit" name="employee_id" value="<?php echo (int) $emp->id; ?>" class="btn btn-success btn-sm"><?php esc_html_e( 'Mark Paid', 'micro-erp' ); ?></button>
									<?php else : ?>
										<em style="color:#646970;font-size:12px;"><?php echo esc_html( gmdate( 'M d, Y', strtotime( $payment->paid_at ) ) ); ?></em>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</form>
</div>
