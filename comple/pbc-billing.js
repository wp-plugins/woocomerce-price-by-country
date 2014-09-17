jQuery( document ).ready(function($) {
	// get value on page load
	var valueR = $('#billing_country').val();
	$.cookie('country', valueR, { expires: 7 , path: '/' });
	
	// set value on select change
	$('#billing_country').change(function(){
	    var value = $('#billing_country').val();
		$.cookie('country', value, { expires: 7 , path: '/' });
	});
});