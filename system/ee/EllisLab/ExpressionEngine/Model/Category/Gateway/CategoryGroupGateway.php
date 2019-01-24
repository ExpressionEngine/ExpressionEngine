<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Category\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * Category Group Table
 */
class CategoryGroupGateway extends Gateway {

	protected static $_table_name = 'category_groups';
	protected static $_primary_key = 'group_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'CategoryGateway',
			'key' => 'group_id'
		)
	);


	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $sort_order;
	protected $exclude_group;
	protected $field_html_formatting;
	protected $can_edit_categories;
	protected $can_delete_categories;
}

// EOF
