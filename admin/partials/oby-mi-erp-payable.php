<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Payable = amounts owed to vendors, tracked via the Accounts Payable account.
$oby_mi_erp_ap_account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code = %s LIMIT %d", '2001', 1 ) );

$oby_mi_erp_rows = array();
$oby_mi_erp_total_items = 0;
$oby_mi_erp_total = 0;
if ( $oby_mi_erp_ap_account ) {
	$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

	$oby_mi_erp_per_page = 20;
	$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

	if ( $oby_mi_erp_search ) {
		$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
		$oby_mi_erp_total_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s",
				$oby_mi_erp_ap_account->id,
				$oby_mi_erp_like
			)
		);
	} else {
		$oby_mi_erp_total_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0",
				$oby_mi_erp_ap_account->id
			)
		);
	}

	$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
	$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
	$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

	// Grand total across ALL payable entries (footer row must not change with paging).
	if ( $oby_mi_erp_search ) {
		$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
		$oby_mi_erp_total = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(l.credit),0) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s",
				$oby_mi_erp_ap_account->id,
				$oby_mi_erp_like
			)
		);
	} else {
		$oby_mi_erp_total = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(l.credit),0) FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0",
				$oby_mi_erp_ap_account->id
			)
		);
	}

	if ( $oby_mi_erp_search ) {
		$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
		$oby_mi_erp_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
				FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0 AND j.description LIKE %s
				ORDER BY j.entry_date DESC LIMIT %d OFFSET %d",
				$oby_mi_erp_ap_account->id,
				$oby_mi_erp_like,
				$oby_mi_erp_per_page,
				$oby_mi_erp_offset
			)
		);
	} else {
		$oby_mi_erp_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT j.entry_date, j.description, j.id AS entry_id, l.credit AS payable_amount
				FROM {$wpdb->prefix}oby_mi_erp_journal_lines l
				INNER JOIN {$wpdb->prefix}oby_mi_erp_journal_entries j ON j.id = l.entry_id
				WHERE l.account_id = %d AND l.credit > 0
				ORDER BY j.entry_date DESC LIMIT %d OFFSET %d",
				$oby_mi_erp_ap_account->id,
				$oby_mi_erp_per_page,
				$oby_mi_erp_offset
			)
		);
	}
} else {
	$oby_mi_erp_search = '';
}
oby_mi_erp_print_admin_notice();
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Accounts Payable', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">
	<p class="text-muted mt-1"><?php esc_html_e( 'Money you owe to vendors and suppliers', 'obydullah-micro-erp' ); ?></p>

	<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'payable', __( 'Search Payables', 'obydullah-micro-erp' ), __( 'Search by description...', 'obydullah-micro-erp' ), array(), $oby_mi_erp_search ); ?>
		</div>
	</div>

	<div class="row mt-1">
		<div class="col-lg-12">
			<div class="bg-light p-3 rounded shadow-sm border">
				<h2 class="h5 mb-3 fw-semibold"><?php esc_html_e( 'Outstanding Payables', 'obydullah-micro-erp' ); ?></h2>

				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered mb-2">
						<thead>
							<tr class="bg-primary text-white">
								<th width="110"><?php esc_html_e( 'Date', 'obydullah-micro-erp' ); ?></th>
								<th width="90"><?php esc_html_e( 'Reference', 'obydullah-micro-erp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'obydullah-micro-erp' ); ?></th>
								<th width="150" class="text-right"><?php esc_html_e( 'Amount Payable', 'obydullah-micro-erp' ); ?></th>
								<th width="110" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
							</tr>
						</thead>
						<tbody class="bg-white">
							<?php if ( empty( $oby_mi_erp_rows ) ) : ?>
								<tr><td colspan="5" class="text-center p-4"><?php esc_html_e( 'No payables recorded.', 'obydullah-micro-erp' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) : ?>
								<tr>
									<td><?php echo esc_html( $oby_mi_erp_row->entry_date ); ?></td>
									<td>JE-<?php echo (int) $oby_mi_erp_row->entry_id; ?></td>
									<td><?php echo esc_html( $oby_mi_erp_row->description ); ?></td>
									<td class="text-right"><strong style="color:#d63638;"><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_row->payable_amount ) ); ?></strong></td>
									<td class="text-right"><a href="<?php echo esc_url( oby_mi_erp_admin_url( 'journal', array( 'view' => $oby_mi_erp_row->entry_id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'View', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></a></td>
								</tr>
							<?php endforeach; ?>
							<tr class="total-row">
								<td colspan="3"><strong><?php esc_html_e( 'Total Payable', 'obydullah-micro-erp' ); ?></strong></td>
								<td class="text-right"><strong><?php echo esc_html( oby_mi_erp_format_money( $oby_mi_erp_total ) ); ?></strong></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php oby_mi_erp_render_pagination( 'payable', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

			</div>
		</div>
	</div>
</div>
