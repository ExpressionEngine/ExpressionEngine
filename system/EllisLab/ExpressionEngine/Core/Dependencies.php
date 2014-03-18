<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Core\ServiceProvider;
use \EllisLab\ExpressionEngine\Core\Validation\Validation;
use \EllisLab\ExpressionEngine\Model\ModelAliasService;
use \EllisLab\ExpressionEngine\Model\ModelBuilder;

/**
 * Global service provider.
 *
 */
class Dependencies extends ServiceProvider {

    public function getModelBuilder()
    {
        return $this->singleton(function($di)
        {
            return new ModelBuilder($di, new ModelAliasService());
        });
    }

    public function getValidation()
    {
        return $this->singleton(function($di)
        {
            return new Validation($di);
        });
    }
}