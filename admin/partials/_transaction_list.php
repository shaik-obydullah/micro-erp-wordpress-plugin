<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tx_mode = ( 'expense' === $tx_mode ) ? 'expense' : 'income';
$label   = 'expense' === $tx_mode ? __( 'Expenses', 'micro-erp' ) : __( 'Income', 'micro-erp' );
$add_btn = 'expense' === $tx_mode ? __( '+ Add Expense', 'micro-erp' ) : __( '+ Add Income', 'micro-erp' );
$acct_type = 'expense' === $tx_mode ? 'expense' : 'income';

global $wpdb;
$accounts = micro_erp_get_accounts( $acct_type );

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT j.*, l.debit, l.credit, l.account_id, a.code AS account_code, a.name AS account_name
		FROM " . micro_erp_table( 'journal_entries' ) . " j
		INNER JOIN " . micro_erp_table( 'journal_lines' ) . " l ON l.entry_id = j.id
		INNER JOIN " . micro_erp_table( 'accounts' ) . " a ON a.id = l.account_id
		WHERE a.type = %s ORDER BY j.entry_date DESC, j.id DESC",
		$acct_type
	)
);

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/' . $tx_mode ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp">
	<h1><?php echo esc_html( $label ); ?></h1>

	<?php if ( isset( $_GET['new'] ) ) : ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'micro_erp_journal_save' ); ?>
			<input type="hidden" name="micro_erp_action" value="save_transaction">
			<input type="hidden" name="tx_mode" value="<?php echo esc_attr( $tx_mode ); ?>">
			<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

			<div class="card">
				<div class="card-header"><?php echo esc_html( $add_btn ); ?></div>
				<div class="card-body" style="padding: 0;">
					<table class="form-table">
						<tr>
							<th><label for="entry_date"><?php esc_html_e( 'Date', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="date" name="entry_date" id="entry_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
						</tr>
						<tr>
							<th><label for="description"><?php esc_html_e( 'Description', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="description" id="description" required></td>
						</tr>
						<tr>
							<th><label for="amount"><?php esc_html_e( 'Amount', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="number" name="amount" id="amount" step="0.01" min="0.01" required></td>
						</tr>
						<tr>
							<th><label for="account"><?php esc_html_e( 'Account', 'micro-erp' ); ?></label></th>
							<td>
								<select name="account_id" id="account">
									<option value="0"><?php esc_html_e( '— Default —', 'micro-erp' ); ?></option>
									<?php foreach ( $accounts as $acct ) : ?>
										<option value="<?php echo (int) $acct->id; ?>"><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="actions-bar">
				<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
				<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save', 'micro-erp' ); ?></button>
			</div>
		</form>

	<?php else : ?>

		<div class="card">
			<div class="card-header">
				<span><?php echo esc_html( $label ); ?> <?php esc_html_e( 'Entries', 'micro-erp' ); ?></span>
				<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-success"><?php echo esc_html( $add_btn ); ?></a>
			</div>
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Account', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Source', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Amount', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total = 0;
						if ( empty( $rows ) ) :
							?>
							<tr><td colspan="6"><?php esc_html_e( 'No entries yet.', 'micro-erp' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $row ) :
							$amount = 'expense' === $tx_mode ? (float) $row->debit : (float) $row->credit;
							$total += $amount;
							?>
							<tr>
								<td><?php echo esc_html( $row->entry_date ); ?></td>
								<td><?php echo esc_html( $row->description ); ?></td>
								<td><?php echo esc_html( $row->account_code . ' - ' . $row->account_name ); ?></td>
								<td><?php echo esc_html( $row->reference_type ? strtoupper( $row->reference_type ) : '—' ); ?></td>
								<td class="text-right"><strong style="color: <?php echo 'expense' === $tx_mode ? '#d63638' : '#00a32a'; ?>;"><?php echo esc_html( micro_erp_format_money( $amount ) ); ?></strong></td>
								<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/journal', 'view' => $row->id ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'View', 'micro-erp' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
						<tr class="total-row">
							<td colspan="4"><strong><?php esc_html_e( 'Total', 'micro-erp' ); ?></strong></td>
							<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total ) ); ?></strong></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

	<?php endif; ?>
</div>
