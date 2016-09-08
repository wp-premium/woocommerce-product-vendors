<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Emails Class.
 *
 * Initializes all custom emails.
 *
 * @category Emails
 * @package  WooCommerce Product Vendors/Emails
 * @version  2.0.0
 */
class WC_Product_Vendors_Emails {
	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function init() {
		$self = new self();

		// add our email classes to WC
		add_filter( 'woocommerce_email_classes', array( $self, 'add_email_classes' ) );

		// add email notification actions
		add_filter( 'woocommerce_email_actions', array( $self, 'add_email_notification_actions' ) );

		// adds action to the resend email dropdown
		add_filter( 'woocommerce_resend_order_emails_available', array( $self, 'add_resend_order_email_action' ) );

		// process when vendor submits a new product
		add_action( 'transition_post_status', array( $self, 'trigger_new_product_email' ), 10, 3 );

		// sends registration emails to vendors and admins
		add_action( 'wcpv_shortcode_registration_form_process', array( $self, 'send_registration_emails' ) );

		// send no stock email
		add_action( 'woocommerce_no_stock', array( $self, 'send_no_stock_email' ) );

		// send low stock email
		add_action( 'woocommerce_low_stock', array( $self, 'send_low_stock_email' ) );

		add_filter( 'woocommerce_template_directory', array( $self, 'template_directory' ), 10, 2 );

    	return true;
	}

	/**
	 * Adds additional email classes to WC
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.16
	 * @param array $classes
	 * @return array $classes
	 */
	public function add_email_classes( $classes ) {
		$classes['WC_Product_Vendors_Registration_Email_To_Admin']     = include( 'emails/class-wc-product-vendors-registration-email-to-admin.php' );
		
		$classes['WC_Product_Vendors_Registration_Email_To_Vendor']    = include( 'emails/class-wc-product-vendors-registration-email-to-vendor.php' );
		
		$classes['WC_Product_Vendors_Order_Email_To_Vendor']           = include( 'emails/class-wc-product-vendors-order-email-to-vendor.php' );
		
		$classes['WC_Product_Vendors_Cancelled_Order_Email_To_Vendor'] = include( 'emails/class-wc-product-vendors-cancelled-order-email-to-vendor.php' );
		
		$classes['WC_Product_Vendors_Approval']                        = include( 'emails/class-wc-product-vendors-approval.php' );
		
		$classes['WC_Product_Vendors_Product_Added_Notice']            = include( 'emails/class-wc-product-vendors-product-added-notice.php' );
		
		$classes['WC_Product_Vendors_Order_Note_To_Customer']          = include( 'emails/class-wc-product-vendors-order-note-to-customer.php' );
		
		$classes['WC_Product_Vendors_Order_Fulfill_Status_To_Admin']   = include( 'emails/class-wc-product-vendors-order-fulfill-status-to-admin.php' );

		return $classes;
	}

	/**
	 * Adds additional email notification actions to WC
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $actions
	 * @return array $actions
	 */
	public function add_email_notification_actions( $actions ) {
		$actions[] = 'woocommerce_order_status_on-hold_to_completed';
		$actions[] = 'woocommerce_order_status_processing_to_cancelled';
		$actions[] = 'woocommerce_order_status_completed_to_cancelled';
		$actions[] = 'wcpv_customer_order_note';

		return $actions;
	}

	/**
	 * Sends email
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $to the email to send to
	 * @param string $subject the subject of the email
	 * @param string $message the message of the email
	 * @param string $headers the headers of the email
	 * @return bool
	 */
	public function send_email( $to = '', $subject = '', $message = '', $headers = '' ) {
		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Add resend order email to vendor item
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $emails
	 * @return array $emails
	 */
	public function add_resend_order_email_action( $emails ) {
		$emails[] = 'order_email_to_vendor';

		return $emails;
	}

	/**
	 * Trigger new product email to admin
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $old_status
	 * @param string $new_status
	 * @param object $post
	 * @return bool
	 */
	public function trigger_new_product_email( $new_status, $old_status, $post ) {
		if ( ! WC_Product_Vendors_Utils::auth_vendor_user() || 'product' !== get_post_type( $post->ID ) ) {
			return;
		}

		if ( 'pending' === $new_status && $old_status !== $new_status ) {
			$emails = WC()->mailer()->get_emails();

			if ( ! empty( $emails ) ) {
				$emails[ 'WC_Product_Vendors_Product_Added_Notice' ]->trigger( $post );
			}
		}

		return true;
	}

	/**
	 * Trigger registration emails
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $args
	 * @return bool
	 */
	public function send_registration_emails( $args ) {
		$emails = WC()->mailer()->get_emails();

		if ( ! empty( $emails ) ) {
			$emails[ 'WC_Product_Vendors_Registration_Email_To_Admin' ]->trigger( $args );
			$emails[ 'WC_Product_Vendors_Registration_Email_To_Vendor' ]->trigger( $args );
		}
		
		return true;
	}

	/**
	 * Sends a no stock email to vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function send_no_stock_email( $product ) {
		// check if product belongs to a vendor
		$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $product->id );

		if ( NULL != $vendor_id ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );

			$vendor_email = $vendor_data['email'];

			$message = sprintf( __( '%s is out of stock.', 'woocommerce-product-vendors' ), html_entity_decode( strip_tags( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );

			$subject = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' ' . __( 'Product Out of Stock', 'woocommerce-product-vendors' );

			return $this->send_email( $vendor_email, $subject, $message );
		}

		return false;
	}

	/**
	 * Sends a low stock email to vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function send_low_stock_email( $product ) {
		// check if product belongs to a vendor
		$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $product->id );

		if ( NULL != $vendor_id ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );

			$vendor_email = $vendor_data['email'];

			$message = sprintf( __( '%s is low in stock.', 'woocommerce-product-vendors' ), html_entity_decode( strip_tags( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' ' . sprintf( __( 'There are %d left', 'woocommerce-product-vendors' ), html_entity_decode( strip_tags( $product->get_total_stock() ) ) );

			$subject = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' ' . __( 'Product Low in Stock', 'woocommerce-product-vendors' );

			return $this->send_email( $vendor_email, $subject, $message );
		}

		return false;		
	}

	/**
	 * Custom template directory.
	 *
	 * @access public
	 * @since 2.0.14
	 * @version 2.0.14
	 * @param  string $directory
	 * @param  string $template
	 */
	public function template_directory( $directory, $template ) {
		$allowed_templates = array(
			'emails/cancelled-order-email-to-vendor.php',
			'emails/plain/cancelled-order-email-to-vendor.php',
			'emails/email-order-details.php',
			'emails/plain/email-order-details.php',
			'emails/order-email-addresses-to-vendor.php',
			'emails/plain/order-email-addresses-to-vendor.php',
			'emails/order-email-to-vendor.php',
			'emails/plain/order-email-to-vendor.php',
			'emails/order-note-to-customer.php',
			'emails/plain/order-note-to-customer.php',
			'emails/product-added-notice.php',
			'emails/plain/product-added-notice.php',
			'emails/vendor-approval.php',
			'emails/plain/vendor-approval.php',
			'emails/vendor-registration-email-to-admin.php',
			'emails/plain/vendor-registration-email-to-admin.php',
			'emails/vendor-registration-email-to-vendor.php',
			'emails/plain/vendor-registration-email-to-vendor.php',
		);

		if ( in_array( $template, $allowed_templates ) ) {
			return 'woocommerce-product-vendors';
		}

		return $directory;
	}
}

WC_Product_Vendors_Emails::init();
