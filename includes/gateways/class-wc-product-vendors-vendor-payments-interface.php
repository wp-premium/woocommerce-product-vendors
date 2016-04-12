<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Payout Interface Class.
 *
 * Interface which payouts must adhere to.
 *
 * @category Payout
 * @package  WooCommerce Product Vendors/Payout Interface
 * @version  2.0.0
 */
interface WC_Product_Vendors_Vendor_Payout_Interface {
	/**
	 * Performs the logic to payout vendors
	 *
	 */
	public function do_payment( $commissions );
}
