(function($) {

	// On document Load
	$(function() {

		// Setup options
		jigoshop_product_type_options();

	});


	function jigoshop_product_type_options() {
		$('select#product-type').change(function(){

			$('body').removeClass('coupon_product')
				.addClass( $(this).val() + '_product' );
		}).change();
	}

})(window.jQuery);