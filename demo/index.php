<?php

use Dev4Press\Generator\Image\Placeholder;

require_once __DIR__ . '/../Placeholder.php';

function save_demo_image( Placeholder $generator, string $name, string $format ): string {
    $filename = $name . '.' . $format;
    $temporary_name = '.' . $name . '-' . bin2hex( random_bytes( 8 ) );
    $temporary_path = __DIR__ . '/' . $temporary_name . '.' . $format;

    try {
        $generator->format( $format )->generate( $temporary_name, __DIR__ );

        if ( ! @rename( $temporary_path, __DIR__ . '/' . $filename ) ) {
            throw new RuntimeException( 'Could not save ' . $filename . '.' );
        }
    } finally {
        if ( is_file( $temporary_path ) ) {
            unlink( $temporary_path );
        }
    }

    return $filename;
}

$images = array(
    'Dimensions on a random dark background (PNG)' => save_demo_image(
        ( new Placeholder() )->size( 480, 270 )->colors( 'dark-random' ),
        'dimensions',
        'png'
    ),
    'Custom text and font size (JPEG)' => save_demo_image(
        ( new Placeholder() )
            ->size( 480, 270 )
            ->colors( '#e9eef5', '#1d3557' )
            ->render( 'text', 'Product preview' )
            ->font( false, false, 28 ),
        'product-preview',
        'jpg'
    ),
    'Decorative rectangles on a shorthand hex background (GIF)' => save_demo_image(
        ( new Placeholder() )
            ->size( 480, 270 )
            ->colors( '#abc' )
            ->rectangles( 5, 'small', 'light-random' )
            ->render( 'empty' ),
        'rectangles',
        'gif'
    ),
);

header( 'Content-Type: text/plain; charset=UTF-8' );
