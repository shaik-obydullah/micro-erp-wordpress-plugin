<?php
/**
 * Renders the Income admin screen, listing income journal entries.
 *
 * @package Obydullah_Micro_ERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$oby_mi_erp_tx_mode = 'income';
require __DIR__ . '/oby-mi-erp-transaction-list.php';
