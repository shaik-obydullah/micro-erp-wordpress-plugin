<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_edit_id = oby_mi_erp_query_int( 'edit' );
$oby_mi_erp_editing = null;
if ( $oby_mi_erp_edit_id ) {
	$oby_mi_erp_editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE id = %d", $oby_mi_erp_edit_id ) );
}

$oby_mi_erp_type_filter = oby_mi_erp_query_key( 'type' );
$oby_mi_erp_search      = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_type_filter && $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE type = %s AND (name LIKE %s OR email LIKE %s OR company LIKE %s)",
			$oby_mi_erp_type_filter,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} elseif ( $oby_mi_erp_type_filter ) {
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE type = %s",
			$oby_mi_erp_type_filter
		)
	);
} elseif ( $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE name LIKE %s OR email LIKE %s OR company LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_type_filter && $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE type = %s AND (name LIKE %s OR email LIKE %s OR company LIKE %s) ORDER BY name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_type_filter,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} elseif ( $oby_mi_erp_type_filter ) {
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE type = %s ORDER BY name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_type_filter,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} elseif ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE name LIKE %s OR email LIKE %s OR company LIKE %s ORDER BY name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts ORDER BY name ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'contacts' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $oby_mi_erp_editing ? esc_html__( 'Edit Contact', 'obydullah-micro-erp' ) : esc_html__( 'Contacts', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $oby_mi_erp_editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'contacts', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Contact', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1">
						<?php echo $oby_mi_erp_editing ? esc_html__( 'Contact Information', 'obydullah-micro-erp' ) : esc_html__( 'New Contact', 'obydullah-micro-erp' ); ?>
					</h2>
					<form method="post" action="">
						<?php
						$form_action = $oby_mi_erp_editing ? 'update_contact' : 'save_contact';
						wp_nonce_field( 'oby_mi_erp_contact_save' );
						?>
						<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $form_action ); ?>">
						<?php if ( $oby_mi_erp_editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

						<div class="mb-3">
							<label for="type" class="form-label"><?php esc_html_e( 'Type', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="type" id="type" class="form-control" required>
								<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $oby_mi_erp_type ) : ?>
									<option value="<?php echo esc_attr( $oby_mi_erp_type ); ?>" <?php selected( $oby_mi_erp_editing ? $oby_mi_erp_editing->type : 'customer', $oby_mi_erp_type ); ?>><?php echo esc_html( ucfirst( $oby_mi_erp_type ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="email" class="form-label"><?php esc_html_e( 'Email', 'obydullah-micro-erp' ); ?></label>
							<input type="email" name="email" id="email" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->email ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="phone" class="form-label"><?php esc_html_e( 'Phone', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="phone" id="phone" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->phone ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="company" class="form-label"><?php esc_html_e( 'Company', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="company" id="company" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->company ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="tax_id" class="form-label"><?php esc_html_e( 'Tax ID', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="tax_id" id="tax_id" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->tax_id ) : ''; ?>">
						</div>

						<div class="mb-3">
							<label for="address" class="form-label"><?php esc_html_e( 'Address', 'obydullah-micro-erp' ); ?></label>
							<textarea name="address" id="address" class="form-control"><?php echo $oby_mi_erp_editing ? esc_textarea( $oby_mi_erp_editing->address ) : ''; ?></textarea>
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
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Contact', 'obydullah-micro-erp' ); ?></button>
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
						<?php esc_html_e( 'All Contacts', 'obydullah-micro-erp' ); ?>
					</h2>

					<!-- Search Box -->
					<form method="get" action="" class="search-section mb-3">
						<input type="hidden" name="page" value="oby-mi-erp/contacts">
						<input type="hidden" name="type" value="<?php echo esc_attr( $oby_mi_erp_type_filter ); ?>">
						<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
							<label for="contact-search" class="form-label mb-0"><?php esc_html_e( 'Search Contacts', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="s" id="contact-search" class="form-control form-control-sm search-field" placeholder="<?php esc_attr_e( 'Search contacts...', 'obydullah-micro-erp' ); ?>" value="<?php echo esc_attr( $oby_mi_erp_search ); ?>">
							<button type="submit" id="search-button" class="btn-primary"><?php esc_html_e( 'Filter', 'obydullah-micro-erp' ); ?></button>

							<?php
							$oby_mi_erp_pill_args = array();
							if ( $oby_mi_erp_search ) {
								$oby_mi_erp_pill_args['s'] = $oby_mi_erp_search;
							}
							$oby_mi_erp_all_url = oby_mi_erp_admin_url( 'contacts', $oby_mi_erp_pill_args );
							?>
							<div class="filter-pills ml-auto" role="group" aria-label="<?php esc_attr_e( 'Filter by contact type', 'obydullah-micro-erp' ); ?>">
								<a href="<?php echo esc_url( $oby_mi_erp_all_url ); ?>" class="<?php echo esc_attr( '' === $oby_mi_erp_type_filter ? 'active' : '' ); ?>"><?php esc_html_e( 'All Types', 'obydullah-micro-erp' ); ?></a>
								<?php foreach ( array( 'customer', 'vendor', 'supplier' ) as $oby_mi_erp_type ) : ?>
									<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'contacts', array_merge( $oby_mi_erp_pill_args, array( 'type' => $oby_mi_erp_type ) ) ) ); ?>" class="<?php echo esc_attr( $oby_mi_erp_type_filter === $oby_mi_erp_type ? 'active' : '' ); ?>"><?php echo esc_html( ucfirst( $oby_mi_erp_type ) ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					</form>

					<!-- Contacts Table -->
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Type', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Email', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Phone', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Company', 'obydullah-micro-erp' ); ?></th>
									<th width="90"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'No contacts found.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $oby_mi_erp_row->name ); ?></strong></td>
										<td><?php echo oby_mi_erp_contact_type_badge( $oby_mi_erp_row->type ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td><?php echo esc_html( $oby_mi_erp_row->email ); ?></td>
										<td><?php echo esc_html( $oby_mi_erp_row->phone ); ?></td>
										<td><?php echo esc_html( $oby_mi_erp_row->company ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_row->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'contacts', array( 'edit' => $oby_mi_erp_row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this contact?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_contact_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_contact">
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

					<?php oby_mi_erp_render_pagination( 'contacts', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
