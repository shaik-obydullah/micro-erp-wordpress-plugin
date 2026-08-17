<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$leave_types = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'leave_types' ) . " ORDER BY name ASC" );
$employees   = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'employees' ) . " WHERE status = 'active' ORDER BY name ASC" );
$requests    = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'leave_requests' ) . " ORDER BY created_at DESC" );

$edit_type_id = isset( $_GET['edit_type'] ) ? (int) $_GET['edit_type'] : 0;
$editing_type = null;
if ( $edit_type_id ) {
	$editing_type = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'leave_types' ) . " WHERE id = %d", $edit_type_id ) );
}

$back_url = add_query_arg( array( 'page' => 'micro-erp/leave' ), admin_url( 'admin.php' ) );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Leave Management', 'micro-erp' ); ?></h1>

	<div class="leave-types">
		<?php foreach ( $leave_types as $lt ) : ?>
			<div class="leave-type-card">
				<h3 style="color:#2271b1;"><?php echo (int) $lt->days_per_year; ?></h3>
				<p><?php echo esc_html( $lt->name ); ?></p>
				<small><?php esc_html_e( 'per year', 'micro-erp' ); ?></small>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="row">
		<div class="card">
			<div class="card-header"><?php esc_html_e( 'New Leave Request', 'micro-erp' ); ?></div>
			<div class="card-body">
				<form method="post" action="">
					<?php wp_nonce_field( 'micro_erp_leave_request_save' ); ?>
					<input type="hidden" name="micro_erp_action" value="save_leave_request">
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
					<table class="form-table" style="width:100%;">
						<tr>
							<th><label for="employee_id"><?php esc_html_e( 'Employee', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="employee_id" id="employee_id" required>
									<option value="0"><?php esc_html_e( 'Select Employee', 'micro-erp' ); ?></option>
									<?php foreach ( $employees as $emp ) : ?>
										<option value="<?php echo (int) $emp->id; ?>"><?php echo esc_html( $emp->employee_id . ' - ' . $emp->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="leave_type_id"><?php esc_html_e( 'Leave Type', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="leave_type_id" id="leave_type_id" required>
									<option value="0"><?php esc_html_e( 'Select Type', 'micro-erp' ); ?></option>
									<?php foreach ( $leave_types as $lt ) : ?>
										<option value="<?php echo (int) $lt->id; ?>"><?php echo esc_html( $lt->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="start_date"><?php esc_html_e( 'Start Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="start_date" id="start_date" required></td>
						</tr>
						<tr>
							<th><label for="end_date"><?php esc_html_e( 'End Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="end_date" id="end_date" required></td>
						</tr>
						<tr>
							<th><label for="reason"><?php esc_html_e( 'Reason', 'micro-erp' ); ?></label></th>
							<td><textarea name="reason" id="reason" rows="2"></textarea></td>
						</tr>
					</table>
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Submit Request', 'micro-erp' ); ?></button>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<?php echo $editing_type ? esc_html__( 'Edit Leave Type', 'micro-erp' ) : esc_html__( 'Leave Types', 'micro-erp' ); ?>
			</div>
			<div class="card-body">
				<form method="post" action="" style="margin-bottom:12px;">
					<?php
					$action = $editing_type ? 'update_leave_type' : 'save_leave_type';
					wp_nonce_field( 'micro_erp_leave_type_save' );
					?>
					<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $editing_type ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $editing_type->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
					<table class="form-table" style="width:100%;">
						<tr>
							<th><label for="lt_name"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="name" id="lt_name" value="<?php echo $editing_type ? esc_attr( $editing_type->name ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="days_per_year"><?php esc_html_e( 'Days / Year', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="number" name="days_per_year" id="days_per_year" min="0" value="<?php echo $editing_type ? (int) $editing_type->days_per_year : 0; ?>" required></td>
						</tr>
						<tr>
							<th><label for="lt_active"><?php esc_html_e( 'Active', 'micro-erp' ); ?></label></th>
							<td><input type="checkbox" name="is_active" id="lt_active" <?php checked( $editing_type ? (int) $editing_type->is_active : 1 ); ?>></td>
						</tr>
					</table>
					<div class="actions">
						<?php if ( $editing_type ) : ?>
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel btn-sm"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
						<?php endif; ?>
						<button type="submit" class="btn btn-success btn-sm"><?php esc_html_e( 'Save Type', 'micro-erp' ); ?></button>
					</div>
				</form>
				<table class="mini-table">
					<tbody>
						<?php foreach ( $leave_types as $lt ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $lt->name ); ?></strong></td>
								<td><?php echo (int) $lt->days_per_year; ?> <?php esc_html_e( 'days', 'micro-erp' ); ?></td>
								<td><?php echo $lt->is_active ? '<span class="badge badge-active">' . esc_html__( 'Active', 'micro-erp' ) . '</span>' : '<span class="badge badge-neutral">' . esc_html__( 'Off', 'micro-erp' ) . '</span>'; ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit_type', $lt->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this leave type?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_leave_type_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_leave_type">
											<input type="hidden" name="id" value="<?php echo (int) $lt->id; ?>">
											<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
											<button class="btn btn-danger btn-sm"><?php esc_html_e( 'Delete', 'micro-erp' ); ?></button>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header"><?php esc_html_e( 'Leave Requests', 'micro-erp' ); ?></div>
		<div class="card-body" style="padding: 0;">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Employee', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Leave Type', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'From', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'To', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Days', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $requests ) ) : ?>
						<tr><td colspan="8"><?php esc_html_e( 'No leave requests yet.', 'micro-erp' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $requests as $req ) : ?>
						<tr>
							<td><strong><?php echo esc_html( micro_erp_employee_name( $req->employee_id ) ); ?></strong></td>
							<td><?php echo esc_html( micro_erp_leave_type_name( $req->leave_type_id ) ); ?></td>
							<td><?php echo esc_html( $req->start_date ); ?></td>
							<td><?php echo esc_html( $req->end_date ); ?></td>
							<td><?php echo esc_html( $req->total_days ); ?></td>
							<td><?php echo esc_html( $req->reason ); ?></td>
							<td><?php echo micro_erp_status_badge( $req->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td>
								<div class="actions">
									<?php if ( 'pending' === $req->status ) : ?>
										<form method="post" action="" class="inline-form">
											<?php wp_nonce_field( 'micro_erp_leave_status' ); ?>
											<input type="hidden" name="micro_erp_action" value="approve_leave">
											<input type="hidden" name="id" value="<?php echo (int) $req->id; ?>">
											<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
											<button class="btn btn-success btn-sm"><?php esc_html_e( 'Approve', 'micro-erp' ); ?></button>
										</form>
										<form method="post" action="" class="inline-form">
											<?php wp_nonce_field( 'micro_erp_leave_status' ); ?>
											<input type="hidden" name="micro_erp_action" value="reject_leave">
											<input type="hidden" name="id" value="<?php echo (int) $req->id; ?>">
											<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
											<button class="btn btn-danger btn-sm"><?php esc_html_e( 'Reject', 'micro-erp' ); ?></button>
										</form>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
