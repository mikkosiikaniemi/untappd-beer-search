jQuery(document).ready(function ($) {

	var update_process_total_exec_time = 0;
	var start_time;
	var total_time = 0;

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

	function process_step(step, self, nonce) {

		// Start measuring execution time.
		start_time = performance.now();

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
					$('#ubs-update-availability-progress').attr('value', response.percentage);
					$('#ubs-update-availability-time-remaining').html('00:00');
					$('.spinner').removeClass('is-active');
					$('#ubs-update-availability').attr('disabled', false);
				} else {
					$('#ubs-update-progess').html( response.step * response.batch_size );
					$('#ubs-update-availability-progress').attr('value', response.percentage);

					// Stop execution time measurement.
					var end_time = performance.now();

					// Increment the total time (in milliseconds).
					total_time += end_time - start_time;

					// Calculate time remaining.
					var time_remaining = Math.floor( (total_time / 1000 / step) * (response.step_count - step) );

					// Determine the minutes and seconds remaining.
					var minutes = Math.floor(time_remaining / 60);
					var seconds = time_remaining - ( minutes * 60 );

					// Helper function to pad number with leading zero.
					function leftFillNum(num, targetLength) {
						return num.toString().padStart(targetLength, 0);
					}

					// Construct time remaining string (min:sec).
					var time_remaining_min_sec = leftFillNum(minutes,2) + ':' + leftFillNum(seconds,2);

					$('#ubs-update-availability-time-remaining').html(time_remaining_min_sec);
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
