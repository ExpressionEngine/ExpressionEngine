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
 * ExpressionEngine Wiki Page Model
 *
 * A model representing a Page in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Page extends Model {

	protected static $_primary_key = 'page_id';
	protected static $_table_name = 'wiki_page';
	
	protected static $_relationships = array(
		'Wiki' => array(
			'type' => 'belongsTo'
		),
		'Revisions' => array(
			'type' => 'hasMany',
			'model' => 'Revision'
		)
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
