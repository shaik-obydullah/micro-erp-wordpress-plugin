<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$contacts = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'contacts' ) . " WHERE type = 'customer' AND status = 'active' ORDER BY name ASC" );

$back_url = add_query_arg( array( 'page' => 'micro-erp/sales' ), admin_url( 'admin.php' ) );

// Record payment view.
$pay_id = isset( $_GET['pay'] ) ? (int) $_GET['pay'] : 0;
if ( $pay_id ) {
	$pay_sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sales' ) . " WHERE id = %d", $pay_id ) );
	$asset_accounts = micro_erp_get_accounts( 'asset' );
	micro_erp_print_admin_notice();
	?>
	<div class="wrap micro-erp">
		<h1><?php esc_html_e( 'Record Payment', 'micro-erp' ); ?></h1>

		<?php if ( ! $pay_sale ) : ?>
			<p><?php esc_html_e( 'Sale not found.', 'micro-erp' ); ?></p>
			<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( '← Back', 'micro-erp' ); ?></a>
			<?php
			return;
		endif;

		$balance = (float) $pay_sale->total - (float) $pay_sale->amount_paid;
		?>
		<div class="invoice-info">
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Sale #', 'micro-erp' ); ?></div><div class="value"><?php echo esc_html( $pay_sale->sale_no ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Customer', 'micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_contact_name( $pay_sale->contact_id ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Date', 'micro-erp' ); ?></div><div class="value"><?php echo esc_html( $pay_sale->sale_date ); ?></div></div>
			</div>
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Original Amount', 'micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_format_money( $pay_sale->total ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Already Paid', 'micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_format_money( $pay_sale->amount_paid ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Balance Due', 'micro-erp' ); ?></div><div class="balance"><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></div></div>
			</div>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'micro_erp_payment_save' ); ?>
			<input type="hidden" name="micro_erp_action" value="record_payment">
			<input type="hidden" name="sale_id" value="<?php echo (int) $pay_sale->id; ?>">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Payment Details', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="amount"><?php esc_html_e( 'Amount', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="number" name="amount" id="amount" step="0.01" min="0.01" max="<?php echo esc_attr( $balance ); ?>" value="<?php echo esc_attr( $balance ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="method"><?php esc_html_e( 'Payment Method', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="method" id="method" required>
									<?php foreach ( array( 'cash', 'bank_transfer', 'check', 'card' ) as $m ) : ?>
										<option value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $m ) ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="reference"><?php esc_html_e( 'Reference #', 'micro-erp' ); ?></label></th>
							<td><input type="text" name="reference" id="reference" placeholder="<?php esc_attr_e( 'Check #, transaction ID, etc.', 'micro-erp' ); ?>"></td>
						</tr>
						<tr>
							<th><label for="deposit_to"><?php esc_html_e( 'Deposit To', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="deposit_to" id="deposit_to" required>
									<option value="0"><?php esc_html_e( '— Default cash account —', 'micro-erp' ); ?></option>
									<?php foreach ( $asset_accounts as $acct ) : ?>
										<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="notes"><?php esc_html_e( 'Notes', 'micro-erp' ); ?></label></th>
							<td><textarea name="notes" id="notes" rows="3"></textarea></td>
						</tr>
					</table>
				</div>
			</div>

			<div class="actions-bar">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn btn-success"><?php esc_html_e( 'Record Payment', 'micro-erp' ); ?></button>
			</div>
		</form>
	</div>
	<?php
	return;
}

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
$edit_items = array();
if ( $edit_id ) {
	$editing    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sales' ) . " WHERE id = %d", $edit_id ) );
	$edit_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sale_items' ) . " WHERE sale_id = %d ORDER BY id ASC", $edit_id ) );
}

$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

$where = ' WHERE 1=1';
$args  = array();
if ( $status_filter ) {
	$where .= ' AND payment_status = %s';
	$args[] = $status_filter;
}
$query = "SELECT s.*, c.name AS customer FROM " . micro_erp_table( 'sales' ) . " s INNER JOIN " . micro_erp_table( 'contacts' ) . " c ON c.id = s.contact_id" . $where . " ORDER BY s.sale_date DESC, s.id DESC";
$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Sale', 'micro-erp' ) : esc_html__( 'Sales Orders', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ New Sale', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<form method="post" action="">
			<?php
			$action = $editing ? 'update_sale' : 'save_sale';
			wp_nonce_field( 'micro_erp_sale_save' );
			?>
			<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
			<?php if ( $editing ) : ?>
				<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
			<?php endif; ?>
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Sale Details', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Sale #', 'micro-erp' ); ?></label></th>
							<td><input type="text" value="<?php echo $editing ? esc_attr( $editing->sale_no ) : esc_attr( micro_erp_next_sale_no() ); ?>" readonly style="background:#f9f9f9;"></td>
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
							<td><input type="date" name="date" id="date" value="<?php echo $editing ? esc_attr( $editing->sale_date ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
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
				<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Sale', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<form method="get" action="" class="filter-bar">
			<input type="hidden" name="page" value="micro-erp/sales">
			<select name="status">
				<option value=""><?php esc_html_e( 'All Payment Status', 'micro-erp' ); ?></option>
				<?php foreach ( array( 'paid', 'unpaid', 'partial' ) as $st ) : ?>
					<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $status_filter, $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'micro-erp' ); ?></button>
		</form>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Sale #', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Total', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Paid', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Balance', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Payment', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="8"><?php esc_html_e( 'No sales found.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $sale ) :
							$balance = (float) $sale->total - (float) $sale->amount_paid;
							?>
							<tr>
								<td><strong><?php echo esc_html( $sale->sale_no ); ?></strong></td>
								<td><?php echo esc_html( $sale->customer ); ?></td>
								<td><?php echo esc_html( $sale->sale_date ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $sale->total ) ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $sale->amount_paid ) ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></td>
								<td><?php echo micro_erp_status_badge( $sale->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $sale->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'View', 'micro-erp' ); ?></a>
										<?php if ( $balance > 0 ) : ?>
											<a href="<?php echo esc_url( add_query_arg( 'pay', $sale->id, $back_url ) ); ?>" class="btn btn-success btn-sm"><?php esc_html_e( 'Record Payment', 'micro-erp' ); ?></a>
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
