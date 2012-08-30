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



//  Check if Jigoshop is active
if ( in_array( 'jigoshop/jigoshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ):



/**
 * Admin scripts
 **/
function jigoshop_coupon_admin_scripts() {

	if (!jigoshop_is_admin_page()) return false;
	wp_enqueue_script('jigoshop_coupon_backend', plugins_url( 'assets/js/write-panels.js' , __FILE__ ), array( 'jigoshop_backend' ), '0.1' );
}
add_action( 'admin_print_scripts' , 'jigoshop_coupon_admin_scripts');



/**
 * Enqueue admin styles
 *
 * @package		Jigoshop
 * @subpackage 	Coupon products for Jigoshop
 * @since 0.1
 *
 **/
function jigoshop_coupon_admin_styles() {

	if ( ! jigoshop_is_admin_page() ) return false;
	wp_enqueue_style( 'jigoshop_coupon_admin_styles', plugins_url( 'assets/css/admin.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'jigoshop_coupon_admin_styles', 640 );



/**
 * Adds Coupon type on product selector
 *
 * @param array $types - array of product type options to select on product page
 * @return array - modified array of product type options to select on product page
 *
 * @package		Jigoshop
 * @subpackage 	Coupon products for Jigoshop
 * @since 0.1
 *
 **/
function jigoshop_add_coupon_type_selector( $types ) {

	$types['coupon'] = __('Coupon', 'jigoshop');
	return $types;
}	
add_filter('jigoshop_product_type_selector', 'jigoshop_add_coupon_type_selector', 10);



/**
 * Adds Coupon tab on admin product
 **/
function jigoshop_product_write_coupon_tab() { 
	?>
	<li class="coupon_tab">
		<a href="#coupon"><?php _e('Coupon', 'jigoshop') ?></a>
	</li>
	<?php	
}
// add_action('jigoshop_product_write_panel_tabs', 'jigoshop_product_write_coupon_tab');



/**
 * Adds Coupon tab content on admin product
 **/
function jigoshop_product_write_coupon_panels() {
	global $post;
	
	$product_coupon = get_post_meta( $post->ID, 'product_coupon', true );
	$coupon = isset($product_coupon['coupon']) ? $product_coupon['coupon'] : '';
	$code = isset($product_coupon['code']) ? $product_coupon['code'] : wp_generate_password(13);
	
	?>
	<div id="coupon" class="panel jigoshop_options_panel">
	<fieldset>
	<?php
	
	// current coupons
	$jigoshop_coupons = get_option('jigoshop_coupons');
	$coupons = array( '0' => __('Select a coupon', 'jigoshop') );
	foreach ($jigoshop_coupons as $code_ => $coupon_) $coupons[$code_] = $code_;
	
	// Visibility
	echo jigoshop_form::select( 'product_coupon_coupon', 'Associated Coupon', $coupons, $coupon, __('Associates an existing coupon rule with this product', 'jigoshop') );
	?>
	</fieldset>
	<fieldset>
		<p class="form-field">
			<label><?php _e('Coupon Code', 'jigoshop'); ?></label>
			<input type="hidden" name="product_coupon_code" value="<?php echo $code ?>"/>
			<code style="padding:5px; font-size: 14px"><?php echo $code ?></code>
			<span class="description"><?php _e('Unique coupon reference for the customer', 'jigoshop'); ?></span>
		</p>
	</fieldset>
	</div>
	<?php 
}
// add_action('jigoshop_product_write_panels', 'jigoshop_product_write_coupon_panels');



function jigoshop_process_product_meta_coupon($post_id, $post) {
	
	if ( !isset($_POST['product_coupon_coupon']) || !isset($_POST['product_coupon_code']) ) return;
	
		$product_coupon = array(
			'coupon' => $_POST['product_coupon_coupon'],
			'code' => $_POST['product_coupon_code']
		);
		update_post_meta( $post_id, 'product_coupon', $product_coupon);
}
//add_action( 'jigoshop_process_product_meta', 'jigoshop_process_product_meta_coupon', 5, 2 );



/**
 * Adds Coupon type
 **/
function jigoshop_add_coupon() {

	$product_types = array( 'Coupon' );

	foreach($product_types as $type) {
		$term = get_term_by( 'slug', sanitize_title($type), 'product_type');
		if (!$term) wp_insert_term($type, 'product_type');
	}
}

if ( is_admin() ) register_activation_hook( __FILE__, 'jigoshop_add_coupon' );



/**
 * Adds Coupon type
 **/
function jigoshop_del_coupon() {

	$product_types = array( 'coupon' );

	foreach($product_types as $type) {
		$term = get_term_by( 'slug', sanitize_title($type), 'product_type');
		if ($term) wp_delete_term( $term->term_id, 'product_type');
	}
}

if ( is_admin() ) register_deactivation_hook( __FILE__, 'jigoshop_del_coupon' );


endif;
// END Check if Jigoshop is active