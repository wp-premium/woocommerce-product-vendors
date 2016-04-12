<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per Product Shipping Class Controller.
 *
 * Adds per product shipping method to WooCommerce
 *
 * @category Per Product Shipping
 * @package  WooCommerce Product Vendors/Per Product Shipping
 * @version  2.0.0
 */
class WC_Product_Vendors_Per_Product_Shipping {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		if ( is_admin() ) {
			include_once( 'class-wc-product-vendors-per-product-shipping-admin.php' );
		}

		add_action( 'woocommerce_shipping_init', array( $this, 'load_shipping_method' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );
	}

	/**
	 * Loads shipping method into WC
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function load_shipping_method() {
		include_once( 'class-wc-product-vendors-per-product-shipping-method.php' );
	}

	/**
	 * Registers shipping method into WC
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_shipping_method( $methods ) {
		$methods[] = 'WC_Product_Vendors_Per_Product_Shipping_Method';

		return $methods;
	}
}

new WC_Product_Vendors_Per_Product_Shipping();
