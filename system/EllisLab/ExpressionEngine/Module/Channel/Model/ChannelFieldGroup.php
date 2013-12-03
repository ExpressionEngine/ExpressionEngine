<?php

class ChannelFieldGroup extends Model {
	protected static $_meta = array(
		'primary_key' 	=> 'group_id',
		'gateway_names' 	=> array('FieldGroupGateway'),
		'key_map'		=> array(
			'group_id' => 'FieldGroupGateway',
			'site_id' => 'FieldGroupGateway'
		)
	);

	public function getChannelFieldStructures()
	{
		return $this->oneToMany('ChannelFieldStructure', 'group_id', 'group_id');
	}

	/**
	 * Validate the field group.
	 *
	 * @throws StructureInvalidException if missing / invalid data
	 * @return void
	 */
	public function validate()
	{

	}

}
