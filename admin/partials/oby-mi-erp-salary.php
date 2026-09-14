<?php
/**
 * Renders the Salary admin screen and its monthly payroll run form.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_month = oby_mi_erp_query_text( 'month', current_time( 'Y-m' ) );
if ( ! preg_match( '/^\d{4}-\d{2}$/', $oby_mi_erp_month ) ) {
	$oby_mi_erp_month = current_time( 'Y-m' );
}

$oby_mi_erp_year = (int) substr( $oby_mi_erp_month, 0, 4 );
$oby_mi_erp_mnum = (int) substr( $oby_mi_erp_month, 5, 2 );

$oby_mi_erp_prev = gmdate( 'Y-m', mktime( 0, 0, 0, $oby_mi_erp_mnum - 1, 1, $oby_mi_erp_year ) );
$oby_mi_erp_next = gmdate( 'Y-m', mktime( 0, 0, 0, $oby_mi_erp_mnum + 1, 1, $oby_mi_erp_year ) );

$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE e.name LIKE %s OR e.employee_id LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like      = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_employees = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT e.*, d.name AS department_name FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_departments d ON d.id = e.department_id
			WHERE e.name LIKE %s OR e.employee_id LIKE %s
			ORDER BY e.employee_id ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_employees = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT e.*, d.name AS department_name FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_departments d ON d.id = e.department_id
			ORDER BY e.employee_id ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

$oby_mi_erp_payments = array();
if ( $oby_mi_erp_employees ) {
	$oby_mi_erp_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_salary_payments WHERE month = %s", $oby_mi_erp_month ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
	foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) {
		$oby_mi_erp_payments[ $oby_mi_erp_row->employee_id ] = $oby_mi_erp_row;
	}
}

// Summary totals across ALL matching employees (cards must not change with paging).
if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like   = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_totals = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0)),0) AS salary,
				COALESCE(SUM(IF(p.status = 'paid', e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0), 0)),0) AS paid,
				COALESCE(SUM(IF(p.status = 'paid', 0, e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0))),0) AS unpaid
			FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_salary_payments p ON p.employee_id = e.id AND p.month = %s
			WHERE e.name LIKE %s OR e.employee_id LIKE %s",
			$oby_mi_erp_month,
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_totals = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COALESCE(SUM(e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0)),0) AS salary,
				COALESCE(SUM(IF(p.status = 'paid', e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0), 0)),0) AS paid,
				COALESCE(SUM(IF(p.status = 'paid', 0, e.basic_salary + COALESCE(p.allowances,0) - COALESCE(p.deductions,0))),0) AS unpaid
			FROM {$wpdb->prefix}oby_mi_erp_employees e
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_salary_payments p ON p.employee_id = e.id AND p.month = %s",
			$oby_mi_erp_month
		)
	);
}

$oby_mi_erp_total_salary = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->salary : 0 );
$oby_mi_erp_total_paid   = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->paid : 0 );
$oby_mi_erp_total_unpaid = (float) ( $oby_mi_erp_totals ? $oby_mi_erp_totals->unpaid : 0 );

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'salary', array( 'month' => $oby_mi_erp_month ) );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Salary', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<div class="month-nav mt-3">
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'salary', array( 'month' => $oby_mi_erp_prev ) ) ); ?>" class="btn-secondary">← <?php esc_html_e( 'Previous', 'obydullah-micro-erp' ); ?></a>
		<strong><?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $oby_mi_erp_mnum, 1, $oby_mi_erp_year ) ) ); ?></strong>
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'salary', array( 'month' => $oby_mi_erp_next ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Next', 'obydullah-micro-erp' ); ?> →</a>
		<form method="post" action="" class="inline-form" style="margin-left:auto;">
			<?php wp_nonce_field( 'oby_mi_erp_salary_paid' ); ?>
			<input type="hidden" name="oby_mi_erp_action" value="mark_salary_paid">
			<input type="hidden" name="month" value="<?php echo esc_attr( $oby_mi_erp_month ); ?>">
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
			<button type="submit" class="btn-save">
				<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
				<?php esc_html_e( 'Mark All Paid', 'obydullah-micro-erp' ); ?>
			</button>
		</form>
	</div>

	<?php
	$oby_mi_erp_month_label = date_i18n( 'F Y', mktime( 0, 0, 0, $oby_mi_erp_mnum, 1, $oby_mi_erp_year ) );
	$oby_mi_erp_pct_paid    = $oby_mi_erp_total_salary > 0 ? round( ( $oby_mi_erp_total_paid / $oby_mi_erp_total_salary ) * 100 ) : 0;
	$oby_mi_erp_pct_unpaid  = max( 0, 100 - $oby_mi_erp_pct_paid );

	$oby_mi_erp_salary_stats = array(
		array(
			'key'   => 'employees',
			'label' => __( 'Total Employees', 'obydullah-micro-erp' ),
			'value' => (int) $oby_mi_erp_total_items,
			'sub'   => $oby_mi_erp_month_label,
			'icon'  => 'groups',
			'bar'   => null,
		),
		array(
			'key'   => 'total',
			'label' => __( 'Total Salary', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $oby_mi_erp_total_salary ),
			'sub'   => $oby_mi_erp_month_label,
			'icon'  => 'chart-line',
			'bar'   => null,
		),
		array(
			'key'   => 'paid',
			'label' => __( 'Paid', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $oby_mi_erp_total_paid ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total salary already paid. */
				__( '%d%% of total salary', 'obydullah-micro-erp' ),
				$oby_mi_erp_pct_paid
			),
			'icon'  => 'money-alt',
			'bar'   => $oby_mi_erp_pct_paid,
		),
		array(
			'key'   => 'due',
			'label' => __( 'Unpaid', 'obydullah-micro-erp' ),
			'value' => oby_mi_erp_format_money( $oby_mi_erp_total_unpaid ),
			'sub'   => sprintf(
				/* translators: %d: percentage of total salary still unpaid. */
				__( '%d%% of total salary', 'obydullah-micro-erp' ),
				$oby_mi_erp_pct_unpaid
			),
			'icon'  => 'warning',
			'bar'   => $oby_mi_erp_pct_unpaid,
		),
	);
	?>
	<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'salary', __( 'Search Employees', 'obydullah-micro-erp' ), __( 'Search by name or employee ID...', 'obydullah-micro-erp' ), array( 'month' => $oby_mi_erp_month ), $oby_mi_erp_search ); ?>
		</div>
	</div>

	<div class="stat-cards">
		<?php foreach ( $oby_mi_erp_salary_stats as $oby_mi_erp_stat ) : ?>
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

	<form method="post" action="">
		<?php wp_nonce_field( 'oby_mi_erp_salary_paid' ); ?>
		<input type="hidden" name="oby_mi_erp_action" value="mark_salary_paid">
		<input type="hidden" name="month" value="<?php echo esc_attr( $oby_mi_erp_month ); ?>">
		<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Salary Sheet', 'obydullah-micro-erp' ); ?> — <?php echo esc_html( date_i18n( 'F Y', mktime( 0, 0, 0, $oby_mi_erp_mnum, 1, $oby_mi_erp_year ) ) ); ?></h2>

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
								<?php if ( empty( $oby_mi_erp_employees ) ) : ?>
									<tr><td colspan="9" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php
								foreach ( $oby_mi_erp_employees as $oby_mi_erp_emp ) :
									$oby_mi_erp_payment = isset( $oby_mi_erp_payments[ $oby_mi_erp_emp->id ] ) ? $oby_mi_erp_payments[ $oby_mi_erp_emp->id ] : null;
									$oby_mi_erp_basic   = (float) $oby_mi_erp_emp->basic_salary;
									$oby_mi_erp_allow   = $oby_mi_erp_payment ? (float) $oby_mi_erp_payment->allowances : 0;
									$oby_mi_erp_deduct  = $oby_mi_erp_payment ? (float) $oby_mi_erp_payment->deductions : 0;
									$oby_mi_erp_net     = $oby_mi_erp_basic + $oby_mi_erp_allow - $oby_mi_erp_deduct;
									$oby_mi_erp_is_paid = $oby_mi_erp_payment && 'paid' === $oby_mi_erp_payment->status;
									?>
									<tr>
										<td><?php echo esc_html( $oby_mi_erp_emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $oby_mi_erp_emp->name ); ?></strong></td>
										<td><?php echo esc_html( $oby_mi_erp_emp->department_name ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_basic ) ); ?></td>
										<td><input type="number" name="allowances[<?php echo (int) $oby_mi_erp_emp->id; ?>]" value="<?php echo esc_attr( $oby_mi_erp_allow ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $oby_mi_erp_is_paid ? 'disabled' : ''; ?>></td>
										<td><input type="number" name="deductions[<?php echo (int) $oby_mi_erp_emp->id; ?>]" value="<?php echo esc_attr( $oby_mi_erp_deduct ); ?>" step="0.01" min="0" style="width:110px;" <?php echo $oby_mi_erp_is_paid ? 'disabled' : ''; ?>></td>
										<td class="text-right fw-bold"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_net ) ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_is_paid ? 'paid' : 'unpaid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td class="text-right">
											<?php if ( ! $oby_mi_erp_is_paid ) : ?>
												<button type="submit" name="employee_id" value="<?php echo (int) $oby_mi_erp_emp->id; ?>" class="btn-success"><?php esc_html_e( 'Mark Paid', 'obydullah-micro-erp' ); ?></button>
											<?php else : ?>
												<em class="text-muted fs-6"><?php echo esc_html( gmdate( 'M d, Y', strtotime( $oby_mi_erp_payment->paid_at ) ) ); ?></em>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'salary', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

				</div>
			</div>
		</div>
	</form>
</div>
