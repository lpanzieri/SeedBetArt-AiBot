<?php

declare( strict_types=1 );

namespace BSP_V2_Tests\Unit;

use BSP_V2_Cache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BSP_V2_Cache: get / set / delete / timestamp tracking.
 */
final class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        bsp_test_reset_stores();
    }

    public function testGetReturnsFalseOnCacheMiss(): void
    {
        $this->assertFalse( BSP_V2_Cache::get( 'nonexistent_key' ) );
    }

    public function testSetAndGetRoundTrip(): void
    {
        BSP_V2_Cache::set( 'test_key', [ 'value' => 42 ] );
        $this->assertSame( [ 'value' => 42 ], BSP_V2_Cache::get( 'test_key' ) );
    }

    public function testSetAndGetStringValue(): void
    {
        BSP_V2_Cache::set( 'str_key', 'hello world' );
        $this->assertSame( 'hello world', BSP_V2_Cache::get( 'str_key' ) );
    }

    public function testDeleteRemovesValue(): void
    {
        BSP_V2_Cache::set( 'del_key', 'data' );
        BSP_V2_Cache::delete( 'del_key' );
        $this->assertFalse( BSP_V2_Cache::get( 'del_key' ) );
    }

    public function testDeleteIsIdempotent(): void
    {
        BSP_V2_Cache::delete( 'ghost_key' );
        $this->assertFalse( BSP_V2_Cache::get( 'ghost_key' ) );
    }

    public function testSetStoresUpdatedAtTimestamp(): void
    {
        $before = time();
        BSP_V2_Cache::set( 'ts_key', 'value' );
        $after = time();

        $ts = BSP_V2_Cache::get_updated_at( 'ts_key' );
        $this->assertGreaterThanOrEqual( $before, $ts );
        $this->assertLessThanOrEqual( $after, $ts );
    }

    public function testGetUpdatedAtReturnsZeroForUnknownKey(): void
    {
        $this->assertSame( 0, BSP_V2_Cache::get_updated_at( 'no_such_key' ) );
    }

    public function testOverwriteUpdatesValue(): void
    {
        BSP_V2_Cache::set( 'ow_key', 'first' );
        BSP_V2_Cache::set( 'ow_key', 'second' );
        $this->assertSame( 'second', BSP_V2_Cache::get( 'ow_key' ) );
    }

    public function testMultipleKeysAreIndependent(): void
    {
        BSP_V2_Cache::set( 'key_a', 'alpha' );
        BSP_V2_Cache::set( 'key_b', 'beta' );

        $this->assertSame( 'alpha', BSP_V2_Cache::get( 'key_a' ) );
        $this->assertSame( 'beta',  BSP_V2_Cache::get( 'key_b' ) );

        BSP_V2_Cache::delete( 'key_a' );
        $this->assertFalse( BSP_V2_Cache::get( 'key_a' ) );
        $this->assertSame( 'beta', BSP_V2_Cache::get( 'key_b' ) );
    }
}
