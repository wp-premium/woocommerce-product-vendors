<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Approval extends WC_Email {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id               = 'vendor_approval';
		$this->title            = __( 'Vendor Approval', 'woocommerce-product-vendors' );
		$this->description      = __( 'When a vendor is approved, this email will be sent.', 'woocommerce-product-vendors' );

		$this->heading          = __( 'Vendor Approval', 'woocommerce-product-vendors' );
		$this->subject          = __( '[{site_title}] Vendor Approval', 'woocommerce-product-vendors' );

		$this->template_base    = WC_PRODUCT_VENDORS_TEMPLATES_PATH;
		$this->template_html    = 'emails/vendor-approval.php';
		$this->template_plain   = 'emails/plain/vendor-approval.php';

		parent::__construct();
	}

	/**
	 * Trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $user_id, $new_role, $old_roles ) {
		$userdata = get_userdata( $user_id );

		$this->recipient = $userdata->user_email;
		
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		update_user_meta( $user_id, '_wcpv_vendor_approval', 'yes' );

		$this->role = $new_role;

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
			'role'          => $this->role,
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
			'role'          => $this->role,
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

return new WC_Product_Vendors_Approval();
