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
 * Folder
 *
 * Folders for private messages
 */
class Folder extends Model {

	protected static $_primary_key = 'member_id';
	protected static $_table_name = 'message_folders';

	protected static $_relationships = [
		'Member' => [
			'type' => 'belongsTo'
		]
	];

	protected static $_typed_columns = [
		'member_id'     => 'int',
		'folder1_name'  => 'string',
		'folder2_name'  => 'string',
		'folder3_name'  => 'string',
		'folder4_name'  => 'string',
		'folder5_name'  => 'string',
		'folder6_name'  => 'string',
		'folder7_name'  => 'string',
		'folder8_name'  => 'string',
		'folder9_name'  => 'string',
		'folder10_name' => 'string'
	];

	protected $member_id;
	protected $folder1_name;
	protected $folder2_name;
	protected $folder3_name;
	protected $folder4_name;
	protected $folder5_name;
	protected $folder6_name;
	protected $folder7_name;
	protected $folder8_name;
	protected $folder9_name;
	protected $folder10_name;
}
// END CLASS

// EOF
