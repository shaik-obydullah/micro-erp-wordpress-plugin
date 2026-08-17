<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT s.*, c.name AS customer
	FROM " . micro_erp_table( 'sales' ) . " s
	INNER JOIN " . micro_erp_table( 'contacts' ) . " c ON c.id = s.contact_id
	WHERE s.payment_status != 'paid'
	ORDER BY s.sale_date ASC"
);

$total_original = 0;
$total_paid     = 0;
$total_balance  = 0;

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/receivable' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Accounts Receivable', 'micro-erp' ); ?></h1>
	<p class="micro-erp-fy-label"><?php esc_html_e( 'Money owed to you by customers', 'micro-erp' ); ?></p>

	<div class="card">
		<div class="card-body" style="padding: 0;">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Customer', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Invoice/Sale #', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Date', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Original Amount', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Paid', 'micro-erp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Balance', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="8"><?php esc_html_e( 'Nothing owed to you. Nice!', 'micro-erp' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) :
						$balance = (float) $row->total - (float) $row->amount_paid;
						$total_original += (float) $row->total;
						$total_paid     += (float) $row->amount_paid;
						$total_balance  += $balance;
						$overdue = strtotime( $row->sale_date . ' +30 days' ) < time();
						?>
						<tr>
							<td><strong><?php echo esc_html( $row->customer ); ?></strong></td>
							<td><?php echo esc_html( $row->sale_no ); ?></td>
							<td><?php echo esc_html( $row->sale_date ); ?></td>
							<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->total ) ); ?></td>
							<td class="text-right"><?php echo esc_html( micro_erp_format_money( $row->amount_paid ) ); ?></td>
							<td class="text-right"><strong<?php echo $overdue ? ' class="overdue"' : ''; ?>><?php echo esc_html( micro_erp_format_money( $balance ) ); ?></strong></td>
							<td><?php echo micro_erp_status_badge( $row->payment_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/sales', 'pay' => $row->id ), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-success btn-sm"><?php esc_html_e( 'Record Payment', 'micro-erp' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr class="total-row">
						<td colspan="3"><strong><?php esc_html_e( 'Total Receivable', 'micro-erp' ); ?></strong></td>
						<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_original ) ); ?></strong></td>
						<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_paid ) ); ?></strong></td>
						<td class="text-right"><strong><?php echo esc_html( micro_erp_format_money( $total_balance ) ); ?></strong></td>
						<td></td>
						<td></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
