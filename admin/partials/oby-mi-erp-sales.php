<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_contacts_key = 'oby_mi_erp_list_contacts_customer_active';
$oby_mi_erp_contacts = wp_cache_get( $oby_mi_erp_contacts_key, 'oby_mi_erp' );
if ( false === $oby_mi_erp_contacts ) {
	global $wpdb;
	$oby_mi_erp_contacts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_contacts WHERE type = %s AND status = %s ORDER BY name ASC", 'customer', 'active' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached below via literal wp_cache_set().
	wp_cache_set( $oby_mi_erp_contacts_key, $oby_mi_erp_contacts, 'oby_mi_erp' );
	if ( function_exists( 'oby_mi_erp_cache_register' ) ) {
		oby_mi_erp_cache_register( $oby_mi_erp_contacts_key );
	}
}

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'sales' );

// Record payment view.
$oby_mi_erp_pay_id = oby_mi_erp_query_int( 'pay' );
if ( $oby_mi_erp_pay_id ) {
	$oby_mi_erp_pay_sale       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales WHERE id = %d", $oby_mi_erp_pay_id ) );
	$oby_mi_erp_asset_accounts = oby_mi_erp_get_accounts( 'asset' );
	oby_mi_erp_print_admin_notice();
	?>
	<div class="wrap oby-mi-erp-page">
		<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Record Payment', 'obydullah-micro-erp' ); ?></h1>
		<hr class="wp-header-end">

		<?php if ( ! $oby_mi_erp_pay_sale ) : ?>
			<p><?php esc_html_e( 'Sale not found.', 'obydullah-micro-erp' ); ?></p>
			<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary"><?php esc_html_e( '← Back', 'obydullah-micro-erp' ); ?></a>
			<?php
			return;
		endif;

		$oby_mi_erp_balance = (float) $oby_mi_erp_pay_sale->total - (float) $oby_mi_erp_pay_sale->amount_paid;
		?>
		<div class="invoice-info mt-3">
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Sale #', 'obydullah-micro-erp' ); ?></div><div class="value"><?php echo esc_html( $oby_mi_erp_pay_sale->sale_no ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Customer', 'obydullah-micro-erp' ); ?></div><div class="value"><?php echo esc_html( oby_mi_erp_contact_name( $oby_mi_erp_pay_sale->contact_id ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></div><div class="value"><?php echo esc_html( $oby_mi_erp_pay_sale->sale_date ); ?></div></div>
			</div>
			<div class="row">
				<div><div class="label"><?php esc_html_e( 'Original Amount', 'obydullah-micro-erp' ); ?></div><div class="value"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_pay_sale->total ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Already Paid', 'obydullah-micro-erp' ); ?></div><div class="value"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_pay_sale->amount_paid ) ); ?></div></div>
				<div><div class="label"><?php esc_html_e( 'Balance Due', 'obydullah-micro-erp' ); ?></div><div class="balance"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_balance ) ); ?></div></div>
			</div>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'oby_mi_erp_payment_save' ); ?>
			<input type="hidden" name="oby_mi_erp_action" value="record_payment">
			<input type="hidden" name="sale_id" value="<?php echo (int) $oby_mi_erp_pay_sale->id; ?>">
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Payment Details', 'obydullah-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="amount" class="form-label"><?php esc_html_e( 'Amount', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" max="<?php echo esc_attr( $oby_mi_erp_balance ); ?>" value="<?php echo esc_attr( $oby_mi_erp_balance ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="method" class="form-label"><?php esc_html_e( 'Payment Method', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="method" id="method" class="form-control" required>
								<?php foreach ( array( 'cash', 'bank_transfer', 'check', 'card' ) as $payment_method_option ) : ?>
									<option value="<?php echo esc_attr( $payment_method_option ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $payment_method_option ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="reference" class="form-label"><?php esc_html_e( 'Reference #', 'obydullah-micro-erp' ); ?></label>
							<input type="text" name="reference" id="reference" class="form-control" placeholder="<?php esc_attr_e( 'Check #, transaction ID, etc.', 'obydullah-micro-erp' ); ?>">
						</div>

						<div class="mb-3">
							<label for="deposit_to" class="form-label"><?php esc_html_e( 'Deposit To', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="deposit_to" id="deposit_to" class="form-control" required>
								<option value="0"><?php esc_html_e( '— Default cash account —', 'obydullah-micro-erp' ); ?></option>
								<?php foreach ( $oby_mi_erp_asset_accounts as $oby_mi_erp_acct ) : ?>
									<option value="<?php echo (int) $oby_mi_erp_acct->id; ?>"><?php echo esc_html( $oby_mi_erp_acct->code . ' - ' . $oby_mi_erp_acct->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="notes" class="form-label"><?php esc_html_e( 'Notes', 'obydullah-micro-erp' ); ?></label>
							<textarea name="notes" id="notes" rows="3" class="form-control"></textarea>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Record Payment', 'obydullah-micro-erp' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
	return;
}

$oby_mi_erp_edit_id    = oby_mi_erp_query_int( 'edit' );
$oby_mi_erp_editing    = null;
$oby_mi_erp_edit_items = array();
if ( $oby_mi_erp_edit_id ) {
	$oby_mi_erp_editing    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sales WHERE id = %d", $oby_mi_erp_edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
	$oby_mi_erp_edit_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_sale_items WHERE sale_id = %d ORDER BY id ASC", $oby_mi_erp_edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
}

$oby_mi_erp_status_filter = oby_mi_erp_query_key( 'status' );
$oby_mi_erp_search        = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_status_filter && $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.payment_status = %s AND (s.sale_no LIKE %s OR c.name LIKE %s)",
			$oby_mi_erp_status_filter,
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} elseif ( $oby_mi_erp_status_filter ) {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.payment_status = %s",
			$oby_mi_erp_status_filter
		)
	);
} elseif ( $oby_mi_erp_search ) {
	$oby_mi_erp_like        = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.sale_no LIKE %s OR c.name LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_status_filter && $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.payment_status = %s AND (s.sale_no LIKE %s OR c.name LIKE %s) ORDER BY s.sale_date DESC, s.id DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_status_filter,
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} elseif ( $oby_mi_erp_status_filter ) {
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.payment_status = %s ORDER BY s.sale_date DESC, s.id DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_status_filter,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} elseif ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id WHERE s.sale_no LIKE %s OR c.name LIKE %s ORDER BY s.sale_date DESC, s.id DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT s.*, c.name AS customer FROM {$wpdb->prefix}oby_mi_erp_sales s INNER JOIN {$wpdb->prefix}oby_mi_erp_contacts c ON c.id = s.contact_id ORDER BY s.sale_date DESC, s.id DESC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

oby_mi_erp_print_admin_notice();
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $oby_mi_erp_editing ? esc_html__( 'Edit Sale', 'obydullah-micro-erp' ) : esc_html__( 'Sales Orders', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $oby_mi_erp_editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ New Sale', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<form method="post" action="">
			<?php
			$form_action = $oby_mi_erp_editing ? 'update_sale' : 'save_sale';
			wp_nonce_field( 'oby_mi_erp_sale_save' );
			?>
			<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $form_action ); ?>">
			<?php if ( $oby_mi_erp_editing ) : ?>
				<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_editing->id; ?>">
			<?php endif; ?>
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Sale Details', 'obydullah-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label class="form-label"><?php esc_html_e( 'Sale #', 'obydullah-micro-erp' ); ?></label>
							<input type="text" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->sale_no ) : esc_attr( oby_mi_erp_next_sale_no() ); ?>" readonly style="background:#f9f9f9;">
						</div>

						<div class="mb-3">
							<label for="contact_id" class="form-label"><?php esc_html_e( 'Customer', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="contact_id" id="contact_id" class="form-control" required>
								<option value="0"><?php esc_html_e( 'Select Customer', 'obydullah-micro-erp' ); ?></option>
								<?php foreach ( $oby_mi_erp_contacts as $oby_mi_erp_contact ) : ?>
									<option value="<?php echo (int) $oby_mi_erp_contact->id; ?>" <?php selected( $oby_mi_erp_editing ? $oby_mi_erp_editing->contact_id : 0, $oby_mi_erp_contact->id ); ?>><?php echo esc_html( $oby_mi_erp_contact->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="mb-3">
							<label for="date" class="form-label"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="date" id="date" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->sale_date ) : esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Line Items', 'obydullah-micro-erp' ); ?></h2>

						<table class="items-table table table-striped table-hover table-bordered" id="items-table">
							<thead>
								<tr class="bg-primary text-white">
									<th class="col-desc"><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
									<th class="col-qty"><?php esc_html_e( 'Quantity', 'obydullah-micro-erp' ); ?></th>
									<th class="col-price"><?php esc_html_e( 'Unit Price', 'obydullah-micro-erp' ); ?></th>
									<th class="col-tax"><?php esc_html_e( 'Tax %', 'obydullah-micro-erp' ); ?></th>
									<th class="col-total text-right"><?php esc_html_e( 'Total', 'obydullah-micro-erp' ); ?></th>
									<th class="col-action"></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( $oby_mi_erp_editing && $oby_mi_erp_edit_items ) : ?>
									<?php foreach ( $oby_mi_erp_edit_items as $oby_mi_erp_item ) : ?>
										<tr>
											<td><input type="text" name="item_description[]" value="<?php echo esc_attr( $oby_mi_erp_item->description ); ?>" required class="form-control form-control-sm"></td>
											<td><input type="number" name="item_quantity[]" value="<?php echo esc_attr( $oby_mi_erp_item->quantity ); ?>" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
											<td><input type="number" name="item_price[]" value="<?php echo esc_attr( $oby_mi_erp_item->unit_price ); ?>" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
											<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( $oby_mi_erp_item->tax_rate ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
											<td class="text-right i-line-total"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_item->total ) ); ?></td>
											<td><button type="button" class="btn-danger i-remove">×</button></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td><input type="text" name="item_description[]" required class="form-control form-control-sm"></td>
										<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
										<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
										<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( oby_mi_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
										<td class="text-right i-line-total">—</td>
										<td><button type="button" class="btn-danger i-remove">×</button></td>
									</tr>
									<tr>
										<td><input type="text" name="item_description[]" required class="form-control form-control-sm"></td>
										<td><input type="number" name="item_quantity[]" value="1" step="0.01" min="1" required class="i-qty form-control form-control-sm"></td>
										<td><input type="number" name="item_price[]" step="0.01" min="0" required class="i-price form-control form-control-sm"></td>
										<td><input type="number" name="item_tax[]" value="<?php echo esc_attr( oby_mi_erp_get_setting( 'default_tax_rate', 0 ) ); ?>" step="0.01" min="0" class="i-tax form-control form-control-sm"></td>
										<td class="text-right i-line-total">—</td>
										<td><button type="button" class="btn-danger i-remove">×</button></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>

						<button type="button" class="btn-primary i-add mt-3"><?php esc_html_e( '+ Add Item', 'obydullah-micro-erp' ); ?></button>

						<div class="totals">
							<table>
								<tr>
									<td><?php esc_html_e( 'Subtotal:', 'obydullah-micro-erp' ); ?></td>
									<td class="t-subtotal">—</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Tax:', 'obydullah-micro-erp' ); ?></td>
									<td class="t-tax">—</td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Discount:', 'obydullah-micro-erp' ); ?></td>
									<td><input type="number" name="discount" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->discount ) : '0'; ?>" step="0.01" min="0" class="t-discount-input form-control form-control-sm text-right" style="width:110px;"></td>
								</tr>
								<tr class="grand-total">
									<td><?php esc_html_e( 'Total:', 'obydullah-micro-erp' ); ?></td>
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
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Notes', 'obydullah-micro-erp' ); ?></h2>
						<textarea name="notes" class="form-control" style="height:80px;resize:vertical;"><?php echo $oby_mi_erp_editing ? esc_textarea( $oby_mi_erp_editing->notes ) : ''; ?></textarea>
					</div>
				</div>
			</div>

			<div class="d-flex mt-2 mb-4">
				<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
				<button type="submit" class="btn-success"><?php esc_html_e( 'Save Sale', 'obydullah-micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="search-section mt-3">
			<div class="search-toolbar d-flex flex-wrap align-items-center gap-2">
				<span class="form-label mb-0"><?php esc_html_e( 'Payment Status', 'obydullah-micro-erp' ); ?></span>

				<?php
				$oby_mi_erp_pill_args = $oby_mi_erp_search ? array( 's' => $oby_mi_erp_search ) : array();
				$oby_mi_erp_all_url   = oby_mi_erp_admin_url( 'sales', $oby_mi_erp_pill_args );
				?>
				<div class="filter-pills" role="group" aria-label="<?php esc_attr_e( 'Filter by payment status', 'obydullah-micro-erp' ); ?>">
					<a href="<?php echo esc_url( $oby_mi_erp_all_url ); ?>" class="<?php echo esc_attr( '' === $oby_mi_erp_status_filter ? 'active' : '' ); ?>"><?php esc_html_e( 'All', 'obydullah-micro-erp' ); ?></a>
					<?php foreach ( array( 'paid', 'unpaid', 'partial' ) as $oby_mi_erp_status ) : ?>
						<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array_merge( $oby_mi_erp_pill_args, array( 'status' => $oby_mi_erp_status ) ) ) ); ?>" class="<?php echo esc_attr( $oby_mi_erp_status_filter === $oby_mi_erp_status ? 'active' : '' ); ?>"><?php echo esc_html( ucfirst( $oby_mi_erp_status ) ); ?></a>
					<?php endforeach; ?>
				</div>

				<?php oby_mi_erp_render_search_bar( 'sales', __( 'Search Sales', 'obydullah-micro-erp' ), __( 'Search by sale # or customer...', 'obydullah-micro-erp' ), array( 'status' => $oby_mi_erp_status_filter ), $oby_mi_erp_search, true ); ?>
			</div>
		</div>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'All Sales', 'obydullah-micro-erp' ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="110"><?php esc_html_e( 'Sale #', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'obydullah-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Total', 'obydullah-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Paid', 'obydullah-micro-erp' ); ?></th>
									<th width="120" class="text-right"><?php esc_html_e( 'Balance', 'obydullah-micro-erp' ); ?></th>
									<th width="100"><?php esc_html_e( 'Payment', 'obydullah-micro-erp' ); ?></th>
									<th width="200" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
									<tr><td colspan="8" class="text-center p-4"><?php esc_html_e( 'No sales found.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php
								foreach ( $oby_mi_erp_rows as $oby_mi_erp_sale ) :
									$oby_mi_erp_balance = (float) $oby_mi_erp_sale->total - (float) $oby_mi_erp_sale->amount_paid;
									?>
									<tr>
										<td><strong><?php echo esc_html( $oby_mi_erp_sale->sale_no ); ?></strong></td>
										<td><?php echo esc_html( $oby_mi_erp_sale->customer ); ?></td>
										<td><?php echo esc_html( $oby_mi_erp_sale->sale_date ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_sale->total ) ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_sale->amount_paid ) ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_balance ) ); ?></td>
										<td><?php echo oby_mi_erp_status_badge( $oby_mi_erp_sale->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array( 'edit' => $oby_mi_erp_sale->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a>
												<?php if ( $oby_mi_erp_balance > 0 ) : ?>
													<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'sales', array( 'pay' => $oby_mi_erp_sale->id ) ) ); ?>" class="pos-action pay"><?php esc_html_e( 'Record Payment', 'obydullah-micro-erp' ); ?></a>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'sales', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
