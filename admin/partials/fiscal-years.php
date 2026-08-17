<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'fiscal_years' ) . " WHERE id = %d", $edit_id ) );
}

$rows = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'fiscal_years' ) . " ORDER BY start_date DESC" );

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/fiscal-years' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Fiscal Year', 'micro-erp' ) : esc_html__( 'Fiscal Years', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ Add Fiscal Year', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Fiscal Year Details', 'micro-erp' ); ?></div>
			<div class="card-body" style="padding: 0;">
				<form method="post" action="">
					<?php
					$action = $editing ? 'save_fiscal_year' : 'save_fiscal_year';
					wp_nonce_field( 'micro_erp_fiscal_year_save' );
					?>
					<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $editing ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
					<table class="form-table">
						<tr>
							<th><label for="name"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="name" id="name" placeholder="e.g. FY 2025-2026" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="start_date"><?php esc_html_e( 'Start Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="start_date" id="start_date" value="<?php echo $editing ? esc_attr( $editing->start_date ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="end_date"><?php esc_html_e( 'End Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="end_date" id="end_date" value="<?php echo $editing ? esc_attr( $editing->end_date ) : ''; ?>" required></td>
						</tr>
					</table>
					<div class="actions-bar">
						<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
						<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Fiscal Year', 'micro-erp' ); ?></button>
					</div>
				</form>
			</div>
		</div>

	<?php else : ?>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Start Date', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'End Date', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No fiscal years found.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
								<td><?php echo esc_html( $row->start_date ); ?></td>
								<td><?php echo esc_html( $row->end_date ); ?></td>
								<td><?php echo $row->is_active ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-neutral">Closed</span>'; // phpcs:ignore ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<?php if ( ! $row->is_active ) : ?>
											<form method="post" action="" class="inline-form">
												<?php wp_nonce_field( 'micro_erp_fiscal_year_activate' ); ?>
												<input type="hidden" name="micro_erp_action" value="activate_fiscal_year">
												<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
												<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
												<button class="btn btn-success btn-sm"><?php esc_html_e( 'Activate', 'micro-erp' ); ?></button>
											</form>
										<?php endif; ?>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this fiscal year?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_fiscal_year_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_fiscal_year">
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
