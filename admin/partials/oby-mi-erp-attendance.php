<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_date = oby_mi_erp_query_text( 'date', current_time( 'Y-m-d' ) );
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $oby_mi_erp_date ) ) {
	$oby_mi_erp_date = current_time( 'Y-m-d' );
}

$oby_mi_erp_employees = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE status = %s ORDER BY employee_id ASC", 'active' ) );

$oby_mi_erp_existing = array();
if ( $oby_mi_erp_employees ) {
	$oby_mi_erp_ids            = array_map( 'intval', wp_list_pluck( $oby_mi_erp_employees, 'id' ) );
	$oby_mi_erp_in_placeholders = implode( ',', array_fill( 0, count( $oby_mi_erp_ids ), '%d' ) );
	$oby_mi_erp_att            = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_attendance WHERE date = %s AND employee_id IN ({$oby_mi_erp_in_placeholders})", array_merge( array( $oby_mi_erp_date ), $oby_mi_erp_ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $oby_mi_erp_att as $oby_mi_erp_a ) {
		$oby_mi_erp_existing[ $oby_mi_erp_a->employee_id ] = $oby_mi_erp_a;
	}
}

$oby_mi_erp_summary = array( 'present' => 0, 'absent' => 0, 'late' => 0, 'half' => 0 );
foreach ( $oby_mi_erp_existing as $oby_mi_erp_a ) {
	if ( isset( $oby_mi_erp_summary[ $oby_mi_erp_a->status ] ) ) {
		$oby_mi_erp_summary[ $oby_mi_erp_a->status ]++;
	}
}
$oby_mi_erp_unmarked = count( $oby_mi_erp_employees ) - count( $oby_mi_erp_existing );

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'attendance', array( 'date' => $oby_mi_erp_date ) );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Attendance', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get" action="" class="date-nav mt-3">
		<input type="hidden" name="page" value="oby-mi-erp/attendance">
		<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'attendance', array( 'date' => current_time( 'Y-m-d' ) ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Today', 'obydullah-micro-erp' ); ?></a>
		<input type="date" name="date" value="<?php echo esc_attr( $oby_mi_erp_date ); ?>" class="form-control form-control-sm">
		<button class="btn-primary"><?php esc_html_e( 'Load', 'obydullah-micro-erp' ); ?></button>
	</form>

	<?php
	$oby_mi_erp_total_emp = count( $oby_mi_erp_employees );
	$oby_mi_erp_stats     = array(
		array(
			'key'   => 'present',
			'label' => __( 'Present', 'obydullah-micro-erp' ),
			'value' => (int) $oby_mi_erp_summary['present'],
			'icon'  => 'yes-alt',
		),
		array(
			'key'   => 'absent',
			'label' => __( 'Absent', 'obydullah-micro-erp' ),
			'value' => (int) $oby_mi_erp_summary['absent'],
			'icon'  => 'no-alt',
		),
		array(
			'key'   => 'late',
			'label' => __( 'Late', 'obydullah-micro-erp' ),
			'value' => (int) $oby_mi_erp_summary['late'],
			'icon'  => 'clock',
		),
		array(
			'key'   => 'unmarked',
			'label' => __( 'Unmarked', 'obydullah-micro-erp' ),
			'value' => (int) $oby_mi_erp_unmarked,
			'icon'  => 'editor-help',
		),
	);
	?>
	<div class="stat-cards">
		<?php foreach ( $oby_mi_erp_stats as $oby_mi_erp_stat ) :
			$oby_mi_erp_pct = $oby_mi_erp_total_emp ? round( ( $oby_mi_erp_stat['value'] / $oby_mi_erp_total_emp ) * 100 ) : 0;
			?>
			<div class="stat-card stat-card--<?php echo esc_attr( $oby_mi_erp_stat['key'] ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $oby_mi_erp_stat['icon'] ); ?>"></span>
				</div>
				<div class="stat-body">
					<span class="stat-value"><?php echo (int) $oby_mi_erp_stat['value']; ?></span>
					<span class="stat-label"><?php echo esc_html( $oby_mi_erp_stat['label'] ); ?></span>
					<?php $oby_mi_erp_stat_sub = sprintf(
					/* translators: 1: percentage, 2: total number of employees. */
					__( '%1$d%% of %2$d employees', 'obydullah-micro-erp' ),
					$oby_mi_erp_pct,
					$oby_mi_erp_total_emp
				); ?>
				<span class="stat-sub"><?php echo esc_html( $oby_mi_erp_stat_sub ); ?></span>
					<div class="stat-bar" role="presentation"><span style="width:<?php echo (int) $oby_mi_erp_pct; ?>%;"></span></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'oby_mi_erp_attendance_save' ); ?>
		<input type="hidden" name="oby_mi_erp_action" value="save_attendance">
		<input type="hidden" name="date" value="<?php echo esc_attr( $oby_mi_erp_date ); ?>">
		<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Mark Attendance', 'obydullah-micro-erp' ); ?> — <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $oby_mi_erp_date ) ) ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check In', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check Out', 'obydullah-micro-erp' ); ?></th>
									<th width="120"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Notes', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $oby_mi_erp_employees ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $oby_mi_erp_employees as $oby_mi_erp_emp ) :
									$oby_mi_erp_rec = isset( $oby_mi_erp_existing[ $oby_mi_erp_emp->id ] ) ? $oby_mi_erp_existing[ $oby_mi_erp_emp->id ] : null;
									?>
									<tr>
										<td><?php echo esc_html( $oby_mi_erp_emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $oby_mi_erp_emp->name ); ?></strong></td>
										<td><?php echo esc_html( oby_mi_erp_department_name( $oby_mi_erp_emp->department_id ) ); ?></td>
										<td><input type="time" name="attendance[<?php echo (int) $oby_mi_erp_emp->id; ?>][check_in]" value="<?php echo $oby_mi_erp_rec ? esc_attr( $oby_mi_erp_rec->check_in ) : ''; ?>" class="form-control form-control-sm"></td>
										<td><input type="time" name="attendance[<?php echo (int) $oby_mi_erp_emp->id; ?>][check_out]" value="<?php echo $oby_mi_erp_rec ? esc_attr( $oby_mi_erp_rec->check_out ) : ''; ?>" class="form-control form-control-sm"></td>
										<td>
											<select name="attendance[<?php echo (int) $oby_mi_erp_emp->id; ?>][status]" class="form-control form-control-sm">
												<?php foreach ( array( 'present', 'absent', 'late', 'half' ) as $oby_mi_erp_status ) : ?>
													<option value="<?php echo esc_attr( $oby_mi_erp_status ); ?>" <?php selected( $oby_mi_erp_rec ? $oby_mi_erp_rec->status : 'present', $oby_mi_erp_status ); ?>><?php echo esc_html( ucfirst( $oby_mi_erp_status ) ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
										<td><input type="text" name="attendance[<?php echo (int) $oby_mi_erp_emp->id; ?>][notes]" value="<?php echo $oby_mi_erp_rec ? esc_attr( $oby_mi_erp_rec->notes ) : ''; ?>" class="form-control form-control-sm"></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="form-actions-bar">
						<?php if ( $oby_mi_erp_employees && $oby_mi_erp_unmarked > 0 ) : ?>
							<?php $oby_mi_erp_unmarked_note = sprintf(
								/* translators: 1: number of unmarked employees, 2: total number of employees. */
								__( '%1$d of %2$d employees unmarked.', 'obydullah-micro-erp' ),
								$oby_mi_erp_unmarked,
								count( $oby_mi_erp_employees )
							); ?>
							<span class="form-actions-note">
								<?php echo esc_html( $oby_mi_erp_unmarked_note ); ?>
							</span>
						<?php endif; ?>
						<button type="submit" class="btn-save">
							<span class="dashicons dashicons-yes" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Attendance', 'obydullah-micro-erp' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
