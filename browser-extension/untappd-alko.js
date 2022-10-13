// Add Untappd rating CSS styles.
var style = document.createElement('style');
style.innerHTML = `
.untappd-rating {
	background-color: #ffffff;
	border: 2px solid #ffcc00;
	border-radius: .25em;
	font-family: LocatorWebMedium;
	font-size: 200%;
	padding: .5rem;
	position: absolute;
	transform: translateX(65%)
}
`;
document.head.appendChild(style);

// Make AJAX request to local REST API to get beer rating by Alko product ID.
const xhr = new XMLHttpRequest();
const url = 'https://demo.local/wp-json/wp/v2/beer';
xhr.open('GET', url, true);
xhr.setRequestHeader("Content-type", "application/json");
xhr.send(null);

xhr.onreadystatechange = (e) => {

	// If local backed responds with HTTP 200, we get something.
	if (200 === xhr.status) {
		// Parse JSON response for beer array.
		const beers = JSON.parse(xhr.responseText);

		// Select all Alko product cards on page.
		const product_cards = document.querySelectorAll('.mini-card-wrap');

		// For each product cars, search for beer by Alko product ID.
		product_cards.forEach(function (card) {

			// Get the Alko product ID from product card.
			const product_id = card.querySelector('.product-data-container').getAttribute('data-alkoproduct');

			// Find corresponding beer (from array returned by REST API call).
			const beer = beers.find(x => x.id == product_id);

			// If beer found, inject rating.
			if (beer) {
				const product_image_wrap = card.querySelector('.mc-image');
				const rating = document.createElement("div");
				rating.classList.add("untappd-rating");

				const rating_score = beer.rating;
				let rating_score_two_digits = new Intl.NumberFormat('en-US', { minimumSignificantDigits: 3, maximumSignificantDigits: 3 }).format(rating_score);
				rating.textContent = '⭐' + rating_score_two_digits;
				product_image_wrap.appendChild(rating);
			}
		});
	}
}
