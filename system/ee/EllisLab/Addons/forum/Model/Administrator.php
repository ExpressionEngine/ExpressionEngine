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
 * ExpressionEngine Administrator Model for the Forum
 *
 * A model representing an administrator in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Administrator extends Model {

	protected static $_primary_key = 'admin_id';
	protected static $_table_name = 'forum_administrators';

	protected static $_typed_columns = array(
		'board_id'        => 'int',
		'admin_group_id'  => 'int',
		'admin_member_id' => 'int',
	);

	protected static $_relationships = array(
		'Board' => array(
			'type' => 'belongsTo'
		),
		'Member' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'admin_member_id',
			'to_key'   => 'member_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Administrator',
				'type' => 'hasMany'
			)
		),
		'MemberGroup' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:MemberGroup',
			'from_key' => 'admin_group_id',
			'to_key'   => 'group_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Administrator',
				'type' => 'hasMany'
			)
		),
	);

	protected static $_validation_rules = array(
		'board_id'        => 'required',
		'admin_group_id'  => 'required',
		'admin_member_id' => 'required',
	);

	protected $admin_id;
	protected $board_id;
	protected $admin_group_id;
	protected $admin_member_id;

	public function getAdminName()
	{
		$name = "";

		if ($this->admin_group_id)
		{
			$name = $this->MemberGroup->group_title;
		}
		elseif ($this->admin_member_id)
		{
			$name = $this->Member->getMemberName();
		}

		return $name;
	}

	public function getType()
	{
		$type = "";

		if ($this->admin_group_id)
		{
			$type = lang('group');
		}
		elseif ($this->admin_member_id)
		{
			$type = lang('individual');
		}

		return $type;
	}

}

// EOF
