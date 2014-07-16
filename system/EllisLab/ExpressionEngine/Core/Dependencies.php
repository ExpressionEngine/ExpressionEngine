<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Core\ServiceProvider;
use \EllisLab\ExpressionEngine\Core\Validation\ValidationFactory;
use \EllisLab\ExpressionEngine\Model\ModelFactory;

/**
 * Global service provider.
 *
 * Provides easy access to the individual services.
 *
 * This should not contain anything specific to the internals of a service!
 * Service dependencies should always be implemented against an interface,
 * to avoid any coupling between the application and the service.
 * This means that you will typically end up with a configurable service
 * factory.
 *
 */
class Dependencies extends ServiceProvider {

    public function getModelFactory()
    {
		$model_alias_path = APPPATH . 'config/model_aliases.php';
		$model_alias_service = new AliasService('Model', $model_alias_path);

        return $this->singleton(function($di) use ($model_alias_service)
        {
            return new ModelFactory(
                $model_alias_service,
                $di->getValidationFactory()
            );
        });
    }

    public function getValidationFactory()
    {
        return $this->singleton(function($di)
        {
            return new ValidationFactory();
        });
    }
}
