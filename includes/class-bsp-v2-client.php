<?php
/**
 * API Client for external services
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Client {
    
    private $odds_api_key;
    private $football_api_key;
    private $user_agent = 'Betting-Signals-Plus-V2/0.2';
    private $default_odds_bookmakers = ['Bet365', 'Unibet', 'Pinnacle', 'Betfair', 'William Hill', 'SingBet'];
    
    public function __construct() {
        $this->odds_api_key = bsp_v2_option('api_key_odds');
        $this->football_api_key = bsp_v2_option('api_key_football');
    }
    
    /**
     * Fetch from Odds API
     */
    public function fetch_odds($sport = 'football', $markets = ['h2h']) {
        if (empty($this->odds_api_key)) {
            return new WP_Error('missing_key', 'Odds API key not configured');
        }

        $requested_markets = $this->normalize_requested_odds_markets($markets);
        $bookmakers = $this->get_odds_bookmakers();

        if (is_wp_error($bookmakers)) {
            return $bookmakers;
        }

        $cache_key = 'odds_' . $sport . '_' . md5(wp_json_encode([
            'markets' => $requested_markets,
            'bookmakers' => $bookmakers,
        ]));
        $cached = BSP_V2_Cache::get($cache_key);
        
        if ($cached !== false) {
            if (bsp_v2_is_debug()) bsp_v2_log_debug('Odds cache hit', ['key' => $cache_key]);
            return $cached;
        }

        $events = $this->fetch_odds_events($sport, $bookmakers);

        if (is_wp_error($events)) {
            return $events;
        }

        if (empty($events)) {
            $empty_result = ['data' => []];
            BSP_V2_Cache::set($cache_key, $empty_result, BSP_V2_CACHE_TTL_ODDS);
            bsp_v2_log_debug('Odds API returned no matching events', ['sport' => $sport]);
            return $empty_result;
        }

        $event_ids = array_values(array_filter(array_map(function($event) {
            return isset($event['id']) ? intval($event['id']) : 0;
        }, $events)));

        if (empty($event_ids)) {
            $empty_result = ['data' => []];
            BSP_V2_Cache::set($cache_key, $empty_result, BSP_V2_CACHE_TTL_ODDS);
            bsp_v2_log_debug('Odds event list contained no valid IDs', ['sport' => $sport]);
            return $empty_result;
        }

        $raw_events = [];
        foreach (array_chunk($event_ids, 10) as $event_id_chunk) {
            $chunk_events = $this->fetch_odds_snapshot_chunk($event_id_chunk, $bookmakers);

            if (is_wp_error($chunk_events)) {
                return $chunk_events;
            }

            if (!empty($chunk_events)) {
                $raw_events = array_merge($raw_events, $chunk_events);
            }
        }

        $data = [
            'data' => $this->normalize_odds_events($raw_events, $requested_markets),
        ];

        BSP_V2_Cache::set($cache_key, $data, BSP_V2_CACHE_TTL_ODDS);
        bsp_v2_log_debug('Odds fetched from API', [
            'sport' => $sport,
            'markets' => $requested_markets,
            'events' => count($data['data']),
            'bookmakers' => count($bookmakers),
        ]);

        return $data;
    }
    
    /**
     * Fetch from Football API
     */
    public function fetch_fixtures($league_id = 39, $season = 2024) {
        if (empty($this->football_api_key)) {
            return new WP_Error('missing_key', 'Football API key not configured');
        }
        
        // Check API call limit
        if (!bsp_v2_check_api_limit('football_api')) {
            return new WP_Error('api_limit_exceeded', 'Football API daily call limit exceeded');
        }
        
        $cache_key = 'fixtures_' . $league_id . '_' . $season;
        $cached = BSP_V2_Cache::get($cache_key);
        
        if ($cached !== false) {
            if (bsp_v2_is_debug()) bsp_v2_log_debug('Fixtures cache hit', ['key' => $cache_key]);
            return $cached;
        }
        
        $url = BSP_V2_FOOTBALL_API_BASE . '/fixtures';
        $args = [
            'league' => $league_id,
            'season' => $season,
        ];
        
        $response = wp_remote_get(add_query_arg($args, $url), [
            'headers' => [
                'User-Agent' => $this->user_agent,
                'x-apisports-key' => $this->football_api_key,
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            bsp_v2_log_error('Football API error: ' . $response->get_error_message());
            return $response;
        }
        
        // Track successful API call
        bsp_v2_track_api_call('football_api');
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            bsp_v2_log_error('Football API returned empty data');
            return new WP_Error('empty_response', 'Empty response from Football API');
        }
        
        BSP_V2_Cache::set($cache_key, $data, BSP_V2_CACHE_TTL_EVENTS);
        bsp_v2_log_debug('Fixtures fetched from API', ['league' => $league_id, 'season' => $season]);
        
        return $data;
    }
    
    /**
     * Fetch team by name from Football API
     */
    public function fetch_team_by_name($team_name) {
        if (empty($this->football_api_key) || empty($team_name)) {
            return false;
        }
        
        // Check API call limit
        if (!bsp_v2_check_api_limit('football_api')) {
            bsp_v2_log_error('Football API limit exceeded for team lookup: ' . $team_name);
            return false;
        }
        
        $cache_key = 'team_' . md5($team_name);
        $cached = BSP_V2_Cache::get($cache_key);
        
        if ($cached !== false) {
            if (bsp_v2_is_debug()) bsp_v2_log_debug('Team cache hit', ['name' => $team_name]);
            return $cached;
        }
        
        $url = BSP_V2_FOOTBALL_API_BASE . '/teams';
        $args = [
            'name' => $team_name,
        ];
        
        $response = wp_remote_get(add_query_arg($args, $url), [
            'headers' => [
                'User-Agent' => $this->user_agent,
                'x-apisports-key' => $this->football_api_key,
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            bsp_v2_log_debug('Football API team lookup error for: ' . $team_name, ['error' => $response->get_error_message()]);
            return false;
        }
        
        // Track successful API call
        bsp_v2_track_api_call('football_api');
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['response']) || empty($data['response'][0])) {
            bsp_v2_log_debug('Football API team not found: ' . $team_name);
            return false;
        }
        
        $team = [
            'team_id' => $data['response'][0]['team']['id'],
            'team_name' => $data['response'][0]['team']['name'],
            'team_logo' => $data['response'][0]['team']['logo'],
        ];
        
        BSP_V2_Cache::set($cache_key, $team, BSP_V2_CACHE_TTL_EVENTS);
        bsp_v2_log_debug('Team fetched from API', ['name' => $team_name, 'id' => $team['team_id']]);
        
        return $team;
    }

    /**
     * Resolve the list of bookmakers to use for odds comparison.
     */
    private function get_odds_bookmakers() {
        $cache_key = 'odds_api_bookmakers';
        $cached = BSP_V2_Cache::get($cache_key);

        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $selected = $this->fetch_selected_odds_bookmakers();
        if (!is_wp_error($selected) && !empty($selected)) {
            $selected = array_slice(array_values(array_unique($selected)), 0, 10);
            BSP_V2_Cache::set($cache_key, $selected, DAY_IN_SECONDS);
            return $selected;
        }

        if (is_wp_error($selected)) {
            bsp_v2_log_debug('Falling back from selected bookmakers lookup', ['error' => $selected->get_error_message()]);
        }

        $available = $this->fetch_public_odds_bookmakers();
        if (is_wp_error($available)) {
            $fallback = array_slice($this->default_odds_bookmakers, 0, 3);
            BSP_V2_Cache::set($cache_key, $fallback, DAY_IN_SECONDS);
            bsp_v2_log_debug('Using hardcoded default bookmakers fallback', ['bookmakers' => $fallback]);
            return $fallback;
        }

        $bookmakers = $this->build_default_odds_bookmakers($available);
        BSP_V2_Cache::set($cache_key, $bookmakers, DAY_IN_SECONDS);

        return $bookmakers;
    }

    /**
     * Fetch upcoming or live events before requesting odds snapshots.
     */
    private function fetch_odds_events($sport, $bookmakers, $limit = 30) {
        $args = [
            'sport' => $sport,
            'status' => 'pending,live',
            'limit' => max(10, intval($limit)),
        ];

        if (!empty($bookmakers[0])) {
            $args['bookmaker'] = $bookmakers[0];
        }

        $events = $this->request_odds_api('/events', $args);
        if (is_wp_error($events)) {
            return $events;
        }

        return is_array($events) ? $events : [];
    }

    /**
     * Fetch odds snapshots for a chunk of up to 10 events.
     */
    private function fetch_odds_snapshot_chunk($event_ids, $bookmakers) {
        if (empty($event_ids)) {
            return [];
        }

        $args = [
            'eventIds' => implode(',', array_map('intval', $event_ids)),
            'bookmakers' => implode(',', $bookmakers),
        ];

        $events = $this->request_odds_api('/odds/multi', $args);
        if (is_wp_error($events)) {
            return $events;
        }

        return is_array($events) ? $events : [];
    }

    /**
     * Normalize requested internal markets to the supported set.
     */
    private function normalize_requested_odds_markets($markets) {
        if (empty($markets) || !is_array($markets)) {
            return ['h2h'];
        }

        $normalized = [];
        foreach ($markets as $market) {
            $market = is_string($market) ? strtolower(trim($market)) : '';
            if (in_array($market, ['h2h', 'totals'], true)) {
                $normalized[] = $market;
            }
        }

        return !empty($normalized) ? array_values(array_unique($normalized)) : ['h2h'];
    }

    /**
     * Normalize provider odds responses into the internal event structure.
     */
    private function normalize_odds_events($events, $requested_markets) {
        $normalized_events = [];

        foreach ($events as $event) {
            if (empty($event['home']) || empty($event['away']) || empty($event['bookmakers']) || !is_array($event['bookmakers'])) {
                continue;
            }

            $normalized_bookmakers = [];

            foreach ($event['bookmakers'] as $bookmaker_name => $markets) {
                if (!is_array($markets)) {
                    continue;
                }

                $normalized_markets = [];

                foreach ($markets as $market) {
                    $market_key = $this->normalize_odds_market_key($market['name'] ?? '');

                    if (!$market_key || !in_array($market_key, $requested_markets, true)) {
                        continue;
                    }

                    $outcomes = [];
                    foreach (($market['odds'] ?? []) as $odds_row) {
                        if (!is_array($odds_row)) {
                            continue;
                        }

                        $outcomes = array_merge($outcomes, $this->normalize_market_outcomes($market_key, $odds_row, $event));
                    }

                    if (empty($outcomes)) {
                        continue;
                    }

                    $normalized_markets[] = [
                        'key' => $market_key,
                        'name' => $market['name'] ?? $market_key,
                        'outcomes' => $outcomes,
                    ];
                }

                if (!empty($normalized_markets)) {
                    $normalized_bookmakers[] = [
                        'name' => $bookmaker_name,
                        'markets' => $normalized_markets,
                    ];
                }
            }

            if (empty($normalized_bookmakers)) {
                continue;
            }

            $normalized_events[] = [
                'event_id' => isset($event['id']) ? intval($event['id']) : 0,
                'home_team' => $event['home'],
                'away_team' => $event['away'],
                'commence_time' => $event['date'] ?? '',
                'status' => $event['status'] ?? '',
                'league' => $event['league']['name'] ?? '',
                'bookmakers' => $normalized_bookmakers,
            ];
        }

        return $normalized_events;
    }

    /**
     * Normalize provider market names to internal strategy keys.
     */
    private function normalize_odds_market_key($market_name) {
        $market_name = strtolower(trim((string) $market_name));

        if ($market_name === 'ml') {
            return 'h2h';
        }

        if (in_array($market_name, ['totals', 'over/under'], true)) {
            return 'totals';
        }

        return null;
    }

    /**
     * Normalize provider odds rows into outcome arrays consumed by the logic layer.
     */
    private function normalize_market_outcomes($market_key, $odds_row, $event) {
        $outcomes = [];

        if ($market_key === 'h2h') {
            if (!empty($odds_row['home'])) {
                $outcomes[] = [
                    'name' => $event['home'],
                    'price' => floatval($odds_row['home']),
                ];
            }

            if (!empty($odds_row['draw'])) {
                $outcomes[] = [
                    'name' => 'Draw',
                    'price' => floatval($odds_row['draw']),
                ];
            }

            if (!empty($odds_row['away'])) {
                $outcomes[] = [
                    'name' => $event['away'],
                    'price' => floatval($odds_row['away']),
                ];
            }

            return $outcomes;
        }

        if ($market_key === 'totals') {
            $line = '';
            if (isset($odds_row['hdp'])) {
                $line = (string) $odds_row['hdp'];
            } elseif (isset($odds_row['max'])) {
                $line = (string) $odds_row['max'];
            }

            if (!empty($odds_row['over'])) {
                $outcomes[] = [
                    'name' => 'Over',
                    'description' => $line,
                    'price' => floatval($odds_row['over']),
                ];
            }

            if (!empty($odds_row['under'])) {
                $outcomes[] = [
                    'name' => 'Under',
                    'description' => $line,
                    'price' => floatval($odds_row['under']),
                ];
            }
        }

        return $outcomes;
    }

    /**
     * Fetch selected bookmakers for the authenticated odds account.
     */
    private function fetch_selected_odds_bookmakers() {
        $payload = $this->request_odds_api('/bookmakers/selected');
        if (is_wp_error($payload)) {
            return $payload;
        }

        return $this->extract_bookmaker_names($payload);
    }

    /**
     * Fetch the public bookmakers catalogue.
     */
    private function fetch_public_odds_bookmakers() {
        $payload = $this->request_odds_api('/bookmakers', [], false, false);
        if (is_wp_error($payload)) {
            return $payload;
        }

        $available = [];
        foreach ($payload as $bookmaker) {
            if (!is_array($bookmaker) || empty($bookmaker['name'])) {
                continue;
            }

            if (!isset($bookmaker['active']) || $bookmaker['active']) {
                $available[] = $bookmaker['name'];
            }
        }

        return array_values(array_unique($available));
    }

    /**
     * Pick a practical default subset of available bookmakers.
     */
    private function build_default_odds_bookmakers($available_bookmakers) {
        $available_lookup = array_fill_keys($available_bookmakers, true);
        $selected = [];

        foreach ($this->default_odds_bookmakers as $bookmaker_name) {
            if (isset($available_lookup[$bookmaker_name])) {
                $selected[] = $bookmaker_name;
            }
        }

        if (empty($selected)) {
            $selected = array_slice($available_bookmakers, 0, 5);
        }

        return array_values(array_unique(array_slice($selected, 0, 10)));
    }

    /**
     * Extract bookmaker names from the flexible selected-bookmakers payload.
     */
    private function extract_bookmaker_names($payload) {
        if (!is_array($payload) || empty($payload)) {
            return [];
        }

        if (isset($payload['bookmakers']) && is_array($payload['bookmakers'])) {
            return $this->extract_bookmaker_names($payload['bookmakers']);
        }

        $names = [];
        foreach ($payload as $key => $value) {
            if (is_numeric($key) && is_string($value) && $value !== '') {
                $names[] = $value;
                continue;
            }

            if (is_array($value)) {
                if (!empty($value['name']) && is_string($value['name'])) {
                    $names[] = $value['name'];
                    continue;
                }

                $names = array_merge($names, $this->extract_bookmaker_names($value));
                continue;
            }

            if (!is_numeric($key) && is_bool($value) && $value) {
                $names[] = $key;
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $names))));
    }

    /**
     * Perform a GET request against Odds-API.io and decode the JSON body.
     */
    private function request_odds_api($path, $args = [], $authenticated = true, $track_usage = true) {
        if ($authenticated && !bsp_v2_check_api_limit('odds_api')) {
            return new WP_Error('api_limit_exceeded', 'Odds API daily call limit exceeded');
        }

        $query_args = $args;
        if ($authenticated) {
            $query_args['apiKey'] = $this->odds_api_key;
        }

        $url = BSP_V2_ODDS_API_BASE . $path;
        $response = wp_remote_get(add_query_arg($query_args, $url), [
            'headers' => [
                'User-Agent' => $this->user_agent,
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            bsp_v2_log_error('Odds API request failed', ['path' => $path, 'error' => $response->get_error_message()]);
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status !== 200) {
            $payload = json_decode($body, true);
            $error_message = is_array($payload) && !empty($payload['error']) ? $payload['error'] : 'Odds API error (HTTP ' . $status . ')';
            bsp_v2_log_error('Odds API returned non-200 response', ['path' => $path, 'status' => $status, 'body' => substr($body, 0, 200)]);
            return new WP_Error('odds_api_error', $error_message);
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            bsp_v2_log_error('Odds API returned invalid JSON', ['path' => $path, 'error' => json_last_error_msg()]);
            return new WP_Error('invalid_response', 'Odds API returned invalid JSON');
        }

        if ($authenticated && $track_usage) {
            bsp_v2_track_api_call('odds_api');
        }

        return $data;
    }
}
