<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Rte\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Toolset Model for the Rich Text Editor
 *
 * A model representing a user toolset in the Rich Text Editor.
 */
class Toolset extends Model {

	protected static $_primary_key = 'toolset_id';
	protected static $_table_name = 'rte_toolsets';

	protected static $_relationships = array(
		'Member' => array(
			'type' => 'belongsTo',
			'model' => 'ee:Member',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'Toolset',
				'type' => 'hasMany',
				'weak' => TRUE
			)
		)
	);

	protected $toolset_id;
	protected $member_id;
	protected $name;
	protected $tools;
	protected $enabled;
}

// EOF
