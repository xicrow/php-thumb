<?php
namespace Xicrow\PhpThumb\Engine;

use Xicrow\PhpThumb\EngineInterface;
use Xicrow\PhpThumb\Utility;

/**
 * Class GdLibrary
 *
 * @package Xicrow\PhpThumb\Engine
 */
class GdLibrary implements EngineInterface {
	/**
	 * Resource for original image
	 *
	 * @var null|resource
	 */
	private $resourceOriginal = null;

	/**
	 * Resource for current modified image
	 *
	 * @var null|resource
	 */
	private $resourceCurrent = null;

	/**
	 * MIME type of the image
	 *
	 * @var null|string
	 */
	private $mimeType = null;

	/**
	 * GdLibrary constructor.
	 */
	public function __construct() {
		// Check prerequisite
		if (!function_exists('imagecreatetruecolor')) {
			die('\Xicrow\PhpThumb\Engine\GdLibrary: Missing "imagecreatetruecolor" function');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function load($file, array $options = []) {
		$this->mimeType = Utility::getMimeType($file);

		$this->resourceOriginal = false;
		if (strpos($this->mimeType, 'gif') !== false) {
			$this->resourceOriginal = imagecreatefromgif($file);
		} elseif (strpos($this->mimeType, 'jpeg') !== false) {
			@ini_set('gd.jpeg_ignore_warning', 1);
			$this->resourceOriginal = imagecreatefromjpeg($file);
		} elseif (strpos($this->mimeType, 'png') !== false) {
			$this->resourceOriginal = imagecreatefrompng($file);
		}

		return is_resource($this->resourceOriginal);
	}

	/**
	 * @inheritdoc
	 */
	public function resize(array $options = []) {
		// Get original width and height
		$originalWidth  = imagesx($this->resourceOriginal);
		$originalHeight = imagesy($this->resourceOriginal);

		// Check for missing dimensions
		if ($options['width'] && !$options['height']) {
			$options['height'] = $originalHeight * ($options['width'] / $originalWidth);
		} elseif ($options['height'] && !$options['width']) {
			$options['width'] = $originalWidth * ($options['height'] / $originalHeight);
		} elseif (!$options['width'] && !$options['height']) {
			$options['width']  = $originalWidth;
			$options['height'] = $originalHeight;
		}

		// Canvas options
		$canvasWidth  = $options['width'];
		$canvasHeight = $options['height'];

		// Options for imagecopyresampled()
		$dstW = $options['width'];
		$dstH = $options['height'];
		$dstX = 0;
		$dstY = 0;
		$srcW = $originalWidth;
		$srcH = $originalHeight;
		$srcX = 0;
		$srcY = 0;

		// Switch resize methods
		switch ($options['method']) {
			case 'crop':
				// Options for imagecopyresampled()
				$dstW = $options['width'];
				$dstH = $options['height'];
				$dstX = 0;
				$dstY = 0;
				$srcW = $originalWidth;
				$srcH = $originalHeight;
				$srcX = 0;
				$srcY = 0;

				// Compare width and height
				$compareWidth  = $originalWidth / $options['width'];
				$compareHeight = $originalHeight / $options['height'];

				// Calculate width or height of source
				if ($compareWidth > $compareHeight) {
					$srcW = round(($originalWidth / $compareWidth * $compareHeight));
				} elseif ($compareHeight > $compareWidth) {
					$srcH = round(($originalHeight / $compareHeight * $compareWidth));
				}

				$underSized = false;
				if (!$options['stretch'] && ($dstW > $srcW || $dstH > $srcH)) {
					$underSized = true;
				}

				if ($underSized) {
					$srcW = $originalWidth;
					$srcH = $originalHeight;
					$dstW = $srcW;
					$dstH = $srcH;
				}

				// Calculate destination X and Y coordinates
				switch ($options['align_x']) {
					case 'left':
					break;
					case 'center':
						if (!$underSized) {
							$srcX = (($originalWidth - $srcW) / 2);
						} else {
							$dstX = (($options['width'] - $srcW) / 2);
						}
					break;
					case 'right':
						if (!$underSized) {
							$srcX = ($originalWidth - $srcW);
						} else {
							$dstX = ($options['width'] - $srcW);
						}
					break;
					default:
						die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown horizontal alignment: ' . $options['align_x']);
					break;
				}
				switch ($options['align_y']) {
					case 'top':
					break;
					case 'middle':
						if (!$underSized) {
							$srcY = (($originalHeight - $srcH) / 2);
						} else {
							$dstY = (($options['height'] - $srcH) / 2);
						}
					break;
					case 'bottom':
						if (!$underSized) {
							$srcY = ($originalHeight - $srcH);
						} else {
							$dstY = ($options['height'] - $srcH);
						}
					break;
					default:
						die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown vertical alignment: ' . $options['align_y']);
					break;
				}
			break;
			case 'fit':
				// Calculate multiplier for original size if stretching is allowed, and image is smaller than requested
				$originalMultiplier = 1;
				if ($options['stretch'] && ($options['width'] > $originalWidth || $options['height'] > $originalHeight)) {
					// Calculate required multiplier level
					if (ceil($options['width'] / $originalWidth) > $originalMultiplier) {
						$originalMultiplier = ceil($options['width'] / $originalWidth);
					}
					if (ceil($options['height'] / $originalHeight) > $originalMultiplier) {
						$originalMultiplier = ceil($options['height'] / $originalHeight);
					}
				}

				// Get constrained image dimensions
				list($fitWidth, $fitHeight) = Utility::constrainDimensions(($originalWidth * $originalMultiplier), ($originalHeight * $originalMultiplier), $options['width'], $options['height']);

				// If fit method is used and trim is enabled, adjust width and height to image dimensions
				if ($options['method'] == 'fit' && $options['trim']) {
					$canvasWidth  = $fitWidth;
					$canvasHeight = $fitHeight;
				}

				// Options for imagecopyresampled()
				$dstW = $fitWidth;
				$dstH = $fitHeight;
				$dstX = 0;
				$dstY = 0;
				$srcW = $originalWidth;
				$srcH = $originalHeight;
				$srcX = 0;
				$srcY = 0;

				// Calculate destination X and Y coordinates
				switch ($options['align_x']) {
					case 'left':
					break;
					case 'center':
						$dstX = (($options['width'] - $dstW) / 2);
					break;
					case 'right':
						$dstX = ($options['width'] - $dstW);
					break;
					default:
						die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown horizontal alignment: ' . $options['align_x']);
					break;
				}
				switch ($options['align_y']) {
					case 'top':
					break;
					case 'middle':
						$dstY = (($options['height'] - $dstH) / 2);
					break;
					case 'bottom':
						$dstY = ($options['height'] - $dstH);
					break;
					default:
						die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown vertical alignment: ' . $options['align_y']);
					break;
				}
			break;
		}

		// Get image background color setting
		$background = 'transparent';
		if ($options['background']) {
			$background = $options['background'];
		}
		if ($background != 'transparent' && (strlen($background) != 7 || substr($background, 0, 1) != '#')) {
			$background = 'transparent';
		}

		// Create canvas
		$this->resourceCurrent = imagecreatetruecolor($canvasWidth, $canvasHeight);

		// Transparent or solid background color
		if ($background == 'transparent') {
			// Create a new transparent color for image
			if (strpos($this->mimeType, 'png') !== false) {
				// Disable blending mode for the image
				imagealphablending($this->resourceCurrent, false);

				// Set transparent color
				$color = imagecolorallocatealpha($this->resourceCurrent, 255, 255, 255, 127);
			} else {
				// Set color
				$color = imagecolorallocate($this->resourceCurrent, 255, 255, 255);
			}

			// Completely fill the background of the new image with allocated color.
			imagefill($this->resourceCurrent, 0, 0, $color);

			if (strpos($this->mimeType, 'png') !== false) {
				// Restore transparency blending
				imagesavealpha($this->resourceCurrent, true);
			}
		} else {
			$backgroundRgb = Utility::hex2rgb($background);

			// Create a new transparent color for image
			$color = imagecolorallocate($this->resourceCurrent, $backgroundRgb[0], $backgroundRgb[1], $backgroundRgb[2]);

			// Completely fill the background of the new image with allocated color.
			imagefill($this->resourceCurrent, 0, 0, $color);
		}

		// Copy and resize part of the image with resampling
		imagecopyresampled($this->resourceCurrent, $this->resourceOriginal, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

		// Convert image to grayscale
		if ($options['grayscale']) {
			imagefilter($this->resourceCurrent, IMG_FILTER_GRAYSCALE);
		}

		return is_resource($this->resourceOriginal);
	}

	/**
	 * @inheritdoc
	 */
	public function watermark(array $options = []) {
		// @todo
	}

	/**
	 * @inheritdoc
	 */
	public function save($file, array $options = []) {
		// Make sure folder exists
		$folderPath = dirname($file);
		if (!file_exists($folderPath)) {
			mkdir($folderPath, 0755, true);
		}

		// Save image data to file path
		if (strpos($this->mimeType, 'png') !== false) {
			return imagepng($this->resourceCurrent, $file, floor($options['quality'] * 0.09));
		} else {
			return imagejpeg($this->resourceCurrent, $file, $options['quality']);
		}
	}
}
