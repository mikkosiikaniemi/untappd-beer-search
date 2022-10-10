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

	// Injecr rating, just a random one for now.
	const product_image_wrap = card.querySelector('.mc-image');
	const rating = document.createElement("div");
	rating.classList.add("untappd-rating");
	rating.textContent = '⭐' + (Math.random() * (5 - 1) + 1).toFixed(2);
	product_image_wrap.appendChild(rating);

	/*
	if (product_id == '703795') {
		const xhr = new XMLHttpRequest();
		const url = 'https://demo.local/wp-json/wp/v2/search/?subtype=beer&search=' + encodeURI(name_without_suffixes);
		xhr.open('GET', url, true);
		xhr.setRequestHeader("Content-type", "application/json");
		xhr.send(null);

		xhr.onreadystatechange = (e) => {
			console.log(xhr.responseText)
		}
	}
	*/
});
