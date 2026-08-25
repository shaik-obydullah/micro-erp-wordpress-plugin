<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = li_mi_erp_query_int( 'edit' );
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}micro_erp_accounts WHERE id = %d", $edit_id ) );
}

$search = li_mi_erp_query_text( 's' );

$per_page = 20;
$paged    = max( 1, li_mi_erp_query_int( 'paged', 1 ) );

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_accounts WHERE code LIKE %s OR name LIKE %s",
			$like,
			$like
		)
	);
} else {
	$total_items = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}micro_erp_accounts WHERE 1 = %d",
			1
		)
	);
}

$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;

if ( $search ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}micro_erp_accounts WHERE code LIKE %s OR name LIKE %s ORDER BY code ASC LIMIT %d OFFSET %d",
			$like,
			$like,
			$per_page,
			$offset
		)
	);
} else {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}micro_erp_accounts ORDER BY code ASC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);
}

$account_types = array(
	'asset'      => __( 'Asset', 'lime-micro-erp' ),
	'liability'  => __( 'Liability', 'lime-micro-erp' ),
	'equity'     => __( 'Equity', 'lime-micro-erp' ),
	'income'     => __( 'Income', 'lime-micro-erp' ),
	'expense'    => __( 'Expense', 'lime-micro-erp' ),
);

$type_badges = array(
	'asset'      => 'status-active',
	'liability'  => 'status-inactive',
	'equity'     => 'status-info',
	'income'     => 'status-info',
	'expense'    => 'status-warning',
);

li_mi_erp_print_admin_notice();

$back_url = li_mi_erp_admin_url( 'accounts' );
?>
<div class="wrap micro-erp-page">
	<h1 class="wp-heading-inline mb-3">
		<?php echo $editing ? esc_html__( 'Edit Account', 'lime-micro-erp' ) : esc_html__( 'Chart of Accounts', 'lime-micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( li_mi_erp_admin_url( 'accounts', array( 'new' => '1' ) ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Account', 'lime-micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $editing || li_mi_erp_query_has( 'new' ) ) : ?>

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm">
					<h2 id="form-title" class="mb-3 mt-1"><?php esc_html_e( 'Account Details', 'lime-micro-erp' ); ?></h2>
					<form method="post" action="">
						<?php
						$action = $editing ? 'update_account' : 'save_account';
						wp_nonce_field( 'li_mi_erp_account_save' );
						?>
						<input type="hidden" name="li_mi_erp_action" value="<?php echo esc_attr( $action ); ?>">
						<?php if ( $editing ) : ?>
							<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
						<?php endif; ?>
						<input type="hidden" name="li_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

						<div class="mb-3">
							<label for="code" class="form-label"><?php esc_html_e( 'Code', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="code" id="code" class="form-control" value="<?php echo $editing ? esc_attr( $editing->code ) : ''; ?>" placeholder="e.g. 5006" required>
						</div>

						<div class="mb-3">
							<label for="name" class="form-label"><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<input type="text" name="name" id="name" class="form-control" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required>
						</div>

						<div class="mb-3">
							<label for="type" class="form-label"><?php esc_html_e( 'Type', 'lime-micro-erp' ); ?> <span class="text-danger">*</span></label>
							<select name="type" id="type" class="form-control" required>
								<?php foreach ( $account_types as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $editing ? $editing->type : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="form-check">
							<input type="checkbox" name="is_active" id="is_active" class="form-check-input" <?php checked( $editing ? (int) $editing->is_active : 1 ); ?>>
							<label for="is_active" class="form-check-label"><?php esc_html_e( 'Active', 'lime-micro-erp' ); ?></label>
						</div>

						<div class="d-flex mt-4">
							<a href="<?php echo esc_url( $back_url ); ?>" class="btn-secondary mr-2"><?php esc_html_e( 'Cancel', 'lime-micro-erp' ); ?></a>
							<button type="submit" class="btn-success"><?php esc_html_e( 'Save Account', 'lime-micro-erp' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php else : ?>

		<div class="row mt-3">
		<div class="col-lg-12">
			<?php li_mi_erp_render_search_bar( 'accounts', __( 'Search Accounts', 'lime-micro-erp' ), __( 'Search by code or name...', 'lime-micro-erp' ), array(), $search ); ?>
		</div>
	</div>

	<div class="row mt-1">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm border">
					<h2 class="h5 mb-3 fw-semibold">
						<?php esc_html_e( 'All Accounts', 'lime-micro-erp' ); ?>
					</h2>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered mb-2">
							<thead>
								<tr class="bg-primary text-white">
									<th width="90"><?php esc_html_e( 'Code', 'lime-micro-erp' ); ?></th>
									<th><?php esc_html_e( 'Name', 'lime-micro-erp' ); ?></th>
									<th width="110"><?php esc_html_e( 'Type', 'lime-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Balance', 'lime-micro-erp' ); ?></th>
									<th width="90"><?php esc_html_e( 'Status', 'lime-micro-erp' ); ?></th>
									<th width="130" class="text-right"><?php esc_html_e( 'Actions', 'lime-micro-erp' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
								$current_type = '';
								foreach ( $rows as $row ) :
									if ( $current_type !== $row->type ) :
										$current_type = $row->type;
										?>
										<tr class="type-group">
											<td colspan="6"><?php echo esc_html( strtoupper( isset( $account_types[ $current_type ] ) ? $account_types[ $current_type ] : $current_type ) ); ?></td>
										</tr>
									<?php endif; ?>
									<tr>
										<td><?php echo esc_html( $row->code ); ?></td>
										<td><?php echo esc_html( $row->name ); ?></td>
										<td><?php echo li_mi_erp_badge( isset( $account_types[ $row->type ] ) ? $account_types[ $row->type ] : $row->type, $type_badges ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
										<td class="text-right fw-bold"><?php echo esc_html( li_mi_erp_format_money( li_mi_erp_account_balance( $row->id ) ) ); ?></td>
										<td><?php echo $row->is_active ? '<span class="status-badge status-active">' . esc_html__( 'Active', 'lime-micro-erp' ) . '</span>' : '<span class="status-badge status-neutral">' . esc_html__( 'Inactive', 'lime-micro-erp' ) . '</span>'; ?></td>
										<td>
											<div class="pos-row-actions">
												<a href="<?php echo esc_url( li_mi_erp_admin_url( 'accounts', array( 'edit' => $row->id ) ) ); ?>" class="pos-action edit pos-icon" aria-label="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Edit', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
												<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this account?', 'lime-micro-erp' ); ?>');">
													<?php wp_nonce_field( 'li_mi_erp_account_delete' ); ?>
													<input type="hidden" name="li_mi_erp_action" value="delete_account">
													<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
													<input type="hidden" name="li_mi_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
													<button type="submit" class="pos-action delete pos-icon" aria-label="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>" title="<?php esc_attr_e( 'Delete', 'lime-micro-erp' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php li_mi_erp_render_pagination( 'accounts', $total_items, $per_page ); ?>

				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
