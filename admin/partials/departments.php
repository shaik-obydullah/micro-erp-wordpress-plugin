<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'departments' ) . " WHERE id = %d", $edit_id ) );
}

$rows = $wpdb->get_results(
	"SELECT d.*, (SELECT COUNT(*) FROM " . micro_erp_table( 'employees' ) . " e WHERE e.department_id = d.id) AS emp_count
	FROM " . micro_erp_table( 'departments' ) . " d ORDER BY d.name ASC"
);

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/departments' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Department', 'micro-erp' ) : esc_html__( 'Departments', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Department', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Department Details', 'micro-erp' ); ?></h2>
					<form method="post" action="">
						<?php
						$action = $editing ? 'update_department' : 'save_department';
						wp_nonce_field( 'micro_erp_department_save' );
						?>
						<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
						<?php if ( $editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="description" class="form-label"><?php esc_html_e( 'Description', 'micro-erp' ); ?></label>
							<textarea name="description" id="description" class="form-control"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
						</div>

						<div class="mb-3">
							<label for="status" class="form-label"><?php esc_html_e( 'Status', 'micro-erp' ); ?></label>
							<select name="status" id="status" class="form-control">
								<option value="active" <?php selected( $editing ? $editing->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'micro-erp' ); ?></option>
								<option value="inactive" <?php selected( $editing ? $editing->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'micro-erp' ); ?></option>
							</select>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
							<button type="submit" class="btn-primary"><?php esc_html_e( 'Save Department', 'micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Departments', 'micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Employees', 'micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No departments found.', 'micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
										<td><?php echo esc_html( $row->description ); ?></td>
										<td><?php echo (int) $row->emp_count; ?></td>
										<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="pos-action edit"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this department?', 'micro-erp' ); ?>');">
													<?php wp_nonce_field( 'micro_erp_department_delete' ); ?>
													<input type="hidden" name="micro_erp_action" value="delete_department">
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
