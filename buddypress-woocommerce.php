<?php
/*
	Plugin Name: Buddypress WooCommerce
	Description: Add WooCommerce My Account area to BuddyPress account
	Version: 1.0
	Author: Robert Staddon
	Author URI: http://abundantdesigns.com
	License: GPLv2 or later
	Text Domain: buddypress-woocommerce
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Override WooCommerce default "is_add_payment_method_page" method so that it returns true if we're on the BuddyPress equivalent
 */
if( ! function_exists('is_add_payment_method_page') ) {
    function is_add_payment_method_page() {
        global $wp;
        
        if( isset( $wp->query_vars['add-payment-method'] ) ) 
            return true;

        return ( is_page( wc_get_page_id( 'myaccount' ) ) && isset( $wp->query_vars['add-payment-method'] ) );
    }
}

class BP_WooCommerce {

	public function __construct() {		
		// Add WooCommerce navigation and subnavigation to BuddyPress
		add_action( 'bp_setup_nav', array( $this, 'bp_navigation') );

		// Re-route WooCommerce Edit Account URL
		add_filter( 'woocommerce_customer_edit_account_url', array( $this, 'customer_edit_account_url' ) );

		// Re-route all WooCommerce URL endpoints to appropriate BuddyPress pages
		add_filter( 'woocommerce_get_endpoint_url', array( $this, 'get_endpoint_url' ), 10, 4 );
	}
		

	/*
	 * Use the BuddyPress "Account Settings" page (/members/username/settings/) instead of the WooCommerce "Edit Account" page (/my-account/edit-account)
	 * The WooCommerce page doesn't have "Display name publicly as..."
	 */
	public function customer_edit_account_url($edit_account_url) {
		global $current_user;
		get_currentuserinfo();
		return '/members/' . $current_user->user_nicename . "/settings";
	}


	/*
	 * Add WooCommerce "My Account" to BuddyPress profile
	 * http://xd3v.com/create-a-premium-social-network-with-woocommerce/
	 */
	public function bp_navigation() {
		global $bp;
		
		$account_url = trailingslashit( $bp->loggedin_user->domain . 'account' );
		$secure_account_url = str_replace( 'http:', 'https:', $account_url );
		
		bp_core_new_nav_item(
			array(
				'name' => __( 'Account', 'buddypress' ), 
				'slug' => 'account',
				'default_subnav_slug' => 'view',
				'show_for_displayed_user' => false, 
				'position' => 30,
				'item_css_id' => 'account',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Dashboard', 'buddypress' ),
				'slug' => 'view',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 10,
				'item_css_id' => 'account-view',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Orders', 'buddypress' ),
				'slug' => 'orders',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 20,
				'item_css_id' => 'account-orders',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Subscriptions', 'buddypress' ),
				'slug' => 'subscriptions',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 30,
				'item_css_id' => 'account-subscriptions',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Downloads', 'buddypress' ),
				'slug' => 'downloads',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 40,
				'item_css_id' => 'account-downloads',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Addresses', 'buddypress' ),
				'slug' => 'edit-address',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 50,
				'item_css_id' => 'account-edit-address',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Payment Methods', 'buddypress' ),
				'slug' => 'payment-methods',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 60,
				'item_css_id' => 'account-payment-methods',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Add Payment Method', 'buddypress' ),
				'slug' => 'add-payment-method',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 60,
				'item_css_id' => 'account-add-payment-method',
			)
		);
		bp_core_new_subnav_item(
			array(
				'name' => __( 'Bookings', 'buddypress' ),
				'slug' => 'bookings',
				'parent_url' => $secure_account_url,
				'parent_slug' => 'account',
				'screen_function' => array( $this, 'account_screens' ),
				'show_for_displayed_user' => false,
				'position' => 70,
				'item_css_id' => 'account-bookings',
			)
		);
		// Remove "Settings > Delete Account" 
		bp_core_remove_subnav_item( 'settings', 'delete-account' );
	}

	/**
	 * These are the screen_functions used by our custom BuddyPress navigation items
	 */
	function account_screens() {
		//add_action( 'bp_template_title', array( $this, 'account_screen_title' ) );
		add_action( 'bp_template_content', array( $this, 'account_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}
	function account_screen_title() {
		echo 'My Account';
	}
	function account_content() {
		wc_print_notices();
		do_action( 'woocommerce_account_content' );
	}


	/**
	 * Point WooCommerce endpoints to BuddyPress My Account pages
	 */
	public function get_endpoint_url( $url, $endpoint, $value, $permalink ) {
		global $current_user;
		get_currentuserinfo();
		
		$base_path = "/members/" . $current_user->user_nicename . "/account/";
		$endpoint_path = $base_path . $endpoint . "/";
		$endpoint_value_path = $endpoint_path . $value;
		
		switch( $endpoint ) {
			case "orders":
			case "subscriptions":
			case "downloads":
			case "edit-address":
			case "payment-methods":
			case "add-payment-method":
			case "delete-payment-method":
			case "set-default-payment-method":
			case "bookings":
				error_log( "TEST:" . var_export($url, true) . "|" . var_export($endpoint, true) . "|" . var_export($value, true) . "|" . var_export($permalink, true) );
				if($value)
					return $endpoint_value_path;
				else
					return $endpoint_path;
				
			case "edit-account":
				return $this->customer_edit_account_url();
				
			default:
				return $url;
		}
		
	//	if("/edit-address" == substr( $url, 0, 13 )) {
	//		return "/" . basename( get_permalink( get_option('woocommerce_myaccount_page_id') ) ) . $url;
	//	}
		return $url;
	}
	
	public function fake_myaccount_page_id( $myaccount_page_id ) {
		return true;
	}
}
new BP_WooCommerce;
