<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'contacts' ) . " WHERE id = %d", $edit_id ) );
}

$type_filter = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$where = ' WHERE 1=1';
$args  = array();
if ( $type_filter ) {
	$where .= ' AND type = %s';
	$args[] = $type_filter;
}
if ( $search ) {
	$where .= ' AND (name LIKE %s OR email LIKE %s OR company LIKE %s)';
	$like   = '%' . $wpdb->esc_like( $search ) . '%';
	$args[] = $like;
	$args[] = $like;
	$args[] = $like;
}

$query = "SELECT * FROM " . micro_erp_table( 'contacts' ) . $where . " ORDER BY name ASC";
$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/contacts' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Contact', 'micro-erp' ) : esc_html__( 'Contacts', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ Add Contact', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="card">
			<div class="card-header"><?php echo $editing ? esc_html__( 'Contact Information', 'micro-erp' ) : esc_html__( 'New Contact', 'micro-erp' ); ?></div>
			<div class="card-body" style="padding: 0;">
				<form method="post" action="">
					<?php
					$action = $editing ? 'update_contact' : 'save_contact';
					wp_nonce_field( 'micro_erp_contact_save' );
					?>
					<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $editing ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
					<table class="form-table">
						<tr>
							<th><label for="type"><?php esc_html_e( 'Type', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="type" id="type" required>
									<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $t ) : ?>
										<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $editing ? $editing->type : 'customer', $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="name"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
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
							<th><label for="company"><?php esc_html_e( 'Company', 'micro-erp' ); ?></label></th>
							<td><input type="text" name="company" id="company" value="<?php echo $editing ? esc_attr( $editing->company ) : ''; ?>"></td>
						</tr>
						<tr>
							<th><label for="tax_id"><?php esc_html_e( 'Tax ID', 'micro-erp' ); ?></label></th>
							<td><input type="text" name="tax_id" id="tax_id" value="<?php echo $editing ? esc_attr( $editing->tax_id ) : ''; ?>"></td>
						</tr>
						<tr>
							<th><label for="address"><?php esc_html_e( 'Address', 'micro-erp' ); ?></label></th>
							<td><textarea name="address" id="address"><?php echo $editing ? esc_textarea( $editing->address ) : ''; ?></textarea></td>
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
						<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Contact', 'micro-erp' ); ?></button>
					</div>
				</form>
			</div>
		</div>

	<?php else : ?>

		<form method="get" action="" class="filter-bar">
			<input type="hidden" name="page" value="micro-erp/contacts">
			<select name="type">
				<option value=""><?php esc_html_e( 'All Types', 'micro-erp' ); ?></option>
				<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $t ) : ?>
					<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type_filter, $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search contacts...', 'micro-erp' ); ?>" value="<?php echo esc_attr( $search ); ?>">
			<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
		</form>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Type', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Email', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Phone', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Company', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="7"><?php esc_html_e( 'No contacts found.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
								<td><?php echo micro_erp_contact_type_badge( $row->type ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td><?php echo esc_html( $row->email ); ?></td>
								<td><?php echo esc_html( $row->phone ); ?></td>
								<td><?php echo esc_html( $row->company ); ?></td>
								<td><?php echo micro_erp_status_badge( $row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this contact?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_contact_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_contact">
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
