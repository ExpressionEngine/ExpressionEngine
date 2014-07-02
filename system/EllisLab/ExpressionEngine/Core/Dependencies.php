<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Core\ServiceProvider;

use \EllisLab\ExpressionEngine\Model\ModelFactory;
use \EllisLab\ExpressionEngine\Core\Validation\ValidationFactory;

/**
 * Global service provider.
 *
 */
class Dependencies extends ServiceProvider {

    public function getModelFactory()
    {
		$model_alias_path = APPPATH . 'config/model_aliases.php';
		$model_alias_service = new AliasService('Model', $model_alias_path);

        return $this->singleton(function($di) use ($model_alias_service)
        {
            return new ModelFactory($di, $model_alias_service);
        });
    }

    public function getValidation()
    {
        return $this->singleton(function($di)
        {
            return new ValidationFactory($di);
        });
    }
}
