<?php
/**
 * Plugin Name: WooCommerce Product Vendors
 * Version: 2.0.20
 * Plugin URI: https://woocommerce.com/products/product-vendors/
 * Description: Set up a multi-vendor marketplace that allows vendors to manage their own products and earn commissions. Run stores similar to Amazon or Etsy.
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Requires at least: 4.4.0
 * Tested up to: 4.6.0
 *
 * Text Domain: woocommerce-product-vendors
 * Domain Path: /languages
 *
 * @package WordPress
 * @author Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	include_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'a97d99fccd651bbdd728f4d67d492c31', '219982' );

if ( ! class_exists( 'WC_Product_Vendors' ) ) :

/**
 * Main class.
 *
 * @package WC_Product_Vendors
 * @since 2.0.0
 * @version 2.0.0
 */
class WC_Product_Vendors {
	private static $_instance = null;

	/**
	 * Get the single instance aka Singleton
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Prevent cloning
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ), '2.0.20' );
	}

	/**
	 * Prevent unserializing instances
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ), '2.0.20' );
	}

	/**
	 * Construct
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		do_action( 'wcpv_loaded' );

		return true;
	}

	/**
	 * Define constants
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private function define_constants() {
		global $wpdb;

		define( 'WC_PRODUCT_VENDORS_VERSION', '2.0.20' );
		define( 'WC_PRODUCT_VENDORS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WC_PRODUCT_VENDORS_TEMPLATES_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WC_PRODUCT_VENDORS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_PRODUCT_VENDORS_TAXONOMY', 'wcpv_product_vendors' );
		define( 'WC_PRODUCT_VENDORS_COMMISSION_TABLE', $wpdb->prefix . 'wcpv_commissions' );
		define( 'WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE', $wpdb->prefix . 'wcpv_per_product_shipping_rules' );

		return true;
	}

	/**
	 * Include all files needed
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function dependencies() {
		include_once( 'includes/class-wc-product-vendors-taxonomy.php' );

		include_once( 'includes/class-wc-product-vendors-utils.php' );

		include_once( 'includes/class-wc-product-vendors-roles-caps.php' );

		include_once( 'includes/class-wc-product-vendors-install.php' );

		include_once( 'includes/class-wc-product-vendors-deactivation.php' );

		include_once( 'includes/gateways/class-wc-product-vendors-vendor-payments-interface.php' );

		include_once( 'includes/gateways/class-wc-product-vendors-paypal-masspay.php' );

		include_once( 'includes/class-wc-product-vendors-commission.php' );

		if ( is_admin() ) {
			include_once( 'includes/admin/class-wc-product-vendors-vendor-order-detail-list.php' );

			include_once( 'includes/admin/class-wc-product-vendors-vendor-orders-list.php' );

			include_once( 'includes/admin/class-wc-product-vendors-store-commission-list.php' );

			include_once( 'includes/admin/class-wc-product-vendors-vendor-order-notes.php' );

			if ( WC_Product_Vendors_Utils::is_vendor() && ! current_user_can( 'manage_options' ) ) {
				include_once( 'includes/admin/class-wc-product-vendors-vendor-dashboard.php' );

				include_once( 'includes/admin/class-wc-product-vendors-vendor-admin.php' );
			} else {
				include_once( 'includes/admin/class-wc-product-vendors-store-admin.php' );
			}

			include_once( 'includes/admin/reports/vendor/class-wc-product-vendors-vendor-reports.php' );

			include_once( 'includes/admin/reports/store/class-wc-product-vendors-store-reports.php' );
		}

		include_once( 'includes/class-wc-product-vendors-vendor-frontend.php' );

		include_once( 'includes/widgets/class-wc-product-vendors-vendor-widget.php' );

		include_once( 'includes/class-wc-product-vendors-widgets.php' );

		include_once( 'includes/class-wc-product-vendors-registration.php' );

		include_once( 'includes/class-wc-product-vendors-shortcodes.php' );

		include_once( 'includes/class-wc-product-vendors-authentication.php' );

		include_once( 'includes/class-wc-product-vendors-order.php' );

		include_once( 'includes/class-wc-product-vendors-emails.php' );

		include_once( 'includes/shipping/per-product/class-wc-product-vendors-per-product-shipping.php' );

		include_once( 'includes/class-wc-product-vendors-payout-scheduler.php' );

		// check for bookings
		if ( class_exists( 'WC_Bookings' ) ) {
			include_once( 'includes/integrations/class-wc-product-vendors-bookings.php' );
		}

		// check for product enquiry
		if ( function_exists( 'init_woocommerce_product_enquirey_form' ) ) {
			include_once( 'includes/integrations/class-wc-product-vendors-product-enquiry.php' );
		}

		return true;
	}

	/**
	 * Initializes hooks
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( 'WC_Product_Vendors_Install', 'init' ) );
		register_deactivation_hook( __FILE__, array( 'WC_Product_Vendors_Deactivation', 'deactivate' ) );

		return true;
	}

	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function init() {
		if ( is_woocommerce_active() ) {
			$this->define_constants();
			$this->load_plugin_textdomain();
			$this->dependencies();
			$this->init_hooks();

		} else {
			// if WooCommerce is not active show notice
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}

		return true;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'wcpv_plugin_locale', get_locale(), 'woocommerce-product-vendors' );

		load_textdomain( 'woocommerce-product-vendors', trailingslashit( WP_LANG_DIR ) . 'woocommerce-product-vendors/woocommerce-product-vendors' . '-' . $locale . '.mo' );

		load_plugin_textdomain( 'woocommerce-product-vendors', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		return true;
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce Product Vendors Plugin requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-product-vendors' ), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';

		return true;
	}
}

WC_Product_Vendors::instance();

endif;
