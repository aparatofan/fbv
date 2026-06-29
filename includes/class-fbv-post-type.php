<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the fbv_verse CPT and fbv_tag taxonomy.
 */
class FBV_Post_Type {

	const POST_TYPE = 'fbv_verse';
	const TAXONOMY  = 'fbv_tag';

	/**
	 * Register the post type and taxonomy.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Verses', 'fbv' ),
					'singular_name' => __( 'Verse', 'fbv' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'supports'            => array( 'title' ),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Tags', 'fbv' ),
					'singular_name' => __( 'Tag', 'fbv' ),
				),
				'public'            => false,
				'show_ui'           => false,
				'show_in_rest'      => false,
				'hierarchical'      => false,
				'show_admin_column' => false,
				'rewrite'           => false,
			)
		);

		self::register_meta();
	}

	/**
	 * Register the verse meta fields.
	 */
	private static function register_meta() {
		$fields = array(
			'_fbv_reference'    => 'string',
			'_fbv_book_number'  => 'integer',
			'_fbv_chapter'      => 'integer',
			'_fbv_verse_start'  => 'integer',
			'_fbv_verse_end'    => 'integer',
			'_fbv_text_pl'      => 'string',
			'_fbv_text_en'      => 'string',
			'_fbv_date_added'   => 'string',
		);

		foreach ( $fields as $key => $type ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'          => $type,
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				)
			);
		}
	}
}
