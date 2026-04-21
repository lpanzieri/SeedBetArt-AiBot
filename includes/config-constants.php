<?php
/**
 * Configuration constants
 */

if (!defined('ABSPATH')) exit;

// API endpoints
define('BSP_V2_ODDS_API_BASE', 'https://api.odds-api.io/v3');
define('BSP_V2_FOOTBALL_API_BASE', 'https://v3.football.api-sports.io');
define('BSP_V2_OPENAI_API_BASE', 'https://api.openai.com/v1');

// Cache settings
define('BSP_V2_CACHE_TTL_ODDS', 3600); // 1 hour
define('BSP_V2_CACHE_TTL_EVENTS', 1800); // 30 minutes
define('BSP_V2_CACHE_TTL_INSIGHTS', 7200); // 2 hours

// Analysis settings
define('BSP_V2_DEFAULT_SPORT', 'football');
define('BSP_V2_MIN_EV_VALUE', 5); // Minimum expected value percentage

// Database settings
define('BSP_V2_DB_MAX_SIZE_GB', 1); // Maximum database size

// Feature flags
define('BSP_V2_ENABLE_AI', true);
define('BSP_V2_ENABLE_PREDICTIONS', true);
define('BSP_V2_ENABLE_CACHING', true);
