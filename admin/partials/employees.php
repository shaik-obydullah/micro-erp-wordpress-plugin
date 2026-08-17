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
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Employee', 'micro-erp' ) : esc_html__( 'Employees', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ Add Employee', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

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

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Basic Information', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="employee_id"><?php esc_html_e( 'Employee ID', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="employee_id" id="employee_id" value="<?php echo $editing ? esc_attr( $editing->employee_id ) : esc_attr( micro_erp_next_employee_id() ); ?>" <?php echo $editing ? '' : 'readonly'; ?> style="background:#f9f9f9;"></td>
						</tr>
						<tr>
							<th><label for="name"><?php esc_html_e( 'Full Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="name" id="name" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="email"><?php esc_html_e( 'Email', 'micro-erp' ); ?></label></th>
							<td><input type="email" name="email" id="email" value="<?php echo $editing ? esc_attr( $editing->email ) : ''; ?>"></td>
						</tr>
						<tr>
							<th><label for="phone"><?php esc_html_e( 'Phone', 'micro-erp' ); ?></label></th>
							<td><input type="text" name="phone" id="phone" value="<?php echo $editing ? esc_attr( $editing->phone ) : ''; ?>"></td>
						</tr>
						<tr>
							<th><label for="gender"><?php esc_html_e( 'Gender', 'micro-erp' ); ?></label></th>
							<td>
								<select name="gender" id="gender">
									<option value=""><?php esc_html_e( 'Select', 'micro-erp' ); ?></option>
									<?php foreach ( array( 'male', 'female', 'other' ) as $g ) : ?>
										<option value="<?php echo esc_attr( $g ); ?>" <?php selected( $editing ? $editing->gender : '', $g ); ?>><?php echo esc_html( ucfirst( $g ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="date_of_birth"><?php esc_html_e( 'Date of Birth', 'micro-erp' ); ?></label></th>
							<td><input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo $editing ? esc_attr( $editing->date_of_birth ) : ''; ?>"></td>
						</tr>
						<tr>
							<th><label for="address"><?php esc_html_e( 'Address', 'micro-erp' ); ?></label></th>
							<td><textarea name="address" id="address"><?php echo $editing ? esc_textarea( $editing->address ) : ''; ?></textarea></td>
						</tr>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Job Information', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="department_id"><?php esc_html_e( 'Department', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="department_id" id="department_id" required>
									<option value="0"><?php esc_html_e( 'Select Department', 'micro-erp' ); ?></option>
									<?php foreach ( $departments as $dept ) : ?>
										<option value="<?php echo (int) $dept->id; ?>" <?php selected( $editing ? $editing->department_id : 0, $dept->id ); ?>><?php echo esc_html( $dept->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="designation"><?php esc_html_e( 'Designation', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="designation" id="designation" value="<?php echo $editing ? esc_attr( $editing->designation ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="date_of_join"><?php esc_html_e( 'Date of Join', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="date_of_join" id="date_of_join" value="<?php echo $editing ? esc_attr( $editing->date_of_join ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="status"><?php esc_html_e( 'Status', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="status" id="status" required>
									<?php foreach ( array( 'active', 'inactive', 'terminated' ) as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $editing ? $editing->status : 'active', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Salary Information', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="basic_salary"><?php esc_html_e( 'Basic Salary', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<input type="number" name="basic_salary" id="basic_salary" step="0.01" min="0" value="<?php echo $editing ? esc_attr( $editing->basic_salary ) : ''; ?>" required>
								<small style="display:block;color:#646970;margin-top:4px;"><?php esc_html_e( 'Monthly basic salary', 'micro-erp' ); ?></small>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="actions-bar">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Employee', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<form method="get" action="" class="filter-bar">
			<input type="hidden" name="page" value="micro-erp/employees">
			<select name="department_id">
				<option value="0"><?php esc_html_e( 'All Departments', 'micro-erp' ); ?></option>
				<?php foreach ( $departments as $dept ) : ?>
					<option value="<?php echo (int) $dept->id; ?>" <?php selected( $dept_filter, $dept->id ); ?>><?php echo esc_html( $dept->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search employees...', 'micro-erp' ); ?>" value="<?php echo esc_attr( $search ); ?>">
			<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
		</form>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Emp ID', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Department', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Designation', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Join Date', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Salary', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="8"><?php esc_html_e( 'No employees found.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->employee_id ); ?></td>
								<td><strong><?php echo esc_html( $row->name ); ?></strong><br><small style="color:#646970;"><?php echo esc_html( $row->email ); ?></small></td>
								<td><?php echo esc_html( micro_erp_department_name( $row->department_id ) ); ?></td>
								<td><?php echo esc_html( $row->designation ); ?></td>
								<td><?php echo esc_html( $row->date_of_join ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->basic_salary ) ); ?></td>
								<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/salary', 'month' => current_time( 'Y-m' ) ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-success btn-sm"><?php esc_html_e( 'Salary', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this employee?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_employee_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_employee">
											<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
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

	<?php endif; ?>
</div>
