<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;
use EllisLab\ExpressionEngine\Model\Category\Display\CategoryFieldLayout;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Category Model
 */
class Category extends ContentModel {

	protected static $_primary_key = 'cat_id';
	protected static $_table_name = 'categories';
	protected static $_gateway_names = array('CategoryGateway', 'CategoryFieldDataGateway');

	protected static $_hook_id = 'category';

	protected static $_relationships = array(
		'CategoryGroup' => array(
			'type' => 'belongsTo'
		),
		'ChannelEntries' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelEntry',
			'pivot' => array(
				'table' => 'category_posts',
				'left' => 'cat_id',
				'right' => 'entry_id'
			)
		),
		'Files' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'File',
			'pivot' => array(
				'table' => 'file_categories',
				'left' => 'cat_id',
				'right' => 'file_id'
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

	protected static $_field_data = array(
		'field_model'     => 'CategoryField',
		'group_column'    => 'group_id',
		'structure_model' => 'CategoryGroup',
	);

	protected static $_validation_rules = array(
		'cat_name'			=> 'required|noHtml|xss',
		'cat_url_title'		=> 'required|alphaDash|unique[group_id]',
		'cat_description'	=> 'xss',
		'cat_order'			=> 'isNaturalNoZero'
	);

	protected static $_events = array(
		'beforeInsert',
		'beforeDelete'
	);

	// Properties
	protected $cat_id;
	protected $site_id;
	protected $group_id;
	protected $parent_id;
	protected $cat_name;
	protected $cat_url_title;
	protected $cat_description;
	protected $cat_image;
	protected $cat_order;

	/**
	 * A link back to the owning category group object.
	 *
	 * @return	Structure	A link back to the Structure object that defines
	 *						this Content's structure.
	 */
	public function getStructure()
	{
		return $this->CategoryGroup;
	}

	/**
	 * Modify the default layout for category fields
	 */
	public function getDisplay(LayoutInterface $layout = NULL)
	{
		$layout = $layout ?: new CategoryFieldLayout();

		return parent::getDisplay($layout);
	}

	/**
	 * New categories get appended
	 */
	public function onBeforeInsert()
	{
		$cat_order = $this->getProperty('cat_order');

		if (empty($cat_order))
		{
			$count = $this->getModelFacade()->get('Category')
				->filter('group_id', $this->getProperty('group_id'))
				->count();
			$this->setProperty('cat_order', $count + 1);
		}

		$parent_id = $this->getProperty('parent_id');

		if (empty($parent_id))
		{
			$this->setProperty('parent_id', 0);
		}
	}

	/**
	 * Converts the fields into facades
	 *
	 * We're doing this here to properly set the format on a given field
	 */
	protected function addFacade($id, $info, $name_prefix = '')
	{
		if (array_key_exists('field_default_fmt', $info))
		{
			$info['field_fmt'] = $info['field_default_fmt'];
		}

		return parent::addFacade($id, $info, $name_prefix);
	}

	/**
	 * Get all nested children for the category, not just top level
	 *
	 * @return Collection All category children
	 */
	public function getAllChildren()
	{
		$children = [];

		if ($this->Children)
		{
			$children = $this->Children->asArray();

			foreach ($this->Children as $child)
			{
				$children = array_merge($children, $child->getAllChildren()->asArray());
			}
		}

		return new Collection($children);
	}
}

// EOF
