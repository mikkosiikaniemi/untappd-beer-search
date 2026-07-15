// Configure local WordPress base URL.
const UBS_WORDPRESS_BASE_URL = 'https://untappd.local';
const UBS_REQUEST_SOURCE = 'extension';

// Cache ratings by Alko ID for this page lifetime.
const ubsRatingCache = new Map();
let ubsBulkRatingsPromise = null;

function ubsLog(message) {
	console.log('[UBS Extension] ' + message);
}

function ubsWarn(message) {
	console.warn('[UBS Extension] ' + message);
}

// Add Untappd rating CSS styles.
const style = document.createElement('style');
style.innerHTML = `
.untappd-rating {
	position: absolute;
	top: .6rem;
	right: 3rem;
	z-index: 4;
	pointer-events: none;
	background-color: #fff9d9;
	border: 1px solid #ffcc00;
	border-radius: .35rem;
	color: #222;
	font-family: LocatorWebMedium, sans-serif;
	font-size: 1rem;
	font-weight: 700;
	line-height: 1;
	padding: .35rem .5rem;
	box-shadow: 0 1px 2px rgba(0, 0, 0, .12);
}
`;
document.head.appendChild(style);

/**
 * Parse Alko product ID from card element.
 *
 * @param {Element} card Product card element.
 * @returns {string|null}
 */
function getProductIdFromCard(card) {
	const idAttr = card.getAttribute('id');
	if (idAttr && idAttr.indexOf('product-') === 0) {
		return idAttr.replace('product-', '');
	}

	const link = card.querySelector('a[href*="/tuotteet/"]');
	if (!link) {
		return null;
	}

	const href = link.getAttribute('href') || '';
	const match = href.match(/\/tuotteet\/(\d+)\//);
	return match ? match[1] : null;
}

/**
 * Inject rating badge to card if not already present.
 *
 * @param {Element} card Product card element.
 * @param {number|string} ratingValue Rating value.
 */
function addRatingToCard(card, ratingValue) {
	if (card.querySelector('.untappd-rating')) {
		return;
	}

	const rating = Number(ratingValue);
	if (!Number.isFinite(rating)) {
		return;
	}

	const badge = document.createElement('div');
	badge.classList.add('untappd-rating');
	badge.textContent = '⭐' + rating.toFixed(2);
	card.appendChild(badge);
}

/**
 * Fetch all ratings once and store to cache.
 *
 * @returns {Promise<Map<string, number>>}
 */
function fetchBulkRatings() {
	if (ubsBulkRatingsPromise) {
		ubsLog('Using cached bulk ratings promise.');
		return ubsBulkRatingsPromise;
	}

	const bulkUrl = UBS_WORDPRESS_BASE_URL + '/wp-json/untappd-beer-search/v1/ratings?source=' + UBS_REQUEST_SOURCE;
	ubsLog('Requesting bulk ratings: ' + bulkUrl);

	ubsBulkRatingsPromise = fetch(bulkUrl)
		.then((response) => {
			ubsLog('Bulk ratings response status: ' + response.status);
			if (!response.ok) {
				throw new Error('Failed to fetch ratings map');
			}
			return response.json();
		})
		.then((data) => {
			const map = new Map();
			Object.keys(data || {}).forEach((key) => {
				const value = Number(data[key]);
				if (Number.isFinite(value)) {
					map.set(String(key), value);
				}
			});
			ubsLog('Bulk ratings loaded. Count: ' + map.size);
			return map;
		})
		.catch((error) => {
			ubsLog('Bulk ratings request failed: ' + error.message);
			return new Map();
		});

	return ubsBulkRatingsPromise;
}

/**
 * Fetch single rating for Alko ID.
 *
 * @param {string} productId Alko ID.
 * @returns {Promise<number|null>}
 */
async function fetchSingleRating(productId) {
	if (ubsRatingCache.has(productId)) {
		ubsLog('Single rating cache hit in extension for Alko ID: ' + productId);
		return ubsRatingCache.get(productId);
	}

	try {
		const singleUrl = UBS_WORDPRESS_BASE_URL + '/wp-json/untappd-beer-search/v1/ratings/' + productId + '?source=' + UBS_REQUEST_SOURCE;
		ubsLog('Requesting single rating: ' + singleUrl);

		const response = await fetch(singleUrl);
		ubsLog('Single rating response status for ' + productId + ': ' + response.status);
		if (!response.ok) {
			ubsRatingCache.set(productId, null);
			return null;
		}

		const data = await response.json();
		const rating = Number(data && data.rating);
		if (Number.isFinite(rating)) {
			ubsLog('Single rating parsed for ' + productId + ': ' + rating.toFixed(2));
			ubsRatingCache.set(productId, rating);
			return rating;
		}
	} catch (error) {
		ubsLog('Single rating request failed for ' + productId + ': ' + error.message);
	}

	ubsRatingCache.set(productId, null);
	return null;
}

/**
 * Render ratings for all visible cards.
 *
 * @returns {Promise<void>}
 */
async function renderRatings() {
	const cards = Array.from(document.querySelectorAll('article[id^="product-"]'));
	if (0 === cards.length) {
		ubsLog('No product cards found on page yet.');
		return;
	}

	ubsLog('Rendering ratings for cards: ' + cards.length);

	const ratingsMap = await fetchBulkRatings();

	for (const card of cards) {
		const productId = getProductIdFromCard(card);
		if (!productId) {
			continue;
		}

		// Prefer bulk map, then fallback to single endpoint for missing keys.
		if (ratingsMap.has(productId)) {
			addRatingToCard(card, ratingsMap.get(productId));
			ubsRatingCache.set(productId, ratingsMap.get(productId));
			continue;
		}

		const rating = await fetchSingleRating(productId);
		if (null !== rating) {
			addRatingToCard(card, rating);
		}
	}
}

let renderTimeout = null;
const observer = new MutationObserver(() => {
	if (renderTimeout) {
		clearTimeout(renderTimeout);
	}
	renderTimeout = setTimeout(() => {
		renderRatings();
	}, 150);
});

observer.observe(document.body, { childList: true, subtree: true });
if ('https:' === window.location.protocol && 0 === UBS_WORDPRESS_BASE_URL.indexOf('http://')) {
	ubsWarn('Mixed-content risk: HTTPS Alko page cannot call HTTP WordPress URL: ' + UBS_WORDPRESS_BASE_URL);
}

ubsWarn('Content script loaded. If no further logs appear, check extension reload and Console log level filters.');
ubsLog('Initialized on ' + window.location.href);
renderRatings();
