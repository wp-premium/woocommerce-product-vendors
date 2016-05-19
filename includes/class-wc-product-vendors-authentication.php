<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Authentication Class.
 *
 * Handles the user/vendor authentication.
 *
 * @category Authentication
 * @package  WooCommerce Product Vendors/Authentication
 * @version  2.0.0
 */
class WC_Product_Vendors_Authentication {
	public $logged_in_vendor;

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// allow non admins to access admin
		add_filter( 'woocommerce_prevent_admin_access', array( $this, 'allow_admin_access' ) );

		// process user on successful login
		add_action( 'wp_login', array( $this, 'vendor_login_successful' ), 10, 2 );

		// clears all cookies
		add_action( 'clear_auth_cookie', array( $this, 'expire_cookie' ) );

		// clears all cookies
		add_action( 'auth_cookie_expired', array( $this, 'expire_cookie' ) );

		// redirect vendors to dashboard instead of profile
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );

		// authenticates the vendor on login
		add_filter( 'authenticate', array( $this, 'login_authentication' ), 30, 3 );

		return true;
	}

	/**
	 * Allow vendors to access backend URL
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function allow_admin_access( $return ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			return false;
		}

		return $return;
	}

	/**
	 * Sets a cookie for the current user
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $user_id
	 * @param string $vendor_id
	 * @return bool
	 */
	public function set_cookie( $user_id, $vendor_id ) {
		$expiry = apply_filters( 'auth_cookie_expiration', current_time( 'timestamp' ) + ( 14 * DAY_IN_SECONDS ), $user_id, true );

		return setcookie( 'wcpv_vendor_id_' . COOKIEHASH, absint( $vendor_id ), 0, SITECOOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * When vendor logs out or cookie expires
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function expire_cookie() {
		return setcookie( 'wcpv_vendor_id_' . COOKIEHASH, ' ', current_time( 'timestamp' ) - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Gets the vendor login cookie
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return mix
	 */
	public static function get_vendor_login_cookie() {
		if ( ! empty( $_COOKIE[ 'wcpv_vendor_id_' . COOKIEHASH ] ) ) {
			return $_COOKIE[ 'wcpv_vendor_id_' . COOKIEHASH ];
		}

		return false;
	}

	/**
	 * Redirect vendors on login
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $redirect_to the URL to redirect to
	 * @param string $request the URL the request was from
	 * @param object $user the user object passed
	 * @return bool
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
		// redirect only if it is a vendor and logging in from wp-admin
		if ( isset( $user->ID ) && WC_Product_Vendors_Utils::is_vendor( $user->ID ) && $request === admin_url() ) {
			
			WC_Product_Vendors_Utils::clear_reports_transients();
			
			$redirect_to = admin_url( 'index.php' );
		}

		return $redirect_to;
	}

	/**
	 * Login Authentication
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $user
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public function login_authentication( $user, $username, $password ) {
		// check valid user
		if ( ! is_wp_error( $user ) && WC_Product_Vendors_Utils::is_vendor( $user->ID ) ) {

			// get all vendor data for this logged in user
			$vendor_data = WC_Product_Vendors_Utils::get_all_vendor_data( $user->ID );

			// if a pending vendor don't allow login at all
			if ( WC_Product_Vendors_Utils::is_pending_vendor( $user->ID ) ) {
				$user = new WP_Error( 'error', __( 'Your application is being reviewed.  You will be notified once approved.', 'woocommerce-product-vendors' ) );

			} elseif ( empty( $vendor_data ) ) {
				$user = new WP_Error( 'error', __( 'Your account is not authorized to manage any vendors.  Please contact us for help.', 'woocommerce-product-vendors' ) );
			
			} else {
				// set the default vendor this user will manage
				$this->logged_in_vendor = key( $vendor_data );
			}
		}

		return $user;
	}

	/**
	 * Sets the vendor session so the correct vendor page is retrieved
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $user_login the login name
	 * @param object $user the user object
	 * @return bool
	 */
	public function vendor_login_successful( $user_login, $user ) {
		if ( WC_Product_Vendors_Utils::is_vendor( $user->ID ) ) {

			// by default set the session to the first vendor 
			// the user is able to manage
			$this->set_cookie( $user->ID, $this->logged_in_vendor );
		}

		return true;
	}
}

new WC_Product_Vendors_Authentication();
