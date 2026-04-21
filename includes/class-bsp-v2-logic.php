<?php
/**
 * Betting analysis logic
 */

if (!defined('ABSPATH')) exit;

class BSP_V2_Logic {
    
    private $client;
    
    public function __construct() {
        $this->client = new BSP_V2_Client();
    }
    
    /**
     * Calculate expected value
     */
    public function calculate_ev($odds, $win_probability) {
        if ($odds <= 1 || $win_probability <= 0 || $win_probability >= 1) {
            return 0;
        }
        
        $ev = ($win_probability * $odds) - 1;
        return round($ev * 100, 2);
    }
    
    /**
     * Save team badges from betting suggestions
     */
    private function save_team_badges_from_bets($bets) {
        if (empty($bets) || !is_array($bets)) {
            return;
        }
        
        // Collect unique teams
        $teams = [];
        foreach ($bets as $bet) {
            if (!empty($bet['home'])) {
                $teams[] = sanitize_text_field($bet['home']);
            }
            if (!empty($bet['away'])) {
                $teams[] = sanitize_text_field($bet['away']);
            }
        }
        
        $teams = array_unique($teams);
        
        // Fetch and save badge for each team
        foreach ($teams as $team_name) {
            if (empty($team_name)) {
                continue;
            }
            
            try {
                // Fetch team data from Football API
                $team_data = $this->client->fetch_team_by_name($team_name);
                
                if ($team_data && !empty($team_data['team_id'])) {
                    // Save badge with team ID and name
                    BSP_V2_Teams::save_badge(
                        $team_data['team_id'],
                        $team_name
                    );
                }
            } catch (Throwable $e) {
                bsp_v2_log_debug('Failed to save badge for team: ' . $team_name, ['error' => $e->getMessage()]);
            }
        }
    }
    
    /**
     * Get value bets
     */
    public function get_value_bets($sport = 'football', $limit = 10) {
        bsp_v2_log_debug('Fetching value bets', ['sport' => $sport, 'limit' => $limit]);
        
        // Get parameters from configuration
        $min_ev = BSP_V2_Search_Params::get_param('min_ev', 'vb');
        $min_odds = BSP_V2_Search_Params::get_param('min_odds', 'vb');
        $max_odds = BSP_V2_Search_Params::get_param('max_odds', 'vb');
        $min_confidence = BSP_V2_Search_Params::get_param('min_confidence', 'vb');
        
        bsp_v2_log_debug('Value Bets Parameters', [
            'min_ev' => $min_ev,
            'min_odds' => $min_odds,
            'max_odds' => $max_odds,
            'min_confidence' => $min_confidence
        ]);
        
        $odds_data = $this->client->fetch_odds($sport);
        
        if (is_wp_error($odds_data)) {
            return $odds_data;
        }
        
        $bets = [];
        $odds_list = $odds_data['data'] ?? [];
        
        foreach ($odds_list as $event) {
            $home_win = 0;
            
            foreach ($event['bookmakers'] ?? [] as $bookmaker) {
                foreach ($bookmaker['markets'] ?? [] as $market) {
                    if ($market['key'] === 'h2h') {
                        foreach ($market['outcomes'] ?? [] as $outcome) {
                            if ($outcome['name'] === $event['home_team']) {
                                $home_win = (float)$outcome['price'];
                            }
                        }
                    }
                }
            }
            
            // Apply odds range filter
            if ($home_win > 0 && $home_win >= $min_odds && $home_win <= $max_odds) {
                $estimated_prob = 1 / $home_win;
                $ev = $this->calculate_ev($home_win, $estimated_prob * 1.05); // Slight adjustment
                
                // Calculate confidence (simplified: based on odds spread)
                $confidence = min(95, 50 + ($max_odds - $home_win) * 2);
                
                // Apply filters: EV threshold and confidence level
                if ($ev >= $min_ev && $confidence >= $min_confidence) {
                    $bets[] = [
                        'home' => $event['home_team'],
                        'away' => $event['away_team'],
                        'odds' => $home_win,
                        'ev' => $ev,
                        'confidence' => $confidence,
                        'market' => 'h2h',
                        'date' => $event['commence_time'],
                    ];
                }
            }
        }
        
        usort($bets, function($a, $b) {
            return $b['ev'] <=> $a['ev'];
        });
        
        $bets = array_slice($bets, 0, $limit);
        
        // Save team badges for all teams in results
        $this->save_team_badges_from_bets($bets);
        
        bsp_v2_log('Found ' . count($bets) . ' value bets', ['min_ev' => $min_ev]);
        
        return $bets;
    }
    
    /**
     * Get Lay The Draw suggestions
     */
    public function get_ltd_suggestions($sport = 'football', $limit = 10) {
        bsp_v2_log_debug('Fetching LTD suggestions', ['sport' => $sport]);
        
        // Get parameters from configuration
        $max_draw_prob = BSP_V2_Search_Params::get_param('max_draw_prob', 'ltd');
        $min_draw_odds = BSP_V2_Search_Params::get_param('min_draw_odds', 'ltd');
        $form_weight = BSP_V2_Search_Params::get_param('form_weight', 'ltd');
        $home_advantage = BSP_V2_Search_Params::get_param('home_advantage', 'ltd');
        
        bsp_v2_log_debug('LTD Parameters', [
            'max_draw_prob' => $max_draw_prob,
            'min_draw_odds' => $min_draw_odds,
            'form_weight' => $form_weight,
            'home_advantage' => $home_advantage
        ]);
        
        $odds_data = $this->client->fetch_odds($sport);
        
        if (is_wp_error($odds_data)) {
            return $odds_data;
        }
        
        $suggestions = [];
        
        // Look for matches with low draw probability (high odds)
        foreach (($odds_data['data'] ?? []) as $event) {
            $draw_odds = 0;
            
            foreach ($event['bookmakers'] ?? [] as $bookmaker) {
                foreach ($bookmaker['markets'] ?? [] as $market) {
                    if ($market['key'] === 'h2h') {
                        foreach ($market['outcomes'] ?? [] as $outcome) {
                            if ($outcome['name'] === 'Draw') {
                                $draw_odds = max($draw_odds, (float)$outcome['price']);
                            }
                        }
                    }
                }
            }
            
            // Calculate draw probability from odds
            $draw_prob = ($draw_odds > 0) ? (100 / $draw_odds) : 0;
            
            // Apply filters: draw probability and odds threshold
            if ($draw_prob > 0 && $draw_prob <= $max_draw_prob && $draw_odds >= $min_draw_odds) {
                $confidence = min(95, 50 + ($max_draw_prob - $draw_prob) * 2);
                
                $suggestions[] = [
                    'home' => $event['home_team'],
                    'away' => $event['away_team'],
                    'draw_odds' => $draw_odds,
                    'draw_prob' => round($draw_prob, 2),
                    'draw_probability' => round($draw_prob, 2),
                    'confidence' => round($confidence, 0),
                    'type' => 'ltd',
                    'date' => $event['commence_time'],
                ];
            }
        }
        
        usort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        $suggestions = array_slice($suggestions, 0, $limit);
        
        // Save team badges for all teams in results
        $this->save_team_badges_from_bets($suggestions);
        
        bsp_v2_log('Found ' . count($suggestions) . ' LTD suggestions', ['max_draw_prob' => $max_draw_prob]);
        
        return $suggestions;
    }
    
    /**
     * Get Under 2.5 suggestions
     */
    public function get_under_25_suggestions($sport = 'football', $limit = 10) {
        bsp_v2_log_debug('Fetching Under 2.5 suggestions', ['sport' => $sport]);
        
        // Get parameters from configuration
        $max_xg = BSP_V2_Search_Params::get_param('max_xg', 'under');
        $form_weight = BSP_V2_Search_Params::get_param('form_weight', 'under');
        $min_odds = BSP_V2_Search_Params::get_param('min_odds', 'under');
        $min_confidence = BSP_V2_Search_Params::get_param('min_confidence', 'under');
        
        bsp_v2_log_debug('Under 2.5 Parameters', [
            'max_xg' => $max_xg,
            'form_weight' => $form_weight,
            'min_odds' => $min_odds,
            'min_confidence' => $min_confidence
        ]);
        
        $odds_data = $this->client->fetch_odds($sport, ['totals']);
        
        if (is_wp_error($odds_data)) {
            return $odds_data;
        }
        
        $suggestions = [];
        
        foreach (($odds_data['data'] ?? []) as $event) {
            $under_25_odds = 0;
            
            foreach ($event['bookmakers'] ?? [] as $bookmaker) {
                foreach ($bookmaker['markets'] ?? [] as $market) {
                    if ($market['key'] === 'totals') {
                        foreach ($market['outcomes'] ?? [] as $outcome) {
                            if (strpos($outcome['name'], 'Under') !== false && strpos($outcome['description'], '2.5') !== false) {
                                $under_25_odds = max($under_25_odds, (float)$outcome['price']);
                            }
                        }
                    }
                }
            }
            
            if ($under_25_odds > 0 && $under_25_odds >= $min_odds) {
                // Simplified xG estimation based on odds
                $estimated_xg = 2.5 - (($under_25_odds - 1) * 0.3);
                
                // Calculate confidence based on xG
                $confidence = min(95, 60 + (($max_xg - $estimated_xg) * 5));
                
                // Apply filters: xG threshold and confidence level
                if ($estimated_xg <= $max_xg && $confidence >= $min_confidence) {
                    $suggestions[] = [
                        'home' => $event['home_team'],
                        'away' => $event['away_team'],
                        'odds' => $under_25_odds,
                        'estimated_xg' => round($estimated_xg, 2),
                        'confidence' => round($confidence, 0),
                        'type' => 'under25',
                        'date' => $event['commence_time'],
                    ];
                }
            }
        }
        
        usort($suggestions, function($a, $b) {
            return $a['estimated_xg'] <=> $b['estimated_xg'];
        });
        
        $suggestions = array_slice($suggestions, 0, $limit);
        
        // Save team badges for all teams in results
        $this->save_team_badges_from_bets($suggestions);
        
        bsp_v2_log('Found ' . count($suggestions) . ' Under 2.5 suggestions', ['max_xg' => $max_xg]);
        
        return $suggestions;
    }
}
