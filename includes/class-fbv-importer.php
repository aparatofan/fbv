<?php
/**
 * CSV importer (WP-CLI command).
 *
 * Usage:
 *   wp fbv import [<file>] [--fetch-en] [--skip-existing]
 *
 * Defaults to data/initial-import.csv when no file is given.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports verses from a CSV (reference, text_pl, tags).
 */
class FBV_Importer {

	/**
	 * Import verses from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Path to the CSV file. Defaults to the bundled data/initial-import.csv.
	 *
	 * [--fetch-en]
	 * : Attempt to auto-fetch the English NWT text from jw.org for each verse.
	 *
	 * [--skip-existing]
	 * : Skip verses whose reference already exists.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fbv import
	 *     wp fbv import data/initial-import.csv --fetch-en
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function import( $args, $assoc_args ) {
		$file = isset( $args[0] ) ? $args[0] : FBV_PLUGIN_DIR . 'data/initial-import.csv';

		$result = self::run_import(
			$file,
			array(
				'fetch_en'      => isset( $assoc_args['fetch-en'] ),
				'skip_existing' => isset( $assoc_args['skip-existing'] ),
				'logger'        => function ( $level, $message ) {
					if ( ! class_exists( 'WP_CLI' ) ) {
						return;
					}
					if ( 'error' === $level ) {
						WP_CLI::warning( $message );
					} elseif ( 'success' === $level ) {
						WP_CLI::log( $message );
					} else {
						WP_CLI::log( $message );
					}
				},
			)
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success(
			sprintf(
				'Imported %d verse(s), skipped %d, failed %d.',
				$result['imported'],
				$result['skipped'],
				$result['failed']
			)
		);
	}

	/**
	 * Core import routine, reusable outside WP-CLI.
	 *
	 * @param string $file Path to CSV.
	 * @param array  $opts {
	 *     @type bool     $fetch_en      Auto-fetch English text.
	 *     @type bool     $skip_existing Skip duplicates.
	 *     @type callable $logger        function( $level, $message ).
	 * }
	 * @return array|WP_Error Counts on success.
	 */
	public static function run_import( $file, array $opts = array() ) {
		$fetch_en      = ! empty( $opts['fetch_en'] );
		$skip_existing = ! empty( $opts['skip_existing'] );
		$logger        = isset( $opts['logger'] ) && is_callable( $opts['logger'] ) ? $opts['logger'] : function () {};

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			/* translators: %s: file path. */
			return new WP_Error( 'fbv_file_missing', sprintf( __( 'CSV file not found or unreadable: %s', 'fbv' ), $file ) );
		}

		$handle = fopen( $file, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'fbv_file_open', __( 'Could not open CSV file.', 'fbv' ) );
		}

		$imported = 0;
		$skipped  = 0;
		$failed   = 0;
		$row_num  = 0;
		$fetcher  = $fetch_en ? new FBV_Fetcher() : null;

		while ( false !== ( $row = fgetcsv( $handle, 0, ',' ) ) ) {
			$row_num++;

			// Skip the header row.
			if ( 1 === $row_num && isset( $row[0] ) && 'reference' === strtolower( trim( $row[0] ) ) ) {
				continue;
			}
			// Skip blank lines.
			if ( empty( array_filter( $row, 'strlen' ) ) ) {
				continue;
			}

			$reference = isset( $row[0] ) ? trim( $row[0] ) : '';
			$text_pl   = isset( $row[1] ) ? trim( $row[1] ) : '';
			$tags_raw  = isset( $row[2] ) ? trim( $row[2] ) : '';

			if ( '' === $reference ) {
				$failed++;
				call_user_func( $logger, 'error', sprintf( 'Row %d: empty reference, skipped.', $row_num ) );
				continue;
			}

			$parsed = FBV_Parser::parse( $reference );
			if ( is_wp_error( $parsed ) ) {
				$failed++;
				call_user_func( $logger, 'error', sprintf( 'Row %d (%s): %s', $row_num, $reference, $parsed->get_error_message() ) );
				continue;
			}

			if ( $skip_existing && self::reference_exists( $parsed['reference'] ) ) {
				$skipped++;
				call_user_func( $logger, 'log', sprintf( 'Row %d (%s): already exists, skipped.', $row_num, $parsed['reference'] ) );
				continue;
			}

			$text_en = '';
			if ( $fetcher ) {
				$fetched = $fetcher->fetch( $parsed );
				$text_en = $fetched['text_en'];
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => FBV_Post_Type::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => $parsed['reference'],
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				$failed++;
				call_user_func( $logger, 'error', sprintf( 'Row %d (%s): %s', $row_num, $reference, $post_id->get_error_message() ) );
				continue;
			}

			update_post_meta( $post_id, '_fbv_reference', $parsed['reference'] );
			update_post_meta( $post_id, '_fbv_book_number', (int) $parsed['book_number'] );
			update_post_meta( $post_id, '_fbv_chapter', (int) $parsed['chapter'] );
			update_post_meta( $post_id, '_fbv_verse_start', (int) $parsed['verse_start'] );
			update_post_meta( $post_id, '_fbv_verse_end', (int) $parsed['verse_end'] );
			update_post_meta( $post_id, '_fbv_text_pl', wp_kses_post( $text_pl ) );
			update_post_meta( $post_id, '_fbv_text_en', wp_kses_post( $text_en ) );
			update_post_meta( $post_id, '_fbv_date_added', current_time( 'mysql' ) );

			$tags = self::parse_tags( $tags_raw );
			if ( ! empty( $tags ) ) {
				wp_set_object_terms( $post_id, $tags, FBV_Post_Type::TAXONOMY, false );
			}

			$imported++;
			call_user_func(
				$logger,
				'success',
				sprintf( 'Row %d: imported %s%s', $row_num, $parsed['reference'], ( '' !== $text_en ? ' (EN fetched)' : '' ) )
			);
		}

		fclose( $handle );

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'failed'   => $failed,
		);
	}

	/**
	 * Whether a verse with the given canonical reference already exists.
	 *
	 * @param string $reference Canonical reference.
	 * @return bool
	 */
	private static function reference_exists( $reference ) {
		$existing = get_posts(
			array(
				'post_type'      => FBV_Post_Type::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_fbv_reference',
				'meta_value'     => $reference,
			)
		);
		return ! empty( $existing );
	}

	/**
	 * Split a comma-separated tag string into clean names.
	 *
	 * @param string $raw Raw tags.
	 * @return string[]
	 */
	private static function parse_tags( $raw ) {
		$clean = array();
		foreach ( explode( ',', (string) $raw ) as $tag ) {
			$tag = sanitize_text_field( trim( $tag ) );
			if ( '' !== $tag ) {
				$clean[] = $tag;
			}
		}
		return array_values( array_unique( $clean ) );
	}
}
