<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model;

class Status extends Model {
	protected static $_meta = array(
		'primary_key' => 'status_id',
		'gateway_names' => array('StatusGateway'),
		'key_map' => array(
			'status_id' => 'StatusGateway',
			'site_id' => 'StatusGateway',
			'group_id' => 'StatusGateway'
		)
	);

	public function getStatusGroup()
	{
		return $this->manyToOne(
			'StatusGroup', 'StatusGroup', 'group_id', 'group_id');
	}

	public function setStatusGroup(StatusGroup $status_group)
	{
		$this->setRelated('StatusGroup', $status_group);
		$this->group_id = $status_group->group_id;
		return $this;
	}	


	public $status_id;
	public $site_id;
	public $group_id;
	public $status;
	public $status_order;
	public $highlight;
}
