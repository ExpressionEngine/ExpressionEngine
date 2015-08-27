<?php

namespace User\addons\Wiki\Model;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

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
 * ExpressionEngine Page Table
 *
 * @package		ExpressionEngine
 * @subpackage	Category\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class PageGateway extends Gateway {

	protected static $_table_name = 'categories';
	protected static $_primary_key = 'cat_id';

	protected static $_related_gateways = array(
		'cat_id' => array(
			'gateway' => 'PageGateway',
			'pivot_table' => 'wiki_category_articles',
			'pivot_key' => 'cat_id',
			'pivot_foreign_key' => 'article_id'
		),
		'parent_id' => array(
			'gateway' => 'CategoryGateway',
			'key'	 => 'cat_id'
		),
	);

	protected $page_id;
	protected $wiki_id;
	protected $page_name;
	protected $page_namespace;
	protected $page_redirect;
	protected $page_locked;
	protected $page_moderated;
	protected $last_updated;
	protected $last_revision_id;
	protected $has_categories;
}
