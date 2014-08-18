<?php
namespace EllisLab\ExpressionEngine\Model\Site;

abstract class Preferences {

	public function compress($preferences)
	{
		return base64_encode(serialize($preferences));
	}

	public function decompress($preferences)
	{
		return base64_decode(unserialize($preferences));
	}

	public abstract function populateFromCompressed($preferences);

	public abstract function getCompressed();


}
