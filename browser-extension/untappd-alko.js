// Select all Alko product cards.
const product_cards = document.querySelectorAll('.mini-card-wrap');

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

// For each product card, inject rating.
product_cards.forEach(function (card) {
	const product_name = card.querySelector('.mc-name');
	let product_id = card.querySelector('.product-data-container').getAttribute('data-alkoproduct');

	const suffixes_to_remove = ['tölkki'];
	name_without_suffixes = '';

	// Remove suffixes from beer name.
	suffixes_to_remove.forEach(function (suffix) {
		name_without_suffixes = product_name.textContent.replace(suffix, '');
		name_without_suffixes = name_without_suffixes.trim();
	});

	// Make AJAX request to local backend to get beer rating by Alko product ID.
	const xhr = new XMLHttpRequest();
	const url = 'https://demo.local/wp-json/wp/v2/beer/' + product_id;
	xhr.open('GET', url, true);
	xhr.setRequestHeader("Content-type", "application/json");
	xhr.send(null);

	xhr.onreadystatechange = (e) => {

		// If local backed responds with HTTP 200, we get rating.
		if (200 === xhr.status) {
			// Inject rating.
			const product_image_wrap = card.querySelector('.mc-image');
			const rating = document.createElement("div");
			rating.classList.add("untappd-rating");

			const beer_data = JSON.parse(xhr.responseText);
			const rating_score = beer_data.rating;
			let rating_score_two_digits = new Intl.NumberFormat('en-US', { minimumSignificantDigits: 3, maximumSignificantDigits: 3 }).format(rating_score);
			rating.textContent = '⭐' + rating_score_two_digits;
			product_image_wrap.appendChild(rating);
		}
	}
});
