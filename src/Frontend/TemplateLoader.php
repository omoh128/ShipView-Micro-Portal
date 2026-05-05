<?php
/**
 * src/Frontend/TemplateLoader.php
 *
 * Intercepts the WordPress template hierarchy for the tracking page
 * and returns the plugin's own standalone template.
 *
 * @package ShipView\Frontend
 */

declare( strict_types=1 );

namespace ShipView\Frontend;

use ShipView\Support\Registerable;

/**
 * Class TemplateLoader
 *
 * @since 1.0.0
 */
final class TemplateLoader implements Registerable {

    /**
     * @var string Plugin root directory with trailing slash.
     */
    private string $dir;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param string               $dir    Plugin root directory.
     * @param array<string, mixed> $config Plugin configuration.
     */
    public function __construct( string $dir, array $config ) {
        $this->dir    = $dir;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void {
        add_filter( 'template_include', [ $this, 'override_template' ] );
    }

    /**
     * Replaces the theme template with the plugin template for the tracking page.
     *
     * @param string $template Resolved template file path.
     * @return string
     */
    public function override_template( string $template ): string {
        if ( ! $this->is_tracking_page() ) {
            return $template;
        }

        $plugin_template = $this->dir . 'templates/tracking-page.php';

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
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
