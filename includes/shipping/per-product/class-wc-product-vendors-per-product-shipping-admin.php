<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Vendors_Per_Product_Shipping_Admin {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_shipping', array( $this, 'add_shipping_rules_table' ) );
    	add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_shipping_rules_table_variation' ), 10, 3 );

    	add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
    	add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation' ), 10, 2 );

    	add_action( 'admin_init', array( $this, 'register_importer' ) );

    	add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    	return true;
	}

	/**
	 * Enqueue scripts
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();
		
		if ( 'product' !== $current_screen->id ) {
			return;
		}

		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		if ( $vendor_data && 'no' === $vendor_data['per_product_shipping'] ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wcpv-per-product-shipping-script', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/js/wcpv-per-product-shipping' . $suffix . '.js', array( 'jquery' ), WC_PRODUCT_VENDORS_VERSION, true );

		$localized_vars = array(
			'i18n_no_row_selected'      => __( 'No row selected', 'woocommerce-product-vendors' ),
			'i18n_product_id'           => __( 'Product ID', 'woocommerce-product-vendors' ),
			'i18n_country_code'         => __( 'Country Code', 'woocommerce-product-vendors' ),
			'i18n_state'                => __( 'State/County Code', 'woocommerce-product-vendors' ),
			'i18n_postcode'             => __( 'Zip/Postal Code', 'woocommerce-product-vendors' ),
			'i18n_cost'                 => __( 'Cost', 'woocommerce-product-vendors' ),
			'i18n_item_cost'            => __( 'Item Cost', 'woocommerce-product-vendors' ),
		);
		
		wp_localize_script( 'wcpv-per-product-shipping-script', 'wcpv_per_product_shipping_local', $localized_vars );

		wp_enqueue_script( 'wcpv-per-product-shipping-script' );

		return true;
	}

	/**
	 * Adds shipping rules table to product edit page variation
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function add_shipping_rules_table_variation( $loop, $variation_data, $variation ) {
		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		if ( $vendor_data && 'no' === $vendor_data['per_product_shipping'] ) {
			return;
		}

		echo '<div class="clear"></div><div class="options_group wcpv-per-product-shipping">';
		
		$this->render_shipping_rules_table( $variation->ID );

		echo '</div>';

		return true;
	}

	/**
	 * Adds shipping rules table to product edit page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function add_shipping_rules_table( $post_id = 0 ) {
		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		if ( $vendor_data && 'no' === $vendor_data['per_product_shipping'] ) {
			return;
		}

		echo '</div><div class="options_group wcpv-per-product-shipping">';

		$this->render_shipping_rules_table();

		return true;
	}

	/**
	 * Renders the shipping rules table
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function render_shipping_rules_table( $post_id = 0 ) {
		global $post, $wpdb;

		if ( ! $post_id ) {
			$post_id = $post->ID;
		}
		?>
		<div class="rules wcpv-per-product-shipping-rules">
			<h3 class="wcpv-shipping-rules-title"><?php esc_html_e( 'Shipping Rules', 'woocommerce-product-vendors' ); ?></h3>

			<table class="widefat">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php esc_html_e( 'Country Code', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'A 2 digit country code, e.g. US. Leave blank to apply to all.', 'woocommerce-product-vendors' ) ); ?></a></th>
						<th><?php esc_html_e( 'State/County Code', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'A state code, e.g. AL. Leave blank to apply to all.', 'woocommerce-product-vendors' ) ); ?></a></th>
						<th><?php esc_html_e( 'Zip/Postal Code', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'Postcode for this rule. Wildcards (*) can be used. Leave blank to apply to all areas.', 'woocommerce-product-vendors' ) ); ?></a></th>
						<th class="cost"><?php esc_html_e( 'Line Cost (Excl. Tax)', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'Decimal cost for the line as a whole.', 'woocommerce-product-vendors' ) ); ?></a></th>
						<th class="item_cost"><?php esc_html_e( 'Item Cost (Excl. Tax)', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'Decimal cost for the item (multiplied by qty).', 'woocommerce-product-vendors' ) ); ?></a></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="6">
							<a href="#" class="button button-primary insert" data-postid="<?php echo $post_id; ?>"><?php esc_html_e( 'Insert row', 'woocommerce-product-vendors' ); ?></a>
							<a href="#" class="button remove"><?php esc_html_e( 'Remove row', 'woocommerce-product-vendors' ); ?></a>

							<a href="#" download="per-product-rates-<?php echo $post_id ?>.csv" class="button export" data-postid="<?php echo $post_id; ?>"><?php esc_html_e( 'Export CSV', 'woocommerce-product-vendors' ); ?></a>

							<?php
							// only store owners are allow to import
							if ( current_user_can( 'manage_options' ) ) {
							?>
							<a href="<?php echo admin_url( 'admin.php?import=wcpv_per_product_shipping_csv' ); ?>" class="button import"><?php esc_html_e( 'Import CSV', 'woocommerce-product-vendors' ); ?></a>
							<?php } ?>
						</th>
					</tr>
				</tfoot>
				<tbody>
					<?php
						$rules = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE . " WHERE product_id = %d ORDER BY rule_order;", $post_id ) );

						foreach ( $rules as $rule ) {
							?>
							<tr>
								<td class="sort">&nbsp;</td>
								<td class="country"><input type="text" value="<?php echo esc_attr( $rule->rule_country ); ?>" placeholder="*" name="per_product_country[<?php echo $post_id; ?>][<?php echo $rule->rule_id ?>]" /></td>
								<td class="state"><input type="text" value="<?php echo esc_attr( $rule->rule_state ); ?>" placeholder="*" name="per_product_state[<?php echo $post_id; ?>][<?php echo $rule->rule_id ?>]" /></td>
								<td class="postcode"><input type="text" value="<?php echo esc_attr( $rule->rule_postcode ); ?>" placeholder="*" name="per_product_postcode[<?php echo $post_id; ?>][<?php echo $rule->rule_id ?>]" /></td>
								<td class="cost"><input type="text" value="<?php echo esc_attr( $rule->rule_cost ); ?>" placeholder="0.00" name="per_product_cost[<?php echo $post_id; ?>][<?php echo $rule->rule_id ?>]" /></td>
								<td class="item_cost"><input type="text" value="<?php echo esc_attr( $rule->rule_item_cost ); ?>" placeholder="0.00" name="per_product_item_cost[<?php echo $post_id; ?>][<?php echo $rule->rule_id ?>]" /></td>
							</tr>
							<?php
						}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}


	/**
	 * Replaces the aseterisks with emtpy string
	 *
	 * @param string $rule
	 * @return string
	 */
	public function replace_aseterisk( $rule ) {
		if ( ! empty( $rule ) && '*' === $rule ) {
			return '';
		}
		return $rule;
	}
	
	/**
	 * Saves the shipping rules
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function save( $post_id ) {
		global $wpdb;
		
		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		if ( $vendor_data && 'no' === $vendor_data['per_product_shipping'] ) {
			return;
		}

		$countries  = ! empty( $_POST['per_product_country'][ $post_id ] ) ? $_POST['per_product_country'][ $post_id ] : '';
		$states     = ! empty( $_POST['per_product_state'][ $post_id ] ) ? $_POST['per_product_state'][ $post_id ] : '';
		$postcodes  = ! empty( $_POST['per_product_postcode'][ $post_id ] ) ? $_POST['per_product_postcode'][ $post_id ] : '';
		$costs      = ! empty( $_POST['per_product_cost'][ $post_id ] ) ? $_POST['per_product_cost'][ $post_id ] : '';
		$item_costs = ! empty( $_POST['per_product_item_cost'][ $post_id ] ) ? $_POST['per_product_item_cost'][ $post_id ] : '';
		$i          = 0;

		if ( $countries ) {
			foreach ( $countries as $key => $value ) {
				if ( $key == 'new' ) {
					foreach ( $value as $new_key => $new_value ) {
						if ( ! empty( $countries[ $key ][ $new_key ] ) || ! empty( $states[ $key ][ $new_key ] ) || ! empty( $postcodes[ $key ][ $new_key ] ) || ! empty( $costs[ $key ][ $new_key ] ) || ! empty( $item_costs[ $key ][ $new_key ] ) ) {
							$wpdb->insert(
								WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE,
								array(
									'rule_country'   => esc_attr( $this->replace_aseterisk( $countries[ $key ][ $new_key ] ) ),
									'rule_state'     => esc_attr( $this->replace_aseterisk( $states[ $key ][ $new_key ] ) ),
									'rule_postcode'  => esc_attr( $this->replace_aseterisk( $postcodes[ $key ][ $new_key ] ) ),
									'rule_cost'      => esc_attr( $costs[ $key ][ $new_key ] ),
									'rule_item_cost' => esc_attr( $item_costs[ $key ][ $new_key ] ),
									'rule_order'     => $i++,
									'product_id'     => absint( $post_id )
								)
							);
						}
					}
				} else {
					if ( ! empty( $countries[ $key ] ) || ! empty( $states[ $key ] ) || ! empty( $postcodes[ $key ] ) || ! empty( $costs[ $key ] ) || ! empty( $item_costs[ $key ] ) ) {
						$wpdb->update(
							WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE,
							array(
								'rule_country'   => esc_attr( $this->replace_aseterisk( $countries[ $key ] ) ),
								'rule_state'     => esc_attr( $this->replace_aseterisk( $states[ $key ] ) ),
								'rule_postcode'  => esc_attr( $this->replace_aseterisk( $postcodes[ $key ] ) ),
								'rule_cost'      => esc_attr( $costs[ $key ] ),
								'rule_item_cost' => esc_attr( $item_costs[ $key ] ),
								'rule_order'     => $i++
							),
							array(
								'product_id' => absint( $post_id ),
								'rule_id'    => absint( $key )
							)
						);
					} else {
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE . " WHERE product_id = %d AND rule_id = %s;", absint( $post_id ), absint( $key ) ) );
					}
				}
			}
		}
	}

	/**
	 * Saves the shipping rules (variations)
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function save_variation( $post_id, $index ) {
		global $wpdb;

		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		if ( $vendor_data && 'no' === $vendor_data['per_product_shipping'] ) {
			return;
		}

		$countries  = ! empty( $_POST['per_product_country'][ $post_id ] ) ? $_POST['per_product_country'][ $post_id ] : '';
		$states     = ! empty( $_POST['per_product_state'][ $post_id ] ) ? $_POST['per_product_state'][ $post_id ] : '';
		$postcodes  = ! empty( $_POST['per_product_postcode'][ $post_id ] ) ? $_POST['per_product_postcode'][ $post_id ] : '';
		$costs      = ! empty( $_POST['per_product_cost'][ $post_id ] ) ? $_POST['per_product_cost'][ $post_id ] : '';
		$item_costs = ! empty( $_POST['per_product_item_cost'][ $post_id ] ) ? $_POST['per_product_item_cost'][ $post_id ] : '';
		$i          = 0;

		if ( $countries ) {
			foreach ( $countries as $key => $value ) {
				if ( $key == 'new' ) {
					foreach ( $value as $new_key => $new_value ) {
						if ( ! empty( $countries[ $key ][ $new_key ] ) || ! empty( $states[ $key ][ $new_key ] ) || ! empty( $postcodes[ $key ][ $new_key ] ) || ! empty( $costs[ $key ][ $new_key ] ) || ! empty( $item_costs[ $key ][ $new_key ] ) ) {
							$wpdb->insert(
								WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE,
								array(
									'rule_country'   => esc_attr( $this->replace_aseterisk( $countries[ $key ][ $new_key ] ) ),
									'rule_state'     => esc_attr( $this->replace_aseterisk( $states[ $key ][ $new_key ] ) ),
									'rule_postcode'  => esc_attr( $this->replace_aseterisk( $postcodes[ $key ][ $new_key ] ) ),
									'rule_cost'      => esc_attr( $costs[ $key ][ $new_key ] ),
									'rule_item_cost' => esc_attr( $item_costs[ $key ][ $new_key ] ),
									'rule_order'     => $i++,
									'product_id'     => absint( $post_id )
								)
							);
						}
					}
				} else {
					if ( ! empty( $countries[ $key ] ) || ! empty( $states[ $key ] ) || ! empty( $postcodes[ $key ] ) || ! empty( $costs[ $key ] ) || ! empty( $item_costs[ $key ] ) ) {
						$wpdb->update(
							WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE,
							array(
								'rule_country'   => esc_attr( $this->replace_aseterisk( $countries[ $key ] ) ),
								'rule_state'     => esc_attr( $this->replace_aseterisk( $states[ $key ] ) ),
								'rule_postcode'  => esc_attr( $this->replace_aseterisk( $postcodes[ $key ] ) ),
								'rule_cost'      => esc_attr( $costs[ $key ] ),
								'rule_item_cost' => esc_attr( $item_costs[ $key ] ),
								'rule_order'     => $i++
							),
							array(
								'product_id' => absint( $post_id ),
								'rule_id'    => absint( $key )
							)
						);
					} else {
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . WC_PRODUCT_VENDORS_PER_PRODUCT_SHIPPING_TABLE . " WHERE product_id = %d AND rule_id = %s;", absint( $post_id ), absint( $key ) ) );
					}
				}
			}
		}
	}

	/**
	 * Registers our shipping rules importer
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_importer() {
		// only store owners are allow to import
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( defined( 'WP_LOAD_IMPORTERS' ) ) {
			register_importer( 'wcpv_per_product_shipping_csv', __( 'WooCommerce Product Vendors Per-product shipping rates (CSV)', 'woocommerce-product-vendors' ), __( 'Import <strong>per-product shipping rates</strong> to your store via a csv file.', 'woocommerce-product-vendors'), array( $this, 'importer' ) );
		}

		return true;
	}

	/**
	 * Loads our shipping rules importer
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function importer() {
		include_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			
			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		include_once( 'class-wc-product-vendors-per-product-shipping-importer.php' );

		$importer = new WC_Product_Vendors_Per_Product_Shipping_Importer();

		$importer->dispatch();

		return true;
	}	
}

new WC_Product_Vendors_Per_Product_Shipping_Admin();
