jQuery(function ($) {

	// Process search form submit action.
	$('#ubs-search').on('submit', function (event, alko_id) {

		$('.submit .button').prop('disabled', true);
		$("#ubs-search .spinner").addClass("is-active");

		var beer_name = $('#beer-name').val();
		var options = $("#beer-names option");

		if (alko_id === null) {
			// Loop thorugh the datalist options to find out the selected option.
			// Get the data attribute to find out Alko product ID.
			for (var i = 0; i < options.length; i++) {
				var option = options[i];

				if (option.innerText === beer_name) {
					alko_id = option.getAttribute('data-alko-id');
					break;
				}
			}
		}

		$('#beer-name').prop('readonly', false);
		$('#beer-name').attr('placeholder', $('#beer-name').data('placeholder-empty'));

		var data = {
			action: 'ubs_get_search_results',
			beer_name: beer_name,
			ubs_nonce: $('#ubs_search_nonce').val(),
			alko_id: alko_id
		};

		$.post(ajaxurl, data, function (response) {
			$('#ubs-untappd-response').html(response);

			// Hide "Associate" button if beer already saved.
			var selected_alko_id = $('select#alko_id').val();
			if ($('button[data-post-id="' + selected_alko_id + '"]')) {
				$('button[data-post-id="' + selected_alko_id + '"]').hide();
			}


			$('.submit .button').prop('disabled', false);
			$("#ubs-search .spinner").removeClass("is-active");
		});

		return false;
	});

	/**
	 * Dynamically watch for Alko ID select element changes, and hide/show
	 * "Associate" button respectively.
	 */
	$(document).on('change', 'select#alko_id', function (element) {
		var selected_alko_id = $(this).val();
		$('.ubs-associate-alko').each(function () {
			var button = $(this);
			if (button.data('post-id') == selected_alko_id) {
				button.hide();
			} else {
				button.show();
			}
		});
	});

	/**
	 * Dynamically watch for beer select (radio buttons); if none was selected
	 * to begin with, save buttons were disabled. Enable them if selection made
	 * later.
	 */
	$(document).on('change', 'input[name="beer-id[]"]', function (element) {
		$('button[name^="ubs-save"]').prop('disabled', false);
	});

	// Process save form submit action.
	$(document).on('submit', '#ubs-search-results', function (element) {

		// Determine which submit button was pressed.
		var form_action = element.originalEvent.submitter.name;

		// Disable submit buttons.
		$("#ubs-search-results button").prop('disabled', true);

		$("#ubs-search-results .spinner").addClass("is-active");

		var data = {
			action: 'ubs_save_selected_results',
			beer_id: $('input[name="beer-id[]"]:checked').val(),
			alko_id: $('#alko_id').val(),
			ubs_nonce: $('#ubs_save_nonce').val(),
		};

		$('input[name="beer-id[]"]:checked').each(function (element) {
			id = $(this).attr("id");
			id = id.replace('beer-check-', '');
			$('#beer-save-' + id).html('<span class="spinner is-active"></span>');
		});

		$.post(ajaxurl, data, function (response) {

			var result = JSON.parse(response);

			if (false === isNaN(result.status)) {

				// If "Save and populate next" pressed, trigger new "populate" click.
				if (form_action === 'ubs-save-and-populate') {
					$('#ubs-untappd-response').html('');
					$("#ubs-alko-populate").trigger("click");
					return;
				}

				$('#beer-check-' + result.beer_id).prop("checked", true).attr("disabled", true);
				$('#beer-save-' + result.beer_id).html('☑️ ' + ' Rating: ' + Number(result.status).toFixed(2));
			} else {
				$('#beer-save-' + result.beer_id).html(result.status);
				$("#ubs-search-results button").prop('disabled', false);
			}

			// Update API requests numeric limit.
			$("#ubs-limit-remaining").html(result.limit_remaining);

			$("#ubs-search-results .spinner").removeClass("is-active");

		});

		return false;
	});

	// Populate search field with first Alko product not yet saved.
	$(document).on('click', '#ubs-alko-populate', function (element) {
		element.preventDefault();

		$('#beer-name').attr('placeholder', $('#beer-name').data('placeholder-populating')).prop('readonly', true);
		$("#ubs-search .spinner").addClass("is-active");
		$('.submit .button').prop('disabled', true);

		var data = {
			action: 'ubs_populate_alko_product',
			ubs_nonce: $('#ubs_search_nonce').val(),
		};

		$.post(ajaxurl, data, function (response) {
			if (response.success === true) {
				$('#ubs-beers-left-to-save').html(response.data.beers_left_to_save);
				$('#beer-name').val(response.data.beer_name);
			}

			$('#ubs-search').trigger('submit', [response.data.alko_id]);
		});

		return false;
	});

	// Remove suffixes from beer name in search input.
	$(document).on('click', '#ubs-search-remove-suffixes', function (element) {
		element.preventDefault();

		var beer_name = $('#beer-name').val().trim();

		// Remove vintage from name.
		if ($.isNumeric(beer_name.substring(beer_name.length - 4))) {
			beer_name = beer_name.substring(0, beer_name.length - 4);
			beer_name = beer_name.trim();
		}

		// Remove suffixes.
		var suffixes = ["DDH", "DIPA", "NEIPA", "IPA", "New England", "Imperial Stout", "Berliner Weisse", "India Pale Ale", "Barley Wine", "Gose"];
		for (var i = 0; i < suffixes.length; i++) {
			suffixes.forEach(function (suffix) {
				if (beer_name.substring(beer_name.length - suffix.length) == suffix) {
					beer_name = beer_name.substring(0, beer_name.length - suffix.length);
					beer_name = beer_name.trim();
				}
			});
		}
		$('#beer-name').val(beer_name);
		$('#ubs-search').submit();
	});

	// Associate another Alko product number with an existing saved beer.
	$(document).on('click', '.ubs-associate-alko', function (element) {
		element.preventDefault();

		var associate_button = $(this);

		associate_button.attr("disabled", true);

		var data = {
			action: 'ubs_associate_additional_alko_id',
			original_alko_id: $(this).data('post-id'),
			additional_alko_id: $('#alko_id').val(),
			ubs_nonce: $('#ubs_search_nonce').val(),
		};

		$.post(ajaxurl, data, function (response) {
			associate_button.html('✓');
		});

		return false;
	});
});
