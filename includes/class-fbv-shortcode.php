<?php
/**
 * [fbv] shortcode handler and asset loader.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the frontend interface.
 */
class FBV_Shortcode {

	/**
	 * Whether the current request should load FBV assets.
	 *
	 * @var bool
	 */
	private $should_enqueue = false;

	/**
	 * Enqueue assets only on singular content that contains the [fbv] shortcode.
	 */
	public function maybe_enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'fbv' ) ) {
			return;
		}

		$this->should_enqueue = true;
		$this->register_assets();
	}

	/**
	 * Register and enqueue CSS/JS plus localized data.
	 */
	private function register_assets() {
		wp_enqueue_style(
			'fbv-frontend',
			FBV_PLUGIN_URL . 'assets/css/fbv-frontend.css',
			array(),
			FBV_VERSION
		);

		wp_enqueue_script(
			'fbv-frontend',
			FBV_PLUGIN_URL . 'assets/js/fbv-frontend.js',
			array(),
			FBV_VERSION,
			true
		);

		wp_localize_script(
			'fbv-frontend',
			'FBV_DATA',
			array(
				'verses'  => $this->get_verses(),
				'tags'    => $this->get_tags(),
				'restUrl' => esc_url_raw( rest_url( FBV_REST_API::NAMESPACE ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'isAdmin' => current_user_can( 'manage_options' ),
				'lang'    => 'pl',
				'i18n'    => $this->strings(),
			)
		);
	}

	/**
	 * Collect all verses via the REST controller (single source of truth).
	 *
	 * @return array
	 */
	private function get_verses() {
		$request  = new WP_REST_Request( 'GET', '/' . FBV_REST_API::NAMESPACE . '/verses' );
		$response = rest_do_request( $request );
		return $response->is_error() ? array() : $response->get_data();
	}

	/**
	 * Collect all tags via the REST controller.
	 *
	 * @return array
	 */
	private function get_tags() {
		$request  = new WP_REST_Request( 'GET', '/' . FBV_REST_API::NAMESPACE . '/tags' );
		$response = rest_do_request( $request );
		return $response->is_error() ? array() : $response->get_data();
	}

	/**
	 * UI strings exposed to JavaScript (PL + EN).
	 *
	 * @return array
	 */
	private function strings() {
		return array(
			'pl' => array(
				'search'       => 'Szukaj wersetów…',
				'allTags'      => 'Wszystkie',
				'addVerse'     => '+ Dodaj werset',
				'editVerse'    => 'Edytuj werset',
				'reference'    => 'Adres wersetu',
				'referencePh'  => 'np. Psalm 147:3',
				'textPl'       => 'Tekst polski',
				'textEn'       => 'Tekst angielski',
				'tags'         => 'Tagi (oddzielone przecinkami)',
				'save'         => 'Zapisz',
				'cancel'       => 'Anuluj',
				'fetching'     => 'Pobieranie…',
				'openPl'       => 'Otwórz w jw.org (PL)',
				'openEn'       => 'Open on jw.org (EN)',
				'edit'         => 'Edytuj',
				'delete'       => 'Usuń',
				'confirmDelete' => 'Czy na pewno usunąć ten werset?',
				'duplicate'    => 'Werset o tym adresie już istnieje.',
				'noResults'    => 'Brak wersetów.',
				'versesOne'    => '%d werset',
				'versesFew'    => '%d wersety',
				'versesMany'   => '%d wersetów',
				'saveError'    => 'Nie udało się zapisać wersetu.',
				'required'     => 'Adres wersetu oraz przynajmniej jeden tekst są wymagane.',
				'fetchFailed'  => 'Automatyczne pobieranie nie powiodło się. Wklej tekst ręcznie.',
			),
			'en' => array(
				'search'       => 'Search verses…',
				'allTags'      => 'All',
				'addVerse'     => '+ Add Verse',
				'editVerse'    => 'Edit Verse',
				'reference'    => 'Verse reference',
				'referencePh'  => 'e.g. Psalm 147:3',
				'textPl'       => 'Polish text',
				'textEn'       => 'English text',
				'tags'         => 'Tags (comma separated)',
				'save'         => 'Save',
				'cancel'       => 'Cancel',
				'fetching'     => 'Fetching…',
				'openPl'       => 'Otwórz w jw.org (PL)',
				'openEn'       => 'Open on jw.org (EN)',
				'edit'         => 'Edit',
				'delete'       => 'Delete',
				'confirmDelete' => 'Really delete this verse?',
				'duplicate'    => 'A verse with this reference already exists.',
				'noResults'    => 'No verses found.',
				'versesOne'    => '%d verse',
				'versesFew'    => '%d verses',
				'versesMany'   => '%d verses',
				'saveError'    => 'Could not save the verse.',
				'required'     => 'Reference and at least one text are required.',
				'fetchFailed'  => 'Auto-fetch failed. Please paste the text manually.',
			),
		);
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Shortcode attributes (unused for v1.0).
	 * @return string
	 */
	public function render( $atts = array() ) {
		// If assets were not enqueued (e.g. shortcode added via a builder),
		// enqueue them now.
		if ( ! $this->should_enqueue ) {
			$this->should_enqueue = true;
			$this->register_assets();
		}

		$is_admin = current_user_can( 'manage_options' );

		ob_start();
		include FBV_PLUGIN_DIR . 'templates/cards.php';
		return ob_get_clean();
	}
}
