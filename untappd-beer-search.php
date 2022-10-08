<?php
/**
 * Untappd Beer Search
 *
 * @package           UBS
 * @author            Mikko Siikaniemi
 *
 * To update the localisation POT file, run the following in the plugin dir:
 * wp i18n make-pot . languages/untappd-beer-search.pot
 *
 * @wordpress-plugin
 * Plugin Name:       Untappd Beer Search
 * Plugin URI:        https://github.com/mikkosiikaniemi/untappd-beer-search
 * Description:       Search beers in Untappd, save info/ratings into custom post type. Requires Untappd API access.
 * Version:           1.0
 * Author:            Mikko Siikaniemi
 * Author URI:        https://mikrogramma.fi
 * Text Domain:       ubs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load dependencies.
require_once plugin_dir_path( __FILE__ ) . 'includes/untappd-beer-search-cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/untappd-beer-search-remote-requests.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/untappd-beer-search-page.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/untappd-beer-search-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/untappd-beer-search-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/untappd-beer-search-alko.php';
