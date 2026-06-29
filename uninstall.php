<?php
/**
 * Uninstall handler for FBV.
 *
 * By default this leaves data intact. To delete all verses and tags on
 * uninstall, define the following constant (e.g. in wp-config.php):
 *
 *     define( 'FBV_DELETE_DATA_ON_UNINSTALL', true );
 *
 * @package FBV
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'FBV_DELETE_DATA_ON_UNINSTALL' ) || ! FBV_DELETE_DATA_ON_UNINSTALL ) {
	return;
}

$fbv_post_type = 'fbv_verse';
$fbv_taxonomy  = 'fbv_tag';

// Delete all verse posts (and their meta).
$fbv_posts = get_posts(
	array(
		'post_type'      => $fbv_post_type,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $fbv_posts as $fbv_post_id ) {
	wp_delete_post( $fbv_post_id, true );
}

// Delete all tags in the taxonomy.
$fbv_terms = get_terms(
	array(
		'taxonomy'   => $fbv_taxonomy,
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $fbv_terms ) ) {
	foreach ( $fbv_terms as $fbv_term_id ) {
		wp_delete_term( $fbv_term_id, $fbv_taxonomy );
	}
}

// Remove any leftover chapter-fetch transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_fbv\_chapter\_%' OR option_name LIKE '\_transient\_timeout\_fbv\_chapter\_%'" );
