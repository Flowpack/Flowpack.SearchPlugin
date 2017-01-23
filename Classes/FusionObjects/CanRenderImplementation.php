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

class CanRenderImplementation extends AbstractFusionObject
{
    /**
     * Evaluate this Fusion object and return the result
     *
     * @return mixed
     */
    public function evaluate()
    {
        return $this->runtime->canRender('/type<' . $this->getType() . '>');
    }

    /**
     * Fusion Type which shall be rendered
     *
     * @return string
     */
    public function getType()
    {
        return $this->fusionValue('type');
    }
}
