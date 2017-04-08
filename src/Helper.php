<?php
namespace Xicrow\PhpThumb;

/**
 * Class Helper
 *
 * @package Xicrow\PhpThumb
 */
class Helper {
	/**
	 * Get thumnail URL
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return string
	 */
	public static function getThumbUrl($image, array $options = []) {
		// Merge options with Thumb default options
		$options = Thumb::mergeOptions($options);

		// Return placeholder if not image is given
		if (empty($image)) {
			return Utility::getPlaceholderImage($image, [
				'width'  => $options['resize']['width'],
				'height' => $options['resize']['height'],
				'text'   => 'Image not given',
			]);
		}

		// Return placeholder if image does not exist
		$imagePath = Thumb::getImagePath($image, $options);
		if (!is_file($imagePath) || !file_exists($imagePath) || !is_readable($imagePath)) {
			return Utility::getPlaceholderImage($image, [
				'width'  => $options['resize']['width'],
				'height' => $options['resize']['height'],
				'text'   => 'Image not found',
			]);
		}

		// Save options to file
		if (!self::saveOptions($image, $options)) {
			die('\Xicrow\PhpThumb\Helper: Unable to save options');
		}

		// Get thumbnail path
		$thumbPath = Thumb::getThumbPath($image, $options);

		// Get thumbnail URL
		$thumbUrl = $thumbPath;
		$thumbUrl = str_replace($options['path_thumbs'], '', $thumbUrl);
		$thumbUrl = str_replace(DIRECTORY_SEPARATOR, '/', $thumbUrl);

		// Return URL for the thumbnail
		return $thumbUrl;
	}

	/**
	 * Save thumbnail options
	 *
	 * @param string $image
	 * @param array  $options
	 *
	 * @return bool
	 */
	public static function saveOptions($image, array $options = []) {
		// Merge options with Thumb default options
		$options = Thumb::mergeOptions($options);

		// Get thumbnail path
		$thumbPath = Thumb::getThumbPath($image, $options);

		// Set thumbnail option path
		$thumbOptionPath = $thumbPath;
		$thumbOptionPath = str_replace('.' . pathinfo($thumbPath, PATHINFO_EXTENSION), '.opt', $thumbOptionPath);

		$result = true;

		// If thumbnail option does not exist
		if (!file_exists($thumbOptionPath)) {
			// Make sure folder exists
			$folderPath = dirname($thumbOptionPath);
			if (!file_exists($folderPath)) {
				mkdir($folderPath, 0755, true);
			}

			// Write options to file
			$result = (bool) file_put_contents($thumbOptionPath, json_encode(['image' => $image, 'options' => $options]));
		}

		// Return result
		return $result;
	}

	/**
	 * Load thumbnail options
	 *
	 * @param string $thumbPath
	 *
	 * @return array|bool
	 */
	public static function loadOptions($thumbPath) {
		// Set thumbnail option path
		$thumbOptionPath = $thumbPath;
		$thumbOptionPath = str_replace('.' . pathinfo($thumbPath, PATHINFO_EXTENSION), '.opt', $thumbOptionPath);

		// Default options to return
		$options = false;

		// If thumbnail option exists
		if (file_exists($thumbOptionPath)) {
			// Get options from file
			$options = json_decode(file_get_contents($thumbOptionPath), true);
		}

		// Return options
		return $options;
	}
}
