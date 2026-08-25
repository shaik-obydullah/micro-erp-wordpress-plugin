<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$leave_types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}micro_erp_leave_types ORDER BY name ASC" );
$employees   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_employees WHERE status = %s ORDER BY name ASC", 'active' ) );

$search = micro_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, micro_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}micro_erp_employees e ON e.id = lr.employee_id
			WHERE lr.reason LIKE %s OR e.name LIKE %s OR e.employee_id LIKE %s",
			$like,
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}micro_erp_employees e ON e.id = lr.employee_id
			WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$requests = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT lr.* FROM {$wpdb->prefix}micro_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}micro_erp_employees e ON e.id = lr.employee_id
			WHERE lr.reason LIKE %s OR e.name LIKE %s OR e.employee_id LIKE %s
			ORDER BY lr.created_at DESC LIMIT %d OFFSET %d",
			$like,
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$requests = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT lr.* FROM {$wpdb->prefix}micro_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}micro_erp_employees e ON e.id = lr.employee_id
			ORDER BY lr.created_at DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

$edit_type_id = micro_erp_query_int( 'edit_type' );
$editing_type = null;
if ( $edit_type_id ) {
	$editing_type = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_leave_types WHERE id = %d", $edit_type_id ) );
}

$back_url = micro_erp_admin_url( 'leave' );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Leave Management', 'lime-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $leave_types ) ) :
		$leave_styles = array(
			array( 'icon' => 'palmtree',       'tone' => 'green', 'match' => array( 'annual', 'earned', 'vacation' ) ),
			array( 'icon' => 'smiley',         'tone' => 'blue',  'match' => array( 'casual', 'personal' ) ),
			array( 'icon' => 'shield-alt2',    'tone' => 'red',   'match' => array( 'sick', 'medical' ) ),
			array( 'icon' => 'admin-users',    'tone' => 'amber', 'match' => array( 'matern', 'patern', 'parent' ) ),
		);
		$default_style = array( 'icon' => 'calendar-alt', 'tone' => 'blue', 'match' => array() );
		?>
		<div class="stat-cards">
			<?php foreach ( $leave_types as $lt ) :
				$name_lower = strtolower( $lt->name );
				$style      = $default_style;
				foreach ( $leave_styles as $ls ) {
					foreach ( $ls['match'] as $needle ) {
						if ( false !== strpos( $name_lower, $needle ) ) {
							$style = $ls;
							break 2;
						}
					}
				}
				?>
				<div class="stat-card stat-card--<?php echo esc_attr( $style['tone'] ); ?>">
					<div class="stat-icon">
						<span class="dashicons dashicons-<?php echo esc_attr( $style['icon'] ); ?>"></span>
					</div>
					<div class="stat-body">
						<span class="stat-value"><?php echo (int) $lt->days_per_year; ?></span>
						<span class="stat-label"><?php echo esc_html( $lt->name ); ?></span>
						<span class="stat-sub"><?php esc_html_e( 'days per year', 'lime-micro-erp' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="row mt-3">
		<div class="col-lg-6 col-md-12">
			<div class="bg-light p-4 rounded shadow-sm mb-4">
				<h2 class="mb-3 mt-1"><?php esc_html_e( 'New Leave Request', 'lime-micro-erp' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'micro_erp_leave_request_save' ); ?>
					<input type="hidden" name="micro_erp_action" value="save_leave_request">
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

					<div class="mb-3">
						<label for="employee_id" class="form-label"><?php esc_html_e( 'Employee', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<select name="employee_id" id="employee_id" class="form-control" required>
							<option value="0"><?php esc_html_e( 'Select Employee', 'lime-micro-erp' ); ?></option>
							<?php foreach ( $employees as $emp ) : ?>
								<option value="<?php echo (int) $emp->id; ?>"><?php echo esc_html( $emp->employee_id . ' - ' . $emp->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="leave_type_id" class="form-label"><?php esc_html_e( 'Leave Type', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<select name="leave_type_id" id="leave_type_id" class="form-control" required>
							<option value="0"><?php esc_html_e( 'Select Type', 'lime-micro-erp' ); ?></option>
							<?php foreach ( $leave_types as $lt ) : ?>
								<option value="<?php echo (int) $lt->id; ?>"><?php echo esc_html( $lt->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="start_date" class="form-label"><?php esc_html_e( 'Start Date', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="date" name="start_date" id="start_date" class="form-control" required>
					</div>

					<div class="mb-3">
						<label for="end_date" class="form-label"><?php esc_html_e( 'End Date', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="date" name="end_date" id="end_date" class="form-control" required>
					</div>

					<div class="mb-3">
						<label for="reason" class="form-label"><?php esc_html_e( 'Reason', 'lime-micro-erp' ); ?></label>
						<textarea name="reason" id="reason" rows="2" class="form-control"></textarea>
					</div>

					<button type="submit" class="btn-success"><?php esc_html_e( 'Submit Request', 'lime-micro-erp' ); ?></button>
				</form>
			</div>
		</div>

		<div class="col-lg-6 col-md-12">
			<div class="bg-light p-4 rounded shadow-sm mb-4">
				<h2 class="mb-3 mt-1"><?php echo $editing_type ? esc_html__( 'Edit Leave Type', 'lime-micro-erp' ) : esc_html__( 'Leave Types', 'lime-micro-erp' ); ?></h2>

				<form method="post" action="" class="mb-3">
					<?php
					$action = $editing_type ? 'update_leave_type' : 'save_leave_type';
					wp_nonce_field( 'micro_erp_leave_type_save' );
					?>
					<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $editing_type ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $editing_type->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

					<div class="mb-3">
						<label for="lt_name" class="form-label"><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="text" name="name" id="lt_name" class="form-control" value="<?php echo $editing_type ? esc_attr( $editing_type->name ) : ''; ?>" required>
					</div>

					<div class="mb-3">
						<label for="days_per_year" class="form-label"><?php esc_html_e( 'Days / Year', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="number" name="days_per_year" id="days_per_year" min="0" class="form-control" value="<?php echo $editing_type ? (int) $editing_type->days_per_year : 0; ?>" required>
					</div>

					<div class="mb-3 form-check">
						<label><input type="checkbox" name="is_active" id="lt_active" <?php checked( $editing_type ? (int) $editing_type->is_active : 1 ); ?>> <?php esc_html_e( 'Active', 'lime-micro-erp' ); ?></label>
					</div>

					<div class="d-flex gap-2">
						<?php if ( $editing_type ) : ?>
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
						<?php endif; ?>
						<button type="submit" class="btn-save">
							<span class="dashicons dashicons-yes" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Type', 'lime-micro-erp' ); ?>
						</button>
					</div>
				</form>

				<table class="table table-striped table-hover table-bordered mb-0">
					<tbody class="bg-white">
						<?php foreach ( $leave_types as $lt ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $lt->name ); ?></strong></td>
								<td><?php echo (int) $lt->days_per_year; ?> <?php esc_html_e( 'days', 'lime-micro-erp' ); ?></td>
								<td><?php echo $lt->is_active ? '<span class="status-badge status-active">' . esc_html__( 'Active', 'lime-micro-erp' ) . '</span>' : '<span class="status-badge status-neutral">' . esc_html__( 'Off', 'lime-micro-erp' ) . '</span>'; // phpcs:ignore ?></td>
								<td width="130">
									<div class="pos-row-actions">
										<a href="<?php echo esc_url( micro_erp_admin_url( 'leave', array( 'edit_type' => $lt->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this leave type?', 'lime-micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_leave_type_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_leave_type">
											<input type="hidden" name="id" value="<?php echo (int) $lt->id; ?>">
											<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
											<button class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
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

	<div class="row mt-1">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'leave', __( 'Search Leave Requests', 'lime-micro-erp' ), __( 'Search by employee, ID or reason...', 'lime-micro-erp' ), array(), $search ); ?>
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Leave Requests', 'lime-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Employee', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'From', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'To', 'lime-micro-erp' ); ?></th>
								<th width="70"><?php esc_html_e( 'Days', 'lime-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'lime-micro-erp' ); ?></th>
								<th width="100"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
								<th width="180" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $requests ) ) : ?>
								<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No leave requests yet.', 'lime-micro-erp' ); ?></td></tr>
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
										<?php if ( 'pending' === $req->status ) : ?>
											<div class="pos-row-actions">
												<form method="post" action="" class="inline-form">
													<?php wp_nonce_field( 'micro_erp_leave_status' ); ?>
													<input type="hidden" name="micro_erp_action" value="approve_leave">
													<input type="hidden" name="id" value="<?php echo (int) $req->id; ?>">
													<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button class="pos-action edit"><?php esc_html_e( 'Approve', 'lime-micro-erp' ); ?></button>
												</form>
												<form method="post" action="" class="inline-form">
													<?php wp_nonce_field( 'micro_erp_leave_status' ); ?>
													<input type="hidden" name="micro_erp_action" value="reject_leave">
													<input type="hidden" name="id" value="<?php echo (int) $req->id; ?>">
													<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button class="pos-action delete"><?php esc_html_e( 'Reject', 'lime-micro-erp' ); ?></button>
												</form>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php micro_erp_render_pagination( 'leave', $total_items, $per_page ); ?>

			</div>
		</div>
	</div>
</div>
