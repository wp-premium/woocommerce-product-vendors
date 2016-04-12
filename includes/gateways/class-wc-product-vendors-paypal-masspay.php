<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require __DIR__  . '/paypal-php-sdk/autoload.php';

/**
 * PayPal Mass Payments Class.
 *
 * Mass Payments by PayPal to mass pay vendor commission.
 *
 * @category Payout
 * @package  WooCommerce Product Vendors/PayPal Masspay
 * @version  2.0.0
 */
class WC_Product_Vendors_PayPal_MassPay implements WC_Product_Vendors_Vendor_Payout_Interface {
	private $_clientID;
	private $_clientSecret;
	private $_apiContext;

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array of objects $commissions
	 * @return bool
	 */
	public function __construct() {
		$environment = get_option( 'wcpv_vendor_settings_paypal_masspay_environment' );

		if ( 'sandbox' === $environment ) {
			$clientID = get_option( 'wcpv_vendor_settings_paypal_masspay_client_id_sandbox' );
			$clientSecret = get_option( 'wcpv_vendor_settings_paypal_masspay_client_secret_sandbox' );
		} else {
			$clientID = get_option( 'wcpv_vendor_settings_paypal_masspay_client_id_live' );
			$clientSecret = get_option( 'wcpv_vendor_settings_paypal_masspay_client_secret_live' );
		}

		if ( empty( $clientID ) || empty( $clientSecret ) ) {
			return;
		}

		$this->_clientID     = $clientID;
		$this->_clientSecret = $clientSecret;

		$this->set_api_context();

		return true;
	}

	/**
	 * Sets the API context
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function set_api_context() {
		$this->_apiContext = new \PayPal\Rest\ApiContext( new \PayPal\Auth\OAuthTokenCredential( $this->_clientID, $this->_clientSecret ) );

		return true;
	}

	/**
	 * Sends payment 
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return object $result
	 */
	public function do_payment( $commissions ) {
		if ( empty( $commissions ) ) {
			//throw new Exception( __( 'No commission to pay', 'woocommerce-product-vendors' ) );

			return;
		}

		$payouts = new \PayPal\Api\Payout();

		$senderBatchHeader = new \PayPal\Api\PayoutSenderBatchHeader();

		$senderBatchHeader->setSenderBatchId( uniqid() )->setEmailSubject( __( 'You have earned a commission', 'woocommerce-product-vendors ' ) );

		$item = 1;

		// add each commission item
		foreach( $commissions as $commission ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $commission->vendor_id );

			if ( empty( $vendor_data ) || empty( $vendor_data['paypal'] ) ) {
				continue;
			}

			$senderItem = new \PayPal\Api\PayoutItem();

			$note = '';
			$note .= sprintf( __( 'You have earned a commission from %s.', 'woocommerce-product-vendors' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );

			$note = apply_filters( 'wcpv_commission_paypal_masspay_vendor_note', $note, $commission );

			$senderItem->setRecipientType( 'Email' )
			    ->setNote( $note )
			    ->setReceiver( $vendor_data['paypal'] )
			    ->setSenderItemId( 'item_' . $item )
			    ->setAmount( new \PayPal\Api\Currency( '{
			    	"value":"' . $commission->total_commission_amount . '",
					"currency":"' . get_woocommerce_currency() . '"
				}' ) );

			$payouts->setSenderBatchHeader( $senderBatchHeader )->addItem( $senderItem );

			$item++;
		}

		$request = clone $payouts;

		$results = $payouts->create( null, $this->_apiContext );

		if ( is_wp_error( $results ) ) {
			throw new Exception( $results->get_error_message() );
		}

		return $results;
	}
}
