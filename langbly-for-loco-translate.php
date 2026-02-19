<?php
/**
 * Plugin Name: Langbly for Loco Translate
 * Plugin URI: https://langbly.com
 * Description: AI-powered translations for Loco Translate — a drop-in Google Translate replacement, 5x cheaper with better quality.
 * Author: Langbly
 * Author URI: https://langbly.com
 * Version: 1.0.2
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Requires Plugins: loco-translate
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: langbly-for-loco-translate
 */

if ( ! defined( 'ABSPATH' ) || ! is_admin() ) {
	return;
}

/**
 * Register Langbly as a translation provider in Loco Translate.
 *
 * The API key is read from wp-config.php via:
 *   define( 'LANGBLY_API_KEY', 'your-api-key' );
 *
 * @param array $apis Existing provider list.
 * @return array Modified provider list.
 */
function langbly_loco_register_provider( array $apis ) {
	$apis[] = array(
		'id'   => 'langbly',
		'key'  => function_exists( 'loco_constant' ) ? loco_constant( 'LANGBLY_API_KEY' ) : '',
		'name' => 'Langbly',
		'url'  => 'https://langbly.com',
	);
	return $apis;
}
add_filter( 'loco_api_providers', 'langbly_loco_register_provider', 10, 1 );

/**
 * Load the translator module when Loco performs an AJAX translation request.
 */
function langbly_loco_ajax_init() {
	require_once __DIR__ . '/translator.php';
	add_filter( 'loco_api_translate_langbly', 'langbly_loco_translate_batch', 0, 4 );
}
add_action( 'loco_api_ajax', 'langbly_loco_ajax_init', 0, 0 );
