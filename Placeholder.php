<?php

namespace Dev4Press\Generator\Image;

class Placeholder {
	public function __construct() {
	}

	public static function instance() {
		static $_instance = false;

		if ( $_instance === false ) {
			$_instance = new Placeholder();
		}

		return $_instance;
	}
}