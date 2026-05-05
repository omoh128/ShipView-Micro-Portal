<?php
/**
 * src/Frontend/AssetLoader.php
 *
 * Enqueues CSS and JS only on the ShipView tracking page.
 *
 * @package ShipView\Frontend
 */

declare( strict_types=1 );

namespace ShipView\Frontend;

use ShipView\Support\Registerable;

/**
 * Class AssetLoader
 *
 * @since 1.0.0
 */
final class AssetLoader implements Registerable {

    /**
     * @var string Plugin public URL with trailing slash.
     */
    private string $url;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param string               $url    Plugin root URL.
     * @param array<string, mixed> $config Plugin configuration.
     */
    public function __construct( string $url, array $config ) {
        $this->url    = $url;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /**
     * Enqueues plugin assets when the current page is the tracking portal.
     *
     * @return void
     */
    public function enqueue(): void {
        if ( ! $this->is_tracking_page() ) {
            return;
        }

        $version = $this->config['version'];

        wp_enqueue_style(
            'shipview-style',
            $this->url . 'assets/css/shipview.css',
            [],
            $version
        );

        wp_enqueue_script(
            'shipview-script',
            $this->url . 'assets/js/shipview.js',
            [],
            $version,
            true  // Load in footer.
        );

        wp_localize_script(
            'shipview-script',
            'ShipViewConfig',
            [
                'restUrl'   => esc_url_raw( rest_url( $this->config['rest_namespace'] . '/shipments' ) ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'refreshMs' => (int) $this->config['refresh_ms'],
                'version'   => $version,
            ]
        );
    }

    /**
     * Returns true when the current request is for the tracking portal page.
     *
     * @return bool
     */
    private function is_tracking_page(): bool {
        $page_id = (int) get_option( 'shipview_page_id', 0 );
        return $page_id > 0 && is_page( $page_id );
    }
}
