<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = oby_mi_erp_query_int( 'edit' );
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE id = %d", $edit_id ) );
}

$departments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_departments WHERE status = %s ORDER BY name ASC", 'active' ) );

$dept_filter = oby_mi_erp_query_int( 'department_id' );
$search      = oby_mi_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $dept_filter && $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE department_id = %d AND (name LIKE %s OR employee_id LIKE %s)",
			$dept_filter,
			$like,
			$like
		)
	);
} elseif ( $dept_filter ) {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE department_id = %d",
			$dept_filter
		)
	);
} elseif ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE name LIKE %s OR employee_id LIKE %s",
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $dept_filter && $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE department_id = %d AND (name LIKE %s OR employee_id LIKE %s) ORDER BY employee_id ASC LIMIT %d OFFSET %d",
			$dept_filter,
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} elseif ( $dept_filter ) {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE department_id = %d ORDER BY employee_id ASC LIMIT %d OFFSET %d",
			$dept_filter,
			$per_page,
			$offset
		)
	);
} elseif ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees WHERE name LIKE %s OR employee_id LIKE %s ORDER BY employee_id ASC LIMIT %d OFFSET %d",
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_employees ORDER BY employee_id ASC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

oby_mi_erp_print_admin_notice();

$back_url = oby_mi_erp_admin_url( 'employees' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Employee', 'obydullah-micro-erp' ) : esc_html__( 'Employees', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'employees', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Employee', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<form method="post" action="">
			<?php
			$action = $editing ? 'update_employee' : 'save_employee';
			wp_nonce_field( 'oby_mi_erp_employee_save' );
			?>
			<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $action ); ?>">
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
			<?php endif; ?>
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Basic Information', 'obydullah-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="employee_id" class="form-label"><?php esc_html_e( 'Employee ID', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="employee_id" id="employee_id" class="form-control" value="<?php echo $editing ? esc_attr( $editing->employee_id ) : esc_attr( oby_mi_erp_next_employee_id() ); ?>" <?php echo $editing ? '' : 'readonly'; ?> style="background:#f9f9f9;">
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Full Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="email" class="form-label"><?php esc_html_e( 'Email', 'obydullah-micro-erp' ); ?></label>
							<input type="email" name="email" id="email" class="form-control" value="<?php echo $editing ? esc_attr( $editing->email ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="phone" class="form-label"><?php esc_html_e( 'Phone', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="phone" id="phone" class="form-control" value="<?php echo $editing ? esc_attr( $editing->phone ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="gender" class="form-label"><?php esc_html_e( 'Gender', 'obydullah-micro-erp' ); ?></label>
							<select name="gender" id="gender" class="form-control">
								<option value=""><?php esc_html_e( 'Select', 'obydullah-micro-erp' ); ?></option>
								<?php foreach ( array( 'male', 'female', 'other' ) as $g ) : ?>
									<option value="<?php echo esc_attr( $g ); ?>" <?php selected( $editing ? $editing->gender : '', $g ); ?>><?php echo esc_html( ucfirst( $g ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="date_of_birth" class="form-label"><?php esc_html_e( 'Date of Birth', 'obydullah-micro-erp' ); ?></label>
							<input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="<?php echo $editing ? esc_attr( $editing->date_of_birth ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="address" class="form-label"><?php esc_html_e( 'Address', 'obydullah-micro-erp' ); ?></label>
							<textarea name="address" id="address" class="form-control"><?php echo $editing ? esc_textarea( $editing->address ) : ''; ?></textarea>
						</div>
					</div>
				</div>

				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Job Information', 'obydullah-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="department_id" class="form-label"><?php esc_html_e( 'Department', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="department_id" id="department_id" class="form-control" required>
								<option value="0"><?php esc_html_e( 'Select Department', 'obydullah-micro-erp' ); ?></option>
								<?php foreach ( $departments as $dept ) : ?>
									<option value="<?php echo (int) $dept->id; ?>" <?php selected( $editing ? $editing->department_id : 0, $dept->id ); ?>><?php echo esc_html( $dept->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="designation" class="form-label"><?php esc_html_e( 'Designation', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="designation" id="designation" class="form-control" value="<?php echo $editing ? esc_attr( $editing->designation ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="date_of_join" class="form-label"><?php esc_html_e( 'Date of Join', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="date_of_join" id="date_of_join" class="form-control" value="<?php echo $editing ? esc_attr( $editing->date_of_join ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="status" class="form-label"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="status" id="status" class="form-control" required>
								<?php foreach ( array( 'active', 'inactive', 'terminated' ) as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $editing ? $editing->status : 'active', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="basic_salary" class="form-label"><?php esc_html_e( 'Basic Salary', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="number" name="basic_salary" id="basic_salary" class="form-control" step="0.01" min="0" value="<?php echo $editing ? esc_attr( $editing->basic_salary ) : ''; ?>" required>
							<div class="form-text"><?php esc_html_e( 'Monthly basic salary', 'obydullah-micro-erp' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<div class="d-flex mt-2 mb-4">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
				<button type="submit" class="btn-success"><?php esc_html_e( 'Save Employee', 'obydullah-micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Employees', 'obydullah-micro-erp' ); ?>
					</h2>

					<!-- Search Box -->
					<form method="get" action="" class="search-section mb-3">
						<input type="hidden" name="page" value="oby-mi-erp/employees">
						<input type="hidden" name="department_id" value="<?php echo (int) $dept_filter; ?>">
						<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
							<label for="employee-search" class="form-label mb-0"><?php esc_html_e( 'Search Employees', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="s" id="employee-search" class="form-control form-control-sm search-field" placeholder="<?php esc_attr_e( 'Search employees...', 'obydullah-micro-erp' ); ?>" value="<?php echo esc_attr( $search ); ?>">
							<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>

							<?php
							$pill_args = array();
							if ( $search ) {
								$pill_args['s'] = $search;
							}
							$all_url = oby_mi_erp_admin_url( 'employees', $pill_args );
							?>
							<div class="filter-pills ml-auto" role="group" aria-label="<?php esc_attr_e( 'Filter by department', 'obydullah-micro-erp' ); ?>">
								<a href="<?php echo esc_url( $all_url ); ?>" class="<?php echo esc_attr( ! $dept_filter ? 'active' : '' ); ?>"><?php esc_html_e( 'All Departments', 'obydullah-micro-erp' ); ?></a>
								<?php foreach ( $departments as $dept ) : ?>
									<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'employees', array_merge( $pill_args, array( 'department_id' => (int) $dept->id ) ) ) ); ?>" class="<?php echo esc_attr( $dept_filter === (int) $dept->id ? 'active' : '' ); ?>"><?php echo esc_html( $dept->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					</form>

					<!-- Employees Table -->
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Designation', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Join Date', 'obydullah-micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Salary', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="160" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No employees found.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><?php echo esc_html( $row->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $row->name ); ?></strong><br><small class="text-muted"><?php echo esc_html( $row->email ); ?></small></td>
										<td><?php echo esc_html( oby_mi_erp_department_name( $row->department_id ) ); ?></td>
										<td><?php echo esc_html( $row->designation ); ?></td>
										<td><?php echo esc_html( $row->date_of_join ); ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( oby_mi_erp_format_money( $row->basic_salary ) ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'employees', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'salary', array( 'month' => current_time( 'Y-m' ) ) ) ); ?>" class="pos-action edit"><?php esc_html_e( 'Salary', 'obydullah-micro-erp' ); ?></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this employee?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_employee_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_employee">
													<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'employees', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
