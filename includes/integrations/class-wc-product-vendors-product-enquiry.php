<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Product_Enquiry {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		add_filter( 'product_enquiry_send_to', array( $this, 'send_to' ), 10, 2 );

		return true;
	}

	/**
	 * Add vendor email to receipient for customer inquiry on products
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $send_to
	 */
	public function send_to( $send_to, $product_id ) {
		if ( $vendor = WC_Product_Vendors_Utils::is_vendor_product( $product_id ) ) {

			if ( $vendor ) {
				$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor[0]->term_id );

				$send_to = is_array( $vendor_data['email'] ) ? array_map( 'sanitize_text_field', $vendor_data['email'] ) : sanitize_text_field( $vendor_data['email'] );
			}
		}

		return $send_to;
	}
}

new WC_Product_Vendors_Product_Enquiry();
