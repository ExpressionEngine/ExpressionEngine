<?php

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;
use EllisLab\ExpressionEngine\Model\Category\Display\CategoryFieldLayout;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Category Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Category extends ContentModel {

	protected static $_primary_key = 'cat_id';
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

	protected static $_validation_rules = array(
		'cat_name'			=> 'required|noHtml|xss',
		'cat_url_title'		=> 'required|alphaDash|unique[group_id]',
		'cat_description'	=> 'xss',
		'cat_order'			=> 'isNaturalNoZero'
	);

	protected static $_events = array(
		'beforeInsert',
		'afterInsert',
		'beforeUpdate',
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

	public static function getExtraData($result_array)
	{
		$field_groups = array_map(function($column) {
			if (array_key_exists('Category__group_id', $column))
			{
				return $column['Category__group_id'];
			}
		}, $result_array);

		$field_groups = array_unique($field_groups);

		$fields = ee('Model')->get('CategoryField')
			->fields('field_id')
			->filter('group_id', 'IN', $field_groups)
			->filter('legacy_field_data', 'n')
			->all();

		if ($fields->count())
		{
			$field_ids = $fields->pluck('field_id');

			$cat_ids = array_map(function($column) {
				return $column['Category__cat_id'];
			}, $result_array);

			$query = ee('Model/Datastore')->rawQuery();

			$main_table = "Category_field_id_{$cat_ids[0]}";

			$query->from('categories');
			$query->select("categories.cat_id as Category__cat_id", FALSE);

			foreach ($field_ids as $field_id)
			{
				$table_alias = "Category_field_id_{$field_id}";

				$query->select("{$table_alias}.data as Category__field_id_{$field_id}", FALSE);
				$query->select("{$table_alias}.metadata as Category__field_rt_{$field_id}", FALSE);
				$query->join("category_field_data_field_{$field_id} AS {$table_alias}", "{$table_alias}.entry_id = categories.cat_id", 'LEFT');
			}

			$query->where_in('categories.cat_id', $cat_ids);

			$data = $query->get()->result_array();

			foreach ($data as $row)
			{
				array_walk($result_array, function (&$data, $key, $field_data) {
					if ($data['Category__cat_id'] == $field_data['Category__cat_id'])
					{
						$data = array_merge($data, $field_data);
					}
				}, $row);
			}
		}

		return $result_array;
	}

	public static function augmentQuery($builder, $query, $model_fields)
	{
		$field_ids = array();

		foreach ($builder->getFilters() as $filter)
		{
			$field = $filter[0];
			if (strpos($field, 'field_id') === 0)
			{
				$field_ids[] = str_replace('field_id_', '', $field);
			}
		}

		foreach (array_keys($builder->getSearch()) as $field)
		{
			if (strpos($field, 'field_id') === 0)
			{
				$field_ids[] = str_replace('field_id_', '', $field);
			}
		}

		foreach ($builder->getOrders() as $order)
		{
			$field = $order[0];

			if (strpos($field, 'field_id') === 0)
			{
				$field_ids[] = str_replace('field_id_', '', $field);
			}
		}

		if ( ! empty($field_ids))
		{
			$field_ids = array_unique($field_ids);

			$fields = ee('Model')->get('CategoryField')
				->fields('field_id')
				->filter('field_id', 'IN', $field_ids)
				->filter('legacy_field_data', 'n')
				->all();

			foreach ($fields->pluck('field_id') as $field_id)
			{
				$table_alias = "Category_field_id_{$field_id}";
				$column_alias = "Category__field_id_{$field_id}";

				$query->select("{$table_alias}.data as {$column_alias}", FALSE);
				$query->join("category_field_data_field_{$field_id} AS {$table_alias}", "{$table_alias}.entry_id = {$model_fields['Category']['Category__cat_id']}", 'LEFT');
				$model_fields['Category'][$column_alias] = $table_alias . '.data';
			}
		}

		return $model_fields;
	}



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
			$count = $this->getFrontend()->get('Category')
				->filter('group_id', $this->getProperty('group_id'))
				->count();
			$this->setProperty('cat_order', $count + 1);
		}
	}

	public function onAfterInsert()
	{
		$this->saveFieldData($this->getValues());
	}

	public function onBeforeUpdate($changed)
	{
		$this->saveFieldData($changed);
	}

	public function onBeforeDelete()
	{
		$this->deleteFieldData();
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
	 * Gets a collection of CategoryGroup objects
	 *
	 * @return Collection A collection of CategoryGroup objects
	 */
	protected function getFieldModels()
	{
		$fields = $this->CategoryGroup->CategoryFields;

		if ($fields->count() == 0)
		{
			$fields = $this->getModelFacade()
				->get('CategoryGroup', $this->group_id)
				->first()
				->CategoryFields;
		}

		return $fields;
	}
}

// EOF
