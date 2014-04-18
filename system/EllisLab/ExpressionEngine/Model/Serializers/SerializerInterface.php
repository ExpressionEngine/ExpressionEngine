<?php
namespace EllisLab\ExpressionEngine\Model\Serializers;

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