<?php

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * The Model frontend. This is the only way the model should be
 * communicated with. Either via the query builder using get() or
 * by creating new instances via make().
 *
 * Manually working with instances of the datastore is *not* supported.
 * All other public methods on it should be considered internal and
 * subject to change.
 */
class Frontend {

	protected $store;

	//todo hook up to di and remove "new".
	public function __construct($store = NULL)
	{
		$this->store = $store ?: new DataStore(ee()->db,  APPPATH.'config/model_aliases.php');
	}

	/**
	 *
	 */
	public function get($name)
	{
		$builder = $this->store->get($name);
		$builder->setFrontend($this);

		return $builder;
	}

	/**
	 *
	 */
	public function make($name, array $data = array())
	{
		return $this->store->make($name, $this, $data);
	}
}