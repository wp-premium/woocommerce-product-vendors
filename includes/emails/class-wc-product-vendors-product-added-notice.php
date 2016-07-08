<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Product_Added_Notice extends WC_Email {
	/**
	 * Constructor
	 *
	 * @access public
	 * @return bool
	 */
	public function __construct() {
		$this->id               = 'product_added_notice';
		$this->title            = __( 'Product Added Notice', 'woocommerce-product-vendors' );
		$this->description      = __( 'When a vendor submits a product, this notice will be sent to the admin.', 'woocommerce-product-vendors' );

		$this->heading          = __( 'Product Added Notice', 'woocommerce-product-vendors' );
		$this->subject          = __( '[{site_title}] New vendor Product', 'woocommerce-product-vendors' );

		$this->template_base    = WC_PRODUCT_VENDORS_TEMPLATES_PATH;
		$this->template_html    = 'emails/product-added-notice.php';
		$this->template_plain   = 'emails/plain/product-added-notice.php';

		// Triggers for this email

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->recipient = $this->get_option( 'recipient' );

		if ( ! $this->recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}

		return true;
	}

	/**
	 * Trigger function.
	 *
	 * @access public
	 * @param object $post
	 * @return bool
	 */
	public function trigger( $post ) {
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		if ( $post ) {
			$this->product_link = admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' );
			$this->product_name = $post->post_title;

			// get current vendor name
			$this->vendor_name = WC_Product_Vendors_Utils::get_logged_in_vendor( 'name' );

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
			'vendor_name'   => $this->vendor_name,
			'product_name'  => $this->product_name,
			'product_link'  => $this->product_link,
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
			'vendor_name'   => $this->vendor_name,
			'product_name'  => $this->product_name,
			'product_link'  => $this->product_link,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true,
			'email'         => $this
		), 'woocommerce-product-vendors/', $this->template_base );

		return ob_get_clean();
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
				'options'       => $this->get_email_type_options()
			)
		);

		return true;
	}
}

return new WC_Product_Vendors_Product_Added_Notice();
