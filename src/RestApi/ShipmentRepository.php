<?php
/**
 * src/RestApi/ShipmentRepository.php
 *
 * Encapsulates all WP_Query / database interaction for shipments.
 *
 * Controller classes should never build WP_Query args directly;
 * they delegate to this class.  This makes the query logic easy
 * to unit-test independently of the REST layer.
 *
 * @package ShipView\RestApi
 */

declare( strict_types=1 );

namespace ShipView\RestApi;

use WP_Post;
use WP_Query;

/**
 * Class ShipmentRepository
 *
 * @since 1.0.0
 */
final class ShipmentRepository {

    /**
     * @var string WordPress post type slug.
     */
    private string $post_type;

    /**
     * @param string $post_type The CPT slug (e.g. 'shipment').
     */
    public function __construct( string $post_type ) {
        $this->post_type = $post_type;
    }

    /**
     * Returns all published shipments, optionally filtered.
     *
     * @param string $status Filter by status slug, or 'all' for no filter.
     * @param string $search Free-text search across AWB, client, and title.
     * @return ShipmentDTO[]
     */
    public function find_all( string $status = 'all', string $search = '' ): array {
        $args = [
            'post_type'      => $this->post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true, // performance: skip SQL_CALC_FOUND_ROWS.
        ];

        if ( '' !== $search ) {
            $args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'relation' => 'OR',
                [
                    'key'     => 'shipview_awb',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'shipview_client',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
            $args['s'] = $search;
        }

        if ( 'all' !== $status ) {
            $args['meta_query'][] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'key'   => 'shipview_status',
                'value' => $status,
            ];
        }

        return array_map(
            static fn( WP_Post $post ) => ShipmentDTO::from_post( $post ),
            ( new WP_Query( $args ) )->posts
        );
    }

    /**
     * Finds a single shipment by its AWB number.
     *
     * @param string $awb The AWB / tracking number.
     * @return ShipmentDTO|null Null when no match is found.
     */
    public function find_by_awb( string $awb ): ?ShipmentDTO {
        $query = new WP_Query(
            [
                'post_type'      => $this->post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'no_found_rows'  => true,
                'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    [
                        'key'   => 'shipview_awb',
                        'value' => $awb,
                    ],
                ],
            ]
        );

        if ( ! $query->have_posts() ) {
            return null;
        }

        return ShipmentDTO::from_post( $query->posts[0] );
    }

    /**
     * Finds a single shipment by post ID, confirming it is the correct CPT.
     *
     * @param int $post_id WordPress post ID.
     * @return WP_Post|null Null when not found or wrong type.
     */
    public function find_post_by_id( int $post_id ): ?WP_Post {
        $post = get_post( $post_id );

        if ( ! $post instanceof WP_Post || $this->post_type !== $post->post_type ) {
            return null;
        }

        return $post;
    }

    /**
     * Applies a partial update to a shipment post and returns the refreshed DTO.
     *
     * @param int                  $post_id Post ID.
     * @param array<string, string> $fields  Map of meta_key => value.
     * @return ShipmentDTO
     */
    public function update( int $post_id, array $fields ): ShipmentDTO {
        foreach ( $fields as $meta_key => $value ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
        }

        // Bump post_modified so "Last sync" timestamps reflect the change.
        wp_update_post(
            [
                'ID'            => $post_id,
                'post_modified' => current_time( 'mysql' ),
            ]
        );

        return ShipmentDTO::from_post( get_post( $post_id ) );
    }

    /**
     * Computes summary statistics from a list of DTOs.
     *
     * @param ShipmentDTO[] $shipments Array of DTOs.
     * @return array<string, int>
     */
    public function stats( array $shipments ): array {
        return [
            'total'      => count( $shipments ),
            'in_transit' => count( array_filter( $shipments, static fn( $s ) => 'in_transit' === $s->status ) ),
            'delivered'  => count( array_filter( $shipments, static fn( $s ) => 'delivered'  === $s->status ) ),
            'exception'  => count( array_filter( $shipments, static fn( $s ) => 'exception'  === $s->status ) ),
            'overdue'    => count( array_filter( $shipments, static fn( $s ) => $s->overdue ) ),
        ];
    }
}
