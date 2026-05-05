<?php
/**
 * src/Support/Registerable.php
 *
 * Every service class must implement this interface so the Plugin
 * bootstrap can call register() on each one uniformly.
 *
 * @package ShipView\Support
 */

declare( strict_types=1 );

namespace ShipView\Support;

/**
 * Interface Registerable
 *
 * @since 1.0.0
 */
interface Registerable {

    /**
     * Hook this service into WordPress.
     *
     * This method should only call add_action() / add_filter() – it must
     * not perform any side-effects (DB reads, HTTP calls, output) directly.
     *
     * @return void
     */
    public function register(): void;
}
