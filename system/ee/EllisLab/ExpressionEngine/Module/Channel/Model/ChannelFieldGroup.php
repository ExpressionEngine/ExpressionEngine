<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class ChannelFieldGroup extends Model {

	protected static $_primary_key 	= 'group_id';
	protected static $_table_name = 'field_groups';

	protected static $_relationships = array(
		'ChannelFields' => array(
			'weak' => TRUE,
			'type' => 'hasMany',
			'model' => 'ChannelField'
		)
	);

	protected static $_validation_rules = array(
		'group_name' => 'required|unique[site_id]|validateName'
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;

	/**
	 * Convenience method to fix inflection
	 */
	public function createChannelField($data)
	{
		return $this->createChannelFields($data);
	}

	public function validateName($key, $value, $params, $rule)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", $value))
		{
			return 'illegal_characters';
		}

		return TRUE;
	}

}
