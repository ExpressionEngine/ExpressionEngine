<?php

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Moderator extends Model {

	protected static $_primary_key = 'mod_id';
	protected static $_table_name = 'exp_forum_moderators';

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

	// protected static $_relationships = array(
	// );

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

}
