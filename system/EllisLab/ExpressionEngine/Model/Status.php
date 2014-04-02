<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model;

class Status extends Model {
	protected static $_primary_key = 'status_id';
	protected static $_gateway_names = array('StatusGateway');
	protected static $_key_map = array(
		'status_id' => 'StatusGateway',
		'site_id' => 'StatusGateway',
		'group_id' => 'StatusGateway'
	);

	protected static $_relationships = array(
		'StatusGroup' => array(
			'type' => 'many_to_one'
		)
	);

	public function getStatusGroup()
	{
		return $this->getRelated('StatusGroup');
	}

	public function setStatusGroup(StatusGroup $status_group)
	{
		return $this->setRelated('StatusGroup', $status_group);
	}


	public $status_id;
	public $site_id;
	public $group_id;
	public $status;
	public $status_order;
	public $highlight;
}
