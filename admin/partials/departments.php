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
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Department', 'micro-erp' ) : esc_html__( 'Departments', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ Add Department', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Department Details', 'micro-erp' ); ?></div>
			<div class="card-body" style="padding: 0;">
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
					<table class="form-table">
						<tr>
							<th><label for="name"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="name" id="name" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="description"><?php esc_html_e( 'Description', 'micro-erp' ); ?></label></th>
							<td><textarea name="description" id="description"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea></td>
						</tr>
						<tr>
							<th><label for="status"><?php esc_html_e( 'Status', 'micro-erp' ); ?></label></th>
							<td>
								<select name="status" id="status">
									<option value="active" <?php selected( $editing ? $editing->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'micro-erp' ); ?></option>
									<option value="inactive" <?php selected( $editing ? $editing->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'micro-erp' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
					<div class="actions-bar">
						<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
						<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Department', 'micro-erp' ); ?></button>
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
							<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Employees', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No departments found.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
								<td><?php echo esc_html( $row->description ); ?></td>
								<td><?php echo (int) $row->emp_count; ?></td>
								<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this department?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_department_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_department">
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
