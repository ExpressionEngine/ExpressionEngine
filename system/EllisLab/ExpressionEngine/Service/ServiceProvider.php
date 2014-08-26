<?php
namespace EllisLab\ExpressionEngine\Service;

use StdClass;

// Dependencies is the global service provider

// Everyone else can decide to create their own. The static registry ensures
// that the singleton will play nicely across all instances of them.

class ServiceProvider {

	protected static $singletonRegistry;

	protected function singleton(\Closure $object)
	{
	    $hash = spl_object_hash($object);

	    // using a method call so its easy to mock up
	    $registry = $this->getRegistry();

	    if ( ! isset($registry->$hash))
	    {
	        $registry->$hash = $object($this);
	    }

	    return $registry->$hash;
	}

	private function getRegistry()
	{
		// using self instead of static to prevent extension.
		// using an object to enforce references.

		if ( ! is_object(self::$singletonRegistry))
		{
			self::$singletonRegistry = new StdClass;
		}

		return static::$singletonRegistry;
	}
}
