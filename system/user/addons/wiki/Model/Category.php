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
 * ExpressionEngine Wiki Categories Model
 *
 * A model representing a Category in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Category extends Model {

	protected static $_primary_key = 'cat_id';
	protected static $_table_name = 'wiki_categories';	
	
//	protected static $_gateway_names = array('CategoryGateway');

/*

	protected static $_relationships = array(
		'Pages' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Page',
			'pivot' => array(
				'table' => 'wiki_category_articles',
				'left' => 'cat_id',
				'right' => 'page_id'
			)
		),
		'Parent' => array(
			'type' => 'belongsTo',
			'model' => 'Category',
			'from_key' => 'parent_id'
		),
		'Children' => array(
			'type' => 'hasMany',
			'model' => 'Category',
			'to_key' => 'parent_id'
		)
	);
	
*/

	protected static $_relationships = array(
		'Wiki' => array(
			'type' => 'belongsTo'
		)
	);	

	

	protected $cat_id;
	protected $wiki_id;
	protected $cat_name;
	protected $parent_id;
	protected $cat_namespace;
}