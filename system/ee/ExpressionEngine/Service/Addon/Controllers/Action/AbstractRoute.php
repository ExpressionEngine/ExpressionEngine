<?php
namespace ExpressionEngine\Service\Addon\Controllers\Action;

use ExpressionEngine\Service\Addon\Controllers\AbstractRoute AS CoreAbstractRoute;

abstract class AbstractRoute extends CoreAbstractRoute
{
    abstract public function process();
}
