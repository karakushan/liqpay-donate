jQuery(document).ready(function($) {
	
	$(document).on('click', '.open_modal', function(event) {
		event.preventDefault();
		let id = $(this).find('a').attr('id')
		$(".lg-pay-button").attr('data-project-id',id);
		$(".ld-amount").attr('data-project-id',id).trigger('input');  
	});

	$(document).on('click', '.ld-modal-close', function(event) {
		event.preventDefault();
		 $(".ld-modal").removeClass('active');
	}); 

	$("body").on('click','.lg-pay-button', function(event) {
		event.preventDefault();
		$(".ld-modal").addClass('active');
		$(".ld-amount").attr('data-project-id',$(this).attr('data-project-id'));
	}); 

	$("body").on('input', '.ld-amount', function(event) {
		event.preventDefault();
		let amount = $(this).val();
		let project_id = $(this).attr('data-project-id');

		$.ajax({
		url: lgData.ajax_url,
		type: 'POST',
		data: { 
			action: 'lg_refresh_data', 
			amount: amount,
			project_id:  project_id
		}
	})
	.done(function(response) {
		$('.ld-modal-content [name="signature"]').val(response.data.signature);
		$('.ld-modal-content [name="data"]').val(response.data.data);
	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	});

	
	
});   