<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_edit_id = oby_mi_erp_query_int( 'edit' );
$oby_mi_erp_editing = null;
if ( $oby_mi_erp_edit_id ) {
	$oby_mi_erp_editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE id = %d", $oby_mi_erp_edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-row lookup gating a write flow; caches are flushed downstream.
}

$oby_mi_erp_search = oby_mi_erp_query_text( 's' );

$oby_mi_erp_per_page = 20;
$oby_mi_erp_paged    = max( 1, oby_mi_erp_query_int( 'paged', 1 ) );

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code LIKE %s OR name LIKE %s",
			$oby_mi_erp_like,
			$oby_mi_erp_like
		)
	);
} else {
	$oby_mi_erp_total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE 1 = %d",
			1
		)
	);
}

$oby_mi_erp_total_pages = max( 1, (int) ceil( $oby_mi_erp_total_items / $oby_mi_erp_per_page ) );
$oby_mi_erp_paged       = min( $oby_mi_erp_paged, $oby_mi_erp_total_pages );
$oby_mi_erp_offset      = ( $oby_mi_erp_paged - 1 ) * $oby_mi_erp_per_page;

if ( $oby_mi_erp_search ) {
	$oby_mi_erp_like = '%' . $wpdb->esc_like( $oby_mi_erp_search ) . '%';
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts WHERE code LIKE %s OR name LIKE %s ORDER BY code ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_like,
			$oby_mi_erp_like,
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
} else {
	$oby_mi_erp_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- filtered admin list query; caching would multiply keys by every filter/page combo without meaningful benefit.
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}oby_mi_erp_accounts ORDER BY code ASC LIMIT %d OFFSET %d",
			$oby_mi_erp_per_page,
			$oby_mi_erp_offset
		)
	);
}

$oby_mi_erp_account_types = array(
	'asset'      => __( 'Asset', 'obydullah-micro-erp' ),
	'liability'  => __( 'Liability', 'obydullah-micro-erp' ),
	'equity'     => __( 'Equity', 'obydullah-micro-erp' ),
	'income'     => __( 'Income', 'obydullah-micro-erp' ),
	'expense'    => __( 'Expense', 'obydullah-micro-erp' ),
);

$oby_mi_erp_type_badges = array(
	'asset'      => 'status-active',
	'liability'  => 'status-inactive',
	'equity'     => 'status-info',
	'income'     => 'status-info',
	'expense'    => 'status-warning',
);

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'accounts' );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $oby_mi_erp_editing ? esc_html__( 'Edit Account', 'obydullah-micro-erp' ) : esc_html__( 'Chart of Accounts', 'obydullah-micro-erp' ); ?>
		<?php if ( ! $oby_mi_erp_editing ) : ?>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'accounts', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Account', 'obydullah-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_editing || oby_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Account Details', 'obydullah-micro-erp' ); ?></h2>
					<form method="post" action="">
						<?php
						$action = $oby_mi_erp_editing ? 'update_account' : 'save_account';
						wp_nonce_field( 'oby_mi_erp_account_save' );
						?>
						<input type="hidden" name="oby_mi_erp_action" value="<?php echo esc_attr( $action ); ?>">
						<?php if ( $oby_mi_erp_editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

						<div class="mb-3">
							<label for="code" class="form-label"><?php esc_html_e( 'Code', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="code" id="code" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->code ) : ''; ?>" placeholder="e.g. 5006" required>
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $oby_mi_erp_editing ? esc_attr( $oby_mi_erp_editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="type" class="form-label"><?php esc_html_e( 'Type', 'obydullah-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="type" id="type" class="form-control" required>
								<?php foreach ( $oby_mi_erp_account_types as $oby_mi_erp_key => $oby_mi_erp_label ) : ?>
									<option value="<?php echo esc_attr( $oby_mi_erp_key ); ?>" <?php selected( $oby_mi_erp_editing ? $oby_mi_erp_editing->type : '', $oby_mi_erp_key ); ?>><?php echo esc_html( $oby_mi_erp_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="form-check">
							<input type="checkbox" name="is_active" id="is_active" class="form-check-input" <?php checked( $oby_mi_erp_editing ? (int) $oby_mi_erp_editing->is_active : 1 ); ?>>
							<label for="is_active" class="form-check-label"><?php esc_html_e( 'Active', 'obydullah-micro-erp' ); ?></label>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $oby_mi_erp_back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'obydullah-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Account', 'obydullah-micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

		<div class="row mt-3">
		<div class="col-lg-12">
			<?php oby_mi_erp_render_search_bar( 'accounts', __( 'Search Accounts', 'obydullah-micro-erp' ), __( 'Search by code or name...', 'obydullah-micro-erp' ), array(), $oby_mi_erp_search ); ?>
		</div>
	</div>

	<div class="row mt-1">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Accounts', 'obydullah-micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Code', 'obydullah-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'obydullah-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Type', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Balance', 'obydullah-micro-erp' ); ?></th>
									<th width="90"><?php esc_html_e( 'Status', 'obydullah-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'obydullah-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
								$oby_mi_erp_current_type = '';
								foreach ( $oby_mi_erp_rows as $oby_mi_erp_row ) :
									if ( $oby_mi_erp_current_type !== $oby_mi_erp_row->type ) :
										$oby_mi_erp_current_type = $oby_mi_erp_row->type;
										?>
										<tr class="type-group">
											<td colspan="6"><?php echo esc_html( strtoupper( isset( $oby_mi_erp_account_types[ $oby_mi_erp_current_type ] ) ? $oby_mi_erp_account_types[ $oby_mi_erp_current_type ] : $oby_mi_erp_current_type ) ); ?></td>
										</tr>
									<?php endif; ?>
									<tr>
										<td><?php echo esc_html( $oby_mi_erp_row->code ); ?></td>
										<td><?php echo esc_html( $oby_mi_erp_row->name ); ?></td>
										<td><?php echo oby_mi_erp_badge( isset( $oby_mi_erp_account_types[ $oby_mi_erp_row->type ] ) ? $oby_mi_erp_account_types[ $oby_mi_erp_row->type ] : $oby_mi_erp_row->type, $oby_mi_erp_type_badges ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( oby_mi_erp_format_money( oby_mi_erp_account_balance( $oby_mi_erp_row->id ) ) ); ?></td>
										<td><?php echo $oby_mi_erp_row->is_active ? '<span class="status-badge status-active">' . esc_html__( 'Active', 'obydullah-micro-erp' ) . '</span>' : '<span class="status-badge status-neutral">' . esc_html__( 'Inactive', 'obydullah-micro-erp' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'accounts', array( 'edit' => $oby_mi_erp_row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this account?', 'obydullah-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'oby_mi_erp_account_delete' ); ?>
													<input type="hidden" name="oby_mi_erp_action" value="delete_account">
													<input type="hidden" name="id" value="<?php echo (int) $oby_mi_erp_row->id; ?>">
													<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'obydullah-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php oby_mi_erp_render_pagination( 'accounts', $oby_mi_erp_total_items, $oby_mi_erp_per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
