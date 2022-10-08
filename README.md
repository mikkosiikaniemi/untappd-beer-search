# Untappd Beer Search for WordPress

A WordPress plugin for searching [Untappd](https://untappd.com) for beer info via [Untappd API](https://untappd.com/api/), and saving the information into a WordPress custom post type.

## Features, ideas & to-do

- [x] Settings page for Untappd API credentials
- [x] Admin page for search & save
- [x] Custom post type for beers
- [x] Localisation support
- [x] AJAX-based requests without page reload
- [x] Import data from [Alko](https://www.alko.fi/valikoimat-ja-hinnasto/hinnasto) (as CSV) and match the corresponding Untappd beer ID
- [x] Ability to re-fetch beer info to update an existing beer 
- [ ] Sort admin listing by rating, ABV, etc.
- [ ] Beer filter by type, ABV, brewery etc.
- [ ] Front-end UI, not only on admin side?
- [ ] Taxonomies for beer type, ABV, country, brewery etc.
- [ ] Search for multiple beers at once (textarea input instead of text)
- [ ] Replace jQuery with native JavaScript
- [ ] Process the whole Alko catalog in batches, trying to match beers as automatically as possible, skipping already imported beers 
