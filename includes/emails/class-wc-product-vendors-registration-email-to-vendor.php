<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Registration_Email_To_Vendor extends WC_Email {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id               = 'vendor_registration_to_vendor';
		$this->title            = __( 'Vendor Registration (Vendor)', 'woocommerce-product-vendors' );
		$this->description      = __( 'When a vendor submits a registration form, this email will be sent to the vendor with login information.', 'woocommerce-product-vendors' );

		$this->heading          = __( 'Vendor Registration', 'woocommerce-product-vendors' );
		$this->subject          = __( '[{site_title}] Vendor Registration', 'woocommerce-product-vendors' );

		$this->template_base    = WC_PRODUCT_VENDORS_TEMPLATES_PATH;
		$this->template_html    = 'emails/vendor-registration-email-to-vendor.php';
		$this->template_plain   = 'emails/plain/vendor-registration-email-to-vendor.php';

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $args ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->user_login = $args['user_login'];
		$this->user_pass  = $args['user_pass'];
		$this->recipient  = $args['user_email'];
		
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
			'user_login'    => $this->user_login,
			'user_pass'     => $this->user_pass,
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
			'user_login'    => $this->user_login,
			'user_pass'     => $this->user_pass,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true,
			'email'         => $this
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

return new WC_Product_Vendors_Registration_Email_To_Vendor();
