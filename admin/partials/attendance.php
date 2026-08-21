<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : current_time( 'Y-m-d' );
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
	$date = current_time( 'Y-m-d' );
}

$employees = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'employees' ) . " WHERE status = 'active' ORDER BY employee_id ASC" );

$existing = array();
if ( $employees ) {
	$ids = wp_list_pluck( $employees, 'id' );
	$in  = implode( ',', array_map( 'intval', $ids ) );
	$att = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'attendance' ) . " WHERE date = %s AND employee_id IN ({$in})", $date ) );
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

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/attendance', 'date' => $date ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Attendance', 'micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get" action="" class="date-nav mt-3">
		<input type="hidden" name="page" value="micro-erp/attendance">
		<a href="<?php echo esc_url( add_query_arg( 'date', current_time( 'Y-m-d' ), add_query_arg( 'page', 'micro-erp/attendance', admin_url( 'admin.php' ) ) ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Today', 'micro-erp' ); ?></a>
		<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>" class="form-control form-control-sm">
		<button class="btn-primary"><?php esc_html_e( 'Load', 'micro-erp' ); ?></button>
	</form>

	<div class="row mt-3 mb-3">
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-success">
				<h4><?php echo (int) $summary['present']; ?></h4>
				<p><?php esc_html_e( 'Present', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-danger">
				<h4><?php echo (int) $summary['absent']; ?></h4>
				<p><?php esc_html_e( 'Absent', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-warning">
				<h4><?php echo (int) $summary['late']; ?></h4>
				<p><?php esc_html_e( 'Late', 'micro-erp' ); ?></p>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 mb-3">
			<div class="stock-summary-card border-left-info">
				<h4><?php echo (int) $unmarked; ?></h4>
				<p><?php esc_html_e( 'Unmarked', 'micro-erp' ); ?></p>
			</div>
		</div>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'micro_erp_attendance_save' ); ?>
		<input type="hidden" name="micro_erp_action" value="save_attendance">
		<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
		<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Mark Attendance', 'micro-erp' ); ?> — <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check In', 'micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Check Out', 'micro-erp' ); ?></th>
									<th width="120"><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Notes', 'micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $employees ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'Add employees first.', 'micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $employees as $emp ) :
									$rec = isset( $existing[ $emp->id ] ) ? $existing[ $emp->id ] : null;
									?>
									<tr>
										<td><?php echo esc_html( $emp->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $emp->name ); ?></strong></td>
										<td><?php echo esc_html( micro_erp_department_name( $emp->department_id ) ); ?></td>
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

					<button type="submit" class="btn-success"><?php esc_html_e( 'Save Attendance', 'micro-erp' ); ?></button>
				</div>
			</div>
		</div>
	</form>
</div>
