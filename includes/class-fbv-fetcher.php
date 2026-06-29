<?php
/**
 * jw.org / wol.jw.org verse fetcher.
 *
 * Retrieves chapter HTML server-side, caches it as a transient and extracts
 * the requested verse range. Both jw.org and wol.jw.org mark every verse with
 * an element id of the form `v{book}{chapter3}{verse3}` (e.g. Psalm 147:3 =>
 * `v19147003`), which is what we anchor on. Falls back gracefully and always
 * returns the study-edition links for manual copy-paste.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verse text fetcher.
 */
class FBV_Fetcher {

	const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Fetch a parsed verse range in both languages.
	 *
	 * @param array $parsed Output of FBV_Parser::parse().
	 * @return array {
	 *     @type string   $text_pl Extracted Polish text (may be empty).
	 *     @type string   $text_en Extracted English text (may be empty).
	 *     @type string   $url_pl  jw.org study-edition Polish chapter URL.
	 *     @type string   $url_en  jw.org English chapter URL.
	 *     @type string[] $errors  Human-readable per-language errors.
	 * }
	 */
	public function fetch( array $parsed ) {
		$book    = (int) $parsed['book_number'];
		$chapter = (int) $parsed['chapter'];
		$start   = (int) $parsed['verse_start'];
		$end     = (int) $parsed['verse_end'];

		$errors = array();

		$text_pl = $this->fetch_language( 'pl', $book, $chapter, $start, $end, $errors );
		$text_en = $this->fetch_language( 'en', $book, $chapter, $start, $end, $errors );

		return array(
			'text_pl' => $text_pl,
			'text_en' => $text_en,
			'url_pl'  => $this->study_url( 'pl', $book, $chapter ),
			'url_en'  => $this->study_url( 'en', $book, $chapter ),
			'errors'  => $errors,
		);
	}

	/**
	 * Fetch and extract a verse range for one language.
	 *
	 * @param string $lang    'pl' or 'en'.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @param int    $start   First verse.
	 * @param int    $end     Last verse.
	 * @param array  $errors  Error accumulator (by reference).
	 * @return string Extracted text, or empty string on failure.
	 */
	private function fetch_language( $lang, $book, $chapter, $start, $end, array &$errors ) {
		$html = $this->get_chapter_html( $lang, $book, $chapter, $errors );
		if ( '' === $html ) {
			return '';
		}

		$text = $this->extract_range( $html, $book, $chapter, $start, $end );
		if ( '' === $text ) {
			$errors[] = sprintf(
				/* translators: 1: language code, 2: verse reference. */
				__( 'Could not locate verse text for %1$s (%2$s). Please paste it manually.', 'fbv' ),
				strtoupper( $lang ),
				FBV_Parser::build_reference( $book, $chapter, $start, $end, $lang )
			);
		}

		return $text;
	}

	/**
	 * Get chapter HTML, using a transient cache and the wol.jw.org fallback.
	 *
	 * @param string $lang    'pl' or 'en'.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @param array  $errors  Error accumulator (by reference).
	 * @return string
	 */
	private function get_chapter_html( $lang, $book, $chapter, array &$errors ) {
		$cache_key = "fbv_chapter_{$lang}_{$book}_{$chapter}";
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		// Try the primary jw.org source first, then the WOL fallback.
		$sources = array(
			$this->jworg_url( $lang, $book, $chapter ),
			$this->wol_url( $lang, $book, $chapter ),
		);

		$html = '';
		foreach ( $sources as $url ) {
			$candidate = $this->remote_get( $url );
			if ( '' === $candidate ) {
				continue;
			}
			// Accept the page only if it actually contains verse markers;
			// JS-rendered shells will not.
			if ( false !== strpos( $candidate, 'id="v' . $book ) ) {
				$html = $candidate;
				break;
			}
			// Keep the last non-empty candidate as a weak fallback.
			if ( '' === $html ) {
				$html = $candidate;
			}
		}

		if ( '' === $html ) {
			$errors[] = sprintf(
				/* translators: %s: language code. */
				__( 'Network error fetching %s chapter from jw.org.', 'fbv' ),
				strtoupper( $lang )
			);
			return '';
		}

		set_transient( $cache_key, $html, self::CACHE_TTL );
		return $html;
	}

	/**
	 * Perform an HTTP GET and return the body, or '' on error.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function remote_get( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'user-agent'  => 'Mozilla/5.0 (compatible; FBV-WordPress-Plugin/' . FBV_VERSION . ')',
				'headers'     => array(
					'Accept'          => 'text/html,application/xhtml+xml',
					'Accept-Language' => 'pl,en;q=0.8',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Extract and concatenate a verse range from chapter HTML.
	 *
	 * @param string $html    Chapter HTML.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @param int    $start   First verse.
	 * @param int    $end     Last verse.
	 * @return string
	 */
	private function extract_range( $html, $book, $chapter, $start, $end ) {
		$parts = array();
		for ( $verse = $start; $verse <= $end; $verse++ ) {
			$one = $this->extract_one( $html, $book, $chapter, $verse );
			if ( '' !== $one ) {
				$parts[] = $one;
			}
		}
		return trim( implode( ' ', $parts ) );
	}

	/**
	 * Extract a single verse's text from chapter HTML.
	 *
	 * The verse content runs from its `id="vXXXX"` marker up to the next
	 * verse marker (or a small look-ahead window). Tags and footnote markers
	 * are stripped and the leading verse number removed.
	 *
	 * @param string $html    Chapter HTML.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @param int    $verse   Verse number.
	 * @return string
	 */
	private function extract_one( $html, $book, $chapter, $verse ) {
		$id = $book . sprintf( '%03d', $chapter ) . sprintf( '%03d', $verse );

		// Find the verse marker.
		if ( ! preg_match( '/id="v' . $id . '"/', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			return '';
		}
		$from = $m[0][1];

		// Content ends at the next verse marker, if any.
		$rest = substr( $html, $from + strlen( $m[0][0] ) );
		if ( preg_match( '/id="v\d+"/', $rest, $next, PREG_OFFSET_CAPTURE ) ) {
			$chunk = substr( $rest, 0, $next[0][1] );
		} else {
			$chunk = substr( $rest, 0, 4000 );
		}

		return $this->clean_text( $chunk, $verse );
	}

	/**
	 * Strip markup, footnote markers and the leading verse number.
	 *
	 * @param string $chunk Raw HTML fragment for one verse.
	 * @param int    $verse Verse number (removed if it leads the text).
	 * @return string
	 */
	private function clean_text( $chunk, $verse ) {
		// Drop superscript/footnote/cross-reference blocks entirely.
		$chunk = preg_replace( '/<sup\b[^>]*>.*?<\/sup>/is', ' ', $chunk );
		// Remove all remaining tags.
		$text = wp_strip_all_tags( $chunk );
		// Decode entities.
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// Remove footnote / cross-reference markers.
		$text = str_replace( array( '+', '*' ), '', $text );
		// Remove a leading verse number (e.g. "3 He heals...").
		$text = preg_replace( '/^\s*' . preg_quote( (string) $verse, '/' ) . '\s+/u', '', $text );
		// Collapse whitespace.
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}

	/**
	 * Build the jw.org English NWT chapter URL (server-rendered, primary EN).
	 * Polish uses the study edition.
	 *
	 * @param string $lang    'pl' or 'en'.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @return string
	 */
	private function jworg_url( $lang, $book, $chapter ) {
		$slug = $this->slug( $lang, $book );
		if ( 'en' === $lang ) {
			return "https://www.jw.org/en/library/bible/nwt/books/{$slug}/{$chapter}/";
		}
		return $this->study_url( 'pl', $book, $chapter );
	}

	/**
	 * Build the jw.org study-edition chapter URL (best for browser reading,
	 * shown to the admin for manual fallback).
	 *
	 * @param string $lang    'pl' or 'en'.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @return string
	 */
	public function study_url( $lang, $book, $chapter ) {
		$slug = $this->slug( $lang, $book );
		if ( 'pl' === $lang ) {
			return "https://www.jw.org/pl/biblioteka/biblia/biblia-wydanie-do-studium/ksiegi/{$slug}/{$chapter}/";
		}
		return "https://www.jw.org/en/library/bible/nwt/books/{$slug}/{$chapter}/";
	}

	/**
	 * Build the wol.jw.org fallback chapter URL.
	 *
	 * @param string $lang    'pl' or 'en'.
	 * @param int    $book    Book number.
	 * @param int    $chapter Chapter.
	 * @return string
	 */
	private function wol_url( $lang, $book, $chapter ) {
		if ( 'pl' === $lang ) {
			return "https://wol.jw.org/pl/wol/b/r12/lp-p/nwt/{$book}/{$chapter}";
		}
		return "https://wol.jw.org/en/wol/b/r1/lp-e/nwt/{$book}/{$chapter}";
	}

	/**
	 * Get a URL-encoded book slug for the given language.
	 *
	 * @param string $lang 'pl' or 'en'.
	 * @param int    $book Book number.
	 * @return string
	 */
	private function slug( $lang, $book ) {
		$entry = FBV_Bible_Books::get( $book );
		if ( ! $entry ) {
			return '';
		}
		$slug = 'en' === $lang ? $entry['slug_en'] : $entry['slug_pl'];
		// Encode any special characters while preserving path slashes/hyphens.
		return rawurlencode( $slug );
	}
}
