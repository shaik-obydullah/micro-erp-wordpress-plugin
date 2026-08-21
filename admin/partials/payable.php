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
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Payable', 'micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money you owe to vendors and suppliers', 'micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Outstanding Payables', 'micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="110"><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
								<th width="90"><?php esc_html_e( 'Reference', 'micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Payable', 'micro-erp' ); ?></th>
								<th width="110" class="text-right"><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No payables recorded.', 'micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) :
								$total += (float) $row->payable_amount;
								?>
								<tr>
									<td><?php echo esc_html( $row->entry_date ); ?></td>
									<td>JE-<?php echo (int) $row->entry_id; ?></td>
									<td><?php echo esc_html( $row->description ); ?></td>
									<td class="text-right"><strong style="color:#d63638;"><?php echo esc_html( micro_erp_format_money( $row->payable_amount ) ); ?></strong></td>
									<td class="text-right"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/journal', 'view' => $row->entry_id ), admin_url( 'admin.php' ) ) ); ?>" class="pos-action edit"><?php esc_html_e( 'View', 'micro-erp' ); ?></a></td>
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
	</div>
</div>
