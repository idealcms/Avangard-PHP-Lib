<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Lib;

/**
 * Class Convertor
 * @package Avangard\lib
 */
class Convertor
{
	/**
	 * Convert xml string to php array - useful to get a serializable value.
	 *
	 * @param string $xml
	 * @return mixed[]
	 */
	public static function covertToArray($xml)
	{
		assert(\class_exists('\DOMDocument'));
		$doc = new \DOMDocument();
		$doc->loadXML($xml);
		$root = $doc->documentElement;
		$output = (array) Helper::domNodeToArray($root);
		$output['@root'] = $root->tagName;

		return (!empty($output) ? $output : []);
	}

}