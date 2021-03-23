<?php
use Xicrow\PhpThumb\Helper;
use Xicrow\PhpThumb\Thumb;

require_once('../vendor/autoload.php');

ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('log_errors', 0);

// Set memory limit, image handling can get heavy...
// - 128MB wasn't enough for at 25MB image
// @todo Test memory limits for various images, maybe set memory limit dynamically based on original image size ?
ini_set('memory_limit', '256M');

// Set default options
Thumb::setOptions([
	// Path to folder for remote cache
	'remote.cache'         => realpath('./webroot/cache_remote'),
	// Expires time for remote cache
	'remote.cache.expires' => '1 month',
	// Full path to folder used for images given with relative path
	'path_images'          => realpath('./webroot'),
	// Full path to folder used for thumbnails
	'path_thumbs'          => realpath('./webroot/thumbnails'),
	// Full path to folder used for watermarks given with relative path
	'path_watermarks'      => realpath('./webroot'),
	// Full path to folder used for fonts given with relative path
	'path_fonts'           => realpath('../src/Fonts'),
	// Quality of the generated image
	'quality'              => 90,
]);

/**
 * @param string $strImage
 * @param array  $arrOptions
 *
 * @return string
 */
function getThumbUrl(string $strImage, array $arrOptions = []): string
{
	if (!array_key_exists('webp', $arrOptions) && !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
		$arrOptions['webp'] = true;
	}

	// Resize in seperate process
	$strThumbUrl = Helper::getThumbUrl($strImage, $arrOptions);
	if (substr($strThumbUrl, 0, 4) != 'http') {
		$strThumbUrl = './webroot/thumbnails' . $strThumbUrl;
	}

	// Resize now
	//					$thumbPath = Thumb::resize($strImage, $arrOptions);
	//					$strThumbUrl  = str_replace('E:\\GitHub\\php-thumb\\demo\\', './', $thumbPath);
	//					$strThumbUrl  = str_replace('\\', '/', $strThumbUrl);

	// Return thumbnail URL
	return $strThumbUrl;
}

/**
 * @param string $strImage
 * @param array  $arrOptions
 *
 * @return string
 */
function getThumbAndDetails(string $strImage, array $arrOptions): string
{
	$strThumbUrl = getThumbUrl($strImage, $arrOptions);

	$strThumbFileSize = 'N/A';
	$strThumbMimeType = 'N/A';
	if (file_exists($strThumbUrl)) {
		$strThumbFileSize = filesize($strThumbUrl) . ' bytes';
		$strThumbMimeType = mime_content_type($strThumbUrl);
	}

	return '
	<table style="width: 100%;">
		<!-- Options -->
		<tr><th style="width: 50%;">Option</th><th>Value</th></tr>
		<tr><td>Size</td><td>' . $arrOptions['resize']['width'] . 'x' . $arrOptions['resize']['height'] . '</td></tr>
		<tr><td>Align</td><td>' . $arrOptions['resize']['align_x'] . ' ' . $arrOptions['resize']['align_y'] . '</td></tr>
		<tr><td>Method</td><td>' . $arrOptions['resize']['method'] . '</td></tr>
		<tr><td>Stretch</td><td>' . ($arrOptions['resize']['stretch'] ? 'Yes' : 'No') . '</td></tr>
		<tr><td>Trim</td><td>' . ($arrOptions['resize']['trim'] ? 'Yes' : 'No') . '</td></tr>
		<tr><td>Grayscale</td><td>' . ($arrOptions['resize']['grayscale'] ? 'Yes' : 'No') . '</td></tr>
		<tr><td>Background</td><td>' . $arrOptions['resize']['background'] . '</td></tr>
		<!-- Stats -->
		<tr><th>Stat</th><th>Value</th></tr>
		<tr><td>Size</td><td>' . $strThumbFileSize . '</td></tr>
		<tr><td>MIME</td><td>' . $strThumbMimeType . '</td></tr>
	</table>
	<div style="width: ' . $arrOptions['resize']['width'] . 'px; height: ' . $arrOptions['resize']['height'] . 'px;">
		<img src="' . $strThumbUrl . '" alt="' . $strThumbUrl . '" title="' . $strThumbUrl . '">
	</div>
	';
}

// Array with default options to replace/extend
$arrOptionsDefault = [
	// Resize options
	'resize'    => [
		// Width of the thumbnail (empty value to auto calculate in relation to height)
		'width'      => 500,
		// Height of the thumbnail (empty value to auto calculate in relation to width)
		'height'     => 400,
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
		'background' => '#666666',
		// Greyscale the thumbnail
		'grayscale'  => false,
	],
	// Watermark options
	'watermark' => [
		// File to add as watermark
		'image'     => false,
		// Width of the watermark image
		'width'     => 100,
		// Height of the watermark image
		'height'    => 100,
		// Text to add as watermark
		'text'      => false,
		// Font to use for text
		'font'      => 'arial.ttf',
		// Font size to use for text
		'font_size' => 15,
		// Color to use for text (hex color ie. #FFFFFF)
		'color'     => '#FFFFFF',
		// Horizontal alignment of watermark within canvas (left, center, right)
		'align_x'   => 'right',
		// Vertical alignment of watermark within canvas (top, middle, bottom)
		'align_y'   => 'bottom',
	],
];

// Array with images to perform work with
$arrImages = [
//	// Square
//	'images/1200x1200.jpg',
//	'images/Squat training.jpg',
//	'images/1200x1200.jpg?ver1',
//	'images/Squat training.jpg?ver2',
//	// Landscape
//	'images/F-16_Demo_Team_2722.jpg',
//	'images/controller-1.jpg',
//	'images/controller-2.jpg',
//	'images/controller-3.jpg',
//	// Portrait
//	'images/email-timing-full.jpg',
//	'images/gky7VZp.jpg',
//	// Transparency
//	'images/2000px-Chevronny_demo.svg.png',
//	'images/Doom_logo.png',
//	// Remote
//	'http://voids.dk/testpat.jpg',
//	'https://www.aceandtate.com/skin/frontend/aceandtate/default/img/prescription/prescription-landing-character-map.png',
//	'http://via.placeholder.com/300x300',
//	'http://via.placeholder.com/300x300?text=Test+1',
//	'http://via.placeholder.com/300x300?text=Test+2',
//	'http://via.placeholder.com/300x300?text=Test+3',
//	// Trigger placeholder images
//	'',
//	'images/non-existing.jpg',
];

// What tests to perform
$bDoTestSimple   = false;
$bDoTestAdvanced = false;
$bDoTestWebp     = false;
?>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Xicrow/PhpThumb</title>
		<style type="text/css">
			body {
				font-family: Verdana, sans-serif;
				font-size: 15px;
			}
			table {
				margin: 5px 0;
				font-size: inherit;
				border-top: 1px solid #CCC;
				border-right: 1px solid #CCC;
				border-collapse: collapse;
			}
			table tr th {
				padding: 5px;
				font-size: inherit;
				text-align: left;
				vertical-align: middle;
				border-bottom: 1px solid #CCC;
				border-left: 1px solid #CCC;
			}
			table tr td {
				padding: 5px;
				font-size: inherit;
				text-align: left;
				vertical-align: middle;
				border-bottom: 1px solid #CCC;
				border-left: 1px solid #CCC;
			}
		</style>
	</head>

	<body>
		<?php
		if ($bDoTestSimple) {
			$arrOptions = array_replace_recursive($arrOptionsDefault, [
				'resize'    => [
					'width'     => 800,
					'height'    => 600,
					'method'    => 'crop',
					'stretch'   => false,
					'trim'      => false,
					'align_x'   => 'center',
					'align_y'   => 'bottom',
					'grayscale' => false,
				],
				'watermark' => [
					'image'     => false,
					'width'     => 155,
					'height'    => 100,
					'text'      => false,
					'font'      => 'arial.ttf',
					'font_size' => 20,
					'color'     => '#000000',
					'align_x'   => 'right',
					'align_y'   => 'bottom',
				],
			]);
			foreach ($arrImages as $strImage) {
				echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
				echo getThumbAndDetails($strImage, $arrOptions);
				echo '</div>';
			}
		}

		if ($bDoTestAdvanced) {
			$arrOption1List = [
				[
					'resize' => [
						'method' => 'fit',
					],
				],
				[
					'resize' => [
						'method' => 'crop',
					],
				],
			];
			$arrOption2List = [
				[
					'resize' => [
						'stretch'   => false,
						'grayscale' => true,
					],
				],
				[
					'resize' => [
						'stretch'   => true,
						'grayscale' => false,
					],
				],
			];
			$arrOption3List = [
				[
					'resize' => [
						'align_x' => 'center',
						'align_y' => 'top',
					],
				],
				[
					'resize' => [
						'align_x' => 'center',
						'align_y' => 'bottom',
					],
				],
				[
					'resize' => [
						'align_x' => 'left',
						'align_y' => 'middle',
					],
				],
				[
					'resize' => [
						'align_x' => 'right',
						'align_y' => 'middle',
					],
				],
			];

			$arrOptionList = [];
			if (isset($arrOption1List)) {
				foreach ($arrOption1List as $arrOption1) {
					if (isset($arrOption2List)) {
						foreach ($arrOption2List as $arrOption2) {
							if (isset($arrOption3List)) {
								foreach ($arrOption3List as $arrOption3) {
									$arrOptionList[] = array_replace_recursive($arrOptionsDefault, $arrOption1, $arrOption2, $arrOption3);
								}
							} else {
								$arrOptionList[] = array_replace_recursive($arrOptionsDefault, $arrOption1, $arrOption2);
							}
						}
					} else {
						$arrOptionList[] = array_replace_recursive($arrOptionsDefault, $arrOption1);
					}
				}
			}

			foreach ($arrImages as $strImage) {
				echo '<div>';
				echo '<a href="webroot/' . $strImage . '">' . $strImage . '</a>';
				echo '<br />';

				foreach ($arrOptionList as $arrOptions) {
					echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
					echo getThumbAndDetails($strImage, $arrOptions);
					echo '</div>';
				}
				echo '<div style="clear: both;"></div>';
				echo '</div>';
			}
		}

		if ($bDoTestWebp) {
			$arrRegularOptions = array_replace_recursive($arrOptionsDefault, [
				'resize' => [
					// Width of the thumbnail (empty value to auto calculate in relation to height)
					'width'      => 400,
					// Height of the thumbnail (empty value to auto calculate in relation to width)
					'height'     => 300,
					// Method to use when resizing
					'method'     => 'crop',
					// Stretch image if smaller than given dimensions
					'stretch'    => true,
					// Trim whitespace if fit method is used
					'trim'       => false,
					// Horizontal alignment of resized image within canvas (left, center, right)
					'align_x'    => 'center',
					// Vertical alignment of resized image within canvas (top, middle, bottom)
					'align_y'    => 'middle',
					// Color used for background when not trimming fitted thumbnails ('transparent' or hex color ie. #FFFFFF)
					'background' => '#FFFFFF',
					// Greyscale the thumbnail
					'grayscale'  => false,
				],
				'webp'   => false,
			]);
			$arrWebpOptions    = array_replace_recursive($arrRegularOptions, [
				'webp' => true,
			]);

			foreach ($arrImages as $strImage) {
				echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
				echo getThumbAndDetails($strImage, $arrRegularOptions);
				echo '</div>';

				echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
				echo getThumbAndDetails($strImage, $arrWebpOptions);
				echo '</div>';

				echo '<div style="clear: both;"></div>';
			}
		}
		?>
	</body>
</html>
