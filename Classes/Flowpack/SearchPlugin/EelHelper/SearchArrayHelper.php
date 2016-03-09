<?php
namespace Flowpack\SearchPlugin\EelHelper;

/*                                                                                                  *
 * This script belongs to the TYPO3 Flow package "Flowpack.SearchPlugin".                           *
 *                                                                                                  *
 * It is free software; you can redistribute it and/or modify it under                              *
 * the terms of the GNU Lesser General Public License, either version 3                             *
 *  of the License, or (at your option) any later version.                                          *
 *                                                                                                  *
 * The TYPO3 project - inspiring people to share!                                                   *
 *                                                                                                  */
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Additional Array Helpers which might once
 *
 * @Flow\Proxy(false)
 */
class SearchArrayHelper implements ProtectedContextAwareInterface {

	/**
	 * Concatenate arrays or values to a new array
	 *
	 * @param array|mixed $arrays First array or value
	 * @return array The array with concatenated arrays or values
	 */
	public function flatten($arrays) {
		$return = array();
		if (is_array($arrays)) {
			array_walk_recursive($arrays, function ($a) use (&$return) {
				$return[] = $a;
			});
		}
		return $return;
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return true;
	}

}
