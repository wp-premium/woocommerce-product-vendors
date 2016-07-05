<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Vendor Registration.
 *
 * Handles the vendor form registration process .
 *
 * @category Registration
 * @package  WooCommerce Product Vendors/Registration
 * @version  2.0.0
 */
class WC_Product_Vendors_Registration {
	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_wc_product_vendors_registration', array( $this, 'registration_ajax' ) );
			add_action( 'wp_ajax_nopriv_wc_product_vendors_registration', array( $this, 'registration_ajax' ) );

		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		}

    	return true;
	}

	/**
	 * Add scripts
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_scripts() {
		wp_enqueue_script( 'wcpv-frontend-scripts' );

		$localized_vars = array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'ajaxRegistrationNonce' => wp_create_nonce( '_wc_product_vendors_registration_nonce' ),
			'success'               => __( 'Your request has been submitted.  You will be contacted shortly.', 'woocommerce-product-vendors' ),
		);
		
		wp_localize_script( 'wcpv-frontend-scripts', 'wcpv_registration_local', $localized_vars );
		
		return true;
	}

	/**
	 * Handles the registration via AJAX
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function registration_ajax() {
		$this->registration_form_validation( $_POST['form_items'] );

		return true;
	}

	/**
	 * Includes the registration form
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function include_form() {
		// check if template has been overriden
		if ( file_exists( get_stylesheet_directory() . '/woocommerce-product-vendors/shortcode-registration-form.php' ) ) {
			
			include( get_stylesheet_directory() . '/woocommerce-product-vendors/shortcode-registration-form.php' );

		} else  {
			include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/shortcode-registration-form.php' );
		}

		return true;
	}

	/**
	 * Validates the registration form
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_items forms items to validate
	 * @return bool
	 */
	public function registration_form_validation( $form_items = array() ) {
		global $errors;

		if ( ! is_array( $form_items ) ) {
			parse_str( $_POST['form_items'], $form_items );
		}

		$form_items = array_map( 'sanitize_text_field', $form_items );

		if ( ! isset( $form_items ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( ! wp_verify_nonce( $_POST['ajaxRegistrationNonce'], '_wc_product_vendors_registration_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		// handle form submission/validation
		if ( ! empty( $form_items ) ) {
			$errors = array();

			if ( ! is_user_logged_in() ) {
				if ( empty( $form_items['firstname'] ) ) {
					$errors[] = __( 'First Name is a required field.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['lastname'] ) ) {
					$errors[] = __( 'Last Name is a required field.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['username'] ) ) {
					$errors[] = __( 'Username is a required field.', 'woocommerce-product-vendors' );
				}

				if ( ! empty( $form_items['username'] ) && username_exists( $form_items['username'] ) ) {
					$errors[] = __( 'Please choose a different username.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['email'] ) ) {
					$errors[] = __( 'Email is a required field.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['confirm_email'] ) ) {
					$errors[] = __( 'Confirm email is a required field.', 'woocommerce-product-vendors' );
				}

				if ( $form_items['confirm_email'] !== $form_items['email'] ) {
					$errors[] = __( 'Emails must match.', 'woocommerce-product-vendors' );
				}

				if ( ! empty( $form_items['email'] ) && false !== email_exists( $form_items['email'] ) ) {
					$errors[] = __( 'Email already exists in our system.', 'woocommerce-product-vendors' );
				}

				if ( ! filter_var( $form_items['email'], FILTER_VALIDATE_EMAIL ) ) {
					$errors[] = __( 'Email is not valid.', 'woocommerce-product-vendors' );
				}
			}

			if ( empty( $form_items['vendor_name'] ) ) {
				$errors[] = __( 'Vendor Name is a required field.', 'woocommerce-product-vendors' );
			}

			// check that the vendor name is not already taken
			// checks against existing terms from "wcpv_product_vendors" taxonomy
			if ( ! empty( $form_items['vendor_name'] ) && term_exists( $form_items['vendor_name'], WC_PRODUCT_VENDORS_TAXONOMY ) ) {
				$errors[] = __( 'Sorry that vendor name already exists. Please enter a different one.', 'woocommerce-product-vendors' );
			}

			if ( empty( $form_items['vendor_description'] ) ) {
				$errors[] = __( 'Vendor Description is a required field.', 'woocommerce-product-vendors' );
			}

			do_action( 'wcpv_shortcode_registration_form_validation', $errors, $form_items );
			
			$errors = apply_filters( 'wcpv_shortcode_registration_form_validation_errors', $errors, $form_items );

			// no errors, lets process the form
			if ( empty( $errors ) ) {
				if ( is_user_logged_in() ) {
					$this->vendor_registration_form_process( $form_items );
				} else {
					$this->vendor_user_registration_form_process( $form_items );
				}
				
			} else {
				wp_send_json( array( 'errors' => $errors ) );
			}
		}
	}

	/**
	 * Process the registration form for just vendor.
	 * As in they already have a user account on the site.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_items sanitized form items
	 * @return bool
	 */
	public function vendor_registration_form_process( $form_items ) {
		$current_user = wp_get_current_user();
		$vendor_name = $form_items['vendor_name'];
		$vendor_desc = $form_items['vendor_description'];

		$term_args = apply_filters( 'wcpv_registration_term_args', array(
			'description' => $vendor_desc,
		) );

		// add vendor name to taxonomy
		$term = wp_insert_term( $vendor_name, WC_PRODUCT_VENDORS_TAXONOMY, $term_args );

		// no errors, term added, continue
		if ( ! is_wp_error( $term ) && ! empty( $current_user ) ) {
			// add user to term meta
			$vendor_data = array();

			$vendor_data['admins'] = $current_user->ID;
			
			update_term_meta( $term['term_id'], 'vendor_data', $vendor_data );

			$args['user_id']     = $current_user->ID;
			$args['user_email']  = $current_user->user_email;
			$args['first_name']  = $current_user->user_firstname;
			$args['last_name']   = $current_user->user_lastname;
			$args['user_login']  = __( 'Same as your account login', 'woocommerce-product-vendors' );
			$args['user_pass']   = __( 'Same as your account password', 'woocommerce-product-vendors' );
			$args['vendor_name'] = $vendor_name;
			$args['vendor_desc'] = $vendor_desc;

			// change this user's role to pending vendor
			wp_update_user( array( 'ID' => $current_user->ID, 'role' => 'wc_product_vendors_pending_vendor' ) );
			
			do_action( 'wcpv_shortcode_registration_form_process', $args, $form_items );

			echo 'success';
			exit;
		} else {
			global $errors;

			if ( is_wp_error( $current_user ) ) {
				$errors[] = $current_user->get_error_message();
			}

			if ( is_wp_error( $term ) ) {
				$errors[] = $term->get_error_message();
			}

			wp_send_json( array( 'errors' => $errors ) );
		}

		return true;
	}

	/**
	 * Process the registration form for vendor and user
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_items sanitized form items
	 * @return bool
	 */
	public function vendor_user_registration_form_process( $form_items ) {
		$username    = $form_items['username'];
		$email       = $form_items['email'];
		$vendor_name = $form_items['vendor_name'];
		$vendor_desc = $form_items['vendor_description'];
		$firstname   = $form_items['firstname'];
		$lastname    = $form_items['lastname'];

		$password = wp_generate_password();

		$args = apply_filters( 'wcpv_shortcode_register_vendor_args', array(
			'user_login'      => $username,
			'user_pass'       => $password,
			'user_email'      => $email,
			'first_name'      => $firstname,
			'last_name'       => $lastname,
			'display_name'    => $firstname,
			'role'            => 'wc_product_vendors_pending_vendor',
		) );
		
		$user_id = wp_insert_user( $args );

		$term_args = apply_filters( 'wcpv_registration_term_args', array(
			'description' => $vendor_desc,
		) );

		// add vendor name to taxonomy
		$term = wp_insert_term( $vendor_name, WC_PRODUCT_VENDORS_TAXONOMY, $term_args );

		// no errors, user created and term added, continue
		if ( ! is_wp_error( $user_id ) && ! is_wp_error( $term ) ) {

			// add user to term meta
			$vendor_data = array();

			$vendor_data['admins'] = $user_id;
			
			update_term_meta( $term['term_id'], 'vendor_data', $vendor_data );

			$args['user_id']     = $user_id;
			$args['vendor_name'] = $vendor_name;
			$args['vendor_desc'] = $vendor_desc;

			do_action( 'wcpv_shortcode_registration_form_process', $args, $form_items );

			echo 'success';
			exit;
		} else {
			global $errors;

			if ( is_wp_error( $user_id ) ) {
				$errors[] = $user_id->get_error_message();
			}

			if ( is_wp_error( $term ) ) {
				$errors[] = $term->get_error_message();
			}

			wp_send_json( array( 'errors' => $errors ) );
		}

		return true;
	}
}
