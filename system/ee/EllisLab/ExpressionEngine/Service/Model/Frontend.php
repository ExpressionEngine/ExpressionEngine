<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Service\Validation\Factory as ValidationFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Frontend
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
 * @link		http://ellislab.com
 */
class Frontend {

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
	 */
	public function get($name, $default_ids = NULL)
	{
		$builder = $this->datastore->get($name);

		if ( ! empty($default_ids))
		{
			$operator = is_array($default_ids) ? 'IN' : '==';
			$shortname = $this->removeAlias($name);
			$builder->filter($name, $operator, $default_ids);
		}

		$builder->setFrontend($this);

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
	 *
	 */
	private function removeAlias($str)
	{
		$str = trim($str);
		$pos = strrpos($str, ' ');

		if ($pos === FALSE)
		{
			return $str;
		}

		return trim(substr($str, $pos));
	}
}