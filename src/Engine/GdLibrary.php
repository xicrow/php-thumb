<?php
namespace Xicrow\PhpThumb\Engine;

use Xicrow\PhpThumb\EngineInterface;
use Xicrow\PhpThumb\Utility;

/**
 * Class GdLibrary
 *
 * @package Xicrow\PhpThumb\Engine
 */
class GdLibrary implements EngineInterface
{
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
    public function __construct()
    {
        // Check prerequisite
        if (!function_exists('imagecreatetruecolor')) {
            trigger_error('\Xicrow\PhpThumb\Engine\GdLibrary: Missing "imagecreatetruecolor" function', E_USER_ERROR);
        }
    }

    /**
     * Get the current resource
     *
     * @return null|resource
     */
    public function getResourceCurrent()
    {
        return $this->resourceCurrent;
    }

    /**
     * @inheritdoc
     */
    public function load($file, array $options = [])
    {
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
    public function resize(array $options = [])
    {
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
                list($fitWidth, $fitHeight) = Utility::constrainDimensions(($originalWidth * $originalMultiplier), ($originalHeight * $originalMultiplier), $options['width'],
                    $options['height']);

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
        if (!empty($options['background'])) {
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
            // Create a new transparent color for image
            $backgroundRgb = Utility::hex2rgb($background);
            $color         = imagecolorallocate($this->resourceCurrent, $backgroundRgb[0], $backgroundRgb[1], $backgroundRgb[2]);

            // Completely fill the background of the new image with allocated color.
            imagefill($this->resourceCurrent, 0, 0, $color);
        }

        // Copy and resize part of the image with resampling
        imagecopyresampled($this->resourceCurrent, $this->resourceOriginal, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        // Convert image to grayscale
        if (!empty($options['grayscale'])) {
            imagefilter($this->resourceCurrent, IMG_FILTER_GRAYSCALE);
        }

        return is_resource($this->resourceCurrent);
    }

    /**
     * @inheritdoc
     */
    public function watermark(array $options = [])
    {
        // Get original width and height
        $originalWidth  = imagesx($this->resourceCurrent);
        $originalHeight = imagesy($this->resourceCurrent);

        // If image is given
        if (!empty($options['image'])) {
            // Get watermark image resource
            $watermark = new GdLibrary();
            $watermark->load($options['image']);
            $watermark->resize([
                'width'      => $options['width'],
                'height'     => $options['height'],
                'method'     => 'fit',
                'stretch'    => true,
                'trim'       => false,
                'align_x'    => 'center',
                'align_y'    => 'middle',
                'background' => 'transparent',
            ]);
            $watermarkResource = $watermark->getResourceCurrent();
            unset($watermark);

            // Options for imagecopyresampled()
            $dstW = $options['width'];
            $dstH = $options['height'];
            $dstX = 0;
            $dstY = 0;
            $srcW = $options['width'];
            $srcH = $options['height'];
            $srcX = 0;
            $srcY = 0;

            // Calculate destination X and Y coordinates
            switch ($options['align_x']) {
                case 'left':
                break;
                case 'center':
                    $dstX = (($originalWidth - $dstW) / 2);
                break;
                case 'right':
                    $dstX = ($originalWidth - $dstW);
                break;
                default:
                    die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown horizontal alignment: ' . $options['align_x']);
                break;
            }
            switch ($options['align_y']) {
                case 'top':
                break;
                case 'middle':
                    $dstY = (($originalHeight - $dstH) / 2);
                break;
                case 'bottom':
                    $dstY = ($originalHeight - $dstH);
                break;
                default:
                    die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown vertical alignment: ' . $options['align_y']);
                break;
            }

            // Copy and resize part of the image with resampling
            imagecopyresampled($this->resourceCurrent, $watermarkResource, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        }

        // If text is given
        if (!empty($options['text'])) {
            // Set angle
            $angle = 0;

            // Set padding
            $padding = 5;

            // Get bounding box for the text
            $textBoundingBox = imagettfbbox($options['font_size'], $angle, $options['font'], $options['text']);

            // Get text width and height
            $textWidth  = abs($textBoundingBox[4] - $textBoundingBox[0]) + $padding;
            $textHeight = abs($textBoundingBox[5] - $textBoundingBox[1]) + $padding;
            unset($textBoundingBox);

            // Calculate text X and Y coordinates
            $x = 0;
            $y = 0;
            switch ($options['align_x']) {
                case 'left':
                break;
                case 'center':
                    $x = (($originalWidth - $textWidth) / 2);
                break;
                case 'right':
                    $x = ($originalWidth - $textWidth);
                break;
                default:
                    die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown horizontal alignment: ' . $options['align_x']);
                break;
            }
            switch ($options['align_y']) {
                case 'top':
                break;
                case 'middle':
                    $y = (($originalHeight - $textHeight) / 2);
                break;
                case 'bottom':
                    $y = ($originalHeight - $textHeight);
                break;
                default:
                    die('\Xicrow\PhpThumb\Engine\GdLibrary: Unknown vertical alignment: ' . $options['align_y']);
                break;
            }

            // Add font size to Y coordinate
            $y += $options['font_size'];

            // Get color
            $colorRgb = Utility::hex2rgb($options['color']);
            $color    = imagecolorallocate($this->resourceCurrent, $colorRgb[0], $colorRgb[1], $colorRgb[2]);

            // Write text to image
            imagettftext($this->resourceCurrent, $options['font_size'], $angle, $x, $y, $color, $options['font'], $options['text']);
        }

        return is_resource($this->resourceCurrent);
    }

    /**
     * @inheritdoc
     */
    public function save($file, array $options = [])
    {
        // Make sure folder exists
        $folderPath = dirname($file);
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        if ($options['webp']) {
            return imagewebp($this->resourceCurrent, $file, $options['quality']);
        }

        // Save image data to file path
        if (strpos($this->mimeType, 'png') !== false) {
            return imagepng($this->resourceCurrent, $file, 9);
        } else {
            return imagejpeg($this->resourceCurrent, $file, $options['quality']);
        }
    }
}
