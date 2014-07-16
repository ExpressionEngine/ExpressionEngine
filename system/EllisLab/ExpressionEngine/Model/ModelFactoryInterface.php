<?php

namespace EllisLab\ExpressionEngine\Model;

interface ModelFactoryInterface {

	/**
	 * Query Factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Query
	 */
	public function get($model_name, $ids = NULL);

	/**
	 * Model factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Model
	 */
	public function make($model, array $data = array(), $dirty = TRUE);

	/**
	 * Gateway factory
	 *
	 * @return \Ellislab\ExpressionEngine\Model\Gateway
	 */
	public function makeGateway($gateway, $data = array());

}