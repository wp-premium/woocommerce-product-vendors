<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Installation/Activation Class.
 *
 * Handles the activation/installation of the plugin.
 *
 * @category Installation
 * @package  WooCommerce Product Vendors/Install
 * @version  2.0.0
 */
class WC_Product_Vendors_Install {
	private static $roles;

	/** @var array DB updates that need to be run */
	private static $db_updates = array(
		'2.0.0' => 'admin/updates/wc-product-vendors-update-2.0.0.php',
	);

	/**
	 * Initialize hooks
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function init( WC_Product_Vendors_Roles_Caps $roles ) {
		self::$roles = $roles;

		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'update_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'updated_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'activate_notice' ) );

		return true;
	}

	/**
	 * Checks the plugin version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'wcpv_version' ) != WC_PRODUCT_VENDORS_VERSION ) ) {
			self::install();

			do_action( 'wcpv_updated' );
		}

		return true;
	}

	/**
	 * Perform actions
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function install_actions() {
		// add roles one time
		if ( 'yes' !== get_option( 'wcpv_add_roles' ) ) {
			self::$roles->add_default_roles();

			update_option( 'wcpv_add_roles', 'yes' );
		}

		if ( ! empty( $_GET['dismiss_wcpv'] ) ) {
			delete_option( 'wcpv_show_update_notice' );			
			add_option( 'wcpv_show_update_notice', false );
		}

		if ( ! empty( $_GET['do_update_wcpv'] ) ) {
			self::update();
			
			wp_redirect( add_query_arg( 'wcpv-updated', 'true', admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpv_vendor_settings' ) ) );
			exit;
		}

		return true;
	}

	/**
	 * Updates the plugin version in db
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_plugin_version() {
		delete_option( 'wcpv_version' );
		add_option( 'wcpv_version', WC_PRODUCT_VENDORS_VERSION );

		return true;
	}

	/**
	 * Updates the plugin version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_wcpv_version() {
		delete_option( 'wcpv_version' );
		add_option( 'wcpv_version', WC_PRODUCT_VENDORS_VERSION );

		return true;
	}

	/**
	 * Updates the commission table db version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_commission_db_version( $version = null ) {
		delete_option( 'wcpv_commissions_db_version' );
		add_option( 'wcpv_commissions_db_version', is_null( $version ) ? WC_PRODUCT_VENDORS_VERSION : $version );

		return true;
	}

	/**
	 * Updates the per product shipping table db version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_per_product_shipping_db_version( $version = null ) {
		delete_option( 'wcpv_per_product_shipping_db_version' );
		add_option( 'wcpv_per_product_shipping_db_version', is_null( $version ) ? WC_PRODUCT_VENDORS_VERSION : $version );

		return true;
	}

	/**
	 * Perform update action
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update() {
		$current_commissions_db_version = '1.0.0';

		foreach ( self::$db_updates as $version => $updater ) {
			if ( version_compare( $current_commissions_db_version, $version, '<' ) ) {
				include( $updater );
				self::update_commission_db_version( $version );
			}
		}

		self::update_commission_db_version();
		self::update_per_product_shipping_db_version();

		delete_option( 'wcpv_show_update_notice' );			
		add_option( 'wcpv_show_update_notice', false );

		return true;
	}

	/**
	 * Checks to see if we need update for 2.0.0
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function needs_update() {
		global $wpdb;

		$commissions = get_posts( array( 'posts_per_page' => 1, 'post_type' => 'shop_commission', 'post_status' => array( 'publish', 'private' ) ) );
		$vendors = $wpdb->get_row( "SELECT term_id FROM $wpdb->term_taxonomy WHERE `taxonomy` = 'shop_vendor'" );
		
		// if 1.0.0 commissions or vendors exists we need to update
		if ( ! empty( $commissions ) || ( ! is_wp_error( $vendors ) && ! empty( $vendors ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show update notice
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function update_notice() {
		$show_notice = get_option( 'wcpv_show_update_notice', true );

		if ( self::needs_update() && $show_notice ) {
			include_once( 'admin/updates/views/html-update-notice-2.0.0.php' );
		}

		return true;
	}

	/**
	 * Show updated notice
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function updated_notice() {
		if ( ! empty( $_GET['wcpv-updated'] ) ) {
		?>
			<div class="update-nag notice">
				<p><?php esc_html_e( 'WooCommerce Product Vendors data update complete. Thank you for updating to the latest version!', 'woocommerce-product-vendors' ); ?></p>
			</div>
		<?php
		}

		return true;
	}

	/**
	 * Do installs
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function install() {
		global $wpdb;

		if ( ! defined( 'WCPV_INSTALLING' ) ) {
			define( 'WCPV_INSTALLING', true );
		}

		self::create_tables();
		self::update_commission_db_version();
		self::update_per_product_shipping_db_version();
		self::update_wcpv_version();
		self::clear_reports_transients();
		
		flush_rewrite_rules();

		return true;
	}

	/**
	 * Prepare tables for modification/add
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wcpv_commissions';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}wcpv_commissions` LIKE 'id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}wcpv_commissions DROP PRIMARY KEY, ADD `id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wcpv_per_product_shipping_rules';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}wcpv_per_product_shipping_rules` LIKE 'rule_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}wcpv_per_product_shipping_rules DROP PRIMARY KEY, ADD `rule_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		dbDelta( self::get_schema() );

		return true;
	}

	/**
	 * Shows activation notice for next steps
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function activate_notice() {
		$show_notice = get_option( 'wcpv_show_activate_notice', true );

		if ( $show_notice ) {
			?>
			<div class="updated woocommerce-message woocommerce-product-vendors-activated" style="border-left-color: #aa559a;">
				<h4><?php esc_html_e( 'WooCommerce Product Vendors Installed &#8211; To get started,', 'woocommerce-product-vendors' ); ?> <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpv_vendor_settings' ); ?>"><?php esc_html_e( 'configure your vendor settings', 'woocommerce-product-vendors' ); ?></a></h4>
			</div>
			<?php

			update_option( 'wcpv_show_activate_notice', '0' );
		}
	}

	/**
	 * Clears all reports transients
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function clear_reports_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wcpv_reports%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wcpv_unfulfilled_products%'" );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%book_dr%'" );

		return true;
	}

	/**
	 * Add tables
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		return "
CREATE TABLE {$wpdb->prefix}wcpv_commissions (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	order_id bigint(20) NOT NULL,
	order_item_id bigint(20) NOT NULL,
	order_date datetime DEFAULT NULL,
	vendor_id bigint(20) NOT NULL,
	vendor_name longtext NOT NULL,
	product_id bigint(20) NOT NULL,
	variation_id bigint(20) NOT NULL,
	product_name longtext NOT NULL,
	variation_attributes longtext NOT NULL,
	product_amount longtext NOT NULL,
	product_quantity longtext NOT NULL,
	product_shipping_amount longtext,
	product_shipping_tax_amount longtext,
	product_tax_amount longtext,
	product_commission_amount longtext NOT NULL,
	total_commission_amount longtext NOT NULL,
	commission_status varchar(20) NOT NULL DEFAULT 'unpaid',
	paid_date datetime DEFAULT NULL,
	PRIMARY KEY  (id)
) $collate;
CREATE TABLE {$wpdb->prefix}wcpv_per_product_shipping_rules (
	rule_id bigint(20) NOT NULL AUTO_INCREMENT,
	product_id bigint(20) NOT NULL,
	rule_country varchar(10) NOT NULL,
	rule_state varchar(10) NOT NULL,
	rule_postcode varchar(200) NOT NULL,
	rule_cost varchar(200) NOT NULL,
	rule_item_cost varchar(200) NOT NULL,
	rule_order bigint(20) NOT NULL,
	PRIMARY KEY  (rule_id)
) $collate;
		";

		return true;
	}
}

WC_Product_Vendors_Install::init( new WC_Product_Vendors_Roles_Caps );
