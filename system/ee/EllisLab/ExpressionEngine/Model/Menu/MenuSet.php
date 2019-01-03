<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Menu;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Menu Set Model
 */
class MenuSet extends Model {

	protected static $_primary_key = 'set_id';
	protected static $_table_name = 'menu_sets';

	protected static $_validation_rules = array(
		'name' => 'required|noHtml|unique'
	);

	protected static $_relationships = array(
		'Items' => array(
			'model' => 'MenuItem',
			'type' => 'HasMany'
		),
		'MemberGroups' => array(
			'model' => 'MemberGroup',
			'type' => 'HasMany',
			'to_key' => 'menu_set_id',
			'weak' => TRUE
		),
	);

	protected $set_id;
	protected $name;

	/**
	 * Builds a tree of menu set items in the current menu set for use in a
	 * SelectField form
	 *
	 * @param array Items tree
	 */
	public function buildItemsTree()
	{
		return $this->buildTreeForItems(
			$this->Items->filter('parent_id', 0)->sortBy('sort')
		);
	}

	/**
	 * Turn the items collection into a nested array of ids => names
	 *
	 * @param Collection $items Top level items to construct tree out of
	 * @return array Items tree
	 */
	protected function buildTreeForItems($items)
	{
		$list = array();

		foreach ($items as $item)
		{
			$children = $item->Children->sortBy('sort');

			if (count($children))
			{
				$list[$item->getId()] = array(
					'name' => $item->name,
					'children' => $this->buildTreeForItems($children)
				);

				continue;
			}

			$list[$item->getId()] = $item->name;
		}

		return $list;
	}
}
