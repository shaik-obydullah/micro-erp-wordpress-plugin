<?php
/**
 * Renders the Departments admin screen and its add/edit form.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_edit_id = oby_mi_erp_query_int( 'edit' );
$oby_mi_erp_editing = null;
if ( $oby_mi_erp_edit_id ) {
	$oby_mi_erp_editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_departments WHERE id = %d", $oby_mi_erp_edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
}

$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_departments d WHERE d.name LIKE %s OR d.description LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_departments d WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT d.*, (SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE e.department_id = d.id) AS emp_count
			FROM {$wpdb->prefix}oby_mi_erp_departments d
			WHERE d.name LIKE %s OR d.description LIKE %s
			ORDER BY d.name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT d.*, (SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_employees e WHERE e.department_id = d.id) AS emp_count
			FROM {$wpdb->prefix}oby_mi_erp_departments d
			ORDER BY d.name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'departments' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $oby_mi_erp_editing ? esc_html__( 'Edit Department', 'obydullah-micro-erp' ) : esc_html__( 'Departments', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $oby_mi_erp_editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'departments', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Department', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Department Details', 'obydullah-micro-erp' ); ?></h2>
					<form method="post" action="">
						<?php
						$form_action = $oby_mi_erp_editing ? 'update_department' : 'save_department';
						wp_nonce_field( 'oby_mi_erp_department_save' );
						?>
						<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $form_action ); ?>">
						<?php if ( $oby_mi_erp_editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="description" class="form-label"><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></label>
							<textarea name="description" id="description" class="form-control"><?php echo $oby_mi_erp_editing ? esc_textarea( $oby_mi_erp_editing->description ) : ''; ?></textarea>
						</div>

						<div class="mb-3">
							<label for="status" class="form-label"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></label>
							<select name="status" id="status" class="form-control">
								<option value="active" <?php selected( $oby_mi_erp_editing ? $oby_mi_erp_editing->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'obydullah-micro-erp' ); ?></option>
								<option value="inactive" <?php selected( $oby_mi_erp_editing ? $oby_mi_erp_editing->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'obydullah-micro-erp' ); ?></option>
							</select>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Department', 'obydullah-micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'departments', __( 'Search Departments', 'obydullah-micro-erp' ), __( 'Search by name or description...', 'obydullah-micro-erp' ), array(), $oby_mi_erp_search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Departments', 'obydullah-micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Employees', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
									<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No departments found.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $oby_mi_erp_row->name ); ?></strong></td>
										<td><?php echo esc_html( $oby_mi_erp_row->description ); ?></td>
										<td><?php echo (int) $oby_mi_erp_row->emp_count; ?></td>
										<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'departments', array( 'edit' => $oby_mi_erp_row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this department?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_department_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_department">
													<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_row->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'departments', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
