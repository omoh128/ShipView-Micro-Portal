<?php
/**
 * src/RestApi/ShipmentController.php
 *
 * REST API controller for the shipment resource.
 *
 * Responsibilities (only):
 *  – Register routes.
 *  – Validate / parse request params.
 *  – Delegate to the Repository.
 *  – Return WP_REST_Response / WP_Error.
 *
 * @package ShipView\RestApi
 */

declare( strict_types=1 );

namespace ShipView\RestApi;

use ShipView\Support\Registerable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class ShipmentController
 *
 * @since 1.0.0
 */
final class ShipmentController implements Registerable {

    private string              $namespace;
    private ShipmentRepository  $repository;

    /**
     * @param array<string, mixed> $config Plugin configuration.
     */
    public function __construct( array $config ) {
        $this->namespace  = $config['rest_namespace'];
        $this->repository = new ShipmentRepository( $config['post_type'] );
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Registers all REST routes.
     *
     * @return void
     */
    public function register_routes(): void {
        // GET /shipments  – collection.
        register_rest_route(
            $this->namespace,
            '/shipments',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_shipments' ],
                'permission_callback' => '__return_true',
                'args'                => $this->collection_args(),
            ]
        );

        // GET /shipments/{awb}  – single by AWB.
        register_rest_route(
            $this->namespace,
            '/shipments/(?P<awb>[a-zA-Z0-9\-]+)',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_by_awb' ],
                'permission_callback' => '__return_true',
            ]
        );

        // PATCH /shipments/{id}  – partial update (auth-gated).
        register_rest_route(
            $this->namespace,
            '/shipments/(?P<id>\d+)',
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_shipment' ],
                'permission_callback' => [ $this, 'can_edit' ],
                'args'                => $this->update_args(),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Callbacks
    // -------------------------------------------------------------------------

    /**
     * GET /shipments
     *
     * @param WP_REST_Request $request Incoming request.
     * @return WP_REST_Response
     */
    public function get_shipments( WP_REST_Request $request ): WP_REST_Response {
        $shipments = $this->repository->find_all(
            (string) $request->get_param( 'status' ),
            (string) $request->get_param( 'search' )
        );

        return new WP_REST_Response(
            [
                'shipments' => array_map( static fn( $s ) => $s->to_array(), $shipments ),
                'stats'     => $this->repository->stats( $shipments ),
                'count'     => count( $shipments ),
                'generated' => current_time( 'c' ),
            ],
            200
        );
    }

    /**
     * GET /shipments/{awb}
     *
     * @param WP_REST_Request $request Incoming request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_by_awb( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $awb      = sanitize_text_field( (string) $request->get_param( 'awb' ) );
        $shipment = $this->repository->find_by_awb( $awb );

        if ( null === $shipment ) {
            return new WP_Error(
                'shipview_not_found',
                __( 'Shipment not found.', 'shipview' ),
                [ 'status' => 404 ]
            );
        }

        return new WP_REST_Response( $shipment->to_array(), 200 );
    }

    /**
     * PATCH /shipments/{id}
     *
     * @param WP_REST_Request $request Incoming request.
     * @return WP_REST_Response|WP_Error
     */
    public function update_shipment( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post_id = (int) $request->get_param( 'id' );
        $post    = $this->repository->find_post_by_id( $post_id );

        if ( null === $post ) {
            return new WP_Error(
                'shipview_not_found',
                __( 'Shipment not found.', 'shipview' ),
                [ 'status' => 404 ]
            );
        }

        // Collect only the fields that were actually sent.
        $update_map = [
            'status' => 'shipview_status',
            'notes'  => 'shipview_notes',
            'eta'    => 'shipview_eta',
        ];

        $fields = [];
        foreach ( $update_map as $param => $meta_key ) {
            $value = $request->get_param( $param );
            if ( null !== $value ) {
                $fields[ $meta_key ] = (string) $value;
            }
        }

        $shipment = $this->repository->update( $post_id, $fields );

        return new WP_REST_Response( $shipment->to_array(), 200 );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    /**
     * Allows requests from any editor or above.
     *
     * @return bool
     */
    public function can_edit(): bool {
        return current_user_can( 'edit_posts' );
    }

    // -------------------------------------------------------------------------
    // Argument schemas
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collection_args(): array {
        return [
            'status' => [
                'type'              => 'string',
                'default'           => 'all',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search' => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function update_args(): array {
        return [
            'status' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'notes'  => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'eta'    => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
}
