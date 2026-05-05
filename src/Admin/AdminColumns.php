<?php
/**
 * src/Admin/AdminColumns.php
 *
 * Customises the Shipments list table columns in wp-admin.
 *
 * @package ShipView\Admin
 */

declare( strict_types=1 );

namespace ShipView\Admin;

use ShipView\Support\Registerable;

/**
 * Class AdminColumns
 *
 * @since 1.0.0
 */
final class AdminColumns implements Registerable {

    /**
     * Colour values used in inline column output.
     */
    private const STATUS_COLOURS = [
        'pending'     => '#f0ad4e',
        'in_transit'  => '#5bc0de',
        'customs'     => '#9b59b6',
        'out_for_del' => '#3498db',
        'delivered'   => '#2ecc71',
        'exception'   => '#e74c3c',
        'returned'    => '#95a5a6',
    ];

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
        $post_type = $this->config['post_type'];

        add_filter( "manage_{$post_type}_posts_columns",       [ $this, 'columns' ] );
        add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'column_content' ], 10, 2 );
        add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
    }

    /**
     * Defines the list-table column headers.
     *
     * @param array<string, string> $columns Existing columns.
     * @return array<string, string>
     */
    public function columns( array $columns ): array {
        unset( $columns['date'] );

        return array_merge(
            $columns,
            [
                'shipview_awb'     => __( 'AWB',         'shipview' ),
                'shipview_carrier' => __( 'Carrier',     'shipview' ),
                'shipview_status'  => __( 'Status',      'shipview' ),
                'shipview_eta'     => __( 'ETA',         'shipview' ),
                'shipview_client'  => __( 'Client',      'shipview' ),
                'shipview_dest'    => __( 'Destination', 'shipview' ),
            ]
        );
    }

    /**
     * Outputs the cell content for each custom column.
     *
     * @param string $column  Column slug.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function column_content( string $column, int $post_id ): void {
        match ( $column ) {
            'shipview_awb'     => $this->render_awb( $post_id ),
            'shipview_carrier' => $this->render_carrier( $post_id ),
            'shipview_status'  => $this->render_status( $post_id ),
            'shipview_eta'     => $this->render_eta( $post_id ),
            'shipview_client'  => $this->render_client( $post_id ),
            'shipview_dest'    => $this->render_destination( $post_id ),
            default            => null,
        };
    }

    /**
     * Declares which columns support sorting.
     *
     * @param array<string, string> $columns Existing sortable columns.
     * @return array<string, string>
     */
    public function sortable_columns( array $columns ): array {
        $columns['shipview_eta']    = 'shipview_eta';
        $columns['shipview_status'] = 'shipview_status';

        return $columns;
    }

    // -------------------------------------------------------------------------
    // Private renderers – each method outputs ONE column cell.
    // -------------------------------------------------------------------------

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_awb( int $post_id ): void {
        $awb = get_post_meta( $post_id, 'shipview_awb', true );
        echo '<code>' . esc_html( $awb ) . '</code>';
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_carrier( int $post_id ): void {
        echo esc_html( (string) get_post_meta( $post_id, 'shipview_carrier', true ) );
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_status( int $post_id ): void {
        $slug     = (string) get_post_meta( $post_id, 'shipview_status', true );
        $statuses = $this->config['statuses'];

        if ( ! isset( $statuses[ $slug ] ) ) {
            return;
        }

        $colour = self::STATUS_COLOURS[ $slug ] ?? '#aaa';

        printf(
            '<span style="color:%s;font-weight:600;">%s %s</span>',
            esc_attr( $colour ),
            esc_html( $statuses[ $slug ]['icon'] ),
            esc_html( __( $statuses[ $slug ]['label'], 'shipview' ) ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
        );
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_eta( int $post_id ): void {
        $eta = (string) get_post_meta( $post_id, 'shipview_eta', true );

        if ( '' === $eta ) {
            return;
        }

        $ts      = strtotime( $eta );
        $overdue = $ts < strtotime( 'today' );

        printf(
            '<span style="color:%s">%s</span>',
            $overdue ? '#e74c3c' : 'inherit',
            esc_html( date_i18n( get_option( 'date_format' ), $ts ) )
        );
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_client( int $post_id ): void {
        echo esc_html( (string) get_post_meta( $post_id, 'shipview_client', true ) );
    }

    /**
     * @param int $post_id Post ID.
     * @return void
     */
    private function render_destination( int $post_id ): void {
        echo esc_html( (string) get_post_meta( $post_id, 'shipview_destination', true ) );
    }
}
