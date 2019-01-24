<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * Model Service Registry
 *
 * This contains general model information. This includes alias => class name
 * mapping, prefix information, installed/enabled information, and access to
 * model metadata.
 */
class Registry {

	/**
	 * @var Array Map of aliases to class names[modelalias => classname]
	 */
	private $aliases;

	/**
	 * @var String $default_prefix The default prefix (usually ee)
	 */
	private $default_prefix;

	/**
	 * @var Array $enabled_prefixes List of active prefixes
	 */
	private $enabled_prefixes;

	/**
	 * @var Array Cache of metadata reader objects
	 */
	private $metadata = array();

	/**
	 * @param Array $aliases Map of aliases to class names[modelalias => classname]
	 * @param String $default_prefix The default prefix (usually ee)
	 * @param Array $enabled_prefixes List of active prefixes
	 */
	public function __construct(array $aliases, $default_prefix, array $enabled_prefixes)
	{
		$this->aliases = $aliases;
		$this->default_prefix = $default_prefix;
		$this->enabled_prefixes = $enabled_prefixes;
	}

	/**
	 * Check if a model exists given an alias
	 *
	 * @param String $alias Model alias (with prefix)
	 * @return bool Exists?
	 */
	public function modelExists($alias)
	{
		return array_key_exists($alias, $this->aliases);
	}

	/**
	 * Check if a model is enabled. This means that the prefix is installed
	 * and any tables the model may need are available to us.
	 *
	 * @param String $model_name Model alias
	 * @return bool Enabled?
	 */
	public function isEnabled($model_name)
	{
		$prefix = $this->getPrefix($model_name);

		return in_array($prefix, $this->enabled_prefixes);
	}

	/**
	 * Given a model alias, get the class name. If a class name
	 * is passed and no alias is found, return that class name.
	 *
	 * @param String $name The alias name to look up
	 * @return String The class name
	 */
	public function expandAlias($name)
	{
		if ( ! strpos($name, ':'))
		{
			$name = $this->default_prefix.':'.$name;
		}

		if ( ! isset($this->aliases[$name]))
		{
			if ( ! class_exists($name))
			{
				throw new \Exception("Unknown model: {$name}");
			}

			return $name;
		}

		return $this->aliases[$name];
	}

	/**
	 * Get a MetaDataReader
	 *
	 * @param String $name Model to read metadata from
	 * @return Object MetaDataReader
	 */
	public function getMetaDataReader($name)
	{
		$class = $this->expandAlias($name);

		if ( ! isset($this->metadata[$class])
			|| $this->metadata[$class]->getName() != $name)
		{
			$this->metadata[$class] = new MetaDataReader($name, $class);
		}

		return $this->metadata[$class];
	}

	/**
	 * Extract the prefix from a model name, or return the default prefix
	 *
	 * @param String $model Model alias
	 * @return String Prefix
	 */
	public function getPrefix($model)
	{
		if (strpos($model, ':'))
		{
			return strstr($model, ':', TRUE);
		}

		return $this->default_prefix;
	}
}
