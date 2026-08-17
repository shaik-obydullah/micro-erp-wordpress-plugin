<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$editing = null;
if ( $edit_id ) {
	$editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . micro_erp_table( 'accounts' ) . " WHERE id = %d", $edit_id ) );
}

$rows = micro_erp_get_accounts();

$account_types = array(
	'asset'      => __( 'Asset', 'micro-erp' ),
	'liability'  => __( 'Liability', 'micro-erp' ),
	'equity'     => __( 'Equity', 'micro-erp' ),
	'income'     => __( 'Income', 'micro-erp' ),
	'expense'    => __( 'Expense', 'micro-erp' ),
);

$type_badges = array(
	'asset'      => 'badge-active',
	'liability'  => 'badge-inactive',
	'equity'     => 'badge-info',
	'income'     => 'badge-info',
	'expense'    => 'badge-warning',
);

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/accounts' ), admin_url( 'admin.php' ) );
?>
<div class="wrap micro-erp">
	<h1>
		<?php echo $editing ? esc_html__( 'Edit Account', 'micro-erp' ) : esc_html__( 'Chart of Accounts', 'micro-erp' ); ?>
		<?php if ( ! $editing ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'new', '1', $back_url ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ Add Account', 'micro-erp' ); ?></a>
		<?php endif; ?>
	</h1>

	<?php if ( $editing || isset( $_GET['new'] ) ) : ?>

		<div class="card">
			<div class="card-header"><?php esc_html_e( 'Account Details', 'micro-erp' ); ?></div>
			<div class="card-body" style="padding: 0;">
				<form method="post" action="">
					<?php
					$action = $editing ? 'update_account' : 'save_account';
					wp_nonce_field( 'micro_erp_account_save' );
					?>
					<input type="hidden" name="micro_erp_action" value="<?php echo esc_attr( $action ); ?>">
					<?php if ( $editing ) : ?>
						<input type="hidden" name="id" value="<?php echo (int) $editing->id; ?>">
					<?php endif; ?>
					<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">
					<table class="form-table">
						<tr>
							<th><label for="code"><?php esc_html_e( 'Code', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="code" id="code" value="<?php echo $editing ? esc_attr( $editing->code ) : ''; ?>" placeholder="e.g. 5006" required></td>
						</tr>
						<tr>
							<th><label for="name"><?php esc_html_e( 'Name', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" name="name" id="name" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required></td>
						</tr>
						<tr>
							<th><label for="type"><?php esc_html_e( 'Type', 'micro-erp' ); ?> <span class="required">*</span></label></th>
							<td>
								<select name="type" id="type" required>
									<?php foreach ( $account_types as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $editing ? $editing->type : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="is_active"><?php esc_html_e( 'Active', 'micro-erp' ); ?></label></th>
							<td><input type="checkbox" name="is_active" id="is_active" <?php checked( $editing ? (int) $editing->is_active : 1 ); ?>></td>
						</tr>
					</table>
					<div class="actions-bar">
						<a href="<?php echo esc_url( $back_url ); ?>" class="btn btn-cancel"><?php esc_html_e( 'Cancel', 'micro-erp' ); ?></a>
						<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Account', 'micro-erp' ); ?></button>
					</div>
				</form>
			</div>
		</div>

	<?php else : ?>

		<div class="card">
			<div class="card-body" style="padding: 0;">
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Code', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Name', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Type', 'micro-erp' ); ?></th>
							<th class="text-right"><?php esc_html_e( 'Balance', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'micro-erp' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'micro-erp' ); ?></th>
						</tr>
					</thead>
					<tbody>
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
								<td><?php echo micro_erp_badge( isset( $account_types[ $row->type ] ) ? $account_types[ $row->type ] : $row->type, $type_badges ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td class="text-right"><?php echo esc_html( micro_erp_format_money( micro_erp_account_balance( $row->id ) ) ); ?></td>
								<td><?php echo $row->is_active ? '<span class="badge badge-active">' . esc_html__( 'Active', 'micro-erp' ) . '</span>' : '<span class="badge badge-neutral">' . esc_html__( 'Inactive', 'micro-erp' ) . '</span>'; ?></td>
								<td>
									<div class="actions">
										<a href="<?php echo esc_url( add_query_arg( 'edit', $row->id, $back_url ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Edit', 'micro-erp' ); ?></a>
										<form method="post" action="" class="inline-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete this account?', 'micro-erp' ); ?>');">
											<?php wp_nonce_field( 'micro_erp_account_delete' ); ?>
											<input type="hidden" name="micro_erp_action" value="delete_account">
											<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
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
