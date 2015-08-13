jQuery(document).ready(function ($) {
	if($('#edd_variable_pricing').prop('checked')){
		$('.edd_field_type_select').hide();			
	}else{
		$('.edd_field_type_select').show();			
	}	
	$( 'body' ).on( 'change', '#edd_variable_pricing', function(e) {
		if($('#edd_variable_pricing').prop('checked')){
			$('.edd_field_type_select').hide();			
		}else{
			$('.edd_field_type_select').show();			
		}
	});	
});