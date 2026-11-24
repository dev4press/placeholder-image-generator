# Placeholder Image Generator

`Dev4Press\Generator\Image\Placeholder` generates PNG, JPEG, or GIF images with optional text and rectangles. Use it for demo content, thumbnails, and layout previews.

## Requirements

- PHP 8.0 or newer
- PHP GD with support for the chosen output format
- GD with FreeType support when rendering text or image dimensions

## Run the demo

From the repository root, run `php demo/index.php`. The demo saves three images in `demo/` and prints their paths. [Demo script](demo/index.php)

## Usage

Run these examples from a PHP file alongside `Placeholder.php`. They save files to the system temporary directory. Pass your own writable directory to `generate()` to save them elsewhere.

### Generate a placeholder with a label

```php
<?php

use Dev4Press\Generator\Image\Placeholder;

require_once __DIR__ . '/Placeholder.php';

$generator = new Placeholder();
$image = $generator
    ->size( 640, 360 )
    ->colors( '#e9eef5' )
    ->render( 'text', 'Product preview' )
    ->generate( false, sys_get_temp_dir() );

echo $image, PHP_EOL;
```

The generator chooses black or white text based on the background when you omit the text color.

### Generate a JPEG with decorative rectangles

With the same setup, use another instance to start from the default settings.

```php
$generator = new Placeholder();
$image = $generator
    ->format( 'jpg' )
    ->size( 320, 180 )
    ->colors( 'light-random' )
    ->render( 'empty' )
    ->rectangles( 4, 'small', '#9ab' )
    ->generate( false, sys_get_temp_dir() );

echo $image, PHP_EOL;
```

## Options

| Method | Options |
| --- | --- |
| `format()` | `png` (default), `jpg`, or `gif`. |
| `size()` | Set the width and height in pixels. A new instance starts at 1280 x 720. |
| `render()` | `size` (default) prints the dimensions. `text` prints your label. `empty` leaves the image free of text. |
| `colors()` | Set the background and optionally the text color. Both accept `#RGB`, `#RRGGBB`, `dark-random`, or `light-random`. |
| `font()` | Uses the bundled Share Tech Mono font at 32 points by default. Pass a font directory, filename, and size to customize it. |
| `rectangles()` | Set the number, color, and size of the rectangles. Sizes are `small`, `medium`, and `big`. |
| `generate()` | Saves the image and returns its path. Pass `false` for an automatic filename, or pass a filename without its extension. |

Rendering and file failures raise a `RuntimeException`. Invalid dimensions, font sizes, or colors raise an `InvalidArgumentException`.

## License

[MIT](license.txt)
