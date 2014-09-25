jQuery( document ).ready(function($) {

	if($('#ship-to-different-address-checkbox').is(':checked')){
		// get value on page load
		var valueR = $('#shipping_country').val();
		$.cookie('country', valueR, { expires: 7 , path: '/' });
		
	} else {
		// get value on page load
		var valueR = $('#billing_country').val();
		$.cookie('country', valueR, { expires: 7 , path: '/' });
	}
	
	$('#ship-to-different-address-checkbox').change(function() { 
		if($('#ship-to-different-address-checkbox').is(':checked')){
			var valueS = $('#shipping_country').val();
			$.cookie('country', valueS, { expires: 7 , path: '/' });
		} else {
			var valueB = $('#billing_country').val();
			$.cookie('country', valueB, { expires: 7 , path: '/' });
		}
	});
	
	// bind select change functions
	$('#shipping_country').change(function() { 
		if($('#ship-to-different-address-checkbox').is(':checked')){
			var value = $('#shipping_country').val();
			$.cookie('country', value, { expires: 7 , path: '/' });
		} 
	});
	
	// bind select change functions
	$('#billing_country').change(function() { 
		if($('#ship-to-different-address-checkbox').is(':checked')){
		} else {
			var value = $('#billing_country').val();
			$.cookie('country', value, { expires: 7 , path: '/' });
		}
	});
});