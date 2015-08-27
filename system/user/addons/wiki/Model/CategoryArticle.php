<?php

namespace User\addons\Wiki\Model;

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
 * ExpressionEngine Wiki Category Articles Model
 *
 * A model representing a Category Article in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CategoryArtcle extends Model {

	protected static $_primary_key = 'cat_id';
	protected static $_table_name = 'wiki_category_articles';

	protected $page_id;
	protected $cat_id;
}