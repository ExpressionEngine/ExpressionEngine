<?php
namespace EllisLab\ExpressionEngine\Model\Serializers;

interface SerializerInterface {

	public function serialize($model);
	public function unserialize($model, $data);
}