<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = micro_erp_query_int( 'edit' );
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_departments WHERE id = %d", $edit_id ) );
}

$search = micro_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, micro_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_departments d WHERE d.name LIKE %s OR d.description LIKE %s",
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_departments d WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT d.*, (SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_employees e WHERE e.department_id = d.id) AS emp_count
			FROM {$wpdb->prefix}micro_erp_departments d
			WHERE d.name LIKE %s OR d.description LIKE %s
			ORDER BY d.name ASC LIMIT %d OFFSET %d",
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT d.*, (SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_employees e WHERE e.department_id = d.id) AS emp_count
			FROM {$wpdb->prefix}micro_erp_departments d
			ORDER BY d.name ASC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

micro_erp_print_admin_notice();

$back_url = micro_erp_admin_url( 'departments' );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Department', 'lime-micro-erp' ) : esc_html__( 'Departments', 'lime-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( micro_erp_admin_url( 'departments', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Department', 'lime-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || micro_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Department Details', 'lime-micro-erp' ); ?></h2>
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
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="description" class="form-label"><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></label>
							<textarea name="description" id="description" class="form-control"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
						</div>

						<div class="mb-3">
							<label for="status" class="form-label"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></label>
							<select name="status" id="status" class="form-control">
								<option value="active" <?php selected( $editing ? $editing->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'lime-micro-erp' ); ?></option>
								<option value="inactive" <?php selected( $editing ? $editing->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'lime-micro-erp' ); ?></option>
							</select>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Department', 'lime-micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'departments', __( 'Search Departments', 'lime-micro-erp' ), __( 'Search by name or description...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Departments', 'lime-micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Employees', 'lime-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No departments found.', 'lime-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
										<td><?php echo esc_html( $row->description ); ?></td>
										<td><?php echo (int) $row->emp_count; ?></td>
										<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( micro_erp_admin_url( 'departments', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this department?', 'lime-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'micro_erp_department_delete' ); ?>
													<input type="hidden" name="micro_erp_action" value="delete_department">
													<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
													<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php micro_erp_render_pagination( 'departments', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
