<?php
/**
 * PHPUnit bootstrap - defines WP stubs and loads plugin files.
 */

define( 'ABSPATH',        dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'BSP_V2_DIR',     dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'BSP_V2_URL',     'http://example.com/wp-content/plugins/test/' );
define( 'BSP_V2_VERSION', '0.2' );
define( 'BSP_V2_DEBUG',   false );
define( 'DB_NAME',        'test_db' );

require_once __DIR__ . '/stubs/wp-functions.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

require_once dirname( __DIR__ ) . '/includes/helpers.php';
require_once dirname( __DIR__ ) . '/includes/config-constants.php';
require_once dirname( __DIR__ ) . '/includes/class-bsp-v2-cache.php';
require_once dirname( __DIR__ ) . '/includes/class-bsp-v2-client.php';
require_once dirname( __DIR__ ) . '/includes/admin-search-params.php';
require_once dirname( __DIR__ ) . '/includes/class-bsp-v2-logic.php';
