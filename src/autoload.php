<?php
spl_autoload_register(function ($class) {
	static $classes = null;
	if ($classes === null) {
		$classes = [
			'Xicrow\\PhpThumb\\Engine\\GdLibrary' => '/Engine/GdLibrary.php',
			'Xicrow\\PhpThumb\\EngineInterface'   => '/EngineInterface.php',
			'Xicrow\\PhpThumb\\Helper'            => '/Helper.php',
			'Xicrow\\PhpThumb\\Thumb'             => '/Thumb.php',
			'Xicrow\\PhpThumb\\Utility'           => '/Utility.php',
		];
	}
	if (isset($classes[$class])) {
		require __DIR__ . $classes[$class];
	}
}, true, false);
