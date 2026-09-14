<?php
/**
 * Renders the Expenses admin screen, listing expense journal entries.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$oby_mi_erp_tx_mode = 'expense';
require __DIR__ . '/oby-mi-erp-transaction-list.php';
