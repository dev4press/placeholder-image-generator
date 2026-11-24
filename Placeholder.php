<?php

namespace Dev4Press\Generator\Image;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

class Placeholder {
	protected array $allowed_render = array(
		'empty',
		'size',
		'text'
	);
	protected array $allowed_sizes = array(
		'big',
		'small',
		'medium'
	);
	protected array $allowed_format = array(
		'png',
		'jpg',
		'gif'
	);

	protected string $font_path = '';
	protected string $font_name = '';
	protected int $font_size = 32;

	protected string $format = 'png';
	protected int $width = 1280;
	protected int $height = 720;
	protected string $render_type = 'size';
	protected string $render_text = 'Lorem Ipsum';

	protected int $rectangles_limit = 0;
	protected string $rectangles_size = 'big';

	protected string $color_background = 'dark-random';
	protected string $color_text = '#ffffff';
	protected string $color_rectangle = 'dark-random';

	public function __construct() {
		$this->font();
	}

	public static function instance(): Placeholder {
		static $_instance = false;

		if ( $_instance === false ) {
			$_instance = new Placeholder();
		}

		return $_instance;
	}

	public function format( string $format = 'png' ): Placeholder {
		$this->format = in_array( $format, $this->allowed_format ) ? $format : 'png';

		return $this;
	}

	public function font( string|false $path = false, string|false $name = false, int|string|false $size = false ): Placeholder {
		$this->font_path = $path === false ? dirname( __FILE__ ) . '/font/' : $path;
		$this->font_name = $name === false ? 'ShareTechMono-Regular.ttf' : $name;
		$this->font_size = $size === false ? 32 : $this->absolute_size( (int) $size );

		return $this;
	}

	public function size( int $width = 1280, int $height = 720 ): Placeholder {
		$this->width  = $width ? $this->absolute_size( $width ) : $this->width;
		$this->height = $height ? $this->absolute_size( $height ) : $this->height;

		return $this;
	}

	public function render( string $size = 'size', string $text = 'Lorem Ipsum' ): Placeholder {
		$this->render_type = in_array( $size, $this->allowed_render ) ? $size : 'size';
		$this->render_text = strip_tags( $text );

		return $this;
	}

	public function rectangles( int $limit = 10, string $size = 'big', string $color = 'dark-random' ): Placeholder {
		$this->rectangles_limit = $limit;
		$this->rectangles_size  = in_array( $size, $this->allowed_sizes ) ? $size : 'big';
		$this->color_rectangle  = $color;

		return $this;
	}

	public function colors( string $background = 'dark-random', string $text = '' ): Placeholder {
		$this->color_background = $background;
		$this->color_text       = $text;

		return $this;
	}

	/**
	 * @throws InvalidArgumentException If the image dimensions, font size, or colors are invalid.
	 * @throws RuntimeException If the image cannot be rendered or saved.
	 */
	public function generate( string|false $name = false, string|false $path = false ): string {
		$path = $path === false ? __DIR__ : $path;
		$path = $this->trailingslashit( $path );

		if ( ! is_dir( $path ) || ! is_writable( $path ) ) {
			throw new RuntimeException( 'Cannot save the image in the specified directory.' );
		}

		if ( ! extension_loaded( 'gd' ) ) {
			throw new RuntimeException( 'The GD extension is required to generate images.' );
		}

		$encoders = array( 'png' => 'imagepng', 'gif' => 'imagegif', 'jpg' => 'imagejpeg' );
		$encoder  = $encoders[ $this->format ] ?? '';
		if ( ! function_exists( $encoder ) ) {
			throw new RuntimeException( 'GD does not support the requested image format.' );
		}

		if ( $this->width < 1 || $this->height < 1 ||
		     $this->width > 2147483647 || $this->height > 2147483647 ) {
			throw new InvalidArgumentException( 'Image dimensions are outside the supported range.' );
		}

		$bg_color = $this->process_color( $this->color_background );
		$bg       = $this->convert_color( $bg_color );

		if ( $this->color_text === '' ) {
			$brightness = 299 * $bg[0] + 587 * $bg[1] + 114 * $bg[2];
			$tx_color   = $brightness >= 128000 ? '#000000' : '#ffffff';
		} else {
			$tx_color = $this->process_color( $this->color_text );
		}

		if ( $name === false ) {
			$name = 'placeholder-' . $this->width . '-' . $this->height . '-' . substr( $bg_color, 1 ) . '-' . substr( $tx_color, 1 ) . '-' . $this->render_type . '-' . time() . '-' . mt_rand( 1000, 9999 );
		}

		$file  = $path . $name . '.' . $this->format;
		$image = @imagecreatetruecolor( $this->width, $this->height );
		if ( $image === false ) {
			throw new RuntimeException( 'GD could not create the image.' );
		}

		$bg_fill = imagecolorallocate( $image, $bg[0], $bg[1], $bg[2] );
		imagefill( $image, 0, 0, $bg_fill );

		$this->add_rectangles( $image );
		$this->add_text( $image, $tx_color );

		if ( ! @$encoder( $image, $file ) || @getimagesize( $file ) === false ) {
			throw new RuntimeException( 'GD could not save the image to the specified file.' );
		}

		return $file;
	}

	protected function add_rectangles( GdImage $image ): void {
		if ( $this->rectangles_limit > 0 ) {
			for ( $i = 0; $i < $this->rectangles_limit; $i ++ ) {
				$rc_color = $this->process_color( $this->color_rectangle );
				$rc       = $this->convert_color( $rc_color );
				$rc_fill  = imagecolorallocate( $image, $rc[0], $rc[1], $rc[2] );

				$max_x = $this->width;
				$max_y = $this->height;

				if ( $this->rectangles_size === 'small' ) {
					$max_x = intval( $max_x / 4 );
					$max_y = intval( $max_y / 4 );
				} else if ( $this->rectangles_size === 'medium' ) {
					$max_x = intval( $max_x / 2 );
					$max_y = intval( $max_y / 2 );
				}

				$x1 = rand( 0, $this->width );
				$y1 = rand( 0, $this->height );

				$x2 = rand( $x1, $x1 + $max_x );
				$y2 = rand( $y1, $y1 + $max_y );

				imagefilledrectangle( $image, $x1, $y1, $x2, $y2, $rc_fill );
			}
		}
	}

	protected function add_text( GdImage $image, string $tx_color ): void {
		$text = '';

		switch ( $this->render_type ) {
			case 'size':
				$text = $this->width . ' x ' . $this->height;
				break;
			case 'text':
				$text = $this->render_text;
				break;
		}

		if ( $text === '' ) {
			return;
		}

		if ( ! function_exists( 'imagettfbbox' ) || ! function_exists( 'imagettftext' ) ) {
			throw new RuntimeException( 'GD with FreeType support is required to render text.' );
		}

		$font = realpath( $this->trailingslashit( $this->font_path ) . $this->font_name );
		if ( $font === false || ! is_file( $font ) || ! is_readable( $font ) ) {
			throw new RuntimeException( 'The TrueType font file cannot be read.' );
		}

		if ( $this->font_size < 0 ) {
			throw new InvalidArgumentException( 'Font size is outside the supported range.' );
		}

		$text_box = @imagettfbbox( $this->font_size, 0, $font, $text );
		if ( $text_box === false ) {
			throw new RuntimeException( 'GD could not measure the text with the specified font.' );
		}

		$tx      = $this->convert_color( $tx_color );
		$tx_fill = imagecolorallocate( $image, $tx[0], $tx[1], $tx[2] );

		$text_width  = abs( $text_box[4] - $text_box[0] );
		$text_height = abs( $text_box[5] - $text_box[1] );
		$text_x      = (int) ( ( $this->width - $text_width ) / 2 );
		$text_y      = (int) ( ( $this->height + $text_height ) / 2 );

		if ( @imagettftext( $image, $this->font_size, 0, $text_x, $text_y, $tx_fill, $font, $text ) === false ) {
			throw new RuntimeException( 'GD could not render the image text.' );
		}
	}

	protected function process_color( string $color ): string {
		switch ( $color ) {
			case 'dark-random':
				$color = $this->random_color_in_range();
				break;
			case 'light-random':
				$color = $this->random_color_in_range( 128, 255 );
				break;
			default:
				$color = $this->sanitize_color( $color );
				if ( $color === '' ) {
					throw new InvalidArgumentException( 'Expected a #RGB or #RRGGBB color, dark-random, or light-random.' );
				}
				break;
		}

		return $color;
	}

	/** @return array{int, int, int} */
	protected function convert_color( string $color ): array {
		if ( strlen( $color ) == 4 ) {
			$color = '#' . str_repeat( $color[1], 2 ) . str_repeat( $color[2], 2 ) . str_repeat( $color[3], 2 );
		}

		if ( sscanf( $color, '#%2x%2x%2x', $red, $green, $blue ) !== 3 ) {
			throw new InvalidArgumentException( 'Unable to decode the image color.' );
		}

		return array( $red, $green, $blue );
	}

	protected function random_color_in_range( int $from = 0, int $to = 127 ): string {
		$color = '#';

		for ( $i = 1; $i <= 3; $i ++ ) {
			$color .= str_pad( dechex( mt_rand( $from, $to ) ), 2, '0', STR_PAD_LEFT );
		}

		return $color;
	}

	protected function sanitize_color( string $color ): string {
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|D', $color ) ) {
			return $color;
		}

		return '';
	}

	private function absolute_size( int $value ): int {
		// Keep the invalid value for generate() to report. abs(PHP_INT_MIN) returns a float.
		return $value === PHP_INT_MIN ? $value : abs( $value );
	}

	protected function trailingslashit( string $path ): string {
		return rtrim( $path, '/\\' ) . '/';
	}
}