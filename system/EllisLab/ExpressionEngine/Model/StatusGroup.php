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

	protected static $_relationships = array(
		'Statuses' => array(
			'type' => 'one_to_many',
			'model' => 'Status'
		)
	);

	public function getStatuses()
	{
		return $this->getRelated('Statuses');
	}

	public function setStatuses(array $statuses)
	{
		return $this->setRelated('Statuses', $statuses);
	}

	protected $group_id;
	protected $site_id;
	protected $group_name;
}
