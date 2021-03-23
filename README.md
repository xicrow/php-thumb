# Thumb

Thumbnail generation for PHP

[![Packagist Latest Stable Version](https://poser.pugx.org/xicrow/php-thumb/v/stable)](https://packagist.org/packages/xicrow/php-thumb)
[![Packagist Total Downloads](https://poser.pugx.org/xicrow/php-thumb/downloads)](https://packagist.org/packages/xicrow/php-thumb)

## Installation

The recommended way to install is through [Composer](https://getcomposer.org/).

```JSON
{
	"require": {
		"xicrow/php-thumb": "~2.0"
	}
}
```

## Example

See examples in the `demo` folder

## TODO

- ~~Handle remote images~~
- ~~Use placeholder images for when errors occur (i.e. missing images)~~
- ~~Improve demos~~
- Cleanup code structure and flow
- When resizing in seperate process, also download remote images in that process, if possible 
- PHPunit tests

## Notes

Explanation of the parameters for imagecopyresampled():  
[php.net/imagecopyresampled](http://php.net/manual/en/function.imagecopyresampled.php#112742)

## License

Copyright &copy; 2017 Jan Ebsen Licensed under the MIT license.
