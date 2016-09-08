<?php
/**
 * Vendor registration email to admin (plain text).
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo __( 'Hello! A vendor has requested to be registered.', 'woocommerce-product-vendors' ) . "\n\n";

echo __( 'Vendor Information', 'woocommerce-product-vendors' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Email: %s', 'woocommerce-product-vendors' ), $user_email ) . "\n\n";
echo sprintf( __( 'First Name: %s', 'woocommerce-product-vendors' ), $first_name ) . "\n\n";
echo sprintf( __( 'Last Name: %s', 'woocommerce-product-vendors' ), $last_name ) . "\n\n";
echo sprintf( __( 'Vendor Name: %s', 'woocommerce-product-vendors' ), $vendor_name ) . "\n\n";
echo __( 'Vendor Description:', 'woocommerce-product-vendors' ) . "\n\n";
echo $vendor_desc . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
