<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Core\ServiceProvider;

use \EllisLab\ExpressionEngine\Model\ModelFactory;
use \EllisLab\ExpressionEngine\Model\ModelAliasService;
use \EllisLab\ExpressionEngine\Core\Validation\Validation;

/**
 * Global service provider.
 *
 */
class Dependencies extends ServiceProvider {

    public function getModelFactory()
    {
        return $this->singleton(function($di)
        {
            return new ModelFactory($di, new ModelAliasService());
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
