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

$per_page    = 20;
$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$count_query = "SELECT COUNT(*) FROM " . micro_erp_table( 'contacts' ) . $where;
$total_items = $args ? (int) $wpdb->get_var( $wpdb->prepare( $count_query, $args ) ) : (int) $wpdb->get_var( $count_query );
$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

$query = "SELECT * FROM " . micro_erp_table( 'contacts' ) . $where . " ORDER BY name ASC LIMIT {$per_page} OFFSET {$offset}";
$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );

micro_erp_print_admin_notice();

$back_url = micro_erp_admin_url( 'contacts' );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Contact', 'micro-erp' ) : esc_html__( 'Contacts', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( micro_erp_admin_url( 'contacts', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Contact', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1">
						<?php echo $editing ? esc_html__( 'Contact Information', 'micro-erp' ) : esc_html__( 'New Contact', 'micro-erp' ); ?>
					</h2>
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

						<div class="mb-3">
							<label for="type" class="form-label"><?php esc_html_e( 'Type', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="type" id="type" class="form-control" required>
								<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $t ) : ?>
									<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $editing ? $editing->type : 'customer', $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="text-danger">*</span></label>
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
							<label for="company" class="form-label"><?php esc_html_e( 'Company', 'micro-erp' ); ?></label>
							<input type="text" name="company" id="company" class="form-control" value="<?php echo $editing ? esc_attr( $editing->company ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="tax_id" class="form-label"><?php esc_html_e( 'Tax ID', 'micro-erp' ); ?></label>
							<input type="text" name="tax_id" id="tax_id" class="form-control" value="<?php echo $editing ? esc_attr( $editing->tax_id ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="address" class="form-label"><?php esc_html_e( 'Address', 'micro-erp' ); ?></label>
							<textarea name="address" id="address" class="form-control"><?php echo $editing ? esc_textarea( $editing->address ) : ''; ?></textarea>
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
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Contact', 'micro-erp' ); ?></button>
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
						<?php esc_html_e( 'All Contacts', 'micro-erp' ); ?>
					</h2>

					<!-- Search Box -->
					<form method="get" action="" class="search-section mb-3">
						<input type="hidden" name="page" value="micro-erp/contacts">
						<input type="hidden" name="type" value="<?php echo esc_attr( $type_filter ); ?>">
						<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
							<label for="contact-search" class="form-label mb-0"><?php esc_html_e( 'Search Contacts', 'micro-erp' ); ?></label>
							<input type="text" name="s" id="contact-search" class="form-control form-control-sm search-field" placeholder="<?php esc_attr_e( 'Search contacts...', 'micro-erp' ); ?>" value="<?php echo esc_attr( $search ); ?>">
							<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>

							<?php
							$pill_args = array();
							if ( $search ) {
								$pill_args['s'] = $search;
							}
							$all_url = micro_erp_admin_url( 'contacts', $pill_args );
							?>
							<div class="filter-pills ml-auto" role="group" aria-label="<?php esc_attr_e( 'Filter by contact type', 'micro-erp' ); ?>">
								<a href="<?php echo esc_url( $all_url ); ?>" class="<?php echo esc_attr( '' === $type_filter ? 'active' : '' ); ?>"><?php esc_html_e( 'All Types', 'micro-erp' ); ?></a>
								<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $t ) : ?>
									<a href="<?php echo esc_url( micro_erp_admin_url( 'contacts', array_merge( $pill_args, array( 'type' => $t ) ) ) ); ?>" class="<?php echo esc_attr( $type_filter === $t ? 'active' : '' ); ?>"><?php echo esc_html( ucfirst( $t ) ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					</form>

					<!-- Contacts Table -->
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Type', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Email', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Phone', 'micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Company', 'micro-erp' ); ?></th>
									<th width="90"><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'No contacts found.', 'micro-erp' ); ?></td></tr>
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
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( micro_erp_admin_url( 'contacts', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this contact?', 'micro-erp' ); ?>');">
													<?php wp_nonce_field( 'micro_erp_contact_delete' ); ?>
													<input type="hidden" name="micro_erp_action" value="delete_contact">
													<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
													<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php micro_erp_render_pagination( 'contacts', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
