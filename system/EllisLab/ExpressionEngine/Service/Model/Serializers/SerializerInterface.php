<?php

namespace EllisLab\ExpressionEngine\Service\Model\Serializers;

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
 * Serializer Interface
 *
 * Interface that describes a model serializer.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Serializers
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface SerializerInterface {

	/**
	 * Serialize a given model.
	 *
	 * @param \EllisLab\ExpressionEngine\Model\Model  $model  Model to serialize
	 * @param array  $cascade  Cascade into children of the model.
	 * @return string Serialized Results
	 */
	public function serialize($model, array $cascade = array());

	/**
	 * Unserialize some data and populate a model.
	 *
	 * @param \EllisLab\ExpressionEngine\Model\Model  $model  Model to populate
	 * @param array  $cascade  Cascade into children of the model.
	 * @return string Serialized Results
	 */
	public function unserialize($model, $data);
}