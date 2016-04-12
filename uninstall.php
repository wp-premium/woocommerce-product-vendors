<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// roles and caps
remove_role( 'wc_product_vendors_admin_vendor' );
remove_role( 'wc_product_vendors_manager_vendor' );
remove_role( 'wc_product_vendors_pending_vendor' );

if ( class_exists( 'WP_Roles' ) ) {
	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}
}

// Remove manage vendors cap to admins and shop managers
if ( is_object( $wp_roles ) ) {
	$wp_roles->remove_cap( 'shop_manager', 'manage_vendors' );
	$wp_roles->remove_cap( 'administrator', 'manage_vendors' );
}

// tables
global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wcpv_commissions" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wcpv_per_product_shipping_rules" );

// options
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wcpv_vendor_settings_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wcpv_product_vendors_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wcpv_reports%'" );
$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%_wcpv_vendor%'" );

// delete database table version
delete_option( 'wcpv_commissions_db_version' );
delete_option( 'wcpv_per_product_shipping_db_version' );
delete_option( 'wcpv_version' );
delete_option( 'wcpv_show_update_notice' );
delete_option( 'woocommerce_wcpv_per_product_settings' );
delete_option( 'wcpv_show_activate_notice' );
delete_option( 'wcpv_add_roles' );
