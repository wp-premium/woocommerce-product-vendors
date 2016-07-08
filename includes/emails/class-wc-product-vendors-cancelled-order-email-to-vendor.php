<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Cancelled_Order_Email_To_Vendor extends WC_Email {
	/**
	 * Constructor
	 *
	 * @access public
	 * @return bool
	 */
	public function __construct() {
		$this->id               = 'cancelled_order_email_to_vendor';
		$this->title            = __( 'Cancelled Order Email (Vendor)', 'woocommerce-product-vendors' );
		$this->description      = __( 'When an order is cancelled with vendor products, this email will be sent out to the vendors.', 'woocommerce-product-vendors' );

		$this->heading          = __( 'Cancelled Order', 'woocommerce-product-vendors' );
		$this->subject          = __( '[{site_title}] Cancelled order ({order_number}) - {order_date}', 'woocommerce-product-vendors' );

		$this->template_base    = WC_PRODUCT_VENDORS_TEMPLATES_PATH;
		$this->template_html    = 'emails/cancelled-order-email-to-vendor.php';
		$this->template_plain   = 'emails/plain/cancelled-order-email-to-vendor.php';

		// Triggers for this email
		add_action( 'woocommerce_order_status_processing_to_cancelled_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_order_status_completed_to_cancelled_notification', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();

		return true;
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @param int $order_id
	 * @return bool
	 */
	public function trigger( $order_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $order_id ) {

			$this->object = wc_get_order( $order_id );

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();

			if ( is_a( $this->object, 'WC_Order' ) && $items = $this->object->get_items( 'line_item' ) ) {

				$vendors = WC_Product_Vendors_Utils::get_vendors_from_order( $this->object );

				add_filter( 'woocommerce_order_get_items', array( $this, 'filter_vendor_items' ), 10, 2 );

				add_filter( 'woocommerce_get_order_item_totals', array( $this, 'filter_order_totals' ), 10, 2 );

				add_filter( 'woocommerce_email_customer_details_fields', array( $this, 'filter_customer_fields' ), 10, 3 );

				add_filter( 'wc_get_template', array( $this, 'filter_customer_addresses' ), 10, 3 );

				$sent = false;

				// send email to each vendor
				foreach( $vendors as $vendor_id => $data ) {
					$this->vendor = $vendor_id;
					
					$this->recipient = $data['email'];

					if ( empty( $this->recipient ) ) {
						continue;
					}

					$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

					$sent = true;
				}

				if ( $sent ) {
					// add order note that email was sent to vendor
					$note = __( 'Cancelled Order email sent to vendor(s).', 'woocommerce-product-vendors' );

					$this->object->add_order_note( $note );
				}
			}
		}

		return true;
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => false,
			'email'         => $this
		), 'woocommerce-product-vendors/', $this->template_base );

		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true,
			'email'         => $this
		), 'woocommerce-product-vendors/', $this->template_base );

		return ob_get_clean();
	}

	/**
	 * Filters the order items for vendor items
	 *
	 * @access public
	 * @param array $items;
	 * @param object $order
	 * @return string
	 */
	public function filter_vendor_items( $items, $order ) {
		foreach ( $items as $item => $value ) {

			// only affect line_items
			if ( 'line_item' === $value['type'] ) { 

				$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $value[ 'product_id' ] );

				// remove the order items that are not from this vendor
				if ( $this->vendor !== $vendor_id ) {
					unset( $items[ $item ] );
					
					continue;
				}
			}
		}

		return $items;
	}

	/**
	 * Filters the order totals
	 *
	 * @access public
	 * @param array $total_rows;
	 * @param object $order
	 * @return string
	 */
	public function filter_order_totals( $total_rows, $order ) {
		// don't show payment method to vendors
		unset( $total_rows['payment_method'] );
		unset( $total_rows['shipping'] );
		unset( $total_rows['order_total'] );

		return $total_rows;
	}

	/**
	 * Filters the customer fields
	 *
	 * @access public
	 * @param array $fields
	 * @param bool $sent_to_admin
	 * @param object $order
	 * @return string
	 */
	public function filter_customer_fields( $fields, $sent_to_admin, $order ) {
		unset( $fields['billing_phone'], $fields['billing_email'] );

		return $fields;
	}

	/**
	 * Filters the customer addresses
	 *
	 * @access public
	 * @param array $template_path the path of the original template
	 * @param string $template_name the name to the original template
	 * @param array $args
	 * @return string
	 */
	public function filter_customer_addresses( $template_path, $template_name, $args ) {
		if ( 'emails/plain/email-addresses.php' !== $template_name && 'emails/email-addresses.php' !== $template_name ) {
			return $template_path;
		}

		if ( 'html' === $this->get_email_type() ) {
			$template_path = WC_PRODUCT_VENDORS_TEMPLATES_PATH . 'emails/order-email-addresses-to-vendor.php';
		} elseif ( 'plain' === $this->get_email_type() ) {
			$template_path = WC_PRODUCT_VENDORS_TEMPLATES_PATH . 'emails/plain/order-email-addresses-to-vendor.php';
		}

		return $template_path;
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @access public
	 * @return bool
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'woocommerce-product-vendors' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'woocommerce-product-vendors' ),
				'default'       => 'yes'
			),
			'subject' => array(
				'title'         => __( 'Subject', 'woocommerce-product-vendors' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-product-vendors' ), $this->subject ),
				'placeholder'   => '',
				'default'       => ''
			),
			'heading' => array(
				'title'         => __( 'Email Heading', 'woocommerce-product-vendors' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-product-vendors' ), $this->heading ),
				'placeholder'   => '',
				'default'       => ''
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'woocommerce-product-vendors' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce-product-vendors' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options()
			)
		);

		return true;
	}
}

return new WC_Product_Vendors_Cancelled_Order_Email_To_Vendor();
