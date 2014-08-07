<?php
namespace EllisLab\ExpressionEngine\Model\Site;


class FlexiblePreferences extends Preferences {
	protected $preferences = array();

	public function __construct($preferences = NULL)
	{
		if ( isset($preferences) )
		{
			$this->populateFromCompressed($preferences);
		}

	}

	public function getCompressed()
	{
		return $this->compress($this->preferences);
	}

	public function populateFromCompressed($preferences)
	{
		$this->preferences = $this->decompress($preferences);
	}


}
