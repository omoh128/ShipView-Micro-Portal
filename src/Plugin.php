<?php
/**
 * src/Plugin.php
 *
 * Central bootstrap / service-container.  Responsible for:
 *   – Defining path/URL constants once.
 *   – Instantiating every service class.
 *   – Wiring activation / deactivation hooks.
 *
 * Nothing in this class echoes HTML or talks to the database directly;
 * that responsibility belongs to the individual service classes.
 *
 * @package ShipView
 */

declare( strict_types=1 );

namespace ShipView;

use ShipView\Admin\AdminColumns;
use ShipView\Admin\SettingsPage;
use ShipView\Frontend\AssetLoader;
use ShipView\Frontend\TemplateLoader;
use ShipView\PostType\ShipmentPostType;
use ShipView\PostType\MetaBox;
use ShipView\RestApi\ShipmentController;
use ShipView\Support\Activator;

/**
 * Class Plugin
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * Absolute path to the plugin root (with trailing slash).
     *
     * @var string
     */
    private string $dir;

    /**
     * Public URL to the plugin root (with trailing slash).
     *
     * @var string
     */
    private string $url;

    /**
     * Merged configuration array from config/plugin.php.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Whether boot() has already been called.
     *
     * @var bool
     */
    private bool $booted = false;

    /**
     * @param string               $plugin_file Absolute path to the main plugin file.
     * @param array<string, mixed> $config      Merged config from config/plugin.php.
     */
    public function __construct( string $plugin_file, array $config ) {
        $this->dir    = plugin_dir_path( $plugin_file );
        $this->url    = plugin_dir_url( $plugin_file );
        $this->config = $config;

        register_activation_hook(
            $plugin_file,
            [ new Activator( $config ), 'activate' ]
        );

        register_deactivation_hook(
            $plugin_file,
            'flush_rewrite_rules'
        );
    }

    /**
     * Registers all services with WordPress hooks.
     * Idempotent – safe to call multiple times.
     *
     * @return void
     */
    public function boot(): void {
        if ( $this->booted ) {
            return;
        }
        $this->booted = true;

        $this->load_textdomain();
        $this->register_services();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Loads the plugin text domain for translations.
     *
     * @return void
     */
    private function load_textdomain(): void {
        add_action(
            'init',
            function (): void {
                load_plugin_textdomain(
                    $this->config['text_domain'],
                    false,
                    dirname( plugin_basename( $this->dir . 'shipview-portal.php' ) ) . '/languages'
                );
            }
        );
    }

    /**
     * Instantiates every service class and calls its register() method.
     *
     * @return void
     */
    private function register_services(): void {
        $services = [
            new ShipmentPostType( $this->config ),
            new MetaBox( $this->config ),
            new ShipmentController( $this->config ),
            new AssetLoader( $this->url, $this->config ),
            new TemplateLoader( $this->dir, $this->config ),
        ];

        // Admin-only services.
        if ( is_admin() ) {
            $services[] = new AdminColumns( $this->config );
            $services[] = new SettingsPage( $this->config );
        }

        foreach ( $services as $service ) {
            $service->register();
        }
    }

    // -------------------------------------------------------------------------
    // Accessors (used by services that need path / url / config)
    // -------------------------------------------------------------------------

    /**
     * @return string Plugin root directory with trailing slash.
     */
    public function dir(): string {
        return $this->dir;
    }

    /**
     * @return string Plugin root URL with trailing slash.
     */
    public function url(): string {
        return $this->url;
    }

    /**
     * @param string $key     Config key.
     * @param mixed  $default Fallback when key is absent.
     * @return mixed
     */
    public function config( string $key, mixed $default = null ): mixed {
        return $this->config[ $key ] ?? $default;
    }
}
