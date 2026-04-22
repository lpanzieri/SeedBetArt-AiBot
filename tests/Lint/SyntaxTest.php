<?php

declare( strict_types=1 );

namespace BSP_V2_Tests\Lint;

use PHPUnit\Framework\TestCase;

/**
 * Runs `php -l` on every PHP file in the plugin.
 * Catches parse errors before they reach the server.
 */
final class SyntaxTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function phpFilesProvider(): array
    {
        $root    = dirname( __DIR__, 2 );
        $exclude = [
            $root . DIRECTORY_SEPARATOR . 'vendor',
            $root . DIRECTORY_SEPARATOR . 'tests',
        ];

        $files = [];
        $iter  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root ) );

        /** @var \SplFileInfo $file */
        foreach ( $iter as $file ) {
            if ( $file->getExtension() !== 'php' ) {
                continue;
            }

            $path = $file->getRealPath();

            foreach ( $exclude as $ex ) {
                if ( str_starts_with( $path, $ex ) ) {
                    continue 2;
                }
            }

            $label         = str_replace( $root . DIRECTORY_SEPARATOR, '', $path );
            $files[$label] = [ $path ];
        }

        return $files;
    }

    /** @dataProvider phpFilesProvider */
    public function testFileHasNoSyntaxErrors( string $path ): void
    {
        $output   = [];
        $exitCode = 0;
        exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1', $output, $exitCode );
        $result = implode( "\n", $output );

        $this->assertSame(
            0,
            $exitCode,
            "PHP syntax error in {$path}:\n{$result}"
        );
    }
}
