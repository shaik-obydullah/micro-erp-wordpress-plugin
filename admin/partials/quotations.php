<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$contacts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_contacts WHERE type = %s AND status = %s ORDER BY name ASC", 'customer', 'active' ) );
$accounts = micro_erp_get_accounts();

$edit_id = micro_erp_query_int( 'edit' );
$editing = null;
$edit_items = array();
if ( $edit_id ) {
	$editing   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_quotations WHERE id = %d", $edit_id ) );
	$edit_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_quotation_items WHERE quotation_id = %d ORDER BY id ASC", $edit_id ) );
}

$search = micro_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, micro_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_quotations q
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = q.contact_id
			WHERE q.quotation_no LIKE %s OR c.name LIKE %s",
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_quotations q
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = q.contact_id
			WHERE 1 = %d",
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
			"SELECT q.* FROM {$wpdb->prefix}micro_erp_quotations q
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = q.contact_id
			WHERE q.quotation_no LIKE %s OR c.name LIKE %s
			ORDER BY q.quotation_date DESC, q.id DESC LIMIT %d OFFSET %d",
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT q.* FROM {$wpdb->prefix}micro_erp_quotations q
			INNER JOIN {$wpdb->prefix}micro_erp_contacts c ON c.id = q.contact_id
			ORDER BY q.quotation_date DESC, q.id DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

$back_url = micro_erp_admin_url( 'quotations' );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Quotation', 'lime-micro-erp' ) : esc_html__( 'Quotations', 'lime-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( micro_erp_admin_url( 'quotations', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ New Quotation', 'lime-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || micro_erp_query_has( 'new' ) ) : ?>

		<form method="post" action="">
			<?php
			$action = $editing ? 'update_quotation' : 'save_quotation';
			wp_nonce_field( 'micro_erp_quotation_save' );
			?>
			<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
			<?php endif; ?>
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Quotation Details', 'lime-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label class="form-label"><?php esc_html_e( 'Quotation #', 'lime-micro-erp' ); ?></label>
							<input type="text" class="form-control" value="<?php echo $editing ? esc_attr( $editing->quotation_no ) : esc_attr( micro_erp_next_quotation_no() ); ?>" readonly style="background:#f9f9f9;">
						</div>

						<div class="mb-3">
							<label for="contact_id" class="form-label"><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="contact_id" id="contact_id" class="form-control" required>
								<option value="0"><?php esc_html_e( 'Select Customer', 'lime-micro-erp' ); ?></option>
								<?php foreach ( $contacts as $c ) : ?>
									<option value="<?php echo (int) $c->id; ?>" <?php selected( $editing ? $editing->contact_id : 0, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="date" class="form-label"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="date" id="date" class="form-control" value="<?php echo $editing ? esc_attr( $editing->quotation_date ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="valid_until" class="form-label"><?php esc_html_e( 'Valid Until', 'lime-micro-erp' ); ?></label>
							<input type="date" name="valid_until" id="valid_until" class="form-control" value="<?php echo $editing ? esc_attr( $editing->valid_until ) : ''; ?>">
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Line Items', 'lime-micro-erp' ); ?></h2>

						<table class="items-table table table-striped table-hover table-bordered" id="items-table">
							<thead>
								<tr class="bg-primary text-white">
									<th class="col-desc"><?php esc_html_e( 'Description', 'lime-micro-erp' ); ?></th>
									<th class="col-qty"><?php esc_html_e( 'Quantity', 'lime-micro-erp' ); ?></th>
									<th class="col-price"><?php esc_html_e( 'Unit Price', 'lime-micro-erp' ); ?></th>
									<th class="col-tax"><?php esc_html_e( 'Tax %', 'lime-micro-erp' ); ?></th>
									<th class="col-total text-right"><?php esc_html_e( 'Total', 'lime-micro-erp' ); ?></th>
									<th class="col-action"></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( $editing && $edit_items ) : ?>
									<?php foreach ( $edit_items as $item ) : ?>
										<tr>
											<td><input type="text" name="item_description[]" value="<?php echo esc_attr( $item->description ); ?>" required class="form-control form-control-sm"></td>
											<td><input type="number" name="item_quantity[]" value="<?php echo esc_attr( $item->quantity ); ?>" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
											<td><input type="number" name="item_price[]" value="<?php echo esc_attr( $item->unit_price ); ?>" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
											<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( $item->tax_rate ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
											<td class="text-right i-line-total"><?php echo esc_html( micro_erp_format_money( $item->total ) ); ?></td>
											<td><button type="button" class="btn-danger i-remove">×</button></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td><input type="text" name="item_description[]" required class="form-control form-control-sm"></td>
										<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
										<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
										<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( micro_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
										<td class="text-right i-line-total">—</td>
										<td><button type="button" class="btn-danger i-remove">×</button></td>
									</tr>
									<tr>
										<td><input type="text" name="item_description[]" required class="form-control form-control-sm"></td>
										<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
										<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
										<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( micro_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
										<td class="text-right i-line-total">—</td>
										<td><button type="button" class="btn-danger i-remove">×</button></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>

						<button type="button" class="btn-primary i-add mt-3"><?php esc_html_e( '+ Add Item', 'lime-micro-erp' ); ?></button>

						<div class="totals">
							<table>
								<tr>
									<td><?php esc_html_e( 'Subtotal:', 'lime-micro-erp' ); ?></td>
									<td class="t-subtotal">—</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Tax:', 'lime-micro-erp' ); ?></td>
									<td class="t-tax">—</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Discount:', 'lime-micro-erp' ); ?></td>
									<td><input type="number" name="discount" value="<?php echo $editing ? esc_attr( $editing->discount ) : '0'; ?>" step="0.01" min="0" class="t-discount-input form-control form-control-sm text-right" style="width:110px;"></td>
								</tr>
								<tr class="grand-total">
									<td><?php esc_html_e( 'Total:', 'lime-micro-erp' ); ?></td>
									<td class="t-grand">—</td>
								</tr>
							</table>
						</div>
						<div style="clear:both;"></div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-8 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Notes', 'lime-micro-erp' ); ?></h2>
						<textarea name="notes" class="form-control" style="height:80px;resize:vertical;"><?php echo $editing ? esc_textarea( $editing->notes ) : ''; ?></textarea>
					</div>
				</div>
			</div>

			<div class="d-flex mt-2 mb-4">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
				<button type="submit" class="btn-primary mr-2"><?php esc_html_e( 'Save as Draft', 'lime-micro-erp' ); ?></button>
				<button type="submit" name="save_and_send" value="1" class="btn-success"><?php esc_html_e( 'Save & Send', 'lime-micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php micro_erp_render_search_bar( 'quotations', __( 'Search Quotations', 'lime-micro-erp' ), __( 'Search by quotation # or customer...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'All Quotations', 'lime-micro-erp' ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="120"><?php esc_html_e( 'Quotation #', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Valid Until', 'lime-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Total', 'lime-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
									<th width="230" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'No quotations yet.', 'lime-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $rows as $q ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $q->quotation_no ); ?></strong></td>
										<td><?php echo esc_html( micro_erp_contact_name( $q->contact_id ) ); ?></td>
										<td><?php echo esc_html( $q->quotation_date ); ?></td>
										<td><?php echo esc_html( $q->valid_until ); ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( micro_erp_format_money( $q->total ) ); ?></td>
										<td><?php echo micro_erp_status_badge( $q->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( micro_erp_admin_url( 'quotations', array( 'edit' => $q->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<?php if ( 'draft' === $q->status ) : ?>
													<form method="post" action="" class="inline-form">
														<?php wp_nonce_field( 'micro_erp_quotation_status' ); ?>
														<input type="hidden" name="micro_erp_action" value="quotation_status">
														<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
														<input type="hidden" name="status" value="sent">
														<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
														<button class="pos-action edit"><?php esc_html_e( 'Send', 'lime-micro-erp' ); ?></button>
													</form>
												<?php endif; ?>
												<?php if ( in_array( $q->status, array( 'sent', 'accepted' ), true ) ) : ?>
													<form method="post" action="" class="inline-form">
														<?php wp_nonce_field( 'micro_erp_quotation_convert' ); ?>
														<input type="hidden" name="micro_erp_action" value="convert_quotation">
														<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
														<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
														<button class="pos-action edit"><?php esc_html_e( 'Convert', 'lime-micro-erp' ); ?></button>
													</form>
												<?php endif; ?>
												<?php if ( 'draft' === $q->status ) : ?>
													<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this quotation?', 'lime-micro-erp' ); ?>');">
														<?php wp_nonce_field( 'micro_erp_quotation_delete' ); ?>
														<input type="hidden" name="micro_erp_action" value="delete_quotation">
														<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
														<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
														<button class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
													</form>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php micro_erp_render_pagination( 'quotations', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
