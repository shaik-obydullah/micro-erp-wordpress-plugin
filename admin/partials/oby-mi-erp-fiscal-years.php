<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = oby_mi_erp_query_int( 'edit' );
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_fiscal_years WHERE id = %d", $edit_id ) );
}

$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_fiscal_years ORDER BY start_date DESC" );

oby_mi_erp_print_admin_notice();

$back_url = oby_mi_erp_admin_url( 'fiscal-years' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Fiscal Year', 'obydullah-micro-erp' ) : esc_html__( 'Fiscal Years', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'fiscal-years', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Fiscal Year', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Fiscal Year Details', 'obydullah-micro-erp' ); ?></h2>
					<form method="post" action="">
						<?php
						$action = $editing ? 'save_fiscal_year' : 'save_fiscal_year';
						wp_nonce_field( 'oby_mi_erp_fiscal_year_save' );
						?>
						<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $action ); ?>">
						<?php if ( $editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" placeholder="e.g. FY 2025-2026" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="start_date" class="form-label"><?php esc_html_e( 'Start Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $editing ? esc_attr( $editing->start_date ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="end_date" class="form-label"><?php esc_html_e( 'End Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $editing ? esc_attr( $editing->end_date ) : ''; ?>" required>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Fiscal Year', 'obydullah-micro-erp' ); ?></button>
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
						<?php esc_html_e( 'All Fiscal Years', 'obydullah-micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Start Date', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'End Date', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="170" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No fiscal years found.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $row ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
										<td><?php echo esc_html( $row->start_date ); ?></td>
										<td><?php echo esc_html( $row->end_date ); ?></td>
										<td><?php echo $row->is_active ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge status-neutral">Closed</span>'; // phpcs:ignore ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'fiscal-years', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<?php if ( ! $row->is_active ) : ?>
													<form method="post" action="" class="inline-form">
														<?php wp_nonce_field( 'oby_mi_erp_fiscal_year_activate' ); ?>
														<input type="hidden" name="oby_mi_erp_action" value="activate_fiscal_year">
														<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
														<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
														<button type="submit" class="pos-action activate"><?php esc_html_e( 'Activate', 'obydullah-micro-erp' ); ?></button>
													</form>
												<?php endif; ?>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this fiscal year?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_fiscal_year_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_fiscal_year">
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
				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
