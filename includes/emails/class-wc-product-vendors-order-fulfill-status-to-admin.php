<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Order_Fulfill_Status_To_Admin extends WC_Email {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id               = 'order_fulfill_status_to_admin';
		$this->title            = __( 'Order Fulfill Status Notification (Admin)', 'woocommerce-product-vendors' );
		$this->description      = __( 'When a vendor updates the fulfillment status of an item, this notification email will be sent to the admin.', 'woocommerce-product-vendors' );

		$this->heading          = __( 'Fulfillment Status Notification', 'woocommerce-product-vendors' );
		$this->subject          = __( '[{site_title}] Fulfillment Status Notification', 'woocommerce-product-vendors' );

		$this->template_base    = WC_PRODUCT_VENDORS_TEMPLATES_PATH;
		$this->template_html    = 'emails/order-fulfill-status-to-admin.php';
		$this->template_plain   = 'emails/plain/order-fulfill-status-to-admin.php';
		
		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->recipient = $this->get_option( 'recipient' );

		if ( ! $this->recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.16
	 * @param array $vendor_data
	 * @param string $status
	 * @param int $order_item_id
	 * @return null
	 */
	public function trigger( $vendor_data, $status = '', $order_item_id ) {
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->vendor_name     = isset( $vendor_data['name'] ) ? $vendor_data['name'] : '';
		$this->fulfill_status  = $status;
		$this->order           = WC_Product_Vendors_Utils::get_order_by_order_item_id( $order_item_id );
		$this->order_item_name = WC_Product_Vendors_Utils::get_order_item_name( $order_item_id );

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
			'vendor_name'        => $this->vendor_name,
			'fulfillment_status' => $this->fulfill_status,
			'order'              => $this->order,
			'order_item_name'    => $this->order_item_name,
			'email_heading'      => $this->get_heading(),
			'sent_to_admin'      => true,
			'plain_text'         => false,
			'email'              => $this
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
			'vendor_name'        => $this->vendor_name,
			'fulfillment_status' => $this->fulfill_status,
			'order'              => $this->order,
			'order_item_name'    => $this->order_item_name,
			'email_heading'      => $this->get_heading(),
			'sent_to_admin'      => true,
			'plain_text'         => true,
			'email'              => $this
		), 'woocommerce-product-vendors/', $this->template_base );

		return ob_get_clean();
	}

	/**
	 * Initialise Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'woocommerce-product-vendors' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'woocommerce-product-vendors' ),
				'default'       => 'yes'
			),
			'recipient' => array(
				'title'         => __( 'Recipient(s)', 'woocommerce-product-vendors' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce-product-vendors' ), esc_attr( get_option('admin_email') ) ),
				'placeholder'   => '',
				'default'       => ''
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
				'options'       => array( 'html' => __( 'HTML', 'woocommerce-product-vendors' ), 'plain' => __( 'Plain', 'woocommerce-product-vendors' ) )
			)
		);
	}
}

return new WC_Product_Vendors_Order_Fulfill_Status_To_Admin();
