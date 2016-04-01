<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Facade
 *
 * This is the only way the datastore should be communicated with. Either via
 * the query builder using get() or by creating new instances via make().
 *
 * Manually working with instances of the datastore is *not* supported.
 * All other public methods on it should be considered internal and
 * subject to change.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Facade {

	protected $datastore;
	protected $validation;

	/**
	 * @param $datastore EllisLab\ExpressionEngine\Service\Model\DataStore
	 */
	public function __construct(DataStore $datastore)
	{
		$this->datastore = $datastore;
	}

	/**
	 * Run a query
	 *
	 * @param String $name Model to run the query on
	 * @param Mixed $default_ids One or more ids to prime the query with [optional]
	 */
	public function get($name, $default_ids = NULL)
	{
		$builder = $this->datastore->get($name);

		if (isset($default_ids))
		{
			$shortname = $this->removeAlias($name);

			if (count($default_ids) == 0)
			{
				$builder->markAsFutile();
			}
			elseif (is_array($default_ids))
			{
				$builder->filter($shortname, 'IN', $default_ids);
			}
			else
			{
				$builder->filter($shortname, $default_ids);
			}
		}

		$builder->setFacade($this);

		return $builder;
	}

	/**
	 * Create a model instance
	 *
	 * @param String $name Model to create
	 * @param Array  $data Initial data
	 */
	public function make($name, array $data = array())
	{
		$model = $this->datastore->make($name, $this, $data);

		if ($this->validation)
		{
			$model->setValidator($this->validation->make());
		}
		return $model;
	}

	/**
	 *
	 */
	public function setValidationFactory(ValidationFactory $validation)
	{
		$this->validation = $validation;
	}

	/**
	 * Remove any aliasing and return the shortname
	 *
	 * A rather naive function, but reliable unless given a completely
	 * garbage model name.
	 */
	private function removeAlias($str)
	{
		$str = trim($str);
		$pos = strrpos($str, ' ');

		if ($pos !== FALSE)
		{
			$str = trim(substr($str, $pos));
		}

		return str_replace(':', '_m_', $str);
	}
}

// EOF
