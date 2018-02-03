<?php
require_once('../../../src/autoload.php');

use \Xicrow\PhpThumb\Thumb;
use \Xicrow\PhpThumb\Helper;

// Get URL for thumbnail
$thumbUrl = urldecode($_SERVER['REQUEST_URI']);
if (!empty($_SERVER['SCRIPT_NAME'])) {
	$thumbUrl = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $thumbUrl);
} elseif (!empty($_SERVER['PHP_SELF'])) {
	$thumbUrl = str_replace(dirname($_SERVER['PHP_SELF']), '', $thumbUrl);
}

// Get path for thumbnail
$thumbPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($thumbUrl, '/');
$thumbPath = str_replace('/', DIRECTORY_SEPARATOR, $thumbPath);
if ($queryPos = strpos($thumbPath, '?')) {
	$thumbPath = substr($thumbPath, 0, $queryPos);
}

// If thumbnail does not exist
if (!file_exists($thumbPath)) {
	// If options can be loaded
	if ($options = Helper::loadOptions($thumbPath)) {
		// Resize the thumbnail
		Thumb::resize($options['image'], $options['options']);
	}
}

// If thumbnail exist
if (file_exists($thumbPath)) {
	// Redirect to thumbnail
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('location: ' . basename($thumbUrl), 302);
	exit;
} else {
	// 404
	header('HTTP/1.0 404 Not Found');
	exit;
}
