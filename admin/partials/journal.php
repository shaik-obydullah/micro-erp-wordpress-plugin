<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$show_form = oby_mi_erp_query_has( 'new' ) || oby_mi_erp_query_has( 'view' );
$view_id   = oby_mi_erp_query_int( 'view' );
$accounts  = oby_mi_erp_get_accounts();

$search = oby_mi_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_journal_entries WHERE description LIKE %s",
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_journal_entries WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like   = '%' . $wpdb->esc_like( $search ) . '%';
	$entries = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_journal_entries WHERE description LIKE %s ORDER BY entry_date DESC, id DESC LIMIT %d OFFSET %d",
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$entries = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_journal_entries ORDER BY entry_date DESC, id DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

$lines_by_entry = array();
if ( ! empty( $entries ) ) {
	$ids            = array_map( 'intval', array_column( $entries, 'id' ) );
	$in_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$lines          = $wpdb->get_results( $wpdb->prepare( "SELECT l.*, a.code, a.name FROM {$wpdb->prefix}oby_mi_erp_journal_lines l INNER JOIN {$wpdb->prefix}oby_mi_erp_accounts a ON a.id = l.account_id WHERE l.entry_id IN ({$in_placeholders}) ORDER BY l.id ASC", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $lines as $line ) {
		$lines_by_entry[ $line->entry_id ][] = $line;
	}
}

oby_mi_erp_print_admin_notice();

$back_url = oby_mi_erp_admin_url( 'journal' );
$from     = oby_mi_erp_query_key( 'from' );
if ( $from && ! in_array( $from, array( 'income', 'expense' ), true ) ) {
	$from = '';
}
if ( $from ) {
	$back_url = oby_mi_erp_admin_url( $from );
}

if ( $view_id ) {
	$view_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_journal_entries WHERE id = %d", $view_id ) );
	$view_lines = isset( $lines_by_entry[ $view_id ] ) ? $lines_by_entry[ $view_id ] : array();
}
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php
		if ( $show_form && $view_id ) {
			esc_html_e( 'View Journal Entry', 'obydullah-micro-erp' );
		} elseif ( $show_form ) {
			esc_html_e( 'New Journal Entry', 'obydullah-micro-erp' );
		} else {
			esc_html_e( 'Journal Entries', 'obydullah-micro-erp' );
		}
		?>
		<?php if ( ! $show_form ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'journal', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ New Entry', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $show_form && $view_id ) : ?>

		<div class="row mt-3">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php echo esc_html( $view_entry->description ); ?></h2>
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th><?php esc_html_e( 'Account', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
									<th width="140" class="text-right"><?php esc_html_e( 'Debit', 'obydullah-micro-erp' ); ?></th>
									<th width="140" class="text-right"><?php esc_html_e( 'Credit', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
								$td = 0;
								$tc = 0;
								foreach ( $view_lines as $line ) :
									$td += (float) $line->debit;
									$tc += (float) $line->credit;
									?>
									<tr>
										<td><?php echo esc_html( $line->code . ' - ' . $line->name ); ?></td>
										<td><?php echo esc_html( $line->description ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $line->debit ) ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $line->credit ) ); ?></td>
									</tr>
								<?php endforeach; ?>
								<tr class="total-row">
									<td colspan="2"><strong><?php esc_html_e( 'Total', 'obydullah-micro-erp' ); ?></strong></td>
									<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $td ) ); ?></strong></td>
									<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $tc ) ); ?></strong></td>
								</tr>
							</tbody>
						</table>
					</div>
					<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mt-2 d-inline-block">← <?php echo esc_html( $from ? __( 'Back to ', 'obydullah-micro-erp' ) . ( 'expense' === $from ? __( 'Expenses', 'obydullah-micro-erp' ) : __( 'Income', 'obydullah-micro-erp' ) ) : __( 'Back to Journal', 'obydullah-micro-erp' ) ); ?></a>
				</div>
			</div>
		</div>

	<?php elseif ( $show_form ) : ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'oby_mi_erp_journal_save' ); ?>
			<input type="hidden" name="oby_mi_erp_action" value="save_journal">
			<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="row mt-3">
				<div class="col-lg-6 col-md-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Entry Details', 'obydullah-micro-erp' ); ?></h2>

						<div class="mb-3">
							<label for="entry_date" class="form-label"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="date" name="entry_date" id="entry_date" class="form-control" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
						</div>

						<div class="mb-3">
							<label for="description" class="form-label"><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="description" id="description" class="form-control" placeholder="<?php esc_attr_e( 'Brief description of this entry', 'obydullah-micro-erp' ); ?>" required>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="bg-light p-4 rounded shadow-sm mb-4">
						<h2 class="mb-3 mt-1"><?php esc_html_e( 'Journal Lines', 'obydullah-micro-erp' ); ?></h2>

						<table class="journal-lines table table-striped table-hover table-bordered" id="journal-lines">
							<thead>
								<tr class="bg-primary text-white">
									<th style="width:30%;"><?php esc_html_e( 'Account', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Debit', 'obydullah-micro-erp' ); ?></th>
									<th width="130"><?php esc_html_e( 'Credit', 'obydullah-micro-erp' ); ?></th>
									<th width="50"></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<tr>
									<td>
										<select name="account_id[]" required class="form-control form-control-sm">
											<option value=""><?php esc_html_e( 'Select Account', 'obydullah-micro-erp' ); ?></option>
											<?php foreach ( $accounts as $acct ) : ?>
												<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td><input type="text" name="line_description[]" class="form-control form-control-sm"></td>
									<td><input type="number" name="debit[]" class="j-debit form-control form-control-sm text-right" step="0.01" min="0" placeholder="0.00"></td>
									<td><input type="number" name="credit[]" class="j-credit form-control form-control-sm text-right" step="0.01" min="0" placeholder="0.00"></td>
									<td><button type="button" class="btn-danger j-remove">×</button></td>
								</tr>
								<tr>
									<td>
										<select name="account_id[]" required class="form-control form-control-sm">
											<option value=""><?php esc_html_e( 'Select Account', 'obydullah-micro-erp' ); ?></option>
											<?php foreach ( $accounts as $acct ) : ?>
												<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td><input type="text" name="line_description[]" class="form-control form-control-sm"></td>
									<td><input type="number" name="debit[]" class="j-debit form-control form-control-sm text-right" step="0.01" min="0" placeholder="0.00"></td>
									<td><input type="number" name="credit[]" class="j-credit form-control form-control-sm text-right" step="0.01" min="0" placeholder="0.00"></td>
									<td><button type="button" class="btn-danger j-remove">×</button></td>
								</tr>
							</tbody>
							<tfoot>
								<tr class="total-row">
									<td colspan="2"><strong><?php esc_html_e( 'Total', 'obydullah-micro-erp' ); ?></strong></td>
									<td class="text-right"><strong class="j-total-debit">0.00</strong></td>
									<td class="text-right"><strong class="j-total-credit">0.00</strong></td>
									<td></td>
								</tr>
							</tfoot>
						</table>

						<button type="button" class="btn-primary j-add-line mt-3"><?php esc_html_e( '+ Add Line', 'obydullah-micro-erp' ); ?></button>
						<p class="j-balance-note form-text mt-1"><?php esc_html_e( 'Debit and Credit totals must be equal.', 'obydullah-micro-erp' ); ?></p>
					</div>
				</div>
			</div>

			<div class="d-flex mt-2 mb-4">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
				<button type="submit" class="btn-success"><?php esc_html_e( 'Save Journal Entry', 'obydullah-micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'journal', __( 'Search Entries', 'obydullah-micro-erp' ), __( 'Search by description...', 'obydullah-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'All Entries', 'obydullah-micro-erp' ); ?></h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="110"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
									<th width="80">#</th>
									<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Source', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Debit', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Credit', 'obydullah-micro-erp' ); ?></th>
									<th width="150" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php if ( empty( $entries ) ) : ?>
									<tr><td colspan="7" class="text-center p-4"><?php esc_html_e( 'No journal entries yet.', 'obydullah-micro-erp' ); ?></td></tr>
								<?php endif; ?>
								<?php foreach ( $entries as $entry ) :
									$entry_lines = isset( $lines_by_entry[ $entry->id ] ) ? $lines_by_entry[ $entry->id ] : array();
									$t_d = 0;
									$t_c = 0;
									foreach ( $entry_lines as $l ) {
										$t_d += (float) $l->debit;
										$t_c += (float) $l->credit;
									}
									?>
									<tr>
										<td><?php echo esc_html( $entry->entry_date ); ?></td>
										<td>JE-<?php echo (int) $entry->id; ?></td>
										<td><strong><?php echo esc_html( $entry->description ); ?></strong></td>
										<td><?php echo esc_html( $entry->reference_type ? ucwords( str_replace( '_', ' ', $entry->reference_type ) ) : '—' ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $t_d ) ); ?></td>
										<td class="text-right"><?php echo esc_html( oby_mi_erp_format_money( $t_c ) ); ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'journal', array( 'view' => $entry->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this entry?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_journal_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_journal">
													<input type="hidden" name="id" value="<?php echo (int) $entry->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'journal', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
