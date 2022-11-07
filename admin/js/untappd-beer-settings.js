jQuery(function ($) {

	// Initiate (re)fetching of Alko price sheet from Alko website.
	$(document).on('click', '#ubs-refetch-alko-prices', function (element) {
		element.preventDefault();

		$(".spinner").addClass("is-active");
		$("#ubs-refetch-alko-prices").prop("disabled", true);
		$("#ubs-price-sheet-message").hide();

		var data = {
			action: 'ubs_fetch_alko_price_sheet',
			ubs_nonce: $('#ubs_settings_nonce').val(),
		};

		$.post(ajaxurl, data, function (response) {
			$(".spinner").removeClass("is-active");
			$("#ubs-refetch-alko-prices").prop("disabled", false);
			if (response.success === true) {
				$("#ubs-price-sheet-fetched").html(response.data.sheet_updated);
			} else {
				$("#ubs-price-sheet-message").html(response.data[0].message);
			}
			$("#ubs-price-sheet-message").show();
		});

		return false;

	});

});
