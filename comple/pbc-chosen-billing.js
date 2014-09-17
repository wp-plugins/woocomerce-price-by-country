jQuery( document ).ready(function($) {
	// Frontend Chosen selects
	$("select.country_select, select.state_select").chosen( { search_contains: true } );
	
	
	var valueR = $('#billing_country').val();
	$.cookie('country', valueR, { expires: 7 , path: '/' });
	
	$('select#billing_country').on('change', function(evt, params) {
		var value = $("#billing_country").val();
		$.cookie('country', value, { expires: 7 , path: '/' });
	});
	
	
	
});