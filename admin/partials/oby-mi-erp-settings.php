<?php
/**
 * Renders the plugin Settings admin screen (company profile and currency).
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$oby_mi_erp_fy               = oby_mi_erp_get_active_fiscal_year();
$oby_mi_erp_accounts         = oby_mi_erp_get_accounts();
$oby_mi_erp_income_accounts  = oby_mi_erp_get_accounts( 'income' );
$oby_mi_erp_expense_accounts = oby_mi_erp_get_accounts( 'expense' );
$oby_mi_erp_asset_accounts   = oby_mi_erp_get_accounts( 'asset' );

oby_mi_erp_print_admin_notice();

$oby_mi_erp_back_url = oby_mi_erp_admin_url( 'settings' );

$oby_mi_erp_currency    = oby_mi_erp_get_currency_symbol();
$oby_mi_erp_company     = oby_mi_erp_get_setting( 'company_name', '' );
$oby_mi_erp_tax_rate    = oby_mi_erp_get_setting( 'default_tax_rate', 5 );
$oby_mi_erp_def_income  = (int) oby_mi_erp_get_setting( 'default_income_account', 0 );
$oby_mi_erp_def_expense = (int) oby_mi_erp_get_setting( 'default_expense_account', 0 );
$oby_mi_erp_cash_acct   = (int) oby_mi_erp_get_setting( 'cash_account', 0 );
?>
<div class="wrap oby-mi-erp-page">
	<h1 class="wp-heading-inline mb-3"><?php esc_html_e( 'Settings', 'obydullah-micro-erp' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $oby_mi_erp_fy ) : ?>
		<div class="bg-light p-3 rounded shadow-sm border mt-3 mb-4 d-flex align-items-center gap-2 flex-wrap">
			<strong><?php esc_html_e( 'Active Fiscal Year:', 'obydullah-micro-erp' ); ?></strong>
			<?php echo esc_html( $oby_mi_erp_fy->name ); ?> (<?php echo esc_html( $oby_mi_erp_fy->start_date ); ?> - <?php echo esc_html( $oby_mi_erp_fy->end_date ); ?>)
			<span class="status-badge status-active"><?php esc_html_e( 'Active', 'obydullah-micro-erp' ); ?></span>
			<a href="<?php echo esc_url( oby_mi_erp_admin_url( 'fiscal-years' ) ); ?>" class="btn-secondary ml-auto">
				<?php esc_html_e( 'Manage Fiscal Years', 'obydullah-micro-erp' ); ?>
				<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
			</a>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'oby_mi_erp_settings_save' ); ?>
		<input type="hidden" name="oby_mi_erp_action" value="save_settings">
		<input type="hidden" name="oby_mi_erp_redirect" value="<?php echo esc_url( $oby_mi_erp_back_url ); ?>">

		<div class="row mt-3">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm mb-4">
					<h2 class="mb-3 mt-1"><?php esc_html_e( 'General Settings', 'obydullah-micro-erp' ); ?></h2>

					<div class="mb-3">
						<label for="company_name" class="form-label"><?php esc_html_e( 'Company Name', 'obydullah-micro-erp' ); ?></label>
						<input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo esc_attr( $oby_mi_erp_company ); ?>">
					</div>

					<div class="mb-3">
						<label for="currency_symbol" class="form-label"><?php esc_html_e( 'Currency Symbol', 'obydullah-micro-erp' ); ?></label>
						<select id="currency_symbol" name="currency_symbol" class="form-control">
							<?php
							foreach ( array(
								'$' => 'USD',
								'€' => 'EUR',
								'£' => 'GBP',
								'৳' => 'BDT',
								'₹' => 'INR',
								'¥' => 'JPY',
							) as $oby_mi_erp_sym => $oby_mi_erp_label ) :
								?>
								<option value="<?php echo esc_attr( $oby_mi_erp_sym ); ?>" <?php selected( $oby_mi_erp_currency, $oby_mi_erp_sym ); ?>><?php echo esc_html( $oby_mi_erp_sym ); ?> - <?php echo esc_html( $oby_mi_erp_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<div class="form-text"><?php esc_html_e( 'Symbol displayed next to all amounts', 'obydullah-micro-erp' ); ?></div>
					</div>

					<div class="mb-3">
						<label for="default_tax_rate" class="form-label"><?php esc_html_e( 'Default Tax Rate (%)', 'obydullah-micro-erp' ); ?></label>
						<input type="number" id="default_tax_rate" name="default_tax_rate" class="form-control" value="<?php echo esc_attr( $oby_mi_erp_tax_rate ); ?>" step="0.01" min="0" max="100">
						<div class="form-text"><?php esc_html_e( 'Applied to new sales and quotations', 'obydullah-micro-erp' ); ?></div>
					</div>
				</div>
			</div>

			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm mb-4">
					<h2 class="mb-3 mt-1"><?php esc_html_e( 'Accounting', 'obydullah-micro-erp' ); ?></h2>

					<div class="mb-3">
						<label for="default_income_account" class="form-label"><?php esc_html_e( 'Default Income Account', 'obydullah-micro-erp' ); ?></label>
						<select id="default_income_account" name="default_income_account" class="form-control">
							<option value="0"><?php esc_html_e( '— Auto (first income account) —', 'obydullah-micro-erp' ); ?></option>
							<?php foreach ( $oby_mi_erp_income_accounts as $oby_mi_erp_acct ) : ?>
								<option value="<?php echo (int) $oby_mi_erp_acct->id; ?>" <?php selected( $oby_mi_erp_def_income, $oby_mi_erp_acct->id ); ?>><?php echo esc_html( $oby_mi_erp_acct->code . ' - ' . $oby_mi_erp_acct->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="default_expense_account" class="form-label"><?php esc_html_e( 'Default Expense Account', 'obydullah-micro-erp' ); ?></label>
						<select id="default_expense_account" name="default_expense_account" class="form-control">
							<option value="0"><?php esc_html_e( '— Auto (first expense account) —', 'obydullah-micro-erp' ); ?></option>
							<?php foreach ( $oby_mi_erp_expense_accounts as $oby_mi_erp_acct ) : ?>
								<option value="<?php echo (int) $oby_mi_erp_acct->id; ?>" <?php selected( $oby_mi_erp_def_expense, $oby_mi_erp_acct->id ); ?>><?php echo esc_html( $oby_mi_erp_acct->code . ' - ' . $oby_mi_erp_acct->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-3">
						<label for="cash_account" class="form-label"><?php esc_html_e( 'Cash/Bank Account', 'obydullah-micro-erp' ); ?></label>
						<select id="cash_account" name="cash_account" class="form-control">
							<option value="0"><?php esc_html_e( '— Auto (first asset account) —', 'obydullah-micro-erp' ); ?></option>
							<?php foreach ( $oby_mi_erp_asset_accounts as $oby_mi_erp_acct ) : ?>
								<option value="<?php echo (int) $oby_mi_erp_acct->id; ?>" <?php selected( $oby_mi_erp_cash_acct, $oby_mi_erp_acct->id ); ?>><?php echo esc_html( $oby_mi_erp_acct->code . ' - ' . $oby_mi_erp_acct->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-6 col-md-12">
				<div class="bg-light p-4 rounded shadow-sm mb-4">
					<h2 class="mb-3 mt-1"><?php esc_html_e( 'Modules', 'obydullah-micro-erp' ); ?></h2>

					<div class="mb-3 form-check">
						<label><input type="checkbox" id="module_accounting" name="module_accounting" <?php checked( (int) oby_mi_erp_get_setting( 'module_accounting', 1 ) ); ?>> <?php esc_html_e( 'Accounting Module', 'obydullah-micro-erp' ); ?></label>
					</div>

					<div class="mb-3 form-check">
						<label><input type="checkbox" id="module_hrm" name="module_hrm" <?php checked( (int) oby_mi_erp_get_setting( 'module_hrm', 1 ) ); ?>> <?php esc_html_e( 'HRM Module', 'obydullah-micro-erp' ); ?></label>
					</div>

					<div class="mb-3 form-check">
						<label><input type="checkbox" id="module_sales" name="module_sales" <?php checked( (int) oby_mi_erp_get_setting( 'module_sales', 1 ) ); ?>> <?php esc_html_e( 'Sales Module', 'obydullah-micro-erp' ); ?></label>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-12">
				<div class="bg-light p-3 rounded shadow-sm mb-4 d-flex align-items-center justify-content-end gap-2 flex-wrap">
					<button type="submit" class="btn-save">
						<span class="dashicons dashicons-yes" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Settings', 'obydullah-micro-erp' ); ?>
					</button>
				</div>
			</div>
		</div>
	</form>
</div>
