<?php
namespace EllisLab\ExpressionEngine\Model\Gateway\Mapper;

class SerializedMapper implements Mapper {

	public function fromDb($value)
	{
		return unserialize($value);
	}

	public function toDb($value)
	{
		return serialize($value);
	}

}
