<?php
namespace Xicrow\PhpThumb;

/**
 * Interface EngineInterface
 *
 * @package Xicrow\PhpThumb
 */
interface EngineInterface {
	/**
	 * Load an image to the engine
	 *
	 * @param string $file
	 * @param array  $options
	 *
	 * @return boolean
	 */
	public function load($file, array $options = []);

	/**
	 * Resize image
	 *
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function resize(array $options = []);

	/**
	 * Watermark the image
	 *
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function watermark(array $options = []);

	/**
	 * Save the current image
	 *
	 * @param string $file
	 * @param array  $options
	 *
	 * @return boolean
	 */
	public function save($file, array $options = []);
}
