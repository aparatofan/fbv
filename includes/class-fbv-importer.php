<?php
/**
 * CSV importer.
 *
 * Works both as a WP-CLI command (`wp fbv import`) and from the frontend admin
 * import dialog (via the REST API, which passes the raw CSV text).
 *
 * Accepted columns (detected from the header row, in any order):
 *   reference, text_pl, text_en, tags
 * If there is no recognised header, columns are read positionally as
 * reference, text_pl, tags.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports verses from a CSV.
 */
class FBV_Importer {

	/**
	 * Import verses from a CSV file (WP-CLI).
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Path to the CSV file. Defaults to the bundled data/initial-import.csv.
	 *
	 * [--skip-existing]
	 * : Skip verses whose reference already exists.
	 *
	 * ## EXAMPLES
	 *
	 *     wp fbv import
	 *     wp fbv import data/initial-import.csv --skip-existing
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function import( $args, $assoc_args ) {
		$file = isset( $args[0] ) ? $args[0] : FBV_PLUGIN_DIR . 'data/initial-import.csv';

		$result = self::run_import(
			$file,
			array(
				'skip_existing' => isset( $assoc_args['skip-existing'] ),
				'logger'        => function ( $level, $message ) {
					if ( ! class_exists( 'WP_CLI' ) ) {
						return;
					}
					if ( 'error' === $level ) {
						WP_CLI::warning( $message );
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
	 * Import from a CSV file path.
	 *
	 * @param string $file Path to CSV.
	 * @param array  $opts Options (skip_existing, logger).
	 * @return array|WP_Error
	 */
	public static function run_import( $file, array $opts = array() ) {
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			/* translators: %s: file path. */
			return new WP_Error( 'fbv_file_missing', sprintf( __( 'CSV file not found or unreadable: %s', 'fbv' ), $file ) );
		}

		$handle = fopen( $file, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'fbv_file_open', __( 'Could not open CSV file.', 'fbv' ) );
		}

		$result = self::import_stream( $handle, $opts );
		fclose( $handle );
		return $result;
	}

	/**
	 * Import from raw CSV text (used by the admin import dialog).
	 *
	 * @param string $csv  CSV content.
	 * @param array  $opts Options (skip_existing, logger).
	 * @return array|WP_Error
	 */
	public static function run_import_string( $csv, array $opts = array() ) {
		$csv = (string) $csv;
		if ( '' === trim( $csv ) ) {
			return new WP_Error( 'fbv_empty_csv', __( 'The CSV is empty.', 'fbv' ) );
		}

		// A real stream handles quoted fields with embedded newlines correctly.
		$handle = fopen( 'php://temp', 'r+' );
		if ( ! $handle ) {
			return new WP_Error( 'fbv_stream', __( 'Could not open a temporary stream.', 'fbv' ) );
		}
		fwrite( $handle, $csv );
		rewind( $handle );

		$result = self::import_stream( $handle, $opts );
		fclose( $handle );
		return $result;
	}

	/**
	 * Core import loop over an open CSV stream.
	 *
	 * @param resource $handle Open readable stream positioned at the start.
	 * @param array    $opts   Options (skip_existing, logger).
	 * @return array {
	 *     @type int $imported
	 *     @type int $skipped
	 *     @type int $failed
	 * }
	 */
	private static function import_stream( $handle, array $opts ) {
		$skip_existing = ! empty( $opts['skip_existing'] );
		$logger        = isset( $opts['logger'] ) && is_callable( $opts['logger'] ) ? $opts['logger'] : function () {};

		$imported = 0;
		$skipped  = 0;
		$failed   = 0;
		$row_num  = 0;
		$map      = null; // Column index map once a header is detected.

		while ( false !== ( $row = fgetcsv( $handle, 0, ',' ) ) ) {
			$row_num++;

			// Skip fully blank lines.
			if ( null === $row || ( 1 === count( $row ) && null === $row[0] ) ) {
				continue;
			}
			if ( empty( array_filter( $row, static function ( $v ) { return '' !== trim( (string) $v ); } ) ) ) {
				continue;
			}

			// First non-empty row: detect a header, otherwise assume positional.
			if ( null === $map ) {
				$map = self::detect_columns( $row );
				if ( false !== $map ) {
					// This row was a header; move on to data rows.
					continue;
				}
				// No header: default positional layout.
				$map = array( 'reference' => 0, 'text_pl' => 1, 'text_en' => null, 'tags' => 2 );
			}

			$reference = self::cell( $row, $map, 'reference' );
			$text_pl   = self::cell( $row, $map, 'text_pl' );
			$text_en   = self::cell( $row, $map, 'text_en' );
			$tags_raw  = self::cell( $row, $map, 'tags' );

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
			call_user_func( $logger, 'success', sprintf( 'Row %d: imported %s', $row_num, $parsed['reference'] ) );
		}

		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'failed'   => $failed,
		);
	}

	/**
	 * Detect column positions from a header row.
	 *
	 * @param array $row First CSV row.
	 * @return array|false Map of field => index, or false if not a header.
	 */
	private static function detect_columns( array $row ) {
		$aliases = array(
			'reference' => array( 'reference', 'ref', 'adres', 'werset' ),
			'text_pl'   => array( 'text_pl', 'pl', 'polish', 'polski', 'tekst_pl', 'tresc' ),
			'text_en'   => array( 'text_en', 'en', 'english', 'angielski', 'tekst_en' ),
			'tags'      => array( 'tags', 'tagi', 'tag' ),
		);

		$map   = array( 'reference' => null, 'text_pl' => null, 'text_en' => null, 'tags' => null );
		$found = false;

		foreach ( $row as $index => $value ) {
			$key = strtolower( trim( (string) $value ) );
			foreach ( $aliases as $field => $names ) {
				if ( in_array( $key, $names, true ) ) {
					$map[ $field ] = $index;
					$found         = true;
				}
			}
		}

		// Only treat the row as a header if at least the reference column matched.
		return ( $found && null !== $map['reference'] ) ? $map : false;
	}

	/**
	 * Read a mapped cell value from a row.
	 *
	 * @param array  $row   CSV row.
	 * @param array  $map   Column map.
	 * @param string $field Field name.
	 * @return string
	 */
	private static function cell( array $row, array $map, $field ) {
		if ( ! isset( $map[ $field ] ) || null === $map[ $field ] ) {
			return '';
		}
		$index = $map[ $field ];
		return isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
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
