jQuery(document).ready(function ($) {

	$('body').on('submit', '#ubs-update-availability', function (e) {
		e.preventDefault();

		const nonce = $('#_wpnonce').val();

		$('#ubs-update-button').attr('disabled', true);
		$('.spinner').addClass('is-active');
		$('.initially-hidden').show();

		// start the process
		process_step(1, self, nonce);

	});

	// Batch processing.
	// See https://pippinsplugins.com/batch-processing-for-big-data/

	function process_step( step, self, nonce) {

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'ubs_update_availability_ajax',
				step: step,
				nonce: nonce,
			},
			dataType: "json",
			success: function( response ) {
				if ('done' === response.step) {
					$('#ubs-update-progess').html( response.beer_count );
					$('#ubs-update-availability-progress').attr( 'value', response.percentage );
					$('.spinner').removeClass('is-active');
					$('#ubs-update-availability').attr('disabled', false);
				} else {
					$('#ubs-update-progess').html( response.step * response.batch_size );
					$('#ubs-update-availability-progress').attr( 'value', response.percentage );
					process_step( parseInt( response.step ), self, nonce );
				}

			}
		}).fail(function (response) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		});

	}

});
