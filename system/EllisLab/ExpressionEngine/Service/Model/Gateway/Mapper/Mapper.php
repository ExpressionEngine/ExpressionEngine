<?php
namespace EllisLab\ExpressionEngine\Model\Gateway\Mapper;


interface Mapper {

	public function fromDb($value);
	public function toDb($value);

}
