<?php
/**
 * src/PostType/MetaBox.php
 *
 * Registers, renders, and saves the Shipment Details meta box.
 *
 * @package ShipView\PostType
 */

declare( strict_types=1 );

namespace ShipView\PostType;

use ShipView\Support\Registerable;
use WP_Post;

/**
 * Class MetaBox
 *
 * @since 1.0.0
 */
final class MetaBox implements Registerable {

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
        add_action( 'add_meta_boxes', [ $this, 'add' ] );
        add_action( 'save_post_' . $this->config['post_type'], [ $this, 'save' ] );
        add_action( 'admin_head', [ $this, 'inline_styles' ] );
    }

    /**
     * Registers the meta box.
     *
     * @return void
     */
    public function add(): void {
        add_meta_box(
            'shipview_details',
            __( 'Shipment Details', 'shipview' ),
            [ $this, 'render' ],
            $this->config['post_type'],
            'normal',
            'high'
        );
    }

    /**
     * Renders the meta box HTML.
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public function render( WP_Post $post ): void {
        wp_nonce_field( 'shipview_save_meta', 'shipview_nonce' );

        $data = $this->get_field_values( $post->ID );
        ?>
        <div class="shipview-meta-grid">

            <div>
                <label for="shipview_awb"><?php esc_html_e( 'AWB / Tracking Number', 'shipview' ); ?></label>
                <input
                    type="text"
                    id="shipview_awb"
                    name="shipview_awb"
                    value="<?php echo esc_attr( $data['shipview_awb'] ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. 157-12345678', 'shipview' ); ?>"
                />
            </div>

            <div>
                <label for="shipview_carrier"><?php esc_html_e( 'Carrier', 'shipview' ); ?></label>
                <input
                    type="text"
                    id="shipview_carrier"
                    name="shipview_carrier"
                    value="<?php echo esc_attr( $data['shipview_carrier'] ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. DHL, FedEx, UPS', 'shipview' ); ?>"
                />
            </div>

            <div>
                <label for="shipview_status"><?php esc_html_e( 'Status', 'shipview' ); ?></label>
                <select id="shipview_status" name="shipview_status">
                    <?php foreach ( $this->config['statuses'] as $value => $meta ) : ?>
                        <option
                            value="<?php echo esc_attr( $value ); ?>"
                            <?php selected( $data['shipview_status'], $value ); ?>
                        >
                            <?php
                            echo esc_html(
                                $meta['icon'] . ' ' . __( $meta['label'], 'shipview' ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                            );
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="shipview_eta"><?php esc_html_e( 'Expected Delivery', 'shipview' ); ?></label>
                <input
                    type="date"
                    id="shipview_eta"
                    name="shipview_eta"
                    value="<?php echo esc_attr( $data['shipview_eta'] ); ?>"
                />
            </div>

            <div>
                <label for="shipview_origin"><?php esc_html_e( 'Origin', 'shipview' ); ?></label>
                <input
                    type="text"
                    id="shipview_origin"
                    name="shipview_origin"
                    value="<?php echo esc_attr( $data['shipview_origin'] ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. Shanghai, CN', 'shipview' ); ?>"
                />
            </div>

            <div>
                <label for="shipview_destination"><?php esc_html_e( 'Destination', 'shipview' ); ?></label>
                <input
                    type="text"
                    id="shipview_destination"
                    name="shipview_destination"
                    value="<?php echo esc_attr( $data['shipview_destination'] ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. Lagos, NG', 'shipview' ); ?>"
                />
            </div>

            <div>
                <label for="shipview_client"><?php esc_html_e( 'Client / Consignee', 'shipview' ); ?></label>
                <input
                    type="text"
                    id="shipview_client"
                    name="shipview_client"
                    value="<?php echo esc_attr( $data['shipview_client'] ); ?>"
                />
            </div>

            <div>
                <label for="shipview_weight"><?php esc_html_e( 'Weight (kg)', 'shipview' ); ?></label>
                <input
                    type="number"
                    step="0.1"
                    min="0"
                    id="shipview_weight"
                    name="shipview_weight"
                    value="<?php echo esc_attr( $data['shipview_weight'] ); ?>"
                />
            </div>

            <div class="shipview-meta-grid__full">
                <label for="shipview_notes"><?php esc_html_e( 'Notes / Last Update', 'shipview' ); ?></label>
                <textarea id="shipview_notes" name="shipview_notes" rows="3"><?php echo esc_textarea( $data['shipview_notes'] ); ?></textarea>
            </div>

        </div>
        <?php
    }

    /**
     * Saves the meta box fields after validating nonce and capability.
     *
     * @param int $post_id Post ID being saved.
     * @return void
     */
    public function save( int $post_id ): void {
        if ( ! $this->is_valid_save_request( $post_id ) ) {
            return;
        }

        foreach ( $this->config['meta_fields'] as $key => $sanitize_callback ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in is_valid_save_request().
            if ( ! isset( $_POST[ $key ] ) ) {
                continue;
            }

            update_post_meta(
                $post_id,
                $key,
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $sanitize_callback( wp_unslash( $_POST[ $key ] ) )
            );
        }
    }

    /**
     * Outputs scoped admin CSS for the meta box grid.
     * Using admin_head keeps the styles out of the front-end.
     *
     * @return void
     */
    public function inline_styles(): void {
        $screen = get_current_screen();

        if ( ! $screen || $this->config['post_type'] !== $screen->post_type ) {
            return;
        }
        ?>
        <style>
            .shipview-meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                padding: 8px 0;
            }
            .shipview-meta-grid label {
                display: block;
                font-weight: 600;
                margin-bottom: 4px;
                color: #1d2327;
            }
            .shipview-meta-grid input,
            .shipview-meta-grid select,
            .shipview-meta-grid textarea {
                width: 100%;
                padding: 6px 8px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            .shipview-meta-grid__full {
                grid-column: 1 / -1;
            }
        </style>
        <?php
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the current meta values for all fields.
     *
     * @param int $post_id Post ID.
     * @return array<string, string>
     */
    private function get_field_values( int $post_id ): array {
        $values = [];

        foreach ( array_keys( $this->config['meta_fields'] ) as $key ) {
            $values[ $key ] = (string) get_post_meta( $post_id, $key, true );
        }

        return $values;
    }

    /**
     * Returns true when the current save request is legitimate.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    private function is_valid_save_request( int $post_id ): bool {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }

        if ( ! isset( $_POST['shipview_nonce'] ) ) {
            return false;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shipview_nonce'] ) ), 'shipview_save_meta' ) ) {
            return false;
        }

        return current_user_can( 'edit_post', $post_id );
    }
}
