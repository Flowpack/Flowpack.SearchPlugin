<?php
namespace Flowpack\SearchPlugin\FusionObjects;

/*
 * This file is part of the Flowpack.SearchPlugin package.
 *
 * (c) Contributors of the Flowpack Team - flowpack.org
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Class CanRenderImplementation
 *
 */
class CanRenderImplementation extends AbstractFusionObject
{
    /**
     * TypoScript Type which shall be rendered
     *
     * @return string
     */
    public function getType()
    {
        return $this->tsValue('type');
    }

    /**
     * Evaluate this TypoScript object and return the result
     *
     * @return mixed
     */
    public function evaluate()
    {
        return $this->tsRuntime->canRender('/type<' . $this->getType() . '>');
    }
}
