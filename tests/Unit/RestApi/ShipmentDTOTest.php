<?php
/**
 * tests/Unit/RestApi/ShipmentDTOTest.php
 *
 * Unit tests for ShipmentDTO.
 *
 * Demonstrates testing the DTO in isolation using Brain\Monkey
 * to stub the WordPress functions it calls.
 *
 * @package ShipView\Tests\Unit\RestApi
 */

declare( strict_types=1 );

namespace ShipView\Tests\Unit\RestApi;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use ShipView\RestApi\ShipmentDTO;
use WP_Post;

/**
 * Class ShipmentDTOTest
 */
class ShipmentDTOTest extends TestCase {

    /**
     * Set up Brain\Monkey before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Tear down Brain\Monkey after each test.
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Returns a minimal WP_Post stub with the given ID.
     *
     * @param int $id Post ID.
     * @return WP_Post
     */
    private function make_post( int $id ): WP_Post {
        $post            = new WP_Post( new \stdClass() );
        $post->ID        = $id;
        $post->post_type = 'shipment';

        return $post;
    }

    /**
     * @test
     * @covers \ShipView\RestApi\ShipmentDTO::from_post
     */
    public function it_maps_post_meta_to_dto_properties(): void {
        $post = $this->make_post( 99 );

        Functions\when( 'get_post_meta' )->justReturn(
            [
                'shipview_awb'         => [ '157-00000001' ],
                'shipview_carrier'     => [ 'DHL' ],
                'shipview_status'      => [ 'in_transit' ],
                'shipview_eta'         => [ '2099-12-31' ], // Far future – never overdue.
                'shipview_origin'      => [ 'Shanghai, CN' ],
                'shipview_destination' => [ 'Lagos, NG' ],
                'shipview_client'      => [ 'Acme Ltd' ],
                'shipview_weight'      => [ '120.5' ],
                'shipview_notes'       => [ 'On feeder vessel' ],
            ]
        );

        Functions\when( 'get_the_title' )->justReturn( 'Lagos Run 01' );
        Functions\when( 'get_option' )->justReturn( 'F j, Y' );
        Functions\when( 'date_i18n' )->returnArg( 2 );       // Return the timestamp as-is.
        Functions\when( 'get_the_modified_date' )->justReturn( '2025-08-01T10:00:00+01:00' );

        $dto = ShipmentDTO::from_post( $post );

        self::assertSame( 99, $dto->id );
        self::assertSame( 'Lagos Run 01', $dto->title );
        self::assertSame( '157-00000001', $dto->awb );
        self::assertSame( 'DHL', $dto->carrier );
        self::assertSame( 'in_transit', $dto->status );
        self::assertSame( 'Shanghai, CN', $dto->origin );
        self::assertSame( 'Lagos, NG', $dto->destination );
        self::assertSame( 'Acme Ltd', $dto->client );
        self::assertSame( '120.5', $dto->weight );
        self::assertSame( 'On feeder vessel', $dto->notes );
        self::assertFalse( $dto->overdue ); // Far future ETA.
    }

    /**
     * @test
     * @covers \ShipView\RestApi\ShipmentDTO::from_post
     */
    public function it_marks_past_eta_as_overdue_when_not_delivered(): void {
        $post = $this->make_post( 42 );

        Functions\when( 'get_post_meta' )->justReturn(
            [
                'shipview_status' => [ 'in_transit' ],
                'shipview_eta'    => [ '2000-01-01' ],  // Way in the past.
            ]
        );

        Functions\when( 'get_the_title' )->justReturn( 'Old Shipment' );
        Functions\when( 'get_option' )->justReturn( 'F j, Y' );
        Functions\when( 'date_i18n' )->returnArg( 2 );
        Functions\when( 'get_the_modified_date' )->justReturn( '' );

        $dto = ShipmentDTO::from_post( $post );

        self::assertTrue( $dto->overdue );
    }

    /**
     * @test
     * @covers \ShipView\RestApi\ShipmentDTO::from_post
     */
    public function it_does_not_mark_delivered_shipment_as_overdue(): void {
        $post = $this->make_post( 7 );

        Functions\when( 'get_post_meta' )->justReturn(
            [
                'shipview_status' => [ 'delivered' ],
                'shipview_eta'    => [ '2000-01-01' ],
            ]
        );

        Functions\when( 'get_the_title' )->justReturn( 'Delivered' );
        Functions\when( 'get_option' )->justReturn( 'F j, Y' );
        Functions\when( 'date_i18n' )->returnArg( 2 );
        Functions\when( 'get_the_modified_date' )->justReturn( '' );

        $dto = ShipmentDTO::from_post( $post );

        self::assertFalse( $dto->overdue );
    }

    /**
     * @test
     * @covers \ShipView\RestApi\ShipmentDTO::to_array
     */
    public function to_array_contains_all_expected_keys(): void {
        $post = $this->make_post( 1 );

        Functions\when( 'get_post_meta' )->justReturn( [] );
        Functions\when( 'get_the_title' )->justReturn( '' );
        Functions\when( 'get_option' )->justReturn( '' );
        Functions\when( 'date_i18n' )->justReturn( '' );
        Functions\when( 'get_the_modified_date' )->justReturn( '' );

        $array = ShipmentDTO::from_post( $post )->to_array();

        $expected_keys = [
            'id', 'title', 'awb', 'carrier', 'status',
            'eta', 'eta_human', 'overdue', 'origin', 'destination',
            'client', 'weight', 'notes', 'updated',
        ];

        foreach ( $expected_keys as $key ) {
            self::assertArrayHasKey( $key, $array, "Missing key: {$key}" );
        }
    }
}
