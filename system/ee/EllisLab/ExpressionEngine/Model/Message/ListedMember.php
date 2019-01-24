<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Message;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Listed member
 *
 * Represents a member's place on another member's list, be it a buddy list or
 * block list
 */
class ListedMember extends Model {

	protected static $_primary_key = 'listed_id';
	protected static $_table_name = 'message_listed';

	protected static $_relationships = [
		'ListedByMember' => [
			'type' => 'belongsTo',
			'model' => 'Member'
		],
		'Member' => [
			'type' => 'belongsTo',
			'from_key' => 'listed_member'
		]
	];

	protected static $_typed_columns = [
		'listed_id'          => 'int',
		'member_id'          => 'int',
		'listed_member'      => 'int',
		'listed_description' => 'string',
		'listed_type'        => 'string'
	];

	protected $listed_id;
	protected $member_id;
	protected $listed_member;
	protected $listed_description;
	protected $listed_type;
}
// END CLASS

// EOF
