<?php
/**
 * src/Support/Activator.php
 *
 * Handles everything that must happen once, on plugin activation:
 *  1. Register the CPT so rewrite rules are available.
 *  2. Create the tracking page if it doesn't already exist.
 *  3. Flush rewrite rules.
 *
 * Kept separate from Plugin so the heavyweight activation logic is
 * loaded only during the activation request, not on every page load.
 *
 * @package ShipView\Support
 */

declare( strict_types=1 );

namespace ShipView\Support;

/**
 * Class Activator
 *
 * @since 1.0.0
 */
final class Activator {

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config Plugin configuration.
     */
    public function __construct( array $config ) {
        $this->config = $config;
    }

    /**
     * Runs on plugin activation.
     *
     * @return void
     */
    public function activate(): void {
        $this->register_post_type();
        $this->create_tracking_page();
        flush_rewrite_rules();
    }

    /**
     * Registers the CPT without hooks so rewrite rules exist before flush.
     *
     * @return void
     */
    private function register_post_type(): void {
        if ( post_type_exists( $this->config['post_type'] ) ) {
            return;
        }

        // Minimal registration – just enough for rewrite rules.
        register_post_type(
            $this->config['post_type'],
            [
                'public'   => false,
                'rewrite'  => false,
                'supports' => [ 'title' ],
            ]
        );
    }

    /**
     * Creates the Shipment Tracker page when it doesn't already exist.
     *
     * @return void
     */
    private function create_tracking_page(): void {
        $existing_id = (int) get_option( 'shipview_page_id', 0 );

        if ( $existing_id && get_post( $existing_id ) instanceof \WP_Post ) {
            return;
        }

        $page_id = wp_insert_post(
            [
                'post_title'   => __( 'Shipment Tracker', 'shipview' ),
                'post_name'    => 'shipment-tracker',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
                'meta_input'   => [ '_shipview_template' => '1' ],
            ]
        );

        if ( ! is_wp_error( $page_id ) ) {
            update_option( 'shipview_page_id', $page_id );
        }
    }
}
