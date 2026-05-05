<?php
/**
 * src/Admin/SettingsPage.php
 *
 * Registers the Tools → ShipView settings / info page in wp-admin.
 *
 * @package ShipView\Admin
 */

declare( strict_types=1 );

namespace ShipView\Admin;

use ShipView\Support\Registerable;

/**
 * Class SettingsPage
 *
 * @since 1.0.0
 */
final class SettingsPage implements Registerable {

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
        add_action( 'admin_menu', [ $this, 'add_page' ] );
    }

    /**
     * Registers the submenu page.
     *
     * @return void
     */
    public function add_page(): void {
        add_submenu_page(
            'tools.php',
            __( 'ShipView Settings', 'shipview' ),
            __( 'ShipView', 'shipview' ),
            'manage_options',
            'shipview-settings',
            [ $this, 'render' ]
        );
    }

    /**
     * Renders the settings page HTML.
     *
     * @return void
     */
    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $page_id  = (int) get_option( 'shipview_page_id', 0 );
        $page_url = $page_id ? get_permalink( $page_id ) : '';
        $rest_url = rest_url( $this->config['rest_namespace'] . '/shipments' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'ShipView Micro-Portal', 'shipview' ); ?></h1>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Tracking Portal URL', 'shipview' ); ?></th>
                    <td>
                        <?php if ( $page_url ) : ?>
                            <a href="<?php echo esc_url( $page_url ); ?>" target="_blank">
                                <?php echo esc_url( $page_url ); ?>
                            </a>
                        <?php else : ?>
                            <em>
                                <?php
                                esc_html_e(
                                    'Page not found. Try deactivating and reactivating the plugin.',
                                    'shipview'
                                );
                                ?>
                            </em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'REST API Endpoint', 'shipview' ); ?></th>
                    <td><code><?php echo esc_url( $rest_url ); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Grid Auto-Refresh', 'shipview' ); ?></th>
                    <td>
                        <?php
                        printf(
                            /* translators: %d: refresh interval in minutes */
                            esc_html__( 'Every %d minutes (client-side fetch)', 'shipview' ),
                            (int) ( $this->config['refresh_ms'] / 60000 )
                        );
                        ?>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Quick Links', 'shipview' ); ?></h2>
            <p>
                <a class="button button-primary"
                   href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $this->config['post_type'] ) ); ?>">
                    <?php esc_html_e( 'Manage Shipments', 'shipview' ); ?>
                </a>
                &nbsp;
                <a class="button"
                   href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $this->config['post_type'] ) ); ?>">
                    <?php esc_html_e( 'Add New Shipment', 'shipview' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
