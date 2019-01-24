<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * NewsView
 *
 * Keeps track of the latest version of ExpressionEngine that a member with
 * control panel access has viewed the latest changelog for, by clicking on
 * little giftbox in the control panel footer after upgrades
 */
class NewsView extends Model {

	protected static $_primary_key = 'news_id';
	protected static $_table_name = 'member_news_views';

	protected static $_typed_columns = [
		'news_id'   => 'int',
		'member_id' => 'int'
	];

	protected static $_relationships = [
		'Member' => [
			'type' => 'belongsTo'
		]
	];

	protected $news_id;
	protected $version;
	protected $member_id;

}
// END CLASS

// EOF
