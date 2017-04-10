<?php
namespace Xicrow\PhpThumb;

/**
 * Class Utility
 *
 * @package Xicrow\PhpThumb
 */
class Utility {
	/**
	 * Get URL for placeholder image
	 *
	 * @param string $url
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function getPlaceholderImage($url = '', array $options = []) {
		// Merge options with default options
		$options = array_merge([
			'width'  => 500,
			'height' => 500,
			'text'   => 'Image not found',
		], $options);

		// Set protocol for placeholder image URL
		$protocol = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$protocol = 'https';
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
			$protocol = 'https';
		}

		// Build querystring
		$urlQuery = [
			'w'        => $options['width'],
			'h'        => $options['height'],
			'txt'      => urlencode($options['text']),
			'bg'       => 'FFFFFF',
			'txtclr'   => '777777',
			'txttrack' => 0,
			'txtsize'  => 60,
			'original' => $url,
		];
		$urlQuery = array_map(function ($key, $value) {
			return $key . '=' . $value;
		}, array_keys($urlQuery), array_values($urlQuery));

		// Build URL
		$url = $protocol . '://placeholdit.imgix.net/~text?' . implode('&', $urlQuery);
		unset($urlQuery);

		// Return URL for placeholder image
		return $url;
	}

	/**
	 * Get MIME type for a given file path
	 *
	 * @param string $filePath
	 *
	 * @return mixed|string
	 */
	public static function getMimeType($filePath) {
		if (stristr(PHP_OS, 'WIN')) {
			$os = 'WIN';
		} else {
			$os = PHP_OS;
		}

		$mimeType = '';

		if (function_exists('mime_content_type') && $os != 'WIN') {
			$mimeType = mime_content_type($filePath);
		}

		// use PECL fileinfo to determine mime type
		if (!self::isValidMimeType($mimeType)) {
			if (function_exists('finfo_open')) {
				$finfo = @finfo_open(FILEINFO_MIME);
				if ($finfo != '') {
					$mimeType = finfo_file($finfo, $filePath);
					finfo_close($finfo);
				}
			}
		}

		// try to determine mime type by using unix file command
		// this should not be executed on windows
		if (!self::isValidMimeType($mimeType) && $os != "WIN") {
			if (preg_match("/FreeBSD|FREEBSD|LINUX/", $os)) {
				$mimeType = trim(@shell_exec('file -bi ' . escapeshellarg($filePath)));
			}
		}

		// use file's extension to determine mime type
		if (!self::isValidMimeType($mimeType)) {
			// set defaults
			$mimeType = 'image/png';
			// file details
			$fileDetails = pathinfo($filePath);
			$ext         = strtolower($fileDetails["extension"]);
			// mime types
			$types = [
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'png'  => 'image/png',
				'gif'  => 'image/gif',
			];

			if (strlen($ext) && strlen($types[$ext])) {
				$mimeType = $types[$ext];
			}
		}

		return $mimeType;
	}

	/**
	 * Check for valid MIME type
	 *
	 * @param string $mimeType
	 *
	 * @return bool
	 */
	public static function isValidMimeType($mimeType) {
		if (preg_match('#jpg|jpeg|gif|png#i', $mimeType)) {
			return true;
		}

		return false;
	}

	/**
	 * Calculate constrained image dimensions
	 *
	 * @param int $current_width
	 * @param int $current_height
	 * @param int $max_width
	 * @param int $max_height
	 *
	 * @return array
	 */
	public static function constrainDimensions($current_width, $current_height, $max_width = 0, $max_height = 0) {
		if (!$max_width and !$max_height) {
			return [
				$current_width,
				$current_height,
			];
		}

		$width_ratio = $height_ratio = 1.0;
		$did_width   = $did_height = false;

		if ($max_width > 0 && $current_width > 0 && $current_width > $max_width) {
			$width_ratio = $max_width / $current_width;
			$did_width   = true;
		}

		if ($max_height > 0 && $current_height > 0 && $current_height > $max_height) {
			$height_ratio = $max_height / $current_height;
			$did_height   = true;
		}

		// Calculate the larger/smaller ratios
		$smaller_ratio = min($width_ratio, $height_ratio);
		$larger_ratio  = max($width_ratio, $height_ratio);

		if (intval($current_width * $larger_ratio) > $max_width || intval($current_height * $larger_ratio) > $max_height) {
			// The larger ratio is too big. It would result in an overflow.
			$ratio = $smaller_ratio;
		} else {
			// The larger ratio fits, and is likely to be a more "snug" fit.
			$ratio = $larger_ratio;
		}

		$w = intval($current_width * $ratio);
		$h = intval($current_height * $ratio);

		// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
		// We also have issues with recursive calls resulting in an ever-changing result. Contraining to the result of a constraint should yield the original result.
		// Thus we look for dimensions that are one pixel shy of the max value and bump them up
		if ($did_width && $w == $max_width - 1) {
			$w = $max_width; // Round it up
		}
		if ($did_height && $h == $max_height - 1) {
			$h = $max_height; // Round it up
		}

		return [
			$w,
			$h,
		];
	}

	/**
	 * Convert hexadecimal color string to RGB
	 *
	 * @param string $hex
	 *
	 * @return array|boolean
	 */
	public static function hex2rgb($hex) {
		$color = str_replace('#', '', $hex);

		if (strlen($color) == 3) {
			return [
				hexdec(str_repeat(substr($color, 0, 1), 2)),
				hexdec(str_repeat(substr($color, 1, 1), 2)),
				hexdec(str_repeat(substr($color, 2, 1), 2)),
			];
		}

		if (strlen($color) == 6) {
			return [
				hexdec(substr($color, 0, 2)),
				hexdec(substr($color, 2, 2)),
				hexdec(substr($color, 4, 2)),
			];
		}

		return false;
	}

	/**
	 * Make a cURL request for a given URL
	 *
	 * @param string $url
	 * @param array  $options [
	 * 'type'         => 'ping',
	 * 'validate_url' => false,
	 * 'timeout'      => 300,
	 * 'redirect'     => 1
	 * ]
	 *
	 * @return mixed
	 */
	public static function curlRequest($url, $options = []) {
		// Merge options with default options
		$options = array_merge([
			// (string) Request type: ping|headers|body
			'type'         => 'ping',
			// (boolean) Validate URL
			'validate_url' => false,
			// (integer) Timeout for the request
			'timeout'      => 300,
			// (integer) Number of redirects to follow
			'redirect'     => 1,
			// (array) Custom HTTP headers
			'http_headers' => [],
			// (array) Custom cURL options
			'curl_options' => [],
		], $options);

		// Validate URL ?
		if ($options['validate_url']) {
			// Rebuild URL path to ensure proper formatting
			$urlParts = parse_url($url);
			if (isset($urlParts['path'])) {
				$tmpArr           = explode('/', trim($urlParts['path'], '/'));
				$urlParts['path'] = '';
				foreach ($tmpArr as $tmp) {
					$urlParts['path'] .= '/' . rawurlencode($tmp);
				}
			}

			// Check for valid URL
			if (!is_array($urlParts) || !isset($urlParts['scheme'])) {
				return false;
			}

			// Rebuild URL
			$url = $urlParts['scheme'] . '://' . $urlParts['host'] . (isset($urlParts['path']) ? $urlParts['path'] : '') . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
		}

		// Set default return value
		$returnValue = false;

		// Set cURL options
		$curlOptions = [
			CURLOPT_USERAGENT      => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
			CURLOPT_FAILONERROR    => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => ceil($options['timeout'] / 10),
			CURLOPT_TIMEOUT        => $options['timeout'],
			CURLOPT_FOLLOWLOCATION => (bool) $options['redirect'],
			CURLOPT_MAXREDIRS      => $options['redirect'],
		];

		// Set options based on type
		switch ($options['type']) {
			case 'ping':
				$curlOptions += [
					CURLOPT_NOBODY => true,
				];
			break;
			case 'headers':
				$curlOptions += [
					CURLOPT_NOBODY   => true,
					CURLOPT_HEADER   => true,
					CURLOPT_FILETIME => true,
				];
			break;
			case 'body':
				$curlOptions += [];
			break;
		}

		// Apply custom HTTP headers, if given
		if (is_array($options['http_headers']) && count($options['http_headers'])) {
			$curlOptions += [
				CURLOPT_HTTPHEADER => $options['http_headers'],
			];
		}

		// Apply custom cURL options, if given
		if (is_array($options['curl_options']) && count($options['curl_options'])) {
			$curlOptions += $options['curl_options'];
		}

		// Execute cURL request
		$curl = curl_init($url);
		curl_setopt_array($curl, $curlOptions);
		$response = curl_exec($curl);

		// Set return value based on type
		switch ($options['type']) {
			case 'ping':
				$returnValue = (curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200);
			break;
			case 'headers':
				$returnValue = curl_getinfo($curl);
				ksort($returnValue);
			break;
			case 'body':
				$returnValue = $response;
			break;
		}

		// Close cURL handle
		curl_close($curl);

		// Return the return value
		return $returnValue;
	}
}
