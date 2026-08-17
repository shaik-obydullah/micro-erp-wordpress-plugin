<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$fy         = micro_erp_get_active_fiscal_year();
$accounts   = micro_erp_get_accounts();
$income_accounts   = micro_erp_get_accounts( 'income' );
$expense_accounts  = micro_erp_get_accounts( 'expense' );
$asset_accounts    = micro_erp_get_accounts( 'asset' );

micro_erp_print_admin_notice();

$back_url = add_query_arg( array( 'page' => 'micro-erp/settings' ), admin_url( 'admin.php' ) );

$currency = micro_erp_get_currency_symbol();
$company  = micro_erp_get_setting( 'company_name', '' );
$tax_rate = micro_erp_get_setting( 'default_tax_rate', 5 );
$def_income  = (int) micro_erp_get_setting( 'default_income_account', 0 );
$def_expense = (int) micro_erp_get_setting( 'default_expense_account', 0 );
$cash_acct   = (int) micro_erp_get_setting( 'cash_account', 0 );
?>
<div class="wrap micro-erp">
	<h1><?php esc_html_e( 'Settings', 'micro-erp' ); ?></h1>

	<?php if ( $fy ) : ?>
		<div class="current-fy">
			<strong><?php esc_html_e( 'Active Fiscal Year:', 'micro-erp' ); ?></strong>
			<?php echo esc_html( $fy->name ); ?> (<?php echo esc_html( $fy->start_date ); ?> - <?php echo esc_html( $fy->end_date ); ?>)
			<span class="badge badge-active" style="margin-left: 8px;"><?php esc_html_e( 'Active', 'micro-erp' ); ?></span>
			<br><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'micro-erp/fiscal-years' ), admin_url( 'admin.php' ) ) ); ?>" style="font-size: 13px;"><?php esc_html_e( 'Manage Fiscal Years', 'micro-erp' ); ?></a>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'micro_erp_settings_save' ); ?>
		<input type="hidden" name="micro_erp_action" value="save_settings">
		<input type="hidden" name="micro_erp_redirect" value="<?php echo esc_url( $back_url ); ?>">

		<h3 class="section-title"><?php esc_html_e( 'General Settings', 'micro-erp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="company_name"><?php esc_html_e( 'Company Name', 'micro-erp' ); ?></label></th>
				<td><input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $company ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="currency_symbol"><?php esc_html_e( 'Currency Symbol', 'micro-erp' ); ?></label></th>
				<td>
					<select id="currency_symbol" name="currency_symbol">
						<?php foreach ( array( '$' => 'USD', '€' => 'EUR', '£' => 'GBP', '৳' => 'BDT', '₹' => 'INR', '¥' => 'JPY' ) as $sym => $label ) : ?>
							<option value="<?php echo esc_attr( $sym ); ?>" <?php selected( $currency, $sym ); ?>><?php echo esc_html( $sym ); ?> - <?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<div class="description"><?php esc_html_e( 'Symbol displayed next to all amounts', 'micro-erp' ); ?></div>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_tax_rate"><?php esc_html_e( 'Default Tax Rate (%)', 'micro-erp' ); ?></label></th>
				<td>
					<input type="number" id="default_tax_rate" name="default_tax_rate" value="<?php echo esc_attr( $tax_rate ); ?>" step="0.01" min="0" max="100">
					<div class="description"><?php esc_html_e( 'Applied to new sales and quotations', 'micro-erp' ); ?></div>
				</td>
			</tr>
		</table>

		<h3 class="section-title"><?php esc_html_e( 'Accounting', 'micro-erp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="default_income_account"><?php esc_html_e( 'Default Income Account', 'micro-erp' ); ?></label></th>
				<td>
					<select id="default_income_account" name="default_income_account">
						<option value="0"><?php esc_html_e( '— Auto (first income account) —', 'micro-erp' ); ?></option>
						<?php foreach ( $income_accounts as $acct ) : ?>
							<option value="<?php echo (int) $acct->id; ?>" <?php selected( $def_income, $acct->id ); ?>><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_expense_account"><?php esc_html_e( 'Default Expense Account', 'micro-erp' ); ?></label></th>
				<td>
					<select id="default_expense_account" name="default_expense_account">
						<option value="0"><?php esc_html_e( '— Auto (first expense account) —', 'micro-erp' ); ?></option>
						<?php foreach ( $expense_accounts as $acct ) : ?>
							<option value="<?php echo (int) $acct->id; ?>" <?php selected( $def_expense, $acct->id ); ?>><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cash_account"><?php esc_html_e( 'Cash/Bank Account', 'micro-erp' ); ?></label></th>
				<td>
					<select id="cash_account" name="cash_account">
						<option value="0"><?php esc_html_e( '— Auto (first asset account) —', 'micro-erp' ); ?></option>
						<?php foreach ( $asset_accounts as $acct ) : ?>
							<option value="<?php echo (int) $acct->id; ?>" <?php selected( $cash_acct, $acct->id ); ?>><?php echo esc_html( $acct->code . ' - ' . $acct->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

		<h3 class="section-title"><?php esc_html_e( 'Modules', 'micro-erp' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="module_accounting"><?php esc_html_e( 'Accounting Module', 'micro-erp' ); ?></label></th>
				<td><input type="checkbox" id="module_accounting" name="module_accounting" <?php checked( (int) micro_erp_get_setting( 'module_accounting', 1 ) ); ?>> <?php esc_html_e( 'Enable', 'micro-erp' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="module_hrm"><?php esc_html_e( 'HRM Module', 'micro-erp' ); ?></label></th>
				<td><input type="checkbox" id="module_hrm" name="module_hrm" <?php checked( (int) micro_erp_get_setting( 'module_hrm', 1 ) ); ?>> <?php esc_html_e( 'Enable', 'micro-erp' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="module_sales"><?php esc_html_e( 'Sales Module', 'micro-erp' ); ?></label></th>
				<td><input type="checkbox" id="module_sales" name="module_sales" <?php checked( (int) micro_erp_get_setting( 'module_sales', 1 ) ); ?>> <?php esc_html_e( 'Enable', 'micro-erp' ); ?></td>
			</tr>
		</table>

		<button type="submit" class="btn btn-success"><?php esc_html_e( 'Save Settings', 'micro-erp' ); ?></button>
	</form>
</div>
