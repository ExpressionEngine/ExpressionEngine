<?php

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Moderator Model for the Forum
 *
 * A model representing a moderator in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Moderator extends Model {

	protected static $_primary_key = 'mod_id';
	protected static $_table_name = 'forum_moderators';

	protected static $_typed_columns = array(
		'board_id'              => 'int',
		'mod_forum_id'          => 'int',
		'mod_member_id'         => 'int',
		'mod_group_id'          => 'int',
		'mod_can_edit'          => 'boolString',
		'mod_can_move'          => 'boolString',
		'mod_can_delete'        => 'boolString',
		'mod_can_split'         => 'boolString',
		'mod_can_merge'         => 'boolString',
		'mod_can_change_status' => 'boolString',
		'mod_can_announce'      => 'boolString',
		'mod_can_view_ip'       => 'boolString',
	);

	protected static $_relationships = array(
		'Board' => array(
			'type' => 'belongsTo'
		),
		'Forum' => array(
			'type'     => 'belongsTo',
			'from_key' => 'mod_forum_id',
			'to_key'   => 'forum_id'
		),
		'Member' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'mod_member_id',
			'to_key'   => 'member_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Moderator',
				'type' => 'hasMany'
			)
		),
		'MemberGroup' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:MemberGroup',
			'from_key' => 'mod_group_id',
			'to_key'   => 'group_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Moderator',
				'type' => 'hasMany'
			)
		),
	);

	protected static $_validation_rules = array(
		'mod_forum_id'          => 'required',
		'mod_member_name'       => 'required',
		'mod_can_edit'          => 'enum[y,n]',
		'mod_can_move'          => 'enum[y,n]',
		'mod_can_delete'        => 'enum[y,n]',
		'mod_can_split'         => 'enum[y,n]',
		'mod_can_merge'         => 'enum[y,n]',
		'mod_can_change_status' => 'enum[y,n]',
		'mod_can_announce'      => 'enum[y,n]',
		'mod_can_view_ip'       => 'enum[y,n]',
	);

	protected $mod_id;
	protected $board_id;
	protected $mod_forum_id;
	protected $mod_member_id;
	protected $mod_member_name;
	protected $mod_group_id;
	protected $mod_can_edit;
	protected $mod_can_move;
	protected $mod_can_delete;
	protected $mod_can_split;
	protected $mod_can_merge;
	protected $mod_can_change_status;
	protected $mod_can_announce;
	protected $mod_can_view_ip;

	public function getModeratorName()
	{
		$name = $this->mod_member_name;

		if ($this->mod_group_id)
		{
			$name = $this->MemberGroup->group_title;
		}

		return $name;
	}

	public function getType()
	{
		$type = "";

		if ($this->mod_group_id)
		{
			$type = lang('group');
		}
		elseif ($this->mod_member_id)
		{
			$type = lang('individual');
		}

		return $type;
	}

}

// EOF
