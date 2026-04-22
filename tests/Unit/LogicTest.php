<?php

declare( strict_types=1 );

namespace BSP_V2_Tests\Unit;

use BSP_V2_Client;
use BSP_V2_Logic;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Tests for BSP_V2_Logic: EV calculation and betting suggestion filtering.
 */
final class LogicTest extends TestCase
{
    protected function setUp(): void
    {
        bsp_test_reset_stores();
    }

    // -----------------------------------------------------------------------
    // calculate_ev
    // -----------------------------------------------------------------------

    public function testCalculateEvReturnsExpectedValue(): void
    {
        // EV = (prob * odds - 1) * 100 = (0.5 * 2.5 - 1) * 100 = 25.00
        $this->assertSame( 25.0, $this->logic()->calculate_ev( 2.5, 0.5 ) );
    }

    public function testCalculateEvRoundsToTwoDecimals(): void
    {
        // EV = (1/3 * 3.5 - 1) * 100 = 16.666... -> 16.67
        $this->assertSame( 16.67, $this->logic()->calculate_ev( 3.5, 1 / 3 ) );
    }

    public function testCalculateEvReturnsZeroWhenOddsAtOrBelowOne(): void
    {
        $l = $this->logic();
        $this->assertSame( 0, $l->calculate_ev( 1.0, 0.5 ) );
        $this->assertSame( 0, $l->calculate_ev( 0.9, 0.5 ) );
    }

    public function testCalculateEvReturnsZeroWhenProbabilityIsZero(): void
    {
        $this->assertSame( 0, $this->logic()->calculate_ev( 2.0, 0.0 ) );
    }

    public function testCalculateEvReturnsZeroWhenProbabilityIsOne(): void
    {
        $this->assertSame( 0, $this->logic()->calculate_ev( 2.0, 1.0 ) );
    }

    public function testCalculateEvReturnsNegativeEv(): void
    {
        // EV = (0.2 * 2.0 - 1) * 100 = -60.00
        $this->assertSame( -60.0, $this->logic()->calculate_ev( 2.0, 0.2 ) );
    }

    // -----------------------------------------------------------------------
    // get_value_bets
    // -----------------------------------------------------------------------

    public function testGetValueBetsPassesThroughWpError(): void
    {
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => new WP_Error( 'e', 'm' ) ] ) ) )
            ->get_value_bets();
        $this->assertInstanceOf( WP_Error::class, $result );
    }

    public function testGetValueBetsReturnsMatchingBet(): void
    {
        $this->setVbParams( 5, 1.5, 10.0, 0 );
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->oddsFixture( 3.0 ) ] ) ) )
            ->get_value_bets();
        $this->assertCount( 1, $result );
        $this->assertSame( 'Home FC', $result[0]['home'] );
        $this->assertSame( 'Away FC', $result[0]['away'] );
    }

    public function testGetValueBetsExcludesBelowMinEv(): void
    {
        $this->setVbParams( 99, 1.5, 10.0, 0 );
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->oddsFixture( 1.6 ) ] ) ) )
            ->get_value_bets();
        $this->assertCount( 0, $result );
    }

    public function testGetValueBetsExcludesOutsideOddsRange(): void
    {
        $this->setVbParams( 0, 2.0, 3.0, 0 );
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->oddsFixture( 1.5 ) ] ) ) )
            ->get_value_bets();
        $this->assertCount( 0, $result );
    }

    public function testGetValueBetsSortsByEvDescending(): void
    {
        $this->setVbParams( 0, 1.5, 10.0, 0 );
        $data   = [ 'data' => [
            $this->event( 'A', 'B', 2.0 ),
            $this->event( 'C', 'D', 4.0 ),
            $this->event( 'E', 'F', 3.0 ),
        ] ];
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $data ] ) ) )->get_value_bets();
        $this->assertGreaterThan( $result[1]['ev'], $result[0]['ev'] );
        $this->assertGreaterThan( $result[2]['ev'], $result[1]['ev'] );
    }

    public function testGetValueBetsRespectsLimit(): void
    {
        $this->setVbParams( 0, 1.5, 10.0, 0 );
        $data = [ 'data' => array_map(
            fn( $i ) => $this->event( "H{$i}", "A{$i}", 2.5 ),
            range( 1, 10 )
        ) ];
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $data ] ) ) )
            ->get_value_bets( limit: 3 );
        $this->assertCount( 3, $result );
    }

    // -----------------------------------------------------------------------
    // get_ltd_suggestions
    // -----------------------------------------------------------------------

    public function testGetLtdPassesThroughWpError(): void
    {
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => new WP_Error( 'e', 'm' ) ] ) ) )
            ->get_ltd_suggestions();
        $this->assertInstanceOf( WP_Error::class, $result );
    }

    public function testGetLtdIncludesMatchWithLowDrawProbability(): void
    {
        $this->setLtdParams( 30, 2.5 );
        // draw_odds=4.0 -> draw_prob=25% <= 30, draw_odds >= 2.5 -> included
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->ltdFixture( 4.0 ) ] ) ) )
            ->get_ltd_suggestions();
        $this->assertCount( 1, $result );
        $this->assertArrayHasKey( 'draw_probability', $result[0] );
    }

    public function testGetLtdExcludesMatchWithHighDrawProbability(): void
    {
        $this->setLtdParams( 20, 2.5 );
        // draw_odds=2.8 -> draw_prob~35.7% > 20 -> excluded
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->ltdFixture( 2.8 ) ] ) ) )
            ->get_ltd_suggestions();
        $this->assertCount( 0, $result );
    }

    public function testGetLtdExcludesMatchBelowMinDrawOdds(): void
    {
        $this->setLtdParams( 50, 5.0 );
        // draw_odds=3.0 < 5.0 -> excluded
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->ltdFixture( 3.0 ) ] ) ) )
            ->get_ltd_suggestions();
        $this->assertCount( 0, $result );
    }

    // -----------------------------------------------------------------------
    // get_under_25_suggestions
    // -----------------------------------------------------------------------

    public function testGetUnder25PassesThroughWpError(): void
    {
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => new WP_Error( 'e', 'm' ) ] ) ) )
            ->get_under_25_suggestions();
        $this->assertInstanceOf( WP_Error::class, $result );
    }

    public function testGetUnder25IncludesMatchWithLowXg(): void
    {
        $this->setUnder25Params( 2.3, 1.5, 0 );
        // under_25_odds=1.8 -> included
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->u25Fixture( 1.8 ) ] ) ) )
            ->get_under_25_suggestions();
        $this->assertCount( 1, $result );
        $this->assertArrayHasKey( 'estimated_xg', $result[0] );
    }

    public function testGetUnder25ExcludesMatchBelowMinOdds(): void
    {
        $this->setUnder25Params( 2.3, 2.0, 0 );
        // under_25_odds=1.8 < 2.0 -> excluded
        $result = ( new BSP_V2_Logic( $this->client( [ 'fetch_odds' => $this->u25Fixture( 1.8 ) ] ) ) )
            ->get_under_25_suggestions();
        $this->assertCount( 0, $result );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function logic(): BSP_V2_Logic
    {
        return new BSP_V2_Logic( $this->client( [] ) );
    }

    /** @param array<string, mixed> $returns */
    private function client( array $returns ): BSP_V2_Client&MockObject
    {
        $mock = $this->createMock( BSP_V2_Client::class );
        foreach ( $returns as $method => $ret ) {
            $mock->method( $method )->willReturn( $ret );
        }
        if ( ! isset( $returns['fetch_team_by_name'] ) ) {
            $mock->method( 'fetch_team_by_name' )->willReturn( null );
        }
        return $mock;
    }

    private function setVbParams( float $minEv, float $minOdds, float $maxOdds, float $minConf ): void
    {
        bsp_test_set_option( 'bsp_v2_vb_min_ev',         $minEv );
        bsp_test_set_option( 'bsp_v2_vb_min_odds',       $minOdds );
        bsp_test_set_option( 'bsp_v2_vb_max_odds',       $maxOdds );
        bsp_test_set_option( 'bsp_v2_vb_min_confidence', $minConf );
    }

    private function setLtdParams( float $maxDrawProb, float $minDrawOdds ): void
    {
        bsp_test_set_option( 'bsp_v2_ltd_max_draw_prob',  $maxDrawProb );
        bsp_test_set_option( 'bsp_v2_ltd_min_draw_odds',  $minDrawOdds );
        bsp_test_set_option( 'bsp_v2_ltd_form_weight',    75 );
        bsp_test_set_option( 'bsp_v2_ltd_home_advantage', 1 );
    }

    private function setUnder25Params( float $maxXg, float $minOdds, float $minConf ): void
    {
        bsp_test_set_option( 'bsp_v2_under_max_xg',         $maxXg );
        bsp_test_set_option( 'bsp_v2_under_min_odds',       $minOdds );
        bsp_test_set_option( 'bsp_v2_under_min_confidence', $minConf );
        bsp_test_set_option( 'bsp_v2_under_form_weight',    70 );
    }

    private function oddsFixture( float $homeOdds ): array
    {
        return [ 'data' => [ $this->event( 'Home FC', 'Away FC', $homeOdds ) ] ];
    }

    private function ltdFixture( float $drawOdds ): array
    {
        return [ 'data' => [ $this->event( 'Home FC', 'Away FC', 2.5, $drawOdds ) ] ];
    }

    private function u25Fixture( float $under25Odds ): array
    {
        return [ 'data' => [ [
            'home_team'     => 'Home FC',
            'away_team'     => 'Away FC',
            'commence_time' => '2026-04-25T15:00:00Z',
            'bookmakers'    => [ [ 'markets' => [ [
                'key'      => 'totals',
                'outcomes' => [ [
                    'name'        => 'Under',
                    'description' => '2.5',
                    'price'       => $under25Odds,
                ] ],
            ] ] ] ],
        ] ] ];
    }

    private function event(
        string $home,
        string $away,
        float  $homeOdds = 2.5,
        float  $drawOdds = 3.5,
        float  $awayOdds = 2.8,
    ): array {
        return [
            'home_team'     => $home,
            'away_team'     => $away,
            'commence_time' => '2026-04-25T15:00:00Z',
            'bookmakers'    => [ [ 'markets' => [ [
                'key'      => 'h2h',
                'outcomes' => [
                    [ 'name' => $home,  'price' => $homeOdds ],
                    [ 'name' => 'Draw', 'price' => $drawOdds ],
                    [ 'name' => $away,  'price' => $awayOdds ],
                ],
            ] ] ] ],
        ];
    }
}
