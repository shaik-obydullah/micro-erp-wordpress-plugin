<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$date = li_mi_erp_query_text( 'date', current_time( 'Y-m-d' ) );
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
	$date = current_time( 'Y-m-d' );
}

$employees = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_employees WHERE status = %s ORDER BY employee_id ASC", 'active' ) );

$existing = array();
if ( $employees ) {
	$ids            = array_map( 'intval', wp_list_pluck( $employees, 'id' ) );
	$in_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$att            = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_attendance WHERE date = %s AND employee_id IN ({$in_placeholders})", array_merge( array( $date ), $ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $att as $a ) {
		$existing[ $a->employee_id ] = $a;
	}
}

$summary = array( 'present' => 0, 'absent' => 0, 'late' => 0, 'half' => 0 );
foreach ( $existing as $a ) {
	if ( isset( $summary[ $a->status ] ) ) {
		$summary[ $a->status ]++;
	}
}
$unmarked = count( $employees ) - count( $existing );

li_mi_erp_print_admin_notice();

$back_url = li_mi_erp_admin_url( 'attendance', array( 'date' => $date ) );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Attendance', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get" action="" class="date-nav mt-3">
		<input type="hidden" name="page" value="micro-erp/attendance">
		<a href="<?php echo esc_url( li_mi_erp_admin_url( 'attendance', array( 'date' => current_time( 'Y-m-d' ) ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Today', 'lime-micro-erp' ); ?></a>
		<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>" class="form-control form-control-sm">
		<button class="btn-primary"><?php esc_html_e( 'Load', 'lime-micro-erp' ); ?></button>
	</form>

	<?php
	$total_emp = count( $employees );
	$stats     = array(
		array(
			'key'   => 'present',
			'label' => __( 'Present', 'lime-micro-erp' ),
			'value' => (int) $summary['present'],
			'icon'  => 'yes-alt',
		),
		array(
			'key'   => 'absent',
			'label' => __( 'Absent', 'lime-micro-erp' ),
			'value' => (int) $summary['absent'],
			'icon'  => 'no-alt',
		),
		array(
			'key'   => 'late',
			'label' => __( 'Late', 'lime-micro-erp' ),
			'value' => (int) $summary['late'],
			'icon'  => 'clock',
		),
		array(
			'key'   => 'unmarked',
			'label' => __( 'Unmarked', 'lime-micro-erp' ),
			'value' => (int) $unmarked,
			'icon'  => 'editor-help',
		),
	);
	?>
	<div class="stat-cards">
		<?php foreach ( $stats as $stat ) :
			$pct = $total_emp ? round( ( $stat['value'] / $total_emp ) * 100 ) : 0;
			?>
			<div class="stat-card stat-card--<?php echo esc_attr( $stat['key'] ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $stat['icon'] ); ?>"></span>
				</div>
				<div class="stat-body">
					<span class="stat-value"><?php echo (int) $stat['value']; ?></span>
					<span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
					<?php $stat_sub = sprintf(
					/* translators: 1: percentage, 2: total number of employees. */
					__( '%1$d%% of %2$d employees', 'lime-micro-erp' ),
					$pct,
					$total_emp
				); ?>
				<span class="stat-sub"><?php echo esc_html( $stat_sub ); ?></span>
					<div class="stat-bar" role="presentation"><span style="width:<?php echo (int) $pct; ?>%;"></span></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'li_mi_erp_attendance_save' ); ?>
		<input type="hidden" name="li_mi_erp_action" value="save_attendance">
		<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
		<input type="hidden" name="li_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Mark Attendance', 'lime-micro-erp' ); ?> — <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'lime-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check In', 'lime-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check Out', 'lime-micro-erp' ); ?></th>
									<th width="120"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Notes', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $employees ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'lime-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $employees as $emp ) :
									$rec = isset( $existing[ $emp->id ] ) ? $existing[ $emp->id ] : null;
									?>
									<tr>
										<td><?php echo esc_html( $emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $emp->name ); ?></strong></td>
										<td><?php echo esc_html( li_mi_erp_department_name( $emp->department_id ) ); ?></td>
										<td><input type="time" name="attendance[<?php echo (int) $emp->id; ?>][check_in]" value="<?php echo $rec ? esc_attr( $rec->check_in ) : ''; ?>" class="form-control form-control-sm"></td>
										<td><input type="time" name="attendance[<?php echo (int) $emp->id; ?>][check_out]" value="<?php echo $rec ? esc_attr( $rec->check_out ) : ''; ?>" class="form-control form-control-sm"></td>
										<td>
											<select name="attendance[<?php echo (int) $emp->id; ?>][status]" class="form-control form-control-sm">
												<?php foreach ( array( 'present', 'absent', 'late', 'half' ) as $st ) : ?>
													<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $rec ? $rec->status : 'present', $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
										<td><input type="text" name="attendance[<?php echo (int) $emp->id; ?>][notes]" value="<?php echo $rec ? esc_attr( $rec->notes ) : ''; ?>" class="form-control form-control-sm"></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="form-actions-bar">
						<?php if ( $employees && $unmarked > 0 ) : ?>
							<?php $unmarked_note = sprintf(
								/* translators: 1: number of unmarked employees, 2: total number of employees. */
								__( '%1$d of %2$d employees unmarked.', 'lime-micro-erp' ),
								$unmarked,
								count( $employees )
							); ?>
							<span class="form-actions-note">
								<?php echo esc_html( $unmarked_note ); ?>
							</span>
						<?php endif; ?>
						<button type="submit" class="btn-save">
							<span class="dashicons dashicons-yes" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Attendance', 'lime-micro-erp' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
