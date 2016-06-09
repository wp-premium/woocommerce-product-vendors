<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per Product Shipping Class.
 *
 * Adds per product shipping method to WooCommerce
 *
 * @category Per Product Shipping
 * @package  WooCommerce Product Vendors/Per Product Shipping
 * @version  2.0.0
 */
class WC_Product_Vendors_Per_Product_Shipping_Method extends WC_Shipping_Method {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'wcpv_per_product';
		$this->method_title       = __( 'Vendors Per Product Shipping', 'woocommerce-product-vendors' );
		$this->method_description = __( 'Per product shipping allows you to define different shipping costs for products per vendor, based on customer location.', 'woocommerce-product-vendors' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled      = $this->settings['enabled'];
		$this->title        = $this->settings['title'];
		$this->availability = $this->settings['availability'];
		$this->countries    = $this->settings['countries'];
		$this->tax_status   = $this->settings['tax_status'];
		$this->cost         = $this->settings['cost'];
		$this->fee          = $this->settings['fee'];
		$this->order_fee    = $this->settings['order_fee'];

		// Actions
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

	/**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
    	$this->form_fields = array(
    		'enabled' => array(
					'title'   => __( 'Enable Shipping Method', 'woocommerce-product-vendors' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable per-product shipping method for vendors', 'woocommerce-product-vendors' ),
					'default' => 'no'
				),
			'title' => array(
					'title'       => __( 'Method Title', 'woocommerce-product-vendors' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-product-vendors' ),
					'default'     => __( 'Per Product Shipping', 'woocommerce-product-vendors' ),
					'desc_tip'    => true
				),
			'tax_status' => array(
					'title'       => __( 'Tax Status', 'woocommerce-product-vendors' ),
					'type'        => 'select',
					'description' => '',
					'default'     => 'taxable',
					'options'     => array(
						'taxable' => __( 'Taxable', 'woocommerce-product-vendors' ),
						'none'    => __( 'None', 'woocommerce-product-vendors' ),
					),
				),
			'cost' => array(
					'title'       => __( 'Default Product Cost', 'woocommerce-product-vendors' ),
					'type'        => 'text',
					'description' => __( 'Cost excluding tax (per product) for products without defined costs. Enter an amount, e.g. 2.50.', 'woocommerce-product-vendors' ),
					'default'     => '',
					'placeholder' => '0',
					'desc_tip'    => true
				),
			'fee' => array(
					'title'       => __( 'Handling Fee (per product)', 'woocommerce-product-vendors' ),
					'type'        => 'text',
					'description' => __( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce-product-vendors' ),
					'default'     => '',
					'placeholder' => '0',
					'desc_tip'    => true
				),
			'order_fee' => array(
					'title'       => __( 'Handling Fee (per order)', 'woocommerce-product-vendors' ),
					'type'        => 'text',
					'description' => __( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woocommerce-product-vendors' ),
					'default'     => '',
					'placeholder' => '0',
					'desc_tip'    => true
				),
			'availability' => array(
					'title'   => __( 'Method availability', 'woocommerce-product-vendors' ),
					'type'    => 'select',
					'default' => 'all',
					'class'   => 'availability',
					'options' => array(
						'all'      => __( 'All allowed countries', 'woocommerce-product-vendors' ),
						'specific' => __( 'Specific Countries', 'woocommerce-product-vendors' )
					)
				),
			'countries' => array(
					'title'   => __( 'Specific Countries', 'woocommerce-product-vendors' ),
					'type'    => 'multiselect',
					'class'   => 'chosen_select',
					'css'     => 'width: 450px;',
					'default' => '',
					'options' => WC()->countries->get_allowed_countries()
				)
		);
    }

    /**
     * Calculate shipping when this method is used standalone.
     */
    public function calculate_shipping( $package = array() ) {
		$_tax          = new WC_Tax();
		$taxes         = array();
		$shipping_cost = 0;

    	// This shipping method loops through products, adding up the cost
    	if ( sizeof( $package['contents'] ) > 0 ) {
			foreach ( $package['contents'] as $item_id => $values ) {
				if ( $values['quantity'] > 0 ) {
					if ( $values['data']->needs_shipping() ) {

						$rule = false;
						$item_shipping_cost = 0;

						if ( $values['variation_id'] ) {
							$rule = WC_Product_Vendors_Utils::get_pp_shipping_matching_rule( $values['variation_id'], $package );
						}

						if ( $rule === false || is_null( $rule ) ) {
							$rule = WC_Product_Vendors_Utils::get_pp_shipping_matching_rule( $values['product_id'], $package );
						}

						if ( $rule ) {
							$item_shipping_cost += $rule->rule_item_cost * $values['quantity'];
							$item_shipping_cost += $rule->rule_cost;
						} elseif ( $this->cost === '0' || $this->cost > 0 ) {
							// Use default
							$item_shipping_cost += $this->cost * $values['quantity'];
						} else {
							// NO default and nothing found - abort
							return;
						}

						// Fee
						$item_shipping_cost += $this->get_fee( $this->fee, $item_shipping_cost ) * $values['quantity'];

						$shipping_cost += $item_shipping_cost;

						if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' && $this->tax_status === 'taxable' ) {

							$rates      = $_tax->get_shipping_tax_rates( $values['data']->get_tax_class() );
							$item_taxes = $_tax->calc_shipping_tax( $item_shipping_cost, $rates );

							// Sum the item taxes
							foreach ( array_keys( $taxes + $item_taxes ) as $key ) {
								$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0);
							}
						}
					}
				}
			}
		}

		// Add order shipping cost + tax
		if ( $this->order_fee ) {

			$order_fee = $this->get_fee( $this->order_fee, $shipping_cost );

			$shipping_cost += $order_fee;

			if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' && $this->tax_status === 'taxable' ) {

				$rates      = $_tax->get_shipping_tax_rates();
				$item_taxes = $_tax->calc_shipping_tax( $order_fee, $rates );

				// Sum the item taxes
				foreach ( array_keys( $taxes + $item_taxes ) as $key ) {
					$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0);
				}
			}
		}

		// Add rate
		$this->add_rate( array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => $shipping_cost,
			'taxes' => $taxes // We calc tax in the method
		) );
    }
}

new WC_Product_Vendors_Per_Product_Shipping_Method();
