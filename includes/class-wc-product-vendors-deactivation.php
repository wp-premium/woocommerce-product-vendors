<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Deactivation Class.
 *
 * Handles the clean up upon deactivation.
 *
 * @category Deactivation
 * @package  WooCommerce Product Vendors/Deactivation
 * @version  2.0.0
 */
class WC_Product_Vendors_Deactivation {
	/**
	 * Constructor not to be instantiated
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private function __construct() {}

	/**
	 * Perform deactivation tasks
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wcpv_scheduled_payment' );
		delete_option( 'wcpv_add_roles' );

		return true;
	}
}
