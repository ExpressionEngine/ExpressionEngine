<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model;

class StatusGroup extends Model {
	protected static $_primary_key = 'group_id';
	protected static $_gateway_names = array('StatusGroupGateway');
	protected static $_key_map = array(
		'group_id' => 'StatusGroupGateway',
		'site_id' => 'StatusGroupGateway'
	);

	public function getStatuses()
	{
		return $this->oneToMany('Statuses', 'Status', 'group_id', 'group_id');
	}

	public function setStatuses(array $statuses)
	{
		$this->setRelated('Statuses', $statuses);
		return $this;
	}

	protected $group_id;
	protected $site_id;
	protected $group_name;
}
