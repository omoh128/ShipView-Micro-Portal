<?php
/**
 * config/plugin.php
 *
 * Single source-of-truth for plugin-wide configuration values.
 * Consumed by the Plugin bootstrap and individual service classes.
 *
 * @package ShipView
 */

declare( strict_types=1 );

return [

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    */
    'version'     => '1.0.0',
    'text_domain' => 'shipview',
    'prefix'      => 'shipview',

    /*
    |--------------------------------------------------------------------------
    | Custom Post Type
    |--------------------------------------------------------------------------
    */
    'post_type'   => 'shipment',

    /*
    |--------------------------------------------------------------------------
    | REST API
    |--------------------------------------------------------------------------
    */
    'rest_namespace' => 'shipview/v1',

    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    | refresh_ms – how often (milliseconds) the JS grid polls the REST API.
    */
    'refresh_ms'     => 30 * 60 * 1000,

    /*
    |--------------------------------------------------------------------------
    | Shipment statuses
    |--------------------------------------------------------------------------
    | Each entry: slug => [ icon, i18n-label ]
    | Labels are plain strings here; classes that output them must wrap with
    | __() / _x() so the .pot scanner picks them up.
    */
    'statuses' => [
        'pending'     => [ 'icon' => '⏳', 'label' => 'Pending'          ],
        'in_transit'  => [ 'icon' => '✈',  'label' => 'In Transit'       ],
        'customs'     => [ 'icon' => '🛃', 'label' => 'Customs'          ],
        'out_for_del' => [ 'icon' => '🚚', 'label' => 'Out for Delivery'  ],
        'delivered'   => [ 'icon' => '✔',  'label' => 'Delivered'        ],
        'exception'   => [ 'icon' => '⚠',  'label' => 'Exception'        ],
        'returned'    => [ 'icon' => '↩',  'label' => 'Returned'         ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta fields
    |--------------------------------------------------------------------------
    | Each entry: meta_key => sanitise_callback
    */
    'meta_fields' => [
        'shipview_awb'         => 'sanitize_text_field',
        'shipview_carrier'     => 'sanitize_text_field',
        'shipview_status'      => 'sanitize_text_field',
        'shipview_eta'         => 'sanitize_text_field',
        'shipview_origin'      => 'sanitize_text_field',
        'shipview_destination' => 'sanitize_text_field',
        'shipview_client'      => 'sanitize_text_field',
        'shipview_weight'      => 'sanitize_text_field',
        'shipview_notes'       => 'sanitize_textarea_field',
    ],

];
