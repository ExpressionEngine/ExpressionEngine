<?php
namespace EllisLab\ExpressionEngine\Service;

use \EllisLab\ExpressionEngine\Service\ServiceProvider;
use \EllisLab\ExpressionEngine\Service\Validation\Factory;
use \EllisLab\ExpressionEngine\Service\Model\Factory;

/**
 * Global service provider.
 *
 * Provides easy access to the individual services.
 *
 * This class should not contain anything specific to the internals of any given
 * service, and correspondingly a service should not itself instantiate anything
 * from the outside (service, library, model, *anything!*), but instead ask for
 * external dependencies to be injected. Ideally this should be in form of a
 * type-hint to a specific interface.
 *
 * This also means that you will typically want a configurable service factory
 * that allows third parties to flip out parts on their own instances without
 * affecting anyone else's implementations.
 *
 * Following these rules allows you to avoid any coupling between the
 * application and the service, which improves testability dramatically. It also
 * makes third party changes easy while maintaining strict isolation.
 *
 * In this class we will always inject EE's default implementation for a given
 * interface.
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
