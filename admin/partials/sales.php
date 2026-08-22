<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$contacts = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'contacts' ) . " WHERE type = 'customer' AND status = 'active' ORDER BY name ASC" );

$back_url = micro_erp_admin_url( 'sales' );

// Record payment view.
$pay_id = isset( $_GET['pay'] ) ? (int) $_GET['pay'] : 0;
if ( $pay_id ) {
	$pay_sale = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'sales' ) . " WHERE id = %d", $pay_id ) );
	$asset_accounts = micro_erp_get_accounts( 'asset' );
	micro_erp_print_admin_notice();
	?>
	<div class="wrap micro-erp-page">
		<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Record Payment', 'lime-micro-erp' ); ?></h1>
		<hr class="wp-header-end">

		<?php if ( ! $pay_sale ) : ?>
			<p><?php esc_html_e( 'Sale not found.', 'lime-micro-erp' ); ?></p>
			<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary"><?php esc_html_e( '← Back', 'lime-micro-erp' ); ?></a>
			<?php
			return;
		endif;

		$balance = (float) $pay_sale->total - (float) $pay_sale->amount_paid;
		?>
		<div class="invoice-info mt-3">
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Sale #', 'lime-micro-erp' ); ?></div><div class="value"><?php echo esc_html( $pay_sale->sale_no ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_contact_name( $pay_sale->contact_id ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></div><div class="value"><?php echo esc_html( $pay_sale->sale_date ); ?></div></div>
			</div>
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Original Amount', 'lime-micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_format_money( $pay_sale->total ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Already Paid', 'lime-micro-erp' ); ?></div><div class="value"><?php echo esc_html( micro_erp_format_money( $pay_sale->amount_paid ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Balance Due', 'lime-micro-erp' ); ?></div><div class="balance"><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></div></div>
			</div>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'micro_erp_payment_save' ); ?>
			<input type="hidden" name="micro_erp_action" value="record_payment">
			<input type="hidden" name="sale_id" value="<?php echo (int) $pay_sale->id; ?>">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Payment Details', 'lime-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="amount" class="form-label"><?php esc_html_e( 'Amount', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" max="<?php echo esc_attr( $balance ); ?>" value="<?php echo esc_attr( $balance ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="method" class="form-label"><?php esc_html_e( 'Payment Method', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="method" id="method" class="form-control" required>
								<?php foreach ( array( 'cash', 'bank_transfer', 'check', 'card' ) as $m ) : ?>
									<option value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $m ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="reference" class="form-label"><?php esc_html_e( 'Reference #', 'lime-micro-erp' ); ?></label>
							<input type="text" name="reference" id="reference" class="form-control" placeholder="<?php esc_attr_e( 'Check #, transaction ID, etc.', 'lime-micro-erp' ); ?>">
						</div>

						<div class="mb-3">
							<label for="deposit_to" class="form-label"><?php esc_html_e( 'Deposit To', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="deposit_to" id="deposit_to" class="form-control" required>
								<option value="0"><?php esc_html_e( '— Default cash account —', 'lime-micro-erp' ); ?></option>
								<?php foreach ( $asset_accounts as $acct ) : ?>
									<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="notes" class="form-label"><?php esc_html_e( 'Notes', 'lime-micro-erp' ); ?></label>
							<textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Record Payment', 'lime-micro-erp' ); ?></button>
						</div>
					</div>
				</div>
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
$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$where = ' WHERE 1=1';
$args  = array();
if ( $status_filter ) {
	$where .= ' AND s.payment_status = %s';
	$args[] = $status_filter;
}
if ( $search ) {
	$where .= ' AND (s.sale_no LIKE %s OR c.name LIKE %s)';
	$like   = '%' . $wpdb->esc_like( $search ) . '%';
	$args[] = $like;
	$args[] = $like;
}
$count_join  = " FROM " . micro_erp_table( 'sales' ) . " s INNER JOIN " . micro_erp_table( 'contacts' ) . " c ON c.id = s.contact_id";
$per_page    = 20;
$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$count_query = "SELECT COUNT(*){$count_join}{$where}"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$total_items = $args ? (int) $wpdb->get_var( $wpdb->prepare( $count_query, $args ) ) : (int) $wpdb->get_var( $count_query );
$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

$query = "SELECT s.*, c.name AS customer FROM " . micro_erp_table( 'sales' ) . " s INNER JOIN " . micro_erp_table( 'contacts' ) . " c ON c.id = s.contact_id" . $where . " ORDER BY s.sale_date DESC, s.id DESC LIMIT {$per_page} OFFSET {$offset}";
$rows  = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );

micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Sale', 'lime-micro-erp' ) : esc_html__( 'Sales Orders', 'lime-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ New Sale', 'lime-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

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

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Sale Details', 'lime-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label class="form-label"><?php esc_html_e( 'Sale #', 'lime-micro-erp' ); ?></label>
							<input type="text" class="form-control" value="<?php echo $editing ? esc_attr( $editing->sale_no ) : esc_attr( micro_erp_next_sale_no() ); ?>" readonly style="background:#f9f9f9;">
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
							<input type="date" name="date" id="date" class="form-control" value="<?php echo $editing ? esc_attr( $editing->sale_date ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
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
				<button type="submit" class="btn-success"><?php esc_html_e( 'Save Sale', 'lime-micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="search-section mt-3">
			<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
				<span class="form-label mb-0"><?php esc_html_e( 'Payment Status', 'lime-micro-erp' ); ?></span>

				<?php
				$pill_args = $search ? array( 's' => $search ) : array();
				$all_url   = micro_erp_admin_url( 'sales', $pill_args );
				?>
				<div class="filter-pills" role="group" aria-label="<?php esc_attr_e( 'Filter by payment status', 'lime-micro-erp' ); ?>">
					<a href="<?php echo esc_url( $all_url ); ?>" class="<?php echo esc_attr( '' === $status_filter ? 'active' : '' ); ?>"><?php esc_html_e( 'All', 'lime-micro-erp' ); ?></a>
					<?php foreach ( array( 'paid', 'unpaid', 'partial' ) as $st ) : ?>
						<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array_merge( $pill_args, array( 'status' => $st ) ) ) ); ?>" class="<?php echo esc_attr( $status_filter === $st ? 'active' : '' ); ?>"><?php echo esc_html( ucfirst( $st ) ); ?></a>
					<?php endforeach; ?>
				</div>

				<?php micro_erp_render_search_bar( 'sales', __( 'Search Sales', 'lime-micro-erp' ), __( 'Search by sale # or customer...', 'lime-micro-erp' ), array( 'status' => $status_filter ), $search, true ); ?>
			</div>
		</div>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'All Sales', 'lime-micro-erp' ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="110"><?php esc_html_e( 'Sale #', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Date', 'lime-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Total', 'lime-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Paid', 'lime-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Balance', 'lime-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Payment', 'lime-micro-erp' ); ?></th>
									<th width="200" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $rows ) ) : ?>
									<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No sales found.', 'lime-micro-erp' ); ?></td></tr>
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
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array( 'edit' => $sale->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a>
												<?php if ( $balance > 0 ) : ?>
													<a href="<?php echo esc_url( micro_erp_admin_url( 'sales', array( 'pay' => $sale->id ) ) ); ?>" class="pos-action pay"><?php esc_html_e( 'Record Payment', 'lime-micro-erp' ); ?></a>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php micro_erp_render_pagination( 'sales', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
