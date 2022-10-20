jQuery(document).ready(function ($) {

	// Process search form submit action.
	$('#ubs-search').submit(function () {

		$("#ubs-search .spinner").addClass("is-active");
		$("#ubs-search [type=submit]").attr("disabled", true);
		$("#ubs-alko-populate").attr("disabled", true);

		var beer_name = $('#beer-name').val();
		var options = $("#beer-names option");
		var alko_id = null;

		// Loop thorugh the datalist options to find out the selected option.
		// Get the data attribute to find out Alko product ID.
		for (var i = 0; i < options.length; i++) {
			var option = options[i];

			if (option.innerText === beer_name) {
				alko_id = option.getAttribute('data-alko-id');
				break;
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
			$("#ubs-alko-populate").attr("disabled", false);
		});

		return false;
	});

	// Process save form submit action.
	$(document).on('submit', '#ubs-search-results', function (e) {

		$("#ubs-search-results .spinner").addClass("is-active");
		$("#ubs-search-results [type=submit]").attr("disabled", true);

		var data = {
			action: 'ubs_save_selected_results',
			beer_id: $('input[name="beer-id[]"]:checked').val(),
			alko_id: $('#alko_id').val(),
			ubs_nonce: $('#ubs_save_nonce').val(),
		};

		$('input[name="beer-id[]"]:checked').each(function (e) {
			id = $(this).attr("id");
			id = id.replace('beer-check-', '');
			$('#beer-save-' + id).html('<span class="spinner is-active"></span>');
		});

		$.post(ajaxurl, data, function (response) {

			var results = $.parseJSON(response);
			$.each(results, function (beer_id, status) {
				if ($.isNumeric(status)) {
					$('#beer-check-' + beer_id).prop("checked", true).attr("disabled", true);
					$('#beer-save-' + beer_id).html('☑️ ' + ' Rating: ' + Number(status).toFixed(2) );
				} else {
					$('#beer-save-' + beer_id).html(status);
				}
			});

			$("#ubs-search-results .spinner").removeClass("is-active");
		});

		return false;
	});

	// Check all checkboxes on search results.
	$(document).on('click', '#ubs-select-all', function (element) {
		element.preventDefault();
		$('input[name="beer-id[]"]').prop("checked", true);
	});

	// Clear all checkboxes on search results.
	$(document).on('click', '#ubs-select-none', function (element) {
		element.preventDefault();
		$('input[name="beer-id[]"]').prop("checked", false);
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
			$('#beer-name').val(response);
			$('#ubs-search').submit();
		});

		return false;
	});
});
