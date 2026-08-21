<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'employees' ) . " WHERE id = %d", $edit_id ) );
}

$departments = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'departments' ) . " WHERE status = 'active' ORDER BY name ASC" );

$dept_filter = isset( $_GET['department_id'] ) ? (int) $_GET['department_id'] : 0;
$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$where = ' WHERE 1=1';
$args  = array();
if ( $dept_filter ) {
	$where .= ' AND department_id = %d';
	$args[] = $dept_filter;
}
if ( $search ) {
	$where .= ' AND (name LIKE %s OR employee_id LIKE %s)';
	$like   = '%' . $wpdb->esc_like( $search ) . '%';
	$args[] = $like;
	$args[] = $like;
}

$query = "SELECT * FROM " . micro_erp_table( 'employees' ) . $where . " ORDER BY employee_id ASC";
$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/employees' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Employee', 'micro-erp' ) : esc_html__( 'Employees', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Employee', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<form method="post" action="">
			<?php
			$action = $editing ? 'update_employee' : 'save_employee';
			wp_nonce_field( 'micro_erp_employee_save' );
			?>
			<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
			<?php endif; ?>
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Basic Information', 'micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="employee_id" class="form-label"><?php esc_html_e( 'Employee ID', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="employee_id" id="employee_id" class="form-control" value="<?php echo $editing ? esc_attr( $editing->employee_id ) : esc_attr( micro_erp_next_employee_id() ); ?>" <?php echo $editing ? '' : 'readonly'; ?> style="background:#f9f9f9;">
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Full Name', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="email" class="form-label"><?php esc_html_e( 'Email', 'micro-erp' ); ?></label>
							<input type="email" name="email" id="email" class="form-control" value="<?php echo $editing ? esc_attr( $editing->email ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="phone" class="form-label"><?php esc_html_e( 'Phone', 'micro-erp' ); ?></label>
							<input type="text" name="phone" id="phone" class="form-control" value="<?php echo $editing ? esc_attr( $editing->phone ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="gender" class="form-label"><?php esc_html_e( 'Gender', 'micro-erp' ); ?></label>
							<select name="gender" id="gender" class="form-control">
								<option value=""><?php esc_html_e( 'Select', 'micro-erp' ); ?></option>
								<?php foreach ( array( 'male', 'female', 'other' ) as $g ) : ?>
									<option value="<?php echo esc_attr( $g ); ?>" <?php selected( $editing ? $editing->gender : '', $g ); ?>><?php echo esc_html( ucfirst( $g ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="date_of_birth" class="form-label"><?php esc_html_e( 'Date of Birth', 'micro-erp' ); ?></label>
							<input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="<?php echo $editing ? esc_attr( $editing->date_of_birth ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="address" class="form-label"><?php esc_html_e( 'Address', 'micro-erp' ); ?></label>
							<textarea name="address" id="address" class="form-control"><?php echo $editing ? esc_textarea( $editing->address ) : ''; ?></textarea>
						</div>
					</div>
				</div>

				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Job Information', 'micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="department_id" class="form-label"><?php esc_html_e( 'Department', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="department_id" id="department_id" class="form-control" required>
								<option value="0"><?php esc_html_e( 'Select Department', 'micro-erp' ); ?></option>
								<?php foreach ( $departments as $dept ) : ?>
									<option value="<?php echo (int) $dept->id; ?>" <?php selected( $editing ? $editing->department_id : 0, $dept->id ); ?>><?php echo esc_html( $dept->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="designation" class="form-label"><?php esc_html_e( 'Designation', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="designation" id="designation" class="form-control" value="<?php echo $editing ? esc_attr( $editing->designation ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="date_of_join" class="form-label"><?php esc_html_e( 'Date of Join', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="date_of_join" id="date_of_join" class="form-control" value="<?php echo $editing ? esc_attr( $editing->date_of_join ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="status" class="form-label"><?php esc_html_e( 'Status', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="status" id="status" class="form-control" required>
								<?php foreach ( array( 'active', 'inactive', 'terminated' ) as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $editing ? $editing->status : 'active', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="basic_salary" class="form-label"><?php esc_html_e( 'Basic Salary', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="number" name="basic_salary" id="basic_salary" class="form-control" step="0.01" min="0" value="<?php echo $editing ? esc_attr( $editing->basic_salary ) : ''; ?>" required>
							<div class="form-text"><?php esc_html_e( 'Monthly basic salary', 'micro-erp' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<div class="d-flex mt-2 mb-4">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn-primary"><?php esc_html_e( 'Save Employee', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Employees', 'micro-erp' ); ?>
					</h2>

					<!-- Search Box -->
					<form method="get" action="" class="search-section mb-3">
						<input type="hidden" name="page" value="micro-erp/employees">
						<div class="d-flex flex-wrap align-items-center gap-2">
							<div class="search-group flex-grow-1">
								<label for="employee-search" class="form-label mb-1"><?php esc_html_e( 'Search Employees', 'micro-erp' ); ?></label>
								<div class="d-flex align-items-center gap-2">
									<select name="department_id" class="form-control form-control-sm" style="max-width:200px;">
										<option value="0"><?php esc_html_e( 'All Departments', 'micro-erp' ); ?></option>
										<?php foreach ( $departments as $dept ) : ?>
											<option value="<?php echo (int) $dept->id; ?>" <?php selected( $dept_filter, $dept->id ); ?>><?php echo esc_html( $dept->name ); ?></option>
										<?php endforeach; ?>
									</select>
									<input type="text" name="s" id="employee-search" class="form-control form-control-sm" placeholder="<?php esc_attr_e( 'Search employees...', 'micro-erp' ); ?>" value="<?php echo esc_attr( $search ); ?>">
									<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
								</div>
								<div class="form-text"><?php esc_html_e( 'Search by name or employee ID', 'micro-erp' ); ?></div>
							</div>
						</div>
					</form>

					<!-- Employees Table -->
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Emp ID', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Designation', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Join Date', 'micro-erp' ); ?></th>
									<th width="110" class="text-right"><?php esc_html_e( 'Salary', 'micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
									<th width="160" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No employees found.', 'micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><?php echo esc_html( $row->employee_id ); ?></td>
										<td><strong><?php echo esc_html( $row->name ); ?></strong><br><small class="text-muted"><?php echo esc_html( $row->email ); ?></small></td>
										<td><?php echo esc_html( micro_erp_department_name( $row->department_id ) ); ?></td>
										<td><?php echo esc_html( $row->designation ); ?></td>
										<td><?php echo esc_html( $row->date_of_join ); ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( micro_erp_format_money( $row->basic_salary ) ); ?></td>
										<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="pos-action edit"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
												<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => current_time( 'Y-m' ) ), admin_url( 'admin.php' ) ) ); ?>" class="pos-action edit"><?php esc_html_e( 'Salary', 'micro-erp' ); ?></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this employee?', 'micro-erp' ); ?>');">
													<?php wp_nonce_field( 'micro_erp_employee_delete' ); ?>
													<input type="hidden" name="micro_erp_action" value="delete_employee">
													<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
													<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button type="submit" class="pos-action delete"><?php esc_html_e( 'Delete', 'micro-erp' ); ?></button>
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
		</div>

	<?php endif; ?>
</div>
