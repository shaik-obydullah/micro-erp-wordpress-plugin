<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$contacts = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'contacts' ) . " WHERE type = 'customer' AND status = 'active' ORDER BY name ASC" );
$accounts = micro_erp_get_accounts();

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
$edit_items = array();
if ( $edit_id ) {
	$editing   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'quotations' ) . " WHERE id = %d", $edit_id ) );
	$edit_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'quotation_items' ) . " WHERE quotation_id = %d ORDER BY id ASC", $edit_id ) );
}

$rows = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'quotations' ) . " ORDER BY quotation_date DESC, id DESC" );

$back_url = add_query_arg( array( 'page' => 'micro-erp/quotations' ), admin_url( 'admin.php' ) );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Quotation', 'micro-erp' ) : esc_html__( 'Quotations', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ New Quotation', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

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

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Quotation Details', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Quotation #', 'micro-erp' ); ?></label></th>
							<td><input type="text" value="<?php echo $editing ? esc_attr( $editing->quotation_no ) : esc_attr( micro_erp_next_quotation_no() ); ?>" readonly style="background:#f9f9f9;"></td>
						</tr>
						<tr>
							<th><label for="contact_id"><?php esc_html_e( 'Customer', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="contact_id" id="contact_id" required>
									<option value="0"><?php esc_html_e( 'Select Customer', 'micro-erp' ); ?></option>
									<?php foreach ( $contacts as $c ) : ?>
										<option value="<?php echo (int) $c->id; ?>" <?php selected( $editing ? $editing->contact_id : 0, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="date"><?php esc_html_e( 'Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="date" id="date" value="<?php echo $editing ? esc_attr( $editing->quotation_date ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="valid_until"><?php esc_html_e( 'Valid Until', 'micro-erp' ); ?></label></th>
							<td><input type="date" name="valid_until" id="valid_until" value="<?php echo $editing ? esc_attr( $editing->valid_until ) : ''; ?>"></td>
						</tr>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Line Items', 'micro-erp' ); ?></div>
				<div class="card-body">
					<table class="items-table" id="items-table">
						<thead>
							<tr>
								<th class="col-desc"><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
								<th class="col-qty"><?php esc_html_e( 'Quantity', 'micro-erp' ); ?></th>
								<th class="col-price"><?php esc_html_e( 'Unit Price', 'micro-erp' ); ?></th>
								<th class="col-tax"><?php esc_html_e( 'Tax %', 'micro-erp' ); ?></th>
								<th class="col-total"><?php esc_html_e( 'Total', 'micro-erp' ); ?></th>
								<th class="col-action"></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( $editing && $edit_items ) : ?>
								<?php foreach ( $edit_items as $item ) : ?>
									<tr>
										<td><input type="text" name="item_description[]" value="<?php echo esc_attr( $item->description ); ?>" required></td>
										<td><input type="number" name="item_quantity[]" value="<?php echo esc_attr( $item->quantity ); ?>" step="0.01" min="1" required class="i-qty"></td>
										<td><input type="number" name="item_price[]" value="<?php echo esc_attr( $item->unit_price ); ?>" step="0.01" min="0" required class="i-price"></td>
										<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( $item->tax_rate ); ?>" step="0.01" min="0" class="i-tax"></td>
										<td class="text-right i-line-total"><?php echo esc_html( micro_erp_format_money( $item->total ) ); ?></td>
										<td><button type="button" class="btn btn-danger btn-sm i-remove">×</button></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td><input type="text" name="item_description[]" required></td>
									<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty"></td>
									<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price"></td>
									<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( micro_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax"></td>
									<td class="text-right i-line-total">—</td>
									<td><button type="button" class="btn btn-danger btn-sm i-remove">×</button></td>
								</tr>
								<tr>
									<td><input type="text" name="item_description[]" required></td>
									<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty"></td>
									<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price"></td>
									<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( micro_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax"></td>
									<td class="text-right i-line-total">—</td>
									<td><button type="button" class="btn btn-danger btn-sm i-remove">×</button></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<button type="button" class="btn btn-primary i-add" style="margin-top:12px;"><?php esc_html_e( '+ Add Item', 'micro-erp' ); ?></button>

					<div class="totals">
						<table>
							<tr>
								<td><?php esc_html_e( 'Subtotal:', 'micro-erp' ); ?></td>
								<td class="t-subtotal">—</td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Tax:', 'micro-erp' ); ?></td>
								<td class="t-tax">—</td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Discount:', 'micro-erp' ); ?></td>
								<td><input type="number" name="discount" value="<?php echo $editing ? esc_attr( $editing->discount ) : '0'; ?>" step="0.01" min="0" class="t-discount-input" style="width:110px;padding:4px 8px;border:1px solid #8c8f94;border-radius:3px;text-align:right;"></td>
							</tr>
							<tr class="grand-total">
								<td><?php esc_html_e( 'Total:', 'micro-erp' ); ?></td>
								<td class="t-grand">—</td>
							</tr>
						</table>
					</div>
					<div style="clear:both;"></div>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Notes', 'micro-erp' ); ?></div>
				<div class="card-body">
					<textarea name="notes" style="width:100%;max-width:100%;padding:10px;border:1px solid #8c8f94;border-radius:4px;font-size:14px;height:80px;resize:vertical;"><?php echo $editing ? esc_textarea( $editing->notes ) : ''; ?></textarea>
				</div>
			</div>

			<div class="actions-bar">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save as Draft', 'micro-erp' ); ?></button>
				<button type="submit" name="save_and_send" value="1" class="btn btn-success"><?php esc_html_e( 'Save & Send', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Quotation #', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Valid Until', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Total', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="7"><?php esc_html_e( 'No quotations yet.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $q ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $q->quotation_no ); ?></strong></td>
								<td><?php echo esc_html( micro_erp_contact_name( $q->contact_id ) ); ?></td>
								<td><?php echo esc_html( $q->quotation_date ); ?></td>
								<td><?php echo esc_html( $q->valid_until ); ?></td>
								<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $q->total ) ); ?></strong></td>
								<td><?php echo micro_erp_status_badge( $q->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $q->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<?php if ( 'draft' === $q->status ) : ?>
											<form method="post" action="" class="inline-form">
												<?php wp_nonce_field( 'micro_erp_quotation_status' ); ?>
												<input type="hidden" name="micro_erp_action" value="quotation_status">
												<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
												<input type="hidden" name="status" value="sent">
												<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
												<button class="btn btn-success btn-sm"><?php esc_html_e( 'Send', 'micro-erp' ); ?></button>
											</form>
										<?php endif; ?>
										<?php if ( in_array( $q->status, array( 'sent', 'accepted' ), true ) ) : ?>
											<form method="post" action="" class="inline-form">
												<?php wp_nonce_field( 'micro_erp_quotation_convert' ); ?>
												<input type="hidden" name="micro_erp_action" value="convert_quotation">
												<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
												<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
												<button class="btn btn-success btn-sm"><?php esc_html_e( 'Convert', 'micro-erp' ); ?></button>
											</form>
										<?php endif; ?>
										<?php if ( 'draft' === $q->status ) : ?>
											<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this quotation?', 'micro-erp' ); ?>');">
												<?php wp_nonce_field( 'micro_erp_quotation_delete' ); ?>
												<input type="hidden" name="micro_erp_action" value="delete_quotation">
												<input type="hidden" name="id" value="<?php echo (int) $q->id; ?>">
												<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
												<button class="btn btn-danger btn-sm"><?php esc_html_e( 'Delete', 'micro-erp' ); ?></button>
											</form>
										<?php endif; ?>
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
