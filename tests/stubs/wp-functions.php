<?php
/**
 * Minimal WordPress function and class stubs for unit testing.
 * Uses in-memory stores — no database or HTTP calls needed.
 */

// ---------------------------------------------------------------------------
// WP_Error stub
// ---------------------------------------------------------------------------
class WP_Error {
    private string $code;
    private string $message;

    public function __construct( string $code = '', string $message = '' ) {
        $this->code    = $code;
        $this->message = $message;
    }

    public function get_error_code(): string    { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}

// ---------------------------------------------------------------------------
// In-memory stores — reset per test via bsp_test_reset_stores()
// ---------------------------------------------------------------------------
global $bsp_test_options, $bsp_test_transients;
$bsp_test_options    = [];
$bsp_test_transients = [];

function bsp_test_reset_stores(): void {
    global $bsp_test_options, $bsp_test_transients;
    $bsp_test_options    = [];
    $bsp_test_transients = [];
}

function bsp_test_set_option( string $name, mixed $value ): void {
    global $bsp_test_options;
    $bsp_test_options[ $name ] = $value;
}

// ---------------------------------------------------------------------------
// WordPress option functions
// ---------------------------------------------------------------------------
function get_option( string $option, mixed $default = false ): mixed {
    global $bsp_test_options;
    return array_key_exists( $option, $bsp_test_options ) ? $bsp_test_options[ $option ] : $default;
}

function update_option( string $option, mixed $value, bool|string $autoload = true ): bool {
    global $bsp_test_options;
    $bsp_test_options[ $option ] = $value;
    return true;
}

function delete_option( string $option ): bool {
    global $bsp_test_options;
    $existed = array_key_exists( $option, $bsp_test_options );
    unset( $bsp_test_options[ $option ] );
    return $existed;
}

// ---------------------------------------------------------------------------
// WordPress transient functions
// ---------------------------------------------------------------------------
function get_transient( string $transient ): mixed {
    global $bsp_test_transients;
    return $bsp_test_transients[ $transient ] ?? false;
}

function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
    global $bsp_test_transients;
    $bsp_test_transients[ $transient ] = $value;
    return true;
}

function delete_transient( string $transient ): bool {
    global $bsp_test_transients;
    $existed = array_key_exists( $transient, $bsp_test_transients );
    unset( $bsp_test_transients[ $transient ] );
    return $existed;
}

// ---------------------------------------------------------------------------
// WordPress utility functions
// ---------------------------------------------------------------------------
function is_wp_error( mixed $thing ): bool    { return $thing instanceof WP_Error; }
function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false {
    return json_encode( $data, $options, $depth );
}
function wp_mkdir_p( string $target ): bool   { return is_dir( $target ) || mkdir( $target, 0755, true ); }
function sanitize_text_field( string $str ): string { return trim( strip_tags( $str ) ); }
function esc_html( string $text ): string     { return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ); }
function esc_attr( string $text ): string     { return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ); }
function esc_url( string $url ): string       { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
function wp_die( string $message = '' ): never { throw new \RuntimeException( 'wp_die: ' . $message ); }
function wp_date( string $format, ?int $timestamp = null, mixed $timezone = null ): string|false {
    return date( $format, $timestamp ?? time() );
}
function apply_filters( string $tag, mixed $value, mixed ...$args ): mixed { return $value; }
function wp_kses_post( string $content ): string { return $content; }
function register_setting(): void {}
function add_action(): void {}
function add_filter(): void {}
function current_user_can(): bool  { return true; }
function wp_nonce_field(): void    {}
function settings_fields(): void   {}
function do_settings_sections(): void {}

// ---------------------------------------------------------------------------
// BSP_V2_Teams stub (avoids loading the real file which has $wpdb dependencies)
// ---------------------------------------------------------------------------
class BSP_V2_Teams {
    public static function get_all_badges(): array           { return []; }
    public static function save_badge( int $id, string $n ): void {}
    public static function get_stored_badge( int $id ): string { return ''; }
}
