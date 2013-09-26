<?php

class ChannelFieldGroup extends Model {
	protected static $meta = array(
		'primary_key' 	=> 'group_id',
		'entity_names' 	=> array('FieldGroupEntity'),
		'key_map'		=> array(
			'group_id' => 'FieldGroupEntity',
			'site_id' => 'FieldGroupEntity'
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
