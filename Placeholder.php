<?php

namespace Dev4Press\Generator\Image;

use Exception;

class Placeholder {
	protected $allowed_render = array(
		'empty',
		'size',
		'text'
	);
	protected $allowed_sizes = array(
		'big',
		'small',
		'medium'
	);
	protected $allowed_format = array(
		'png',
		'jpg',
		'gif'
	);

	protected $font_path = '';
	protected $font_name = '';
	protected $font_size = 32;

	protected $format = 'png';
	protected $width = 1280;
	protected $height = 720;
	protected $render_type = 'size';
	protected $render_text = 'Lorem Ipsum';

	protected $rectangles_limit = 0;
	protected $rectangles_size = 'big';

	protected $color_background = 'dark-random';
	protected $color_text = '#ffffff';
	protected $color_rectangle = 'dark-random';

	public function __construct() {
		$this->font();
	}

	public static function instance() {
		static $_instance = false;

		if ( $_instance === false ) {
			$_instance = new Placeholder();
		}

		return $_instance;
	}

	public function format( string $format = 'png' ) : Placeholder {
		$this->format = in_array( $format, $this->allowed_format ) ? $format : 'png';

		return $this;
	}

	public function font( $path = false, $name = false, $size = false ) : Placeholder {
		$this->font_path = $path === false ? dirname( __FILE__ ) . '/font/' : $path;
		$this->font_name = $name === false ? 'ShareTechMono-Regular.ttf' : $name;
		$this->font_size = $size === false ? 32 : abs( intval( $size ) );

		return $this;
	}

	public function size( int $width = 1280, int $height = 720 ) : Placeholder {
		$this->width  = $width ? abs( $width ) : $this->width;
		$this->height = $height ? abs( $height ) : $this->height;

		return $this;
	}

	public function render( string $size = 'size', string $text = 'Lorem Ipsum' ) : Placeholder {
		$this->render_type = in_array( $size, $this->allowed_render ) ? $size : 'size';
		$this->render_text = strip_tags( $text );

		return $this;
	}

	public function rectangles( int $limit = 10, string $size = 'big', string $color = 'dark-random' ) : Placeholder {
		$this->rectangles_limit = $limit;
		$this->rectangles_size  = in_array( $size, $this->allowed_sizes ) ? $size : 'size';
		$this->color_rectangle  = $color;

		return $this;
	}

	public function colors( string $background = 'dark-random', string $text = '' ) : Placeholder {
		$this->color_background = $background;
		$this->color_text       = ! empty( $text ) ? $text : ( $background == 'dark-random' ? '#FFFFFF' : ( $background == 'light-random' ? '#000000' : $text ) );

		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function generate( $name = false, $path = false ) : string {
		$path = $path === false ? dirname( __FILE__ ) : $path;
		$path = $this->trailingslashit( $path );

		if ( ! file_exists( $path ) || ! is_writeable( $path ) ) {
			throw new Exception( 'Can\'t save image in the specified location.' );
		} else {
			$bg_color = $this->process_color( $this->color_background );
			$tx_color = $this->process_color( $this->color_text );

			if ( $name === false ) {
				$name = 'placeholder-' . $this->width . '-' . $this->height . '-' . substr( $bg_color, 1 ) . '-' . substr( $tx_color, 1 ) . '-' . $this->render_type . '-' . time() . '-' . mt_rand( 1000, 9999 );
			}

			$name .= '.' . $this->format;

			$image = imagecreatetruecolor( $this->width, $this->height );

			$bg      = $this->convert_color( $bg_color );
			$bg_fill = imagecolorallocate( $image, $bg[0], $bg[1], $bg[2] );

			imagefill( $image, 0, 0, $bg_fill );

			$this->add_rectangles( $image );
			$this->add_text( $image, $tx_color );

			$file = $path . $name;

			switch ( $this->format ) {
				case 'png':
					imagepng( $image, $file );
					break;
				case 'gif':
					imagegif( $image, $file );
					break;
				case 'jpg':
					imagejpeg( $image, $file );
					break;
			}

			return $file;
		}
	}

	protected function add_rectangles( $image ) {
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

	protected function add_text( $image, $tx_color ) {
		$text = '';

		switch ( $this->render_type ) {
			case 'size':
				$text = $this->width . ' x ' . $this->height;
				break;
			case 'text':
				$text = $this->render_text;
				break;
		}

		if ( ! empty( $text ) ) {
			$tx      = $this->convert_color( $tx_color );
			$tx_fill = imagecolorallocate( $image, $tx[0], $tx[1], $tx[2] );

			$font     = $this->trailingslashit( $this->font_path ) . $this->font_name;
			$text_box = imagettfbbox( $this->font_size, 0, $font, $text );

			$text_width  = abs( $text_box[4] - $text_box[0] );
			$text_height = abs( $text_box[5] - $text_box[1] );
			$text_x      = ( $this->width - $text_width ) / 2;
			$text_y      = ( $this->height + $text_height ) / 2;

			imagettftext( $image, $this->font_size, 0, $text_x, $text_y, $tx_fill, $font, $text );
		}
	}

	protected function process_color( string $color ) : string {
		switch ( $color ) {
			case 'dark-random':
				$color = $this->random_color_in_range();
				break;
			case 'light-random':
				$color = $this->random_color_in_range( 128, 255 );
				break;
			default:
				$color = $this->sanitize_color( $color );
				break;
		}

		return $color;
	}

	protected function convert_color( $color ) {
		if ( strlen( $color ) == 7 ) {
			return sscanf( $color, "#%2x%2x%2x" );
		} else {
			return sscanf( $color, "#%1x%1x%1x" );
		}
	}

	protected function random_color_in_range( $from = 0, $to = 127 ) : string {
		$color = '#';

		for ( $i = 1; $i <= 3; $i ++ ) {
			$color .= str_pad( dechex( mt_rand( $from, $to ) ), 2, '0', STR_PAD_LEFT );
		}

		return $color;
	}

	protected function sanitize_color( string $color ) : string {
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		return '';
	}

	protected function trailingslashit( $path ) : string {
		return rtrim( $path, '/\\' ) . '/';
	}
}