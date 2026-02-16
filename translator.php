<?php
/**
 * Langbly translation handler for Loco Translate.
 *
 * Processes batch translation requests by calling the Langbly API,
 * which is Google Translate v2 compatible.
 *
 * @package Langbly_For_Loco_Translate
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Langbly API base URL.
 */
if ( ! defined( 'LANGBLY_API_URL' ) ) {
	define( 'LANGBLY_API_URL', 'https://api.langbly.com/language/translate/v2' );
}

/**
 * Maximum number of strings per API request.
 */
if ( ! defined( 'LANGBLY_MAX_ITEMS' ) ) {
	define( 'LANGBLY_MAX_ITEMS', 50 );
}

/**
 * Maximum total characters per API request.
 */
if ( ! defined( 'LANGBLY_MAX_CHARS' ) ) {
	define( 'LANGBLY_MAX_CHARS', 10000 );
}

/**
 * Process a batch of translation requests from Loco Translate.
 *
 * @param string[]      $targets Empty array to fill with translated strings.
 * @param array[]       $items   Array of source items, each with 'source' key.
 * @param Loco_Locale   $locale  Target locale object.
 * @param array         $config  Provider configuration including API key.
 * @return string[] Translated strings keyed by index.
 * @throws Loco_error_Exception On API errors.
 */
function langbly_loco_translate_batch( $targets, array $items, Loco_Locale $locale, array $config = array() ) {
	// Get API key from config (set via loco_constant in wp-config.php).
	$api_key = isset( $config['key'] ) ? $config['key'] : '';
	if ( empty( $api_key ) ) {
		throw new Loco_error_Exception(
			__( 'Langbly API key not configured. Please define LANGBLY_API_KEY in your wp-config.php file.', 'langbly-for-loco-translate' )
		);
	}

	// Extract source texts from items.
	$source_texts = array();
	foreach ( $items as $i => $item ) {
		$source_texts[ $i ] = isset( $item['source'] ) ? $item['source'] : '';
	}

	// Determine target language code from Loco_Locale.
	$target_lang = langbly_loco_map_locale( $locale );

	// Determine source language code.
	$source_lang = 'en';
	if ( class_exists( 'Loco_mvc_PostParams' ) ) {
		$params = Loco_mvc_PostParams::get();
		if ( isset( $params['source'] ) && is_string( $params['source'] ) && '' !== $params['source'] ) {
			$source_locale = Loco_Locale::parse( $params['source'] );
			if ( $source_locale instanceof Loco_Locale ) {
				$source_lang = langbly_loco_map_locale( $source_locale );
			}
		}
	}

	// Split into chunks respecting API limits.
	$chunks = langbly_loco_chunk_strings( $source_texts );

	foreach ( $chunks as $chunk ) {
		$indices = array_keys( $chunk );
		$texts   = array_values( $chunk );

		// Call Langbly API.
		$response = wp_remote_post(
			LANGBLY_API_URL,
			array(
				'method'  => 'POST',
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
					'X-API-Key'    => $api_key,
					'User-Agent'   => 'langbly-loco-translate/1.0.0',
				),
				'body'    => wp_json_encode(
					array(
						'q'      => $texts,
						'target' => $target_lang,
						'source' => $source_lang,
						'format' => 'text',
					)
				),
			)
		);

		// Handle network errors.
		if ( is_wp_error( $response ) ) {
			throw new Loco_error_Exception(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Langbly API request failed: %s', 'langbly-for-loco-translate' ),
					esc_html( $response->get_error_message() )
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		// Handle HTTP errors.
		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = 'Unknown error';
			if ( isset( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			}

			if ( 401 === $status_code ) {
				throw new Loco_error_Exception(
					esc_html__( 'Langbly API key is invalid. Check your LANGBLY_API_KEY in wp-config.php', 'langbly-for-loco-translate' )
				);
			}

			if ( 429 === $status_code ) {
				throw new Loco_error_Exception(
					esc_html__( 'Langbly API rate limit exceeded. Please try again in a moment.', 'langbly-for-loco-translate' )
				);
			}

			throw new Loco_error_Exception(
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					esc_html__( 'Langbly API error (%1$d): %2$s', 'langbly-for-loco-translate' ),
					$status_code,
					esc_html( $error_message )
				)
			);
		}

		// Parse successful response.
		if ( ! isset( $data['data']['translations'] ) || ! is_array( $data['data']['translations'] ) ) {
			throw new Loco_error_Exception(
				esc_html__( 'Unexpected response format from Langbly API.', 'langbly-for-loco-translate' )
			);
		}

		$translations = $data['data']['translations'];

		foreach ( $indices as $j => $original_index ) {
			if ( isset( $translations[ $j ]['translatedText'] ) ) {
				$targets[ $original_index ] = $translations[ $j ]['translatedText'];
			}
		}
	}

	return $targets;
}

/**
 * Split an array of strings into chunks that respect Langbly API limits.
 *
 * Each chunk has at most LANGBLY_MAX_ITEMS items and at most
 * LANGBLY_MAX_CHARS total characters.
 *
 * @param array $strings Associative array of index => string.
 * @return array[] Array of chunks, each an associative array of index => string.
 */
function langbly_loco_chunk_strings( array $strings ) {
	$chunks        = array();
	$current_chunk = array();
	$current_chars = 0;
	$current_count = 0;

	foreach ( $strings as $index => $text ) {
		$text_len = mb_strlen( (string) $text, 'UTF-8' );

		// Start a new chunk if adding this string would exceed limits.
		if (
			$current_count > 0 &&
			( $current_count >= LANGBLY_MAX_ITEMS || $current_chars + $text_len > LANGBLY_MAX_CHARS )
		) {
			$chunks[]      = $current_chunk;
			$current_chunk = array();
			$current_chars = 0;
			$current_count = 0;
		}

		$current_chunk[ $index ] = $text;
		$current_chars          += $text_len;
		++$current_count;
	}

	if ( ! empty( $current_chunk ) ) {
		$chunks[] = $current_chunk;
	}

	return $chunks;
}

/**
 * Map a Loco_Locale object to an ISO 639-1 language code.
 *
 * Handles special cases like Chinese variants and Brazilian Portuguese.
 *
 * @param Loco_Locale $locale Loco locale object.
 * @return string ISO 639-1 language code.
 */
function langbly_loco_map_locale( Loco_Locale $locale ) {
	$tag = $locale->__toString();

	// Special cases that need the full tag or a specific mapping.
	$special_map = array(
		'zh-CN' => 'zh',
		'zh-TW' => 'zh-TW',
		'zh-HK' => 'zh-TW',
		'pt-BR' => 'pt',
		'pt-PT' => 'pt',
		'nb-NO' => 'no',
		'nn-NO' => 'no',
	);

	// Normalize tag separators (Loco may use underscores).
	$normalized = str_replace( '_', '-', $tag );

	if ( isset( $special_map[ $normalized ] ) ) {
		return $special_map[ $normalized ];
	}

	// Default: return first two characters (ISO 639-1).
	$lang = $locale->lang;
	if ( ! empty( $lang ) ) {
		return strtolower( $lang );
	}

	return strtolower( substr( $tag, 0, 2 ) );
}
