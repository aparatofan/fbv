<?php
/**
 * Bible book reference table.
 *
 * Maps all 66 Bible books (Protestant / NWT canon) to their canonical order
 * number, Polish and English citation names, jw.org URL slugs and matching
 * aliases used by the reference parser.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static lookup table for Bible books.
 */
class FBV_Bible_Books {

	/**
	 * Cached book table.
	 *
	 * @var array<int,array>|null
	 */
	private static $books = null;

	/**
	 * Get the full book table keyed by canonical number (1-66).
	 *
	 * Each entry contains:
	 *  - name_pl   : Polish citation name (e.g. "Psalm")
	 *  - name_en   : English name (e.g. "Psalms")
	 *  - slug_pl   : jw.org Polish URL slug
	 *  - slug_en   : jw.org English URL slug
	 *  - aliases   : extra spellings accepted by the parser (PL + EN)
	 *
	 * @return array<int,array>
	 */
	public static function all() {
		if ( null !== self::$books ) {
			return self::$books;
		}

		self::$books = array(
			1  => array( 'name_pl' => 'Rodzaju', 'name_en' => 'Genesis', 'slug_pl' => 'rodzaju', 'slug_en' => 'genesis', 'aliases' => array( '1 Mojzeszowa', 'Geneza' ) ),
			2  => array( 'name_pl' => 'Wyjścia', 'name_en' => 'Exodus', 'slug_pl' => 'wyjscia', 'slug_en' => 'exodus', 'aliases' => array( '2 Mojzeszowa' ) ),
			3  => array( 'name_pl' => 'Kapłańska', 'name_en' => 'Leviticus', 'slug_pl' => 'kaplanska', 'slug_en' => 'leviticus', 'aliases' => array( '3 Mojzeszowa' ) ),
			4  => array( 'name_pl' => 'Liczb', 'name_en' => 'Numbers', 'slug_pl' => 'liczb', 'slug_en' => 'numbers', 'aliases' => array( '4 Mojzeszowa' ) ),
			5  => array( 'name_pl' => 'Powtórzonego Prawa', 'name_en' => 'Deuteronomy', 'slug_pl' => 'powtorzonego-prawa', 'slug_en' => 'deuteronomy', 'aliases' => array( '5 Mojzeszowa' ) ),
			6  => array( 'name_pl' => 'Jozuego', 'name_en' => 'Joshua', 'slug_pl' => 'jozuego', 'slug_en' => 'joshua', 'aliases' => array() ),
			7  => array( 'name_pl' => 'Sędziów', 'name_en' => 'Judges', 'slug_pl' => 'sedziow', 'slug_en' => 'judges', 'aliases' => array() ),
			8  => array( 'name_pl' => 'Rut', 'name_en' => 'Ruth', 'slug_pl' => 'rut', 'slug_en' => 'ruth', 'aliases' => array() ),
			9  => array( 'name_pl' => '1 Samuela', 'name_en' => '1 Samuel', 'slug_pl' => '1-samuela', 'slug_en' => '1-samuel', 'aliases' => array() ),
			10 => array( 'name_pl' => '2 Samuela', 'name_en' => '2 Samuel', 'slug_pl' => '2-samuela', 'slug_en' => '2-samuel', 'aliases' => array() ),
			11 => array( 'name_pl' => '1 Królów', 'name_en' => '1 Kings', 'slug_pl' => '1-krolow', 'slug_en' => '1-kings', 'aliases' => array() ),
			12 => array( 'name_pl' => '2 Królów', 'name_en' => '2 Kings', 'slug_pl' => '2-krolow', 'slug_en' => '2-kings', 'aliases' => array() ),
			13 => array( 'name_pl' => '1 Kronik', 'name_en' => '1 Chronicles', 'slug_pl' => '1-kronik', 'slug_en' => '1-chronicles', 'aliases' => array() ),
			14 => array( 'name_pl' => '2 Kronik', 'name_en' => '2 Chronicles', 'slug_pl' => '2-kronik', 'slug_en' => '2-chronicles', 'aliases' => array() ),
			15 => array( 'name_pl' => 'Ezdrasza', 'name_en' => 'Ezra', 'slug_pl' => 'ezdrasza', 'slug_en' => 'ezra', 'aliases' => array() ),
			16 => array( 'name_pl' => 'Nehemiasza', 'name_en' => 'Nehemiah', 'slug_pl' => 'nehemiasza', 'slug_en' => 'nehemiah', 'aliases' => array() ),
			17 => array( 'name_pl' => 'Estery', 'name_en' => 'Esther', 'slug_pl' => 'estery', 'slug_en' => 'esther', 'aliases' => array() ),
			18 => array( 'name_pl' => 'Hioba', 'name_en' => 'Job', 'slug_pl' => 'hioba', 'slug_en' => 'job', 'aliases' => array() ),
			19 => array( 'name_pl' => 'Psalm', 'name_en' => 'Psalms', 'slug_pl' => 'psalmy', 'slug_en' => 'psalms', 'aliases' => array( 'Psalmy', 'Psalmów', 'Psalm' ) ),
			20 => array( 'name_pl' => 'Przysłów', 'name_en' => 'Proverbs', 'slug_pl' => 'przyslow', 'slug_en' => 'proverbs', 'aliases' => array( 'Przyslow' ) ),
			21 => array( 'name_pl' => 'Kaznodziei', 'name_en' => 'Ecclesiastes', 'slug_pl' => 'kaznodziei', 'slug_en' => 'ecclesiastes', 'aliases' => array( 'Koheleta' ) ),
			22 => array( 'name_pl' => 'Pieśń nad Pieśniami', 'name_en' => 'Song of Solomon', 'slug_pl' => 'piesn-nad-piesniami', 'slug_en' => 'song-of-solomon', 'aliases' => array( 'Piesn nad Piesniami', 'Pnp', 'Song of Songs' ) ),
			23 => array( 'name_pl' => 'Izajasza', 'name_en' => 'Isaiah', 'slug_pl' => 'izajasza', 'slug_en' => 'isaiah', 'aliases' => array() ),
			24 => array( 'name_pl' => 'Jeremiasza', 'name_en' => 'Jeremiah', 'slug_pl' => 'jeremiasza', 'slug_en' => 'jeremiah', 'aliases' => array() ),
			25 => array( 'name_pl' => 'Lamentacje', 'name_en' => 'Lamentations', 'slug_pl' => 'lamentacje', 'slug_en' => 'lamentations', 'aliases' => array( 'Treny' ) ),
			26 => array( 'name_pl' => 'Ezechiela', 'name_en' => 'Ezekiel', 'slug_pl' => 'ezechiela', 'slug_en' => 'ezekiel', 'aliases' => array() ),
			27 => array( 'name_pl' => 'Daniela', 'name_en' => 'Daniel', 'slug_pl' => 'daniela', 'slug_en' => 'daniel', 'aliases' => array() ),
			28 => array( 'name_pl' => 'Ozeasza', 'name_en' => 'Hosea', 'slug_pl' => 'ozeasza', 'slug_en' => 'hosea', 'aliases' => array() ),
			29 => array( 'name_pl' => 'Joela', 'name_en' => 'Joel', 'slug_pl' => 'joela', 'slug_en' => 'joel', 'aliases' => array() ),
			30 => array( 'name_pl' => 'Amosa', 'name_en' => 'Amos', 'slug_pl' => 'amosa', 'slug_en' => 'amos', 'aliases' => array() ),
			31 => array( 'name_pl' => 'Abdiasza', 'name_en' => 'Obadiah', 'slug_pl' => 'abdiasza', 'slug_en' => 'obadiah', 'aliases' => array() ),
			32 => array( 'name_pl' => 'Jonasza', 'name_en' => 'Jonah', 'slug_pl' => 'jonasza', 'slug_en' => 'jonah', 'aliases' => array() ),
			33 => array( 'name_pl' => 'Micheasza', 'name_en' => 'Micah', 'slug_pl' => 'micheasza', 'slug_en' => 'micah', 'aliases' => array() ),
			34 => array( 'name_pl' => 'Nahuma', 'name_en' => 'Nahum', 'slug_pl' => 'nahuma', 'slug_en' => 'nahum', 'aliases' => array() ),
			35 => array( 'name_pl' => 'Habakuka', 'name_en' => 'Habakkuk', 'slug_pl' => 'habakuka', 'slug_en' => 'habakkuk', 'aliases' => array() ),
			36 => array( 'name_pl' => 'Sofoniasza', 'name_en' => 'Zephaniah', 'slug_pl' => 'sofoniasza', 'slug_en' => 'zephaniah', 'aliases' => array() ),
			37 => array( 'name_pl' => 'Aggeusza', 'name_en' => 'Haggai', 'slug_pl' => 'aggeusza', 'slug_en' => 'haggai', 'aliases' => array() ),
			38 => array( 'name_pl' => 'Zachariasza', 'name_en' => 'Zechariah', 'slug_pl' => 'zachariasza', 'slug_en' => 'zechariah', 'aliases' => array() ),
			39 => array( 'name_pl' => 'Malachiasza', 'name_en' => 'Malachi', 'slug_pl' => 'malachiasza', 'slug_en' => 'malachi', 'aliases' => array() ),
			40 => array( 'name_pl' => 'Mateusza', 'name_en' => 'Matthew', 'slug_pl' => 'mateusza', 'slug_en' => 'matthew', 'aliases' => array() ),
			41 => array( 'name_pl' => 'Marka', 'name_en' => 'Mark', 'slug_pl' => 'marka', 'slug_en' => 'mark', 'aliases' => array() ),
			42 => array( 'name_pl' => 'Łukasza', 'name_en' => 'Luke', 'slug_pl' => 'lukasza', 'slug_en' => 'luke', 'aliases' => array( 'Lukasza' ) ),
			43 => array( 'name_pl' => 'Jana', 'name_en' => 'John', 'slug_pl' => 'jana', 'slug_en' => 'john', 'aliases' => array() ),
			44 => array( 'name_pl' => 'Dzieje Apostolskie', 'name_en' => 'Acts', 'slug_pl' => 'dzieje-apostolskie', 'slug_en' => 'acts', 'aliases' => array( 'Dzieje', 'Dziejów Apostolskich' ) ),
			45 => array( 'name_pl' => 'Rzymian', 'name_en' => 'Romans', 'slug_pl' => 'rzymian', 'slug_en' => 'romans', 'aliases' => array() ),
			46 => array( 'name_pl' => '1 Koryntian', 'name_en' => '1 Corinthians', 'slug_pl' => '1-koryntian', 'slug_en' => '1-corinthians', 'aliases' => array( '1 Korytian' ) ),
			47 => array( 'name_pl' => '2 Koryntian', 'name_en' => '2 Corinthians', 'slug_pl' => '2-koryntian', 'slug_en' => '2-corinthians', 'aliases' => array( '2 Korytian' ) ),
			48 => array( 'name_pl' => 'Galatów', 'name_en' => 'Galatians', 'slug_pl' => 'galatow', 'slug_en' => 'galatians', 'aliases' => array( 'Galatow' ) ),
			49 => array( 'name_pl' => 'Efezjan', 'name_en' => 'Ephesians', 'slug_pl' => 'efezjan', 'slug_en' => 'ephesians', 'aliases' => array() ),
			50 => array( 'name_pl' => 'Filipian', 'name_en' => 'Philippians', 'slug_pl' => 'filipian', 'slug_en' => 'philippians', 'aliases' => array() ),
			51 => array( 'name_pl' => 'Kolosan', 'name_en' => 'Colossians', 'slug_pl' => 'kolosan', 'slug_en' => 'colossians', 'aliases' => array() ),
			52 => array( 'name_pl' => '1 Tesaloniczan', 'name_en' => '1 Thessalonians', 'slug_pl' => '1-tesaloniczan', 'slug_en' => '1-thessalonians', 'aliases' => array() ),
			53 => array( 'name_pl' => '2 Tesaloniczan', 'name_en' => '2 Thessalonians', 'slug_pl' => '2-tesaloniczan', 'slug_en' => '2-thessalonians', 'aliases' => array() ),
			54 => array( 'name_pl' => '1 Tymoteusza', 'name_en' => '1 Timothy', 'slug_pl' => '1-tymoteusza', 'slug_en' => '1-timothy', 'aliases' => array() ),
			55 => array( 'name_pl' => '2 Tymoteusza', 'name_en' => '2 Timothy', 'slug_pl' => '2-tymoteusza', 'slug_en' => '2-timothy', 'aliases' => array() ),
			56 => array( 'name_pl' => 'Tytusa', 'name_en' => 'Titus', 'slug_pl' => 'tytusa', 'slug_en' => 'titus', 'aliases' => array() ),
			57 => array( 'name_pl' => 'Filemona', 'name_en' => 'Philemon', 'slug_pl' => 'filemona', 'slug_en' => 'philemon', 'aliases' => array() ),
			58 => array( 'name_pl' => 'Hebrajczyków', 'name_en' => 'Hebrews', 'slug_pl' => 'hebrajczykow', 'slug_en' => 'hebrews', 'aliases' => array( 'Hebrajczykow' ) ),
			59 => array( 'name_pl' => 'Jakuba', 'name_en' => 'James', 'slug_pl' => 'jakuba', 'slug_en' => 'james', 'aliases' => array() ),
			60 => array( 'name_pl' => '1 Piotra', 'name_en' => '1 Peter', 'slug_pl' => '1-piotra', 'slug_en' => '1-peter', 'aliases' => array() ),
			61 => array( 'name_pl' => '2 Piotra', 'name_en' => '2 Peter', 'slug_pl' => '2-piotra', 'slug_en' => '2-peter', 'aliases' => array() ),
			62 => array( 'name_pl' => '1 Jana', 'name_en' => '1 John', 'slug_pl' => '1-jana', 'slug_en' => '1-john', 'aliases' => array() ),
			63 => array( 'name_pl' => '2 Jana', 'name_en' => '2 John', 'slug_pl' => '2-jana', 'slug_en' => '2-john', 'aliases' => array() ),
			64 => array( 'name_pl' => '3 Jana', 'name_en' => '3 John', 'slug_pl' => '3-jana', 'slug_en' => '3-john', 'aliases' => array() ),
			65 => array( 'name_pl' => 'Judy', 'name_en' => 'Jude', 'slug_pl' => 'judy', 'slug_en' => 'jude', 'aliases' => array() ),
			66 => array( 'name_pl' => 'Objawienie', 'name_en' => 'Revelation', 'slug_pl' => 'objawienie', 'slug_en' => 'revelation', 'aliases' => array( 'Apokalipsa', 'Objawienia' ) ),
		);

		return self::$books;
	}

	/**
	 * Get a single book by canonical number.
	 *
	 * @param int $number Book number (1-66).
	 * @return array|null
	 */
	public static function get( $number ) {
		$books = self::all();
		return isset( $books[ $number ] ) ? $books[ $number ] : null;
	}

	/**
	 * Get a book's display name in the requested language.
	 *
	 * @param int    $number Book number.
	 * @param string $lang   'pl' or 'en'.
	 * @return string
	 */
	public static function name( $number, $lang = 'pl' ) {
		$book = self::get( $number );
		if ( ! $book ) {
			return '';
		}
		return 'en' === $lang ? $book['name_en'] : $book['name_pl'];
	}

	/**
	 * Normalise a string for fuzzy matching: lowercase, strip Polish
	 * diacritics and collapse whitespace.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function normalize( $value ) {
		$value = trim( (string) $value );
		// Lowercase (multibyte-aware).
		if ( function_exists( 'mb_strtolower' ) ) {
			$value = mb_strtolower( $value, 'UTF-8' );
		} else {
			$value = strtolower( $value );
		}

		$map = array(
			'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
			'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
		);
		$value = strtr( $value, $map );

		// Collapse any run of non-alphanumerics to a single space.
		$value = preg_replace( '/[^a-z0-9]+/u', ' ', $value );
		return trim( $value );
	}

	/**
	 * Look up a book number from a (Polish or English) book name.
	 *
	 * @param string $name Book name as typed by the user.
	 * @return int|null Canonical book number, or null if not found.
	 */
	public static function find_number( $name ) {
		$needle = self::normalize( $name );
		if ( '' === $needle ) {
			return null;
		}

		foreach ( self::all() as $number => $book ) {
			$candidates = array( $book['name_pl'], $book['name_en'] );
			$candidates = array_merge( $candidates, $book['aliases'] );
			foreach ( $candidates as $candidate ) {
				if ( self::normalize( $candidate ) === $needle ) {
					return $number;
				}
			}
		}

		return null;
	}
}
