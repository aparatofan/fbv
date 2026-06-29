<?php
/**
 * REST API endpoints for FBV.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the /fbv/v1 REST routes.
 */
class FBV_REST_API {

	const REST_NAMESPACE = 'fbv/v1';

	/**
	 * Register all routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/verses',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_verses' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'search' => array( 'type' => 'string', 'required' => false ),
						'tag'    => array( 'type' => 'string', 'required' => false ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_verse' ),
					'permission_callback' => array( $this, 'require_admin' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/verses/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_verse' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_verse' ),
					'permission_callback' => array( $this, 'require_admin' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_verse' ),
					'permission_callback' => array( $this, 'require_admin' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tags',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tags' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Permission callback for admin-only write endpoints.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function require_admin( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'fbv_forbidden', __( 'You are not allowed to do that.', 'fbv' ), array( 'status' => 403 ) );
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'fbv_bad_nonce', __( 'Invalid or missing security token.', 'fbv' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/* ---------------------------------------------------------------------
	 * Verses
	 * ------------------------------------------------------------------- */

	/**
	 * GET /verses — list all verses in canonical order.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_verses( WP_REST_Request $request ) {
		$args = array(
			'post_type'      => FBV_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'ID'             => 'ASC',
			),
			'meta_key'       => '_fbv_book_number',
		);

		$tag = $request->get_param( 'tag' );
		if ( $tag ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => FBV_Post_Type::TAXONOMY,
					'field'    => 'slug',
					'terms'    => sanitize_title( $tag ),
				),
			);
		}

		$query   = new WP_Query( $args );
		$verses  = array();
		foreach ( $query->posts as $post ) {
			$verses[] = $this->serialize_verse( $post );
		}

		// Sort fully by book, chapter, verse_start (meta_value_num only covers book).
		usort(
			$verses,
			static function ( $a, $b ) {
				return array( $a['book_number'], $a['chapter'], $a['verse_start'] )
					<=> array( $b['book_number'], $b['chapter'], $b['verse_start'] );
			}
		);

		$search = trim( (string) $request->get_param( 'search' ) );
		if ( '' !== $search ) {
			$verses = $this->filter_by_search( $verses, $search );
		}

		return rest_ensure_response( array_values( $verses ) );
	}

	/**
	 * Filter a serialized verse list by a free-text query.
	 *
	 * @param array  $verses Serialized verses.
	 * @param string $search Search term.
	 * @return array
	 */
	private function filter_by_search( array $verses, $search ) {
		$needle = FBV_Bible_Books::normalize( $search );
		if ( '' === $needle ) {
			return $verses;
		}

		return array_filter(
			$verses,
			static function ( $verse ) use ( $needle ) {
				$haystack = implode(
					' ',
					array(
						$verse['reference'],
						$verse['reference_en'],
						$verse['text_pl'],
						$verse['text_en'],
						implode( ' ', wp_list_pluck( $verse['tags'], 'name' ) ),
					)
				);
				return false !== strpos( FBV_Bible_Books::normalize( $haystack ), $needle );
			}
		);
	}

	/**
	 * GET /verses/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_verse( WP_REST_Request $request ) {
		$post = $this->get_verse_post( (int) $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		return rest_ensure_response( $this->serialize_verse( $post ) );
	}

	/**
	 * POST /verses.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_verse( WP_REST_Request $request ) {
		$data = $this->validate_payload( $request );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => FBV_Post_Type::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $data['reference'],
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->save_meta( $post_id, $data, true );
		$this->save_tags( $post_id, $data['tags'] );

		$response = rest_ensure_response( $this->serialize_verse( get_post( $post_id ) ) );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * PUT /verses/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_verse( WP_REST_Request $request ) {
		$post = $this->get_verse_post( (int) $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data = $this->validate_payload( $request );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		wp_update_post(
			array(
				'ID'         => $post->ID,
				'post_title' => $data['reference'],
			)
		);

		$this->save_meta( $post->ID, $data, false );
		$this->save_tags( $post->ID, $data['tags'] );

		return rest_ensure_response( $this->serialize_verse( get_post( $post->ID ) ) );
	}

	/**
	 * DELETE /verses/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_verse( WP_REST_Request $request ) {
		$post = $this->get_verse_post( (int) $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$result = wp_delete_post( $post->ID, true );
		if ( ! $result ) {
			return new WP_Error( 'fbv_delete_failed', __( 'Could not delete the verse.', 'fbv' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $post->ID ) );
	}

	/* ---------------------------------------------------------------------
	 * Tags
	 * ------------------------------------------------------------------- */

	/**
	 * GET /tags — list all tags with counts.
	 *
	 * @return WP_REST_Response
	 */
	public function get_tags() {
		$terms = get_terms(
			array(
				'taxonomy'   => FBV_Post_Type::TAXONOMY,
				'hide_empty' => true,
			)
		);

		$out = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[] = array(
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => (int) $term->count,
				);
			}
		}

		return rest_ensure_response( $out );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Fetch and validate a verse post by ID.
	 *
	 * @param int $id Post ID.
	 * @return WP_Post|WP_Error
	 */
	private function get_verse_post( $id ) {
		$post = get_post( $id );
		if ( ! $post || FBV_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'fbv_not_found', __( 'Verse not found.', 'fbv' ), array( 'status' => 404 ) );
		}
		return $post;
	}

	/**
	 * Validate and sanitise an incoming create/update payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	private function validate_payload( WP_REST_Request $request ) {
		$reference = sanitize_text_field( (string) $request->get_param( 'reference' ) );
		$text_pl   = $this->sanitize_verse_text( (string) $request->get_param( 'text_pl' ) );
		$text_en   = $this->sanitize_verse_text( (string) $request->get_param( 'text_en' ) );

		if ( '' === $reference ) {
			return new WP_Error( 'fbv_missing_reference', __( 'Verse reference is required.', 'fbv' ), array( 'status' => 400 ) );
		}
		if ( '' === $text_pl && '' === $text_en ) {
			return new WP_Error( 'fbv_missing_text', __( 'At least one language text (PL or EN) is required.', 'fbv' ), array( 'status' => 400 ) );
		}

		$parsed = FBV_Parser::parse( $reference );
		if ( is_wp_error( $parsed ) ) {
			return new WP_Error( 'fbv_bad_reference', $parsed->get_error_message(), array( 'status' => 400 ) );
		}

		$tags = $this->parse_tags( $request->get_param( 'tags' ) );

		return array(
			'reference'   => $parsed['reference'],
			'book_number' => $parsed['book_number'],
			'chapter'     => $parsed['chapter'],
			'verse_start' => $parsed['verse_start'],
			'verse_end'   => $parsed['verse_end'],
			'text_pl'     => $text_pl,
			'text_en'     => $text_en,
			'tags'        => $tags,
		);
	}

	/**
	 * Sanitise verse text while preserving line breaks.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function sanitize_verse_text( $text ) {
		return trim( wp_kses_post( $text ) );
	}

	/**
	 * Normalise a tags input (string or array) into a clean array of names.
	 *
	 * @param mixed $tags Raw tags input.
	 * @return string[]
	 */
	private function parse_tags( $tags ) {
		if ( is_array( $tags ) ) {
			$list = $tags;
		} else {
			$list = explode( ',', (string) $tags );
		}

		$clean = array();
		foreach ( $list as $tag ) {
			$tag = sanitize_text_field( trim( $tag ) );
			if ( '' !== $tag ) {
				$clean[] = $tag;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Persist verse meta fields.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Validated payload.
	 * @param bool  $is_new  Whether this is a new verse.
	 */
	private function save_meta( $post_id, array $data, $is_new ) {
		update_post_meta( $post_id, '_fbv_reference', $data['reference'] );
		update_post_meta( $post_id, '_fbv_book_number', (int) $data['book_number'] );
		update_post_meta( $post_id, '_fbv_chapter', (int) $data['chapter'] );
		update_post_meta( $post_id, '_fbv_verse_start', (int) $data['verse_start'] );
		update_post_meta( $post_id, '_fbv_verse_end', (int) $data['verse_end'] );
		update_post_meta( $post_id, '_fbv_text_pl', $data['text_pl'] );
		update_post_meta( $post_id, '_fbv_text_en', $data['text_en'] );

		if ( $is_new || ! get_post_meta( $post_id, '_fbv_date_added', true ) ) {
			update_post_meta( $post_id, '_fbv_date_added', current_time( 'mysql' ) );
		}
	}

	/**
	 * Assign tags to a verse, creating terms as needed.
	 *
	 * @param int      $post_id Post ID.
	 * @param string[] $tags    Tag names.
	 */
	private function save_tags( $post_id, array $tags ) {
		wp_set_object_terms( $post_id, $tags, FBV_Post_Type::TAXONOMY, false );
	}

	/**
	 * Convert a verse post into the REST representation.
	 *
	 * @param WP_Post $post Verse post.
	 * @return array
	 */
	private function serialize_verse( WP_Post $post ) {
		$book    = (int) get_post_meta( $post->ID, '_fbv_book_number', true );
		$chapter = (int) get_post_meta( $post->ID, '_fbv_chapter', true );
		$start   = (int) get_post_meta( $post->ID, '_fbv_verse_start', true );
		$end     = (int) get_post_meta( $post->ID, '_fbv_verse_end', true );

		$terms = wp_get_object_terms( $post->ID, FBV_Post_Type::TAXONOMY );
		$tags  = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = array( 'name' => $term->name, 'slug' => $term->slug );
			}
		}

		return array(
			'id'           => $post->ID,
			'reference'    => (string) get_post_meta( $post->ID, '_fbv_reference', true ),
			'reference_en' => $book ? FBV_Parser::build_reference( $book, $chapter, $start, $end, 'en' ) : '',
			'book_number'  => $book,
			'chapter'      => $chapter,
			'verse_start'  => $start,
			'verse_end'    => $end,
			'text_pl'      => (string) get_post_meta( $post->ID, '_fbv_text_pl', true ),
			'text_en'      => (string) get_post_meta( $post->ID, '_fbv_text_en', true ),
			'date_added'   => (string) get_post_meta( $post->ID, '_fbv_date_added', true ),
			'tags'         => $tags,
		);
	}
}
