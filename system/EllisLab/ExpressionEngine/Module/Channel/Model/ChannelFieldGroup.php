<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Model;

class ChannelFieldGroup extends Model {
	protected static $_meta = array(
		'primary_key' 	=> 'group_id',
		'gateway_names' 	=> array('ChannelFieldGroupGateway'),
		'key_map'		=> array(
			'group_id' => 'ChannelFieldGroupGateway',
			'site_id' => 'ChannelFieldGroupGateway'
		)
	);

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;

	public function getChannelFieldStructures()
	{
		return $this->oneToMany(
			'ChannelFieldStructures', 'ChannelFieldStructure', 'group_id', 'group_id');
	}

}
