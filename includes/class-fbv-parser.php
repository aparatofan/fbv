<?php
/**
 * Reference parser: turns a human verse reference into structured data.
 *
 * Examples handled:
 *   "Psalm 147:3"          -> book 19, ch 147, v 3-3
 *   "Przysłów 17:17"       -> book 20, ch 17, v 17-17
 *   "2 Koryntian 1:3, 4"   -> book 47, ch 1, v 3-4
 *   "Mateusza 6:19–20"     -> book 40, ch 6, v 19-20 (en dash)
 *   "1 Korytian 13:4-8"    -> book 46, ch 13, v 4-8 (alias spelling)
 *   "Psalm 15:5b"          -> book 19, ch 15, v 5-5 (letter suffix dropped)
 *   "Proverbs 17:17"       -> English book name accepted
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless reference parser.
 */
class FBV_Parser {

	/**
	 * Parse a reference string.
	 *
	 * @param string $reference Raw reference.
	 * @return array|WP_Error {
	 *     @type int    $book_number  Canonical book number (1-66).
	 *     @type int    $chapter      Chapter number.
	 *     @type int    $verse_start  First verse.
	 *     @type int    $verse_end    Last verse.
	 *     @type string $book_name_pl Polish book name.
	 *     @type string $book_name_en English book name.
	 *     @type string $reference    Canonical Polish reference string.
	 * }
	 */
	public static function parse( $reference ) {
		$raw = trim( (string) $reference );
		if ( '' === $raw ) {
			return new WP_Error( 'fbv_empty_reference', __( 'Verse reference is empty.', 'fbv' ) );
		}

		// Normalise dash variants to a plain hyphen.
		$normalized = str_replace( array( '–', '—', '−' ), '-', $raw );

		// Split "Book Chapter:Verses". The book name is everything before the
		// final "<number>:" group, so book names that start with a digit
		// (e.g. "1 Koryntian") are handled correctly.
		if ( ! preg_match( '/^(.+?)\s+(\d+)\s*:\s*(.+)$/u', $normalized, $m ) ) {
			return new WP_Error(
				'fbv_unparseable_reference',
				/* translators: %s: the reference the user typed. */
				sprintf( __( 'Could not parse reference "%s". Expected something like "Psalm 147:3".', 'fbv' ), $raw )
			);
		}

		$book_part    = trim( $m[1] );
		$chapter      = (int) $m[2];
		$verses_part  = trim( $m[3] );

		$book_number = FBV_Bible_Books::find_number( $book_part );
		if ( null === $book_number ) {
			return new WP_Error(
				'fbv_unknown_book',
				/* translators: %s: book name the user typed. */
				sprintf( __( 'Unknown Bible book: "%s".', 'fbv' ), $book_part )
			);
		}

		// Collect every verse number mentioned (handles "4-8", "3, 4", "5b").
		if ( ! preg_match_all( '/\d+/', $verses_part, $vm ) || empty( $vm[0] ) ) {
			return new WP_Error(
				'fbv_no_verse_number',
				/* translators: %s: the reference the user typed. */
				sprintf( __( 'No verse number found in "%s".', 'fbv' ), $raw )
			);
		}

		$numbers     = array_map( 'intval', $vm[0] );
		$verse_start = min( $numbers );
		$verse_end   = max( $numbers );

		$book = FBV_Bible_Books::get( $book_number );

		return array(
			'book_number'  => $book_number,
			'chapter'      => $chapter,
			'verse_start'  => $verse_start,
			'verse_end'    => $verse_end,
			'book_name_pl' => $book['name_pl'],
			'book_name_en' => $book['name_en'],
			'reference'    => self::build_reference( $book_number, $chapter, $verse_start, $verse_end, 'pl' ),
		);
	}

	/**
	 * Build a canonical reference string in the given language.
	 *
	 * @param int    $book_number Book number.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start First verse.
	 * @param int    $verse_end   Last verse.
	 * @param string $lang        'pl' or 'en'.
	 * @return string
	 */
	public static function build_reference( $book_number, $chapter, $verse_start, $verse_end, $lang = 'pl' ) {
		$name  = FBV_Bible_Books::name( $book_number, $lang );
		$verse = (int) $verse_start;
		if ( (int) $verse_end > (int) $verse_start ) {
			$verse .= '-' . (int) $verse_end;
		}
		return trim( $name . ' ' . (int) $chapter . ':' . $verse );
	}
}
