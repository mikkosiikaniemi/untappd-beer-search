jQuery(function($) {

	// Process search form submit action.
	$('#ubs-search').on( 'submit', function ( event, alko_id ) {

		var populate_button_orig_state = $("#ubs-alko-populate").attr("disabled");

		$("#ubs-search .spinner").addClass("is-active");
		$("#ubs-search [type=submit]").attr("disabled", true);
		$("#ubs-alko-populate").attr("disabled", true);

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

		var data = {
			action: 'ubs_get_search_results',
			beer_name: beer_name,
			ubs_nonce: $('#ubs_search_nonce').val(),
			alko_id: alko_id
		};

		$.post(ajaxurl, data, function (response) {
			$('#ubs-untappd-response').html(response);
			$("#ubs-search .spinner").removeClass("is-active");
			$("#ubs-search [type=submit]").attr("disabled", false);
			$("#ubs-alko-populate").attr("disabled", populate_button_orig_state);
		});

		return false;
	});

	// Process save form submit action.
	$(document).on('submit', '#ubs-search-results', function (element) {

		$("#ubs-search-results .spinner").addClass("is-active");
		$("#ubs-search-results [type=submit]").attr("disabled", true);

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

			var results = $.parseJSON(response);
			$.each(results, function (beer_id, beer_data) {
				if ($.isNumeric(beer_data.status)) {
					$('#beer-check-' + beer_id).prop("checked", true).attr("disabled", true);
					$('#beer-save-' + beer_id).html('☑️ ' + ' Rating: ' + Number(beer_data.status).toFixed(2));
				} else {
					$('#beer-save-' + beer_id).html(beer_data.status);
				}
				$("#ubs-limit-remaining").html(beer_data.limit_remaining);
			});

			$("#ubs-search-results .spinner").removeClass("is-active");
		});

		return false;
	});

	// Populate search field with first Alko product not yet saved.
	$(document).on('click', '#ubs-alko-populate', function (element) {
		element.preventDefault();

		$("#ubs-search .spinner").addClass("is-active");
		$("#ubs-alko-populate").attr("disabled", true);
		$("#ubs-search [type=submit]").attr("disabled", true);

		var data = {
			action: 'ubs_populate_alko_product',
			ubs_nonce: $('#ubs_search_nonce').val(),
		};

		$.post(ajaxurl, data, function (response) {
			if (response.success === true) {
				$('#ubs-beers-left-to-save').html(response.data.beers_left_to_save);
				$('#beer-name').val(response.data.beer_name);
			}

			$('#ubs-search').trigger( 'submit', [ response.data.alko_id ] );
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
