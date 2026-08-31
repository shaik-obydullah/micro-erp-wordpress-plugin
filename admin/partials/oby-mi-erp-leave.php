<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_leave_types_key = 'oby_mi_erp_list_leave_types';
$oby_mi_erp_leave_types = wp_cache_get( $oby_mi_erp_leave_types_key, 'oby_mi_erp' );
if ( false === $oby_mi_erp_leave_types ) {
	global $wpdb;
	$oby_mi_erp_leave_types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_leave_types ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached below via literal wp_cache_set().
	wp_cache_set( $oby_mi_erp_leave_types_key, $oby_mi_erp_leave_types, 'oby_mi_erp' );
	if ( function_exists( 'oby_mi_erp_cache_register' ) ) {
		oby_mi_erp_cache_register( $oby_mi_erp_leave_types_key );
	}
}

$oby_mi_erp_employees_key = 'oby_mi_erp_list_employees_active';
$oby_mi_erp_employees = wp_cache_get( $oby_mi_erp_employees_key, 'oby_mi_erp' );
if ( false === $oby_mi_erp_employees ) {
	global $wpdb;
	$oby_mi_erp_employees = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE status = %s ORDER BY name ASC", 'active' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached below via literal wp_cache_set().
	wp_cache_set( $oby_mi_erp_employees_key, $oby_mi_erp_employees, 'oby_mi_erp' );
	if ( function_exists( 'oby_mi_erp_cache_register' ) ) {
		oby_mi_erp_cache_register( $oby_mi_erp_employees_key );
	}
}

$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_employees e ON e.id = lr.employee_id
			WHERE lr.reason LIKE %s OR e.name LIKE %s OR e.employee_id LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_employees e ON e.id = lr.employee_id
			WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_requests = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT lr.* FROM {$wpdb->prefix}oby_mi_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_employees e ON e.id = lr.employee_id
			WHERE lr.reason LIKE %s OR e.name LIKE %s OR e.employee_id LIKE %s
			ORDER BY lr.created_at DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_requests = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT lr.* FROM {$wpdb->prefix}oby_mi_erp_leave_requests lr
			LEFT JOIN {$wpdb->prefix}oby_mi_erp_employees e ON e.id = lr.employee_id
			ORDER BY lr.created_at DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

$oby_mi_erp_edit_type_id = oby_mi_erp_query_int( 'edit_type' );
$oby_mi_erp_editing_type = null;
if ( $oby_mi_erp_edit_type_id ) {
	$oby_mi_erp_editing_type = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_leave_types WHERE id = %d", $oby_mi_erp_edit_type_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
}

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'leave' );

oby_mi_erp_print_admin_notice();
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Leave Management', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $oby_mi_erp_leave_types ) ) :
		$oby_mi_erp_leave_styles = array(
			array( 'icon' => 'palmtree',       'tone' => 'green', 'match' => array( 'annual', 'earned', 'vacation' ) ),
			array( 'icon' => 'smiley',         'tone' => 'blue',  'match' => array( 'casual', 'personal' ) ),
			array( 'icon' => 'shield-alt2',    'tone' => 'red',   'match' => array( 'sick', 'medical' ) ),
			array( 'icon' => 'admin-users',    'tone' => 'amber', 'match' => array( 'matern', 'patern', 'parent' ) ),
		);
		$oby_mi_erp_default_style = array( 'icon' => 'calendar-alt', 'tone' => 'blue', 'match' => array() );
		?>
		<div class="stat-cards">
			<?php foreach ( $oby_mi_erp_leave_types as $oby_mi_erp_lt ) :
				$oby_mi_erp_name_lower = strtolower( $oby_mi_erp_lt->name );
				$oby_mi_erp_style      = $oby_mi_erp_default_style;
				foreach ( $oby_mi_erp_leave_styles as $oby_mi_erp_ls ) {
					foreach ( $oby_mi_erp_ls['match'] as $oby_mi_erp_needle ) {
						if ( false !== strpos( $oby_mi_erp_name_lower, $oby_mi_erp_needle ) ) {
							$oby_mi_erp_style = $oby_mi_erp_ls;
							break 2;
						}
					}
				}
				?>
				<div class="stat-card stat-card--<?php echo esc_attr( $oby_mi_erp_style['tone'] ); ?>">
					<div class="stat-icon">
						<span class="dashicons dashicons-<?php echo esc_attr( $oby_mi_erp_style['icon'] ); ?>"></span>
					</div>
					<div class="stat-body">
						<span class="stat-value"><?php echo (int) $oby_mi_erp_lt->days_per_year; ?></span>
						<span class="stat-label"><?php echo esc_html( $oby_mi_erp_lt->name ); ?></span>
						<span class="stat-sub"><?php esc_html_e( 'days per year', 'obydullah-micro-erp' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="row mt-3">
		<div class="col-lg-6 col-md-12">
			<div class="bg-light p-4 rounded shadow-sm mb-4">
				<h2 class="mb-3 mt-1"><?php esc_html_e( 'New Leave Request', 'obydullah-micro-erp' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'oby_mi_erp_leave_request_save' ); ?>
					<input type="hidden" name="oby_mi_erp_action" value="save_leave_request">
					<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

					<div class="mb-3">
						<label for="employee_id" class="form-label"><?php esc_html_e( 'Employee', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<select name="employee_id" id="employee_id" class="form-control" required>
							<option value="0"><?php esc_html_e( 'Select Employee', 'obydullah-micro-erp' ); ?></option>
							<?php foreach ( $oby_mi_erp_employees as $oby_mi_erp_emp ) : ?>
								<option value="<?php echo (int) $oby_mi_erp_emp->id; ?>"><?php echo esc_html( $oby_mi_erp_emp->employee_id . ' - ' . $oby_mi_erp_emp->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="leave_type_id" class="form-label"><?php esc_html_e( 'Leave Type', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<select name="leave_type_id" id="leave_type_id" class="form-control" required>
							<option value="0"><?php esc_html_e( 'Select Type', 'obydullah-micro-erp' ); ?></option>
							<?php foreach ( $oby_mi_erp_leave_types as $oby_mi_erp_lt ) : ?>
								<option value="<?php echo (int) $oby_mi_erp_lt->id; ?>"><?php echo esc_html( $oby_mi_erp_lt->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="start_date" class="form-label"><?php esc_html_e( 'Start Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="date" name="start_date" id="start_date" class="form-control" required>
					</div>

					<div class="mb-3">
						<label for="end_date" class="form-label"><?php esc_html_e( 'End Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="date" name="end_date" id="end_date" class="form-control" required>
					</div>

					<div class="mb-3">
						<label for="reason" class="form-label"><?php esc_html_e( 'Reason', 'obydullah-micro-erp' ); ?></label>
						<textarea name="reason" id="reason" rows="2" class="form-control"></textarea>
					</div>

					<button type="submit" class="btn-success"><?php esc_html_e( 'Submit Request', 'obydullah-micro-erp' ); ?></button>
				</form>
			</div>
		</div>

		<div class="col-lg-6 col-md-12">
			<div class="bg-light p-4 rounded shadow-sm mb-4">
				<h2 class="mb-3 mt-1"><?php echo $oby_mi_erp_editing_type ? esc_html__( 'Edit Leave Type', 'obydullah-micro-erp' ) : esc_html__( 'Leave Types', 'obydullah-micro-erp' ); ?></h2>

				<form method="post" action="" class="mb-3">
					<?php
					$action = $oby_mi_erp_editing_type ? 'update_leave_type' : 'save_leave_type';
					wp_nonce_field( 'oby_mi_erp_leave_type_save' );
					?>
					<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $oby_mi_erp_editing_type ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_editing_type->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

					<div class="mb-3">
						<label for="lt_name" class="form-label"><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="text" name="name" id="lt_name" class="form-control" value="<?php echo $oby_mi_erp_editing_type ? esc_attr( $oby_mi_erp_editing_type->name ) : ''; ?>" required>
					</div>

					<div class="mb-3">
						<label for="days_per_year" class="form-label"><?php esc_html_e( 'Days / Year', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
						<input type="number" name="days_per_year" id="days_per_year" min="0" class="form-control" value="<?php echo $oby_mi_erp_editing_type ? (int) $oby_mi_erp_editing_type->days_per_year : 0; ?>" required>
					</div>

					<div class="mb-3 form-check">
						<label><input type="checkbox" name="is_active" id="lt_active" <?php checked( $oby_mi_erp_editing_type ? (int) $oby_mi_erp_editing_type->is_active : 1 ); ?>> <?php esc_html_e( 'Active', 'obydullah-micro-erp' ); ?></label>
					</div>

					<div class="d-flex gap-2">
						<?php if ( $oby_mi_erp_editing_type ) : ?>
							<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
						<?php endif; ?>
						<button type="submit" class="btn-save">
							<span class="dashicons dashicons-yes" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Type', 'obydullah-micro-erp' ); ?>
						</button>
					</div>
				</form>

				<table class="table table-striped table-hover table-bordered mb-0">
					<tbody class="bg-white">
						<?php foreach ( $oby_mi_erp_leave_types as $oby_mi_erp_lt ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $oby_mi_erp_lt->name ); ?></strong></td>
								<td><?php echo (int) $oby_mi_erp_lt->days_per_year; ?> <?php esc_html_e( 'days', 'obydullah-micro-erp' ); ?></td>
								<td><?php echo $oby_mi_erp_lt->is_active ? '<span class="status-badge status-active">' . esc_html__( 'Active', 'obydullah-micro-erp' ) . '</span>' : '<span class="status-badge status-neutral">' . esc_html__( 'Off', 'obydullah-micro-erp' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td width="130">
									<div class="pos-row-actions">
										<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'leave', array( 'edit_type' => $oby_mi_erp_lt->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this leave type?', 'obydullah-micro-erp' ); ?>');">
											<?php wp_nonce_field( 'oby_mi_erp_leave_type_delete' ); ?>
											<input type="hidden" name="oby_mi_erp_action" value="delete_leave_type">
											<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_lt->id; ?>">
											<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
											<button class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
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
			<?php oby_mi_erp_render_search_bar( 'leave', __( 'Search Leave Requests', 'obydullah-micro-erp' ), __( 'Search by employee, ID or reason...', 'obydullah-micro-erp' ), array(), $oby_mi_erp_search ); ?>
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Leave Requests', 'obydullah-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th><?php esc_html_e( 'Employee', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'From', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'To', 'obydullah-micro-erp' ); ?></th>
								<th width="70"><?php esc_html_e( 'Days', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'obydullah-micro-erp' ); ?></th>
								<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
								<th width="180" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $oby_mi_erp_requests ) ) : ?>
								<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No leave requests yet.', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $oby_mi_erp_requests as $oby_mi_erp_req ) : ?>
								<tr>
									<td><strong><?php echo esc_html( oby_mi_erp_employee_name( $oby_mi_erp_req->employee_id ) ); ?></strong></td>
									<td><?php echo esc_html( oby_mi_erp_leave_type_name( $oby_mi_erp_req->leave_type_id ) ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_req->start_date ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_req->end_date ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_req->total_days ); ?></td>
									<td><?php echo esc_html( $oby_mi_erp_req->reason ); ?></td>
									<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_req->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
									<td>
										<?php if ( 'pending' === $oby_mi_erp_req->status ) : ?>
											<div class="pos-row-actions">
												<form method="post" action="" class="inline-form">
													<?php wp_nonce_field( 'oby_mi_erp_leave_status' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="approve_leave">
													<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_req->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
													<button class="pos-action edit"><?php esc_html_e( 'Approve', 'obydullah-micro-erp' ); ?></button>
												</form>
												<form method="post" action="" class="inline-form">
													<?php wp_nonce_field( 'oby_mi_erp_leave_status' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="reject_leave">
													<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_req->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
													<button class="pos-action delete"><?php esc_html_e( 'Reject', 'obydullah-micro-erp' ); ?></button>
												</form>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php oby_mi_erp_render_pagination( 'leave', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

			</div>
		</div>
	</div>
</div>
