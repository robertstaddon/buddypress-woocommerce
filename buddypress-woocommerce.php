<?php
/*
	Plugin Name: Buddypress WooCommerce
	Description: Add WooCommerce My Account area to BuddyPress account
	Version: 1.1
	Author: Robert Staddon
	Author URI: https://abundantdesigns.com
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
          // Require WooCommerce to be active
          if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
               // Check for BuddyPress and initialize if it is active
               add_action( 'bp_include', array( $this, 'init' ) );
          }
	}
     
     /*
      * Initialize the plugin hooks if WooCommerce and BuddyPress are active
      */
     public function init() {
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
	public function customer_edit_account_url( $edit_account_url = "" ) {
		$current_user = wp_get_current_user();
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
		
          $wc_account_menu_items = $this->get_wc_account_menu_items();

          // Add top-level Account menu item
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
          
          $position = 0;
          foreach ( $wc_account_menu_items as $key => $item_title ) {
               $position += 10;
               if ( $key == 'dashboard') $key = 'view';
               
               bp_core_new_subnav_item(
                    array(
                         'name' => __( $item_title, 'buddypress' ),
                         'slug' => $key,
                         'parent_url' => $secure_account_url,
                         'parent_slug' => 'account',
                         'screen_function' => array( $this, 'account_screens' ),
                         'show_for_displayed_user' => false,
                         'position' => $position,
                         'item_css_id' => 'account-' . $key,
                    )
               );              
          }

		// Remove "Settings > Delete Account" 
		bp_core_remove_subnav_item( 'settings', 'delete-account' );
	}

     /**
      * Get $key => $value array of WooCommerce Account menu items for BuddyPress Account menu
      */
     public function get_wc_account_menu_items() {
          // Start with the WooCommerce Account menu items
          $wc_account_menu_items = wc_get_account_menu_items();         

          // Add new items
          $wc_account_menu_items['add-payment-method'] = "Add Payment Method";

          // Remove items that are on other BuddyPress menus
          unset( $wc_account_menu_items['customer-logout'] );
          unset( $wc_account_menu_items['edit-account'] );
          
          return $wc_account_menu_items;
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
		$current_user = wp_get_current_user();
		
		$base_path = "/members/" . $current_user->user_nicename . "/account/";
		$endpoint_path = $base_path . $endpoint . "/";
		$endpoint_value_path = $endpoint_path . $value;
		
          $wc_account_menu_items = $this->get_wc_account_menu_items();
          $wc_account_menu_items["delete-payment-method"] = "Delete Payment Method";
          $wc_account_menu_items["set-default-payment-method"] = "Set Default Payment Method";          
          
          if( $endpoint == "edit-account" ) {
               return $this->customer_edit_account_url();
          }
          elseif ( array_key_exists( $endpoint, $wc_account_menu_items ) )  {
               if($value)
                    return $endpoint_value_path;
               else
                    return $endpoint_path;         
          }
          else {
               return $url;
          }
		
	//	if("/edit-address" == substr( $url, 0, 13 )) {
	//		return "/" . basename( get_permalink( get_option('woocommerce_myaccount_page_id') ) ) . $url;
	//	}
	}
}
new BP_WooCommerce;
