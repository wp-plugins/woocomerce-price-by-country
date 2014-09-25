jQuery( document ).ready(function($) {
	// Frontend Chosen selects
	$("select.country_select, select.state_select").chosen( { search_contains: true } );
	
	
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
	$('select#shipping_country').on('change', function(evt, params) {
		if($('#ship-to-different-address-checkbox').is(':checked')){
			var valueW = $('#shipping_country').val();
			$.cookie('country', valueW, { expires: 7 , path: '/' });
		}
	});
	
	// bind select change functions
	$('select#billing_country').on('change', function(evt, params) {
		if($('#ship-to-different-address-checkbox').is(':checked')){
		} else {
			var valueR = $('#billing_country').val();
			$.cookie('country', valueR, { expires: 7 , path: '/' });
		}
	});
});