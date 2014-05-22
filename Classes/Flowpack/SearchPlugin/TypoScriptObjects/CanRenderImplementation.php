<?php
namespace Flowpack\SearchPlugin\TypoScriptObjects;


/*                                                                                                  *
 * This script belongs to the TYPO3 Flow package "Flowpack.SearchPlugin.TypoScriptObjects".         *
 *                                                                                                  *
 * It is free software; you can redistribute it and/or modify it under                              *
 * the terms of the GNU Lesser General Public License, either version 3                             *
 *  of the License, or (at your option) any later version.                                          *
 *                                                                                                  *
 * The TYPO3 project - inspiring people to share!                                                   *
 *                                                                                                  */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Class CanRenderImplementation
 *
 */
class CanRenderImplementation extends AbstractTypoScriptObject {

	/**
	 * TypoScript Type which shall be rendered
	 * @return string
	 */
	public function getType() {
		return $this->tsValue('type');
	}

	/**
	 * Evaluate this TypoScript object and return the result
	 *
	 * @return mixed
	 */
	public function evaluate() {
		return $this->tsRuntime->canRender('/type<' . $this->getType() . '>');
	}
}