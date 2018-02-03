<?php
namespace Xicrow\PhpThumb;

/**
 * Class Thumb
 *
 * @package Xicrow\PhpThumb
 */
class Thumb {
	/**
	 * Engine to use for resizing
	 *
	 * @var string
	 */
	private static $engine = '\Xicrow\PhpThumb\Engine\GdLibrary';

	/**
	 * Default options, can be modified for class generally on bootstrap or on each call
	 *
	 * @var array
	 */
	private static $options = [
		// Path to folder for remote cache
		'remote.cache'         => './cache_remote',
		// Expires time for remote cache
		'remote.cache.expires' => '1 month',
		// Full path to folder used for images given with relative path
		'path_images'          => './images',
		// Full path to folder used for thumbnails
		'path_thumbs'          => './thumbs',
		// Full path to folder used for watermarks given with relative path
		'path_watermarks'      => './watermarks',
		// Full path to folder used for fonts given with relative path
		'path_fonts'           => './Fonts',
		// Resize options
		'resize'               => [
			// Width of the thumbnail (empty value to auto calculate in relation to height)
			'width'      => 500,
			// Height of the thumbnail (empty value to auto calculate in relation to width)
			'height'     => 500,
			// Method to use when resizing
			'method'     => 'fit',
			// Stretch image if smaller than given dimensions
			'stretch'    => true,
			// Trim whitespace if fit method is used
			'trim'       => false,
			// Horizontal alignment of resized image within canvas (left, center, right)
			'align_x'    => 'center',
			// Vertical alignment of resized image within canvas (top, middle, bottom)
			'align_y'    => 'middle',
			// Color used for background when not trimming fitted thumbnails ('transparent' or hex color ie. #FFFFFF)
			'background' => 'transparent',
			// Greyscale the thumbnail
			'grayscale'  => false,
		],
		// Watermark options
		'watermark'            => [
			// Image to add as watermark
			'image'     => false,
			// Width of the watermark image
			'width'     => 100,
			// Height of the watermark image
			'height'    => 100,
			// Text to add as watermark
			'text'      => false,
			// Font to use for text
			'font'      => 'Arial',
			// Font size to use for text
			'font_size' => 10,
			// Color to use for text (hex color ie. #FFFFFF)
			'color'     => '#FFFFFF',
			// Horizontal alignment of watermark within canvas (left, center, right)
			'align_x'   => 'center',
			// Vertical alignment of watermark within canvas (top, middle, bottom)
			'align_y'   => 'middle',
		],
		// Quality of the generated image
		'quality'              => 80,
	];

	/**
	 * Set the engine to use for resizing
	 *
	 * @param string $engine
	 *
	 * @return bool
	 */
	public static function setEngine($engine) {
		// Check if supplied engine is valid
		if (!class_exists($engine)) {
			// Return false, invalid engine
			return false;
		}
		$engineInstance = new $engine();
		if (!$engineInstance instanceof EngineInterface) {
			// Return false, invalid engine
			return false;
		}

		// Set engine to use
		self::$engine = $engine;

		// Return true, new engine set
		return true;
	}

	/**
	 * Overwrite default options
	 *
	 * @param array $options
	 */
	public static function setOptions(array $options = []) {
		// Merge options with default options
		self::$options = self::mergeOptions($options);
	}

	/**
	 * Merge and return given options with default options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function mergeOptions(array $options = []) {
		return array_replace_recursive(self::$options, $options);
	}

	/**
	 * Get full image path
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function getImagePath($image, array $options = []) {
		// Merge options with default options
		$options = self::mergeOptions($options);

		// Remote file ?
		if (substr($image, 0, 4) == 'http') {
			// Set path for remote cache file
			$remoteCachePath = $image;
			$remoteCachePath = ltrim($remoteCachePath, 'https://');
			$remoteCachePath = str_replace('~', '', $remoteCachePath);
			$remoteCachePath = preg_replace('#/{2,}#', '/', $remoteCachePath);
			if (mb_strpos($remoteCachePath, '?') !== false) {
				$remoteCachePath = mb_substr($remoteCachePath, 0, mb_strpos($remoteCachePath, '?')) . '-'.md5(mb_substr($remoteCachePath, mb_strpos($remoteCachePath, '?')));
			}
			$remoteCachePath = str_replace('/', DIRECTORY_SEPARATOR, $remoteCachePath);
			if (!preg_match('#\.(jpg|jpeg|png|gif)#', $remoteCachePath)) {
			    $remoteCachePath.= '.jpg';
            }
			$remoteCachePath = $options['remote.cache'] . DIRECTORY_SEPARATOR . $remoteCachePath;

			// Check if remote cache exists and is valid
			$remoteCacheValid = false;
			if (file_exists($remoteCachePath) && is_file($remoteCachePath) && is_readable($remoteCachePath)) {
				$remoteCacheExpireTime = strtotime('+' . $options['remote.cache.expires'], filemtime($remoteCachePath));
				$remoteCacheValid      = ($remoteCacheExpireTime > time());
			}

			// If remote cache is not valid
			if (!$remoteCacheValid) {
				// Get remote data
				$remoteData = Utility::curlRequest($image, [
					'type'         => 'body',
					'validate_url' => false,
					'timeout'      => 5,
					'redirect'     => 1,
				]);

				// Save remote data to cache
				if (!file_exists(dirname($remoteCachePath))) {
					mkdir(dirname($remoteCachePath), 0755, true);
				}
				file_put_contents($remoteCachePath, $remoteData);
			}

			// Check if remote cache has any data
			if (filesize($remoteCachePath) == 0) {
				$remoteCachePath = false;
			}

			// Return path to remote cache
			$imagePath = $remoteCachePath;
		} else {
			// Get full image path
			$imagePath = $image;
			if (mb_strpos($imagePath, '?') !== false) {
				$imagePath = mb_substr($imagePath, 0, mb_strpos($imagePath, '?'));
			}
			if (file_exists($imagePath)) {
				$imagePath = realpath($imagePath);
			} else {
				$imagePath = rtrim($options['path_images'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($imagePath, DIRECTORY_SEPARATOR);
			}
			$imagePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
		}

		// Return path to image
		return $imagePath;
	}

	/**
	 * Get full thumbnail path
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function getThumbPath($image, array $options = []) {
		// Merge options with default options
		$options = self::mergeOptions($options);

		// Get image path
		$imagePath = self::getImagePath($image, $options);

		// Get image modified
		$imageModified = null;
		if (file_exists($imagePath)) {
			$imageModified = filemtime($imagePath);
		}

		// Get image path information
		$imageFolder         = pathinfo($imagePath, PATHINFO_DIRNAME);
		$imageFolderRelative = false;
		if ($imageFolder != $options['path_images'] && strpos($imageFolder, $options['path_images']) !== false) {
			$imageFolderRelative = trim(substr($imageFolder, strlen($options['path_images'])), DIRECTORY_SEPARATOR);
		}
		$imageFileName = pathinfo($imagePath, PATHINFO_FILENAME);
		$imageFileExt  = pathinfo($imagePath, PATHINFO_EXTENSION);

		// Set path to thumbnail file
		$thumbPath = rtrim($options['path_thumbs'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if ($imageFolderRelative) {
			$thumbPath .= $imageFolderRelative . DIRECTORY_SEPARATOR;
		}
		$thumbPath .= $imageFileName . '-' . md5(json_encode($options) . $imageModified) . '.' . $imageFileExt;

		// Return path to thumbnail
		return $thumbPath;
	}

	/**
	 * Resize an image
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function resize($image, array $options = []) {
		// Merge options with default options
		$options = self::mergeOptions($options);

		// Get image path
		$imagePath = self::getImagePath($image, $options);

		// Get thumbnail path
		$thumbPath = self::getThumbPath($image, $options);

		// If image exist
		if (file_exists($imagePath)) {
			// If thumbnail does not already exist
			if (!file_exists($thumbPath)) {
				/**
				 * Get engine instance
				 *
				 * @var \Xicrow\PhpThumb\EngineInterface $engine
				 */
				$engine = new self::$engine();

				// Load image
				$engine->load($imagePath, $options);
				if ($options['resize'] && (!empty($options['resize']['width']) || !empty($options['resize']['height']))) {
					// Resize
					$engine->resize($options['resize']);
				}
				if ($options['watermark'] && (!empty($options['watermark']['image']) || !empty($options['watermark']['text']))) {
					// Watermark
					if (!empty($options['watermark']['image']) && !file_exists($options['watermark']['image'])) {
						$options['watermark']['image'] = rtrim($options['path_watermarks'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $options['watermark']['image'];
					}
					if (!empty($options['watermark']['text']) && !file_exists($options['watermark']['font'])) {
						$options['watermark']['font'] = rtrim($options['path_fonts'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $options['watermark']['font'];
					}
					$engine->watermark($options['watermark']);
				}

				// Save thumbnail
				$engine->save($thumbPath, $options);

				// Clear engine from memory
				unset($engine);
			}
		}

		// Return path to thumbnail
		return $thumbPath;
	}
}
