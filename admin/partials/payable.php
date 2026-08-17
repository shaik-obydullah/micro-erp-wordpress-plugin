<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Payable = amounts owed to vendors, tracked via the Accounts Payable account.
$ap_account = $wpdb->get_row( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE code = '2001' LIMIT 1" );

$rows = array();
if ( $ap_account ) {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
			FROM " . micro_erp_table( 'journal_lines' ) . " l
			INNER JOIN " . micro_erp_table( 'journal_entries' ) . " j ON j.id = l.entry_id
			WHERE l.account_id = %d AND l.credit > 0
			ORDER BY j.entry_date DESC",
			$ap_account->id
		)
	);
}

$total = 0;
micro_erp_print_admin_notice();
?>
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Accounts Payable', 'micro-erp' ); ?></h1>
	<p class="micro-erp-fy-label"><?php esc_html_e( 'Money you owe to vendors and suppliers', 'micro-erp' ); ?></p>

	<div class="card">
		<div class="card-body" style="padding: 0;">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Reference', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Amount Payable', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No payables recorded.', 'micro-erp' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) :
						$total += (float) $row->payable_amount;
						?>
						<tr>
							<td><?php echo esc_html( $row->entry_date ); ?></td>
							<td>JE-<?php echo (int) $row->entry_id; ?></td>
							<td><?php echo esc_html( $row->description ); ?></td>
							<td class="text-right"><strong class="overdue"><?php echo esc_html( micro_erp_format_money( $row->payable_amount ) ); ?></strong></td>
							<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/journal', 'view' => $row->entry_id ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'View', 'micro-erp' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					<tr class="total-row">
						<td colspan="3"><strong><?php esc_html_e( 'Total Payable', 'micro-erp' ); ?></strong></td>
						<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total ) ); ?></strong></td>
						<td></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
