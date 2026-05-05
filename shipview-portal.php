<?php
/**
 * Plugin Name:       ShipView Micro-Portal
 * Plugin URI:        https://omomoh.com/shipview
 * Description:       A dedicated shipment tracking control-room portal with a live-refresh grid, custom post type, and REST API endpoint.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Omomoh Agiogu
 * Author URI:        https://omomoh.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shipview
 * Domain Path:       /languages
 *
 * @package ShipView
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// Autoloader – prefer Composer; fall back gracefully so WP can show the error.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    add_action(
        'admin_notices',
        static function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__(
                'ShipView: Composer dependencies are missing. Run <code>composer install</code> inside the plugin directory.',
                'shipview'
            );
            echo '</p></div>';
        }
    );
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Returns the single Plugin instance (lazy-initialised).
 *
 * @return \ShipView\Plugin
 */
function shipview(): \ShipView\Plugin {
    static $instance = null;

    if ( null === $instance ) {
        $config   = require __DIR__ . '/config/plugin.php';
        $instance = new \ShipView\Plugin( __FILE__, $config );
    }

    return $instance;
}

// Boot the plugin.
shipview()->boot();