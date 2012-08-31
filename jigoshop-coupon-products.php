<?php
/*
Plugin Name: Coupon Products for Jigoshop
Plugin URI: http://wordpress.org/extend/plugins/jigoshop-coupon-products
Description: Extends JigoShop adding a new 'coupon' product type
Version: 0.1
Author: Carlos Sanz García
Author URI: http://codingsomething.wordpress.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


	
/**
 * Checks Jigoshop Version
 *
 * @package		Jigoshop
 * @subpackage 	Utils
 * @since 		0.1
 *
 **/
function jigoshop_coupon_version() {
	require_once(ABSPATH.'wp-admin/includes/plugin.php');
	$plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/jigoshop/jigoshop.php');
	return $plugin_data['Version'];
};



/**
 * Adds coupon type
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_add_coupon() {

	$product_types = array( 'Coupon' );

	foreach($product_types as $type) {
		$term = get_term_by( 'slug', sanitize_title($type), 'product_type');
		if (!$term) wp_insert_term($type, 'product_type');
	};
};
if ( is_admin() ) register_activation_hook( __FILE__, 'jigoshop_add_coupon' );



/**
 * Delete coupon type
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_del_coupon() {

	$product_types = array( 'coupon' );

	foreach($product_types as $type) {
		$term = get_term_by( 'slug', sanitize_title($type), 'product_type');
		if ($term) wp_delete_term( $term->term_id, 'product_type');
	};
};
if (is_admin()) register_deactivation_hook( __FILE__, 'jigoshop_del_coupon' );



/**
 * Init plugin with dependences + hooks
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since 		0.1
 *
 **/
function jigoshop_coupon_init() {
	
	load_plugin_textdomain( 'jigoshop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
	// dependeces
	$active_plugins_ = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	if ( (in_array( 'jigoshop/jigoshop.php', $active_plugins_ ) && jigoshop_coupon_version() >= '1.3' ) && in_array( 'jigoshop-order-locator/jigoshop-order-locator.php', $active_plugins_)  ):
		
		add_action( 'jigoshop_new_order', 'jigoshop_check_for_coupons', 10 );
			
		// admin hooks
		if (is_admin()) {
			
			add_action( 'save_post', 'jigoshop_save_coupons', 1, 2 );
			add_action( 'trashed_post', 'jigoshop_trashed_coupon', 20);
			add_filter( 'post_row_actions', 'jigoshop_coupon_row_actions', 20, 2);
			add_action( 'admin_print_scripts' , 'jigoshop_coupon_admin_scripts');
			add_action( 'admin_enqueue_scripts', 'jigoshop_coupon_admin_styles', 640 );
			add_filter( 'jigoshop_product_type_selector', 'jigoshop_add_coupon_type_selector', 10);
			add_action( 'jigoshop_process_shop_order_meta', 'jigoshop_check_for_coupons', 10 );
			add_action( 'jigoshop_process_shop_coupon_save', 'jigoshop_save_coupon', 10, 2 );
			add_action( 'admin_head', 'jigoshop_remove_coupon_delete_link', 20 );
		};
		
	else:
		
		if (is_admin())
            add_action( 'admin_notices', 'jigoshop_coupon_dependences');
		
	endif;
};
add_action('plugins_loaded', 'jigoshop_coupon_init');



/**
 * Enqueue admin scripts
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_coupon_admin_scripts() {

	if (!jigoshop_is_admin_page()) return false;
	wp_enqueue_script('jigoshop_coupon_backend', plugins_url( 'assets/js/write-panels.js' , __FILE__ ), array( 'jigoshop_backend' ), '0.1' );
};



/**
 * Enqueue admin styles
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
 **/
function jigoshop_coupon_admin_styles() {

	if ( ! jigoshop_is_admin_page() ) return false;
	wp_enqueue_style( 'jigoshop_coupon_admin_styles', plugins_url( 'assets/css/admin.css' , __FILE__ ) );
};



/**
 * Adds Coupon type on product selector
 *
 * @param array $types - array of product type options to select on product page
 * @return array - modified array of product type options to select on product page
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
 **/
function jigoshop_add_coupon_type_selector( $types ) {

	$types['coupon'] = __('Coupon', 'jigoshop');
	return $types;
};



/**
 * Save post in shop coupons
 *
 * @param array $types - array of product type options to select on product page
 * @return array - modified array of product type options to select on product page
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
 **/
function jigoshop_save_coupons( $post_id, $post ) {
	global $wpdb;

	if ( !$_POST ) return $post_id;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' && $post->post_type != 'shop_order' && $post->post_type != 'shop_coupon' ) return $post_id;

	do_action( 'jigoshop_process_'.$post->post_type.'_save', $post_id, $post );
};



/**
 * Adds Coupon type on product selector
 *
 * @param array $types - array of product type options to select on product page
 * @return array - modified array of product type options to select on product page
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
 **/
function jigoshop_check_for_coupons($post_id = false) {
	
	if ( !$post_id ) return;
	
	$order_items = get_post_meta($post_id, 'order_items', true);
	
	foreach ($order_items as $row => $item):
		$row++;
		
		if ( has_term( 'coupon', 'product_type', $item['id'] ) ):
			
			$regular_price = get_post_meta($item['id'], 'regular_price', true);
			
			if ( $item['qty'] > 1) {
				for ( $n=1; $n < ($item['qty']+1); $n++):
					
					$locator = jigoshop_generate_locator($post_id, $item['id'], $row, $n);
					jigoshop_create_coupon($locator, $regular_price);
					
				endfor;
			} else {
				
				$locator = jigoshop_generate_locator($post_id, $item['id'], $row );
				jigoshop_create_coupon($locator, $regular_price);
			};
			
		endif;
		
	endforeach;
};



/**
 * prevent move to trash any default page
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_trashed_coupon($post_id = false) {
	if ( !$post_id ) return;
	
	$locator_id = get_post_meta($post_id, 'locator_id', true);  ######### THIS IS SLOW CHANGE THIS
	
	if ( jigoshop_exists_order_coupon($locator_id) )
		wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
};



/**
 * disable trash button on coupon row actions
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_coupon_row_actions($actions, $post) {
	
	if ($post->post_type != 'shop_coupon') return;
	
	if ( jigoshop_exists_order_coupon($post->post_title) )
		unset($actions['trash']);
	
	return $actions;
};



/**
 * disable trash button on page publish meta box
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since 0.1
 *
**/
function jigoshop_remove_coupon_delete_link() {
	global $pagenow, $post;
	
	if (!$post) return;
	
	if ( $pagenow == 'post.php' && jigoshop_exists_order_coupon($post->post_title) ) {
		echo "<!-- Jigoshop Coupon Products remove delete link -->" . PHP_EOL;
		echo "<style type=\"text/css\" media=\"screen\">" . PHP_EOL;
		echo "	#misc-publishing-actions > .misc-pub-section:first-child, #delete-action { display: none !important};" . PHP_EOL;
		echo "</style>" . PHP_EOL;
	};
};




/**
 * Process when coupon saves
 *
 * @param string $locator - locator id of coupon
 * @param int	 $amount - regular_price of coupon
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_save_coupon($post_id = false, $post = false) {
	global $wpdb;
	
	if ( !$post_id || !$post ) return;
	
	$locator_id = get_post_meta($post_id, 'locator_id', true);
	
	if (empty($locator_id)) return;
	
	if (!jigoshop_exists_order_coupon($locator_id)) return;
		
	if ($post->post_title == $locator_id && $post->post_status == 'publish') return;

	wp_update_post(array('ID' => $post->ID, 'post_status' => 'publish', 'post_title' => $locator_id)); ############################## USE HERE WPDB
};



/**
 * Checks if coupon really exists on orders
 *
 * @param string $locator - locator id of coupon
 * @return bool
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_exists_order_coupon($locator = false) {
	global $wpdb;
	
	if (!$locator) return;
	
	$orders = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'order_items'");
	
	if (!empty($orders)): foreach ($orders as $order):
			
		$order_id = $order->post_id;
		$order_items = maybe_unserialize($order->meta_value);
			
		foreach ($order_items as $row => $item) {
			$row++;
			if ( $item['qty'] > 1) {
				for ( $n=1; $n < ($item['qty']+1); $n++):
					
					if ($locator == jigoshop_generate_locator($order_id, $item['id'], $row, $n))
						return true;
					
				endfor;
			} else {
				
				if ($locator == jigoshop_generate_locator($order_id, $item['id'], $row))
					return true;
			};
			
		};
		
	endforeach; endif;
	
	return false;
};


/**
 * Checks if locator exists as coupon
 *
 * @param string $locator - locator id of coupon
 * @param int	 $amount - regular_price of coupon
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
 *
**/
function jigoshop_create_coupon($locator = false, $amount = 0) {
	global $wpdb;
	
	if (!$locator || !$amount) return;
	
	$coupon_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id  WHERE post_status != 'trash' AND meta_key = 'locator_id' AND meta_value = '$locator'");
	
	if (!empty($coupon_id)) return;
	
	$coupon_args = array(
		'post_title' => $locator,
		'post_type' => 'shop_coupon',
		'post_status' => 'publish'
	);
	
	$coupon_id = wp_insert_post($coupon_args);
	update_post_meta( $coupon_id, 'locator_id', $locator );
	update_post_meta( $coupon_id, 'usage_limit', 1 );
	update_post_meta( $coupon_id, 'amount', $amount );
	update_post_meta( $coupon_id, 'type', 'fixed_cart' );
};



/**
 * admin notice: update your old data 
 *
 * @package		Jigoshop
 * @subpackage 	Jigosgop Coupon Products
 * @since		0.1
**/
function jigoshop_coupon_dependences() {
	global $current_screen;
		
    echo "<div class=\"error\">" . PHP_EOL;
	echo "<p><strong>Jigoshop Coupon Products:</strong></p>" . PHP_EOL;
	echo "<p>" . __('This plugin requires at least <strong>Jigoshop 1.3</strong> and <strong>Jigoshop Order Locator</strong> plugins.', 'jigoshop') . "</p>" . PHP_EOL;
    echo "</div>" . PHP_EOL;
};