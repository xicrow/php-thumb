<?php
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
ini_set('log_errors', 0);

// Set memory limit, image handling can get heavy...
// - 128MB wasn't enough for at 25MB image
// @todo Test memory limits for various images, maybe set memory limit dynamically based on original image size ?
ini_set('memory_limit', '256M');

require_once('../src/autoload.php');

use \Xicrow\PhpThumb\Thumb;
use \Xicrow\PhpThumb\Helper;

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
 * Get thumbnail URL for a given image and options
 *
 * @param string $image
 * @param array  $options
 *
 * @return string
 */
function getThumbUrl($image, $options = [])
{
    if (!array_key_exists('webp', $options) && !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $options['webp'] = true;
    }

    // Resize in seperate process
    $thumbUrl = Helper::getThumbUrl($image, $options);
    if (substr($thumbUrl, 0, 4) != 'http') {
        $thumbUrl = './webroot/thumbnails' . $thumbUrl;
    }

    // Resize now
    //					$thumbPath = Thumb::resize($image, $options);
    //					$thumbUrl  = str_replace('E:\\GitHub\\php-thumb\\demo\\', './', $thumbPath);
    //					$thumbUrl  = str_replace('\\', '/', $thumbUrl);

    // Return thumbnail URL
    return $thumbUrl;
}

// Array with default options to replace/extend
$optionsDefault = [
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
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Xicrow/PhpThumb</title>
		<style type="text/css">
			body {
				font-family: Arial, sans-serif;
				font-size: 13px;
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
        $image   = 'images/1200x1200.jpg';
        $options = array_replace_recursive($optionsDefault, [
            'resize' => [
                // Width of the thumbnail (empty value to auto calculate in relation to height)
                'width'      => 800,
                // Height of the thumbnail (empty value to auto calculate in relation to width)
                'height'     => 800,
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

        echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
        $thumbUrl = getThumbUrl($image, $options);
        echo '<table width="100%" border="0">';
        echo '<tr><th width="50%">Setting</th><th>Value</th></tr>';
        echo '<tr><td>Size</td><td>' . $options['resize']['width'] . 'x' . $options['resize']['height'] . '</td></tr>';
        echo '<tr><td>Align</td><td>' . $options['resize']['align_x'] . ' ' . $options['resize']['align_y'] . '</td></tr>';
        echo '<tr><td>Method</td><td>' . $options['resize']['method'] . '</td></tr>';
        echo '<tr><td>Stretch</td><td>' . (int)$options['resize']['stretch'] . '</td></tr>';
        echo '<tr><td>Trim</td><td>' . (int)$options['resize']['trim'] . '</td></tr>';
        echo '<tr><td>Greyscale</td><td>' . (int)$options['resize']['grayscale'] . '</td></tr>';
        echo '<tr><td>Background</td><td>' . $options['resize']['background'] . '</td></tr>';
        echo '</table>';
        echo '<div style="width: ' . $options['resize']['width'] . 'px; height: ' . $options['resize']['height'] . 'px;">';
        echo '<img src="' . $thumbUrl . '" title="' . $thumbUrl . '" style="border: 1px solid #CCC;">';
        echo '</div>';
        if (file_exists($thumbUrl)) {
            echo '<table width="100%" border="0">';
            echo '<tr><th width="50%">Stat</th><th>Value</th></tr>';
            echo '<tr><td>Size</td><td>' . filesize($thumbUrl) . ' bytes</td></tr>';
            echo '</table>';
        }
        echo '</div>';

        $options = array_replace_recursive($options, [
            'webp' => true,
        ]);

        echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
        $thumbUrl = getThumbUrl($image, $options);
        echo '<table width="100%" border="0">';
        echo '<tr><th width="50%">Setting</th><th>Value</th></tr>';
        echo '<tr><td>Size</td><td>' . $options['resize']['width'] . 'x' . $options['resize']['height'] . '</td></tr>';
        echo '<tr><td>Align</td><td>' . $options['resize']['align_x'] . ' ' . $options['resize']['align_y'] . '</td></tr>';
        echo '<tr><td>Method</td><td>' . $options['resize']['method'] . '</td></tr>';
        echo '<tr><td>Stretch</td><td>' . (int)$options['resize']['stretch'] . '</td></tr>';
        echo '<tr><td>Trim</td><td>' . (int)$options['resize']['trim'] . '</td></tr>';
        echo '<tr><td>Greyscale</td><td>' . (int)$options['resize']['grayscale'] . '</td></tr>';
        echo '<tr><td>Background</td><td>' . $options['resize']['background'] . '</td></tr>';
        echo '</table>';
        echo '<div style="width: ' . $options['resize']['width'] . 'px; height: ' . $options['resize']['height'] . 'px;">';
        echo '<img src="' . $thumbUrl . '" title="' . $thumbUrl . '" style="border: 1px solid #CCC;">';
        echo '</div>';
        if (file_exists($thumbUrl)) {
            echo '<table width="100%" border="0">';
            echo '<tr><th width="50%">Stat</th><th>Value</th></tr>';
            echo '<tr><td>Size</td><td>' . filesize($thumbUrl) . ' bytes</td></tr>';
            echo '</table>';
        }
        echo '</div>';

        $images = [
            // Square
            //			'images/1200x1200.jpg',
            //			'images/Squat training.jpg',
            //			'images/1200x1200.jpg?ver1',
            //			'images/Squat training.jpg?ver2',
            // Landscape
            //			'images/F-16_Demo_Team_2722.jpg',
            //			'images/controller-1.jpg',
            //			'images/controller-2.jpg',
            //			'images/controller-3.jpg',
            // Portrait
            //			'images/email-timing-full.jpg',
            //			'images/gky7VZp.jpg',
            // Transparency
            //			'images/2000px-Chevronny_demo.svg.png',
            //			'images/Doom_logo.png',
            // Remote
            //			'http://voids.dk/testpat.jpg',
            //			'https://www.aceandtate.com/skin/frontend/aceandtate/default/img/prescription/prescription-landing-character-map.png',
            //			'http://via.placeholder.com/300x300',
            //			'http://via.placeholder.com/300x300?text=Test+1',
            //			'http://via.placeholder.com/300x300?text=Test+2',
            //			'http://via.placeholder.com/300x300?text=Test+3',
            // Trigger placeholder images
            //			'',
            //			'images/non-existing.jpg',
        ];
        if (false) {
            // Simple test
            $options = array_replace_recursive($optionsDefault, [
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
            foreach ($images as $image) {
                echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
                $thumbUrl = getThumbUrl($image, $options);
                echo '<table width="100%" border="0">';
                echo '<tr><th width="50%">Setting</th><th>Value</th></tr>';
                echo '<tr><td>Size</td><td>' . $options['resize']['width'] . 'x' . $options['resize']['height'] . '</td></tr>';
                echo '<tr><td>Align</td><td>' . $options['resize']['align_x'] . ' ' . $options['resize']['align_y'] . '</td></tr>';
                echo '<tr><td>Method</td><td>' . $options['resize']['method'] . '</td></tr>';
                echo '<tr><td>Stretch</td><td>' . (int)$options['resize']['stretch'] . '</td></tr>';
                echo '<tr><td>Trim</td><td>' . (int)$options['resize']['trim'] . '</td></tr>';
                echo '<tr><td>Greyscale</td><td>' . (int)$options['resize']['grayscale'] . '</td></tr>';
                echo '<tr><td>Background</td><td>' . $options['resize']['background'] . '</td></tr>';
                echo '</table>';
                echo '<div style="width: ' . $options['resize']['width'] . 'px; height: ' . $options['resize']['height'] . 'px;">';
                echo '<img src="' . $thumbUrl . '" title="' . $thumbUrl . '" style="border: 1px solid #CCC;">';
                echo '</div>';
                echo '</div>';
            }
        }
        if (false) {
            // Advanced test
            $option1List = [
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
            $option2List = [
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
            $option3List = [
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
            $optionList  = [];
            if (isset($option1List)) {
                foreach ($option1List as $option1) {
                    if (isset($option2List)) {
                        foreach ($option2List as $option2) {
                            if (isset($option3List)) {
                                foreach ($option3List as $option3) {
                                    $optionList[] = array_replace_recursive($optionsDefault, $option1, $option2, $option3);
                                }
                            } else {
                                $optionList[] = array_replace_recursive($optionsDefault, $option1, $option2);
                            }
                        }
                    } else {
                        $optionList[] = array_replace_recursive($optionsDefault, $option1);
                    }
                }
            }

            foreach ($images as $image) {
                echo '<div>';
                echo '<a href="webroot/' . $image . '">' . $image . '</a>';
                echo '<br />';

                foreach ($optionList as $options) {
                    echo '<div style="margin: 5px; padding: 5px; background: #EEE; border: 1px solid #CCC; float: left;">';
                    $thumbUrl = getThumbUrl($image, $options);
                    echo '<table width="100%" border="0">';
                    echo '<tr><th width="50%">Setting</th><th>Value</th></tr>';
                    echo '<tr><td>Size</td><td>' . $options['resize']['width'] . 'x' . $options['resize']['height'] . '</td></tr>';
                    echo '<tr><td>Align</td><td>' . $options['resize']['align_x'] . ' ' . $options['resize']['align_y'] . '</td></tr>';
                    echo '<tr><td>Method</td><td>' . $options['resize']['method'] . '</td></tr>';
                    echo '<tr><td>Stretch</td><td>' . (int)$options['resize']['stretch'] . '</td></tr>';
                    echo '<tr><td>Trim</td><td>' . (int)$options['resize']['trim'] . '</td></tr>';
                    echo '<tr><td>Grayscale</td><td>' . (int)$options['resize']['grayscale'] . '</td></tr>';
                    echo '<tr><td>Background</td><td>' . $options['resize']['background'] . '</td></tr>';
                    echo '</table>';
                    echo '<div style="width: ' . $options['resize']['width'] . 'px; height: ' . $options['resize']['height'] . 'px;">';
                    echo '<img src="' . $thumbUrl . '">';
                    echo '</div>';
                    echo '</div>';
                }
                echo '<div style="clear: both;"></div>';
                echo '</div>';
            }
        }
        ?>
	</body>
</html>
