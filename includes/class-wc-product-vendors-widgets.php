<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Widgets Class. 
 *
 * Registers all widgets.
 *
 * @category Widgets
 * @package  WooCommerce Product Vendors/Widgets
 * @version  2.0.0
 */
class WC_Product_Vendors_Widgets {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

    	return true;
	}

	/**
	 * Registers all widgets
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_widgets() {
		register_widget( 'WC_Product_Vendors_Vendor_Widget' );

		return true;
	}
}

new WC_Product_Vendors_Widgets();
