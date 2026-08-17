<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$show_form = isset( $_GET['new'] ) || isset( $_GET['view'] );
$view_id   = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
$accounts  = micro_erp_get_accounts();

$entries = $wpdb->get_results( "SELECT * FROM " . micro_erp_table( 'journal_entries' ) . " ORDER BY entry_date DESC, id DESC" );

$lines_by_entry = array();
if ( ! empty( $entries ) ) {
	$ids   = array_column( $entries, 'id' );
	$in    = implode( ',', array_map( 'intval', $ids ) );
	$lines = $wpdb->get_results( "SELECT l.*, a.code, a.name FROM " . micro_erp_table( 'journal_lines' ) . " l INNER JOIN " . micro_erp_table( 'accounts' ) . " a ON a.id = l.account_id WHERE l.entry_id IN ({$in}) ORDER BY l.id ASC" );
	foreach ( $lines as $line ) {
		$lines_by_entry[ $line->entry_id ][] = $line;
	}
}

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/journal' ), admin_url( 'admin.php' ) );

if ( $view_id ) {
	$view_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'journal_entries' ) . " WHERE id = %d", $view_id ) );
	$view_lines = isset( $lines_by_entry[ $view_id ] ) ? $lines_by_entry[ $view_id ] : array();
}
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $show_form ? esc_html__( 'New Journal Entry', 'micro-erp' ) : esc_html__( 'Journal Entries', 'micro-erp' ); ?>
		<?php if ( ! $show_form ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ New Entry', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $show_form && $view_id ) : ?>

		<div class="card">
			<div class="card-header"><?php echo esc_html( $view_entry->description ); ?></div>
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Account', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Debit', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Credit', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
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
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $line->debit ) ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $line->credit ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr class="total-row">
							<td colspan="2"><strong><?php esc_html_e( 'Total', 'micro-erp' ); ?></strong></td>
							<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $td ) ); ?></strong></td>
							<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $tc ) ); ?></strong></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( '← Back to Journal', 'micro-erp' ); ?></a>

	<?php elseif ( $show_form ) : ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'micro_erp_journal_save' ); ?>
			<input type="hidden" name="micro_erp_action" value="save_journal">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Entry Details', 'micro-erp' ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="entry_date"><?php esc_html_e( 'Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="entry_date" id="entry_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="description"><?php esc_html_e( 'Description', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="description" id="description" placeholder="<?php esc_attr_e( 'Brief description of this entry', 'micro-erp' ); ?>" required></td>
						</tr>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><?php esc_html_e( 'Journal Lines', 'micro-erp' ); ?></div>
				<div class="card-body">
					<table class="journal-lines" id="journal-lines">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Account', 'micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Debit', 'micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Credit', 'micro-erp' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<select name="account_id[]" required>
										<option value=""><?php esc_html_e( 'Select Account', 'micro-erp' ); ?></option>
										<?php foreach ( $accounts as $acct ) : ?>
											<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td><input type="text" name="line_description[]"></td>
								<td><input type="number" name="debit[]" class="j-debit" step="0.01" min="0" placeholder="0.00"></td>
								<td><input type="number" name="credit[]" class="j-credit" step="0.01" min="0" placeholder="0.00"></td>
								<td><button type="button" class="btn btn-danger btn-sm j-remove">×</button></td>
							</tr>
							<tr>
								<td>
									<select name="account_id[]" required>
										<option value=""><?php esc_html_e( 'Select Account', 'micro-erp' ); ?></option>
										<?php foreach ( $accounts as $acct ) : ?>
											<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td><input type="text" name="line_description[]"></td>
								<td><input type="number" name="debit[]" class="j-debit" step="0.01" min="0" placeholder="0.00"></td>
								<td><input type="number" name="credit[]" class="j-credit" step="0.01" min="0" placeholder="0.00"></td>
								<td><button type="button" class="btn btn-danger btn-sm j-remove">×</button></td>
							</tr>
						</tbody>
						<tfoot>
							<tr class="total-row">
								<td colspan="2"><strong><?php esc_html_e( 'Total', 'micro-erp' ); ?></strong></td>
								<td class="text-right"><strong class="j-total-debit">0.00</strong></td>
								<td class="text-right"><strong class="j-total-credit">0.00</strong></td>
								<td></td>
							</tr>
						</tfoot>
					</table>
					<button type="button" class="btn btn-primary j-add-line" style="margin-top: 12px;"><?php esc_html_e( '+ Add Line', 'micro-erp' ); ?></button>
					<p class="j-balance-note" style="margin-top: 8px; font-size: 12px; color: #646970;"><?php esc_html_e( 'Debit and Credit totals must be equal.', 'micro-erp' ); ?></p>
				</div>
			</div>

			<div class="actions-bar">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Journal Entry', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
							<th>#</th>
							<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Source', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Debit', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Credit', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $entries ) ) : ?>
							<tr><td colspan="7"><?php esc_html_e( 'No journal entries yet.', 'micro-erp' ); ?></td></tr>
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
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $t_d ) ); ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( $t_c ) ); ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'view', $entry->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'View', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this entry?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_journal_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_journal">
											<input type="hidden" name="id" value="<?php echo (int) $entry->id; ?>">
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
