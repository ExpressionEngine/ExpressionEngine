<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
			'type' => 'belongsTo'
		)
	);

	protected $toolset_id;
	protected $member_id;
	protected $name;
	protected $tools;
	protected $enabled;
}

// EOF
