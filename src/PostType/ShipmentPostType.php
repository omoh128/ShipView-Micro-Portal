<?php
/**
 * src/PostType/ShipmentPostType.php
 *
 * Registers the `shipment` Custom Post Type.
 *
 * @package ShipView\PostType
 */

declare( strict_types=1 );

namespace ShipView\PostType;

use ShipView\Support\Registerable;

/**
 * Class ShipmentPostType
 *
 * @since 1.0.0
 */
final class ShipmentPostType implements Registerable {

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
     * {@inheritdoc}
     */
    public function register(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
    }

    /**
     * Registers the CPT with WordPress.
     *
     * @return void
     */
    public function register_post_type(): void {
        register_post_type(
            $this->config['post_type'],
            [
                'labels'          => $this->labels(),
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => true,
                'show_in_rest'    => true,
                'menu_icon'       => 'dashicons-airplane',
                'menu_position'   => 25,
                'supports'        => [ 'title' ],
                'capability_type' => 'post',
                'has_archive'     => false,
                'rewrite'         => false,
            ]
        );
    }

    /**
     * Returns the translated label array for the CPT.
     *
     * @return array<string, string>
     */
    private function labels(): array {
        return [
            'name'               => __( 'Shipments',          'shipview' ),
            'singular_name'      => __( 'Shipment',           'shipview' ),
            'add_new'            => __( 'Add Shipment',       'shipview' ),
            'add_new_item'       => __( 'Add New Shipment',   'shipview' ),
            'edit_item'          => __( 'Edit Shipment',      'shipview' ),
            'new_item'           => __( 'New Shipment',       'shipview' ),
            'view_item'          => __( 'View Shipment',      'shipview' ),
            'search_items'       => __( 'Search Shipments',   'shipview' ),
            'not_found'          => __( 'No shipments found', 'shipview' ),
            'not_found_in_trash' => __( 'Nothing in trash',   'shipview' ),
            'menu_name'          => __( 'Shipments',          'shipview' ),
        ];
    }
}
