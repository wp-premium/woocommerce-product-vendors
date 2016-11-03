<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Shortcodes Class.
 *
 * Registers all shortcodes.
 *
 * @category Shortcodes
 * @package  WooCommerce Product Vendors/Shortcodes
 * @version  2.0.0
 */
class WC_Product_Vendors_Shortcodes {
	public $registration;

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		$this->registration = new WC_Product_Vendors_Registration();

		add_shortcode( 'wcpv_registration', array( $this, 'render_registration_shortcode' ) );
		add_shortcode( 'wcpv_vendor_list', array( $this, 'vendor_list_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		return true;
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function enqueue_scripts() {
		global $post;

		// load this script only if registration shortcode is present
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wcpv_registration' ) ) {
			$this->registration->add_scripts();
		}

		return true;
	}

	/**
	 * Renders the registration form for new vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $atts user specified attributes
	 * @return html $form
	 */
	public function render_registration_shortcode( $atts ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p class="woocommerce-message">' . __( 'Hey there! The vendor registration form is not showing since you\'re logged in as an Administrator. If you\'d like to verify the form is working, please log out and view this page again.', 'woocommerce-product-vendors' ) . '</p>';
		}

		// no need to show vendor this form
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			return sprintf( '<p class="woocommerce-message">' . __( 'Great! You\'re already a vendor! Perhaps you want to go to the %1$sVendor Dashboard?%2$s', 'woocommerce-product-vendors' ) . '</p>', '<a href="' . esc_url( admin_url() ) . '">', '</a>' );
		}

		ob_start();

		$this->registration->include_form();

		$form = ob_get_clean();

		return $form;
	}

	/**
	 * Displays a list of vendors
	 *
	 * @access public
	 * @since 2.0.4
	 * @version 2.0.4
	 * @param array $atts user specified attributes
	 * @return html
	 */
	public function vendor_list_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'show_name' => true,
			'show_logo' => false,
		), $atts, 'wcpv_vendor_list' );

		$args = array(
			'hierarchical' => false,
		);

		$vendors = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, apply_filters( 'wcpv_vendor_list_args', $args ) );

		ob_start();

		// check if template has been overriden
		if ( file_exists( get_stylesheet_directory() . '/woocommerce-product-vendors/shortcode-vendor-list.php' ) ) {
			include( get_stylesheet_directory() . '/woocommerce-product-vendors/shortcode-vendor-list.php' );

		} else {
			include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/shortcode-vendor-list.php' );
		}

		$html = ob_get_clean();

		return $html;
	}
}

new WC_Product_Vendors_Shortcodes();
