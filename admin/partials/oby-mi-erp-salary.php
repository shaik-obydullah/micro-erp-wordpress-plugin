<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$month = oby_mi_erp_query_text( 'month', current_time( 'Y-m' ) );
if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
	$month = current_time( 'Y-m' );
}

$year  = (int) substr( $month, 0, 4 );
$mnum  = (int) substr( $month, 5, 2 );

$prev = gmdate( 'Y-m', mktime( 0, 0, 0, $mnum - 1, 1, $year ) );
$next = gmdate( 'Y-m', mktime( 0, 0, 0, $mnum + 1, 1, $year ) );

$search = oby_mi_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE e.name LIKE %s OR e.employee_id LIKE %s",
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$employees = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT e.*, d.name AS department_name FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_departments d ON d.id = e.department_id
			WHERE e.name LIKE %s OR e.employee_id LIKE %s
			ORDER BY e.employee_id ASC LIMIT %d OFFSET %d",
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$employees = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT e.*, d.name AS department_name FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_departments d ON d.id = e.department_id
			ORDER BY e.employee_id ASC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

$payments = array();
if ( $employees ) {
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_salary_payments WHERE month = %s", $month ) );
	foreach ( $rows as $r ) {
		$payments[ $r->employee_id ] = $r;
	}
}

// Summary totals across ALL matching employees (cards must not change with paging).
if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0)),0) AS salary,
				COALESCE(SUM(IF(p.status = 'paid', e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0), 0)),0) AS paid,
				COALESCE(SUM(IF(p.status = 'paid', 0, e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0))),0) AS unpaid
			FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_salary_payments p ON p.employee_id = e.id AND p.month = %s
			WHERE e.name LIKE %s OR e.employee_id LIKE %s",
			$month,
			$like,
			$like
		)
	);
} else {
	$totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0)),0) AS salary,
				COALESCE(SUM(IF(p.status = 'paid', e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0), 0)),0) AS paid,
				COALESCE(SUM(IF(p.status = 'paid', 0, e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0))),0) AS unpaid
			FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_salary_payments p ON p.employee_id = e.id AND p.month = %s",
			$month
		)
	);
}

$total_salary = (float) ( $totals ? $totals->salary : 0 );
$total_paid   = (float) ( $totals ? $totals->paid : 0 );
$total_unpaid = (float) ( $totals ? $totals->unpaid : 0 );

oby_mi_erp_print_admin_notice();

$back_url = oby_mi_erp_admin_url( 'salary', array( 'month' => $month ) );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Salary', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<div class="month-nav mt-3">
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'salary', array( 'month' => $prev ) ) ); ?>" class="btn-secondary">← <?php esc_html_e( 'Previous', 'obydullah-micro-erp' ); ?></a>
		<strong><?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) ) ); ?></strong>
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'salary', array( 'month' => $next ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Next', 'obydullah-micro-erp' ); ?> →</a>
		<form method="post" action="" class="inline-form" style="margin-left:auto;">
			<?php wp_nonce_field( 'oby_mi_erp_salary_paid' ); ?>
			<input type="hidden" name="oby_mi_erp_action" value="mark_salary_paid">
			<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
			<button type="submit" class="btn-save">
				<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'Mark All Paid', 'obydullah-micro-erp' ); ?>
			</button>
		</form>
	</div>

	<?php
	$month_label = date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) );
	$pct_paid    = $total_salary > 0 ? round( ( $total_paid / $total_salary ) * 100 ) : 0;
	$pct_unpaid  = max( 0, 100 - $pct_paid );

	$salary_stats = array(
		array(
			'key'   => 'employees',
			'label' => __( 'Total Employees', 'obydullah-micro-erp' ),
			'value' => (int) $total_items,
			'sub'   => $month_label,
			'icon'  => 'groups',
			'bar'   => null,
		),
		array(
			'key'   => 'total',
			'label' => __( 'Total Salary', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $total_salary ),
			'sub'   => $month_label,
			'icon'  => 'chart-line',
			'bar'   => null,
		),
		array(
			'key'   => 'paid',
			'label' => __( 'Paid', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $total_paid ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total salary already paid. */
				__( '%d%% of total salary', 'obydullah-micro-erp' ),
				$pct_paid
			),
			'icon'  => 'money-alt',
			'bar'   => $pct_paid,
		),
		array(
			'key'   => 'due',
			'label' => __( 'Unpaid', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $total_unpaid ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total salary still unpaid. */
				__( '%d%% of total salary', 'obydullah-micro-erp' ),
				$pct_unpaid
			),
			'icon'  => 'warning',
			'bar'   => $pct_unpaid,
		),
	);
	?>
	<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'salary', __( 'Search Employees', 'obydullah-micro-erp' ), __( 'Search by name or employee ID...', 'obydullah-micro-erp' ), array( 'month' => $month ), $search ); ?>
		</div>
	</div>

	<div class="stat-cards">
		<?php foreach ( $salary_stats as $stat ) : ?>
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

	<form method="post" action="">
		<?php wp_nonce_field( 'oby_mi_erp_salary_paid' ); ?>
		<input type="hidden" name="oby_mi_erp_action" value="mark_salary_paid">
		<input type="hidden" name="month" value="<?php echo esc_attr( $month ); ?>">
		<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Salary Sheet', 'obydullah-micro-erp' ); ?> — <?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $mnum, 1, $year ) ) ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'obydullah-micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Basic Salary', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Allowances', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Deductions', 'obydullah-micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Net Pay', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="140" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $employees ) ) : ?>
									<tr><td colspan="9" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $employees as $emp ) :
									$payment   = isset( $payments[ $emp->id ] ) ? $payments[ $emp->id ] : null;
									$basic     = (float) $emp->basic_salary;
									$allow     = $payment ? (float) $payment->allowances : 0;
									$deduct    = $payment ? (float) $payment->deductions : 0;
									$net       = $basic + $allow - $deduct;
									$is_paid   = $payment && 'paid' === $payment->status;
									?>
									<tr>
										<td><?php echo esc_html( $emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $emp->name ); ?></strong></td>
										<td><?php echo esc_html( $emp->department_name ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $basic ) ); ?></td>
										<td><input type="number" name="allowances[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $allow ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
										<td><input type="number" name="deductions[<?php echo (int) $emp->id; ?>]" value="<?php echo esc_attr( $deduct ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $is_paid ? 'disabled' : ''; ?>></td>
										<td class="text-right fw-bold"><?php echo esc_html( oby_mi_erp_format_money( $net ) ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $is_paid ? 'paid' : 'unpaid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td class="text-right">
											<?php if ( ! $is_paid ) : ?>
												<button type="submit" name="employee_id" value="<?php echo (int) $emp->id; ?>" class="btn-success"><?php esc_html_e( 'Mark Paid', 'obydullah-micro-erp' ); ?></button>
											<?php else : ?>
												<em class="text-muted fs-6"><?php echo esc_html( gmdate( 'M d, Y', strtotime( $payment->paid_at ) ) ); ?></em>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'salary', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>
	</form>
</div>
