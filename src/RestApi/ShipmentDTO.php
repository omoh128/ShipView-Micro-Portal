<?php
/**
 * src/RestApi/ShipmentDTO.php
 *
 * An immutable Value Object that represents a single shipment as seen
 * by the REST API and the frontend grid.
 *
 * Benefits of a DTO over a raw array:
 *  – IDE autocompletion / type safety on every property.
 *  – A single canonical place that defines the REST response shape.
 *  – Easy to unit-test without touching the database.
 *
 * @package ShipView\RestApi
 */

declare( strict_types=1 );

namespace ShipView\RestApi;

use WP_Post;

/**
 * Class ShipmentDTO
 *
 * @since 1.0.0
 */
final class ShipmentDTO {

    /**
     * Statuses that are considered "terminal" (never overdue).
     *
     * @var string[]
     */
    private const TERMINAL_STATUSES = [ 'delivered', 'returned' ];

    public readonly int    $id;
    public readonly string $title;
    public readonly string $awb;
    public readonly string $carrier;
    public readonly string $status;
    public readonly string $eta;
    public readonly string $eta_human;
    public readonly bool   $overdue;
    public readonly string $origin;
    public readonly string $destination;
    public readonly string $client;
    public readonly string $weight;
    public readonly string $notes;
    public readonly string $updated;

    private function __construct(
        int    $id,
        string $title,
        string $awb,
        string $carrier,
        string $status,
        string $eta,
        string $eta_human,
        bool   $overdue,
        string $origin,
        string $destination,
        string $client,
        string $weight,
        string $notes,
        string $updated,
    ) {
        $this->id          = $id;
        $this->title       = $title;
        $this->awb         = $awb;
        $this->carrier     = $carrier;
        $this->status      = $status;
        $this->eta         = $eta;
        $this->eta_human   = $eta_human;
        $this->overdue     = $overdue;
        $this->origin      = $origin;
        $this->destination = $destination;
        $this->client      = $client;
        $this->weight      = $weight;
        $this->notes       = $notes;
        $this->updated     = $updated;
    }

    /**
     * Constructs a DTO from a WP_Post, reading all required meta in one call.
     *
     * @param WP_Post $post WordPress post object of type `shipment`.
     * @return self
     */
    public static function from_post( WP_Post $post ): self {
        $meta = get_post_meta( $post->ID );

        /**
         * Helper: safely read a meta value from the bulk-loaded array.
         *
         * @param string $key Meta key.
         * @return string
         */
        $get = static fn( string $key ): string =>
            isset( $meta[ $key ][0] ) ? (string) $meta[ $key ][0] : '';

        $eta_raw = $get( 'shipview_eta' );
        $eta_ts  = $eta_raw ? strtotime( $eta_raw ) : false;
        $status  = $get( 'shipview_status' );

        $overdue = $eta_ts !== false
            && $eta_ts < strtotime( 'today' )
            && ! in_array( $status, self::TERMINAL_STATUSES, true );

        return new self(
            id:          $post->ID,
            title:       get_the_title( $post ),
            awb:         $get( 'shipview_awb' ),
            carrier:     $get( 'shipview_carrier' ),
            status:      $status,
            eta:         $eta_raw,
            eta_human:   $eta_ts ? date_i18n( get_option( 'date_format' ), $eta_ts ) : '',
            overdue:     $overdue,
            origin:      $get( 'shipview_origin' ),
            destination: $get( 'shipview_destination' ),
            client:      $get( 'shipview_client' ),
            weight:      $get( 'shipview_weight' ),
            notes:       $get( 'shipview_notes' ),
            updated:     get_the_modified_date( 'c', $post ),
        );
    }

    /**
     * Serialises the DTO to a plain array suitable for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'awb'         => $this->awb,
            'carrier'     => $this->carrier,
            'status'      => $this->status,
            'eta'         => $this->eta,
            'eta_human'   => $this->eta_human,
            'overdue'     => $this->overdue,
            'origin'      => $this->origin,
            'destination' => $this->destination,
            'client'      => $this->client,
            'weight'      => $this->weight,
            'notes'       => $this->notes,
            'updated'     => $this->updated,
        ];
    }
}
