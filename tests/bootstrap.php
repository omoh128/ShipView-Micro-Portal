<?php
/**
 * tests/bootstrap.php
 *
 * PHPUnit bootstrap. Loads Composer's autoloader (which also loads
 * Brain\Monkey stubs so we can test without a live WordPress install).
 */

declare( strict_types=1 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
