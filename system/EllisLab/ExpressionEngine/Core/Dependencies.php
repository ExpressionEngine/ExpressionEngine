<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Core\ServiceProvider;

use \EllisLab\ExpressionEngine\Model\ModelBuilder;
use \EllisLab\ExpressionEngine\Model\AliasService as ModelAliasService;
use \EllisLab\ExpressionEngine\Core\Validation\Validation;

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