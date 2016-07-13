<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

use StdClass;
use ZipArchive;

class Export {

	private $zip;

	private $channels = array();
	private $status_groups = array();
	private $category_groups = array();
	private $upload_destinations = array();

	public function __construct()
	{
		if ( ! defined('JSON_PRETTY_PRINT'))
		{
			define('JSON_PRETTY_PRINT', 0);
		}
	}

	/**
	 * Create the channel set zip file for one or more channels.
	 *
	 * Automatically grabs all the related data that it needs.
	 *
	 * @param Array $channels List of channel instances
	 * @return String Path to the generated zip file
	 */
	public function zip($channels)
	{
		$this->zip = new ZipArchive();
		$location = PATH_CACHE."cset/{$channels[0]->channel_name}.zip";

		if ( ! is_dir(PATH_CACHE.'cset/'))
		{
			mkdir(PATH_CACHE.'cset/', DIR_WRITE_MODE);
		}

		$this->zip->open($location, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$base = new \StdClass;

		foreach ($channels as $channel)
		{
			$this->exportChannel($channel);
		}

		$base->channels = array_values($this->channels);
		$base->status_groups = $this->status_groups;
		$base->category_groups = $this->category_groups;
		$base->upload_destinations = array_values($this->upload_destinations);

		$cset_json = json_encode($base, JSON_PRETTY_PRINT);

		$this->zip->addFromString('channel_set.json', $cset_json);
		$this->zip->close();

		return $location;
	}

	/**
	 * Export a channel
	 *
	 * @param Model $channel Channel to export
	 * @return void
	 */
	private function exportChannel($channel)
	{
		// already in process
		if (isset($this->channels[$channel->getId()]))
		{
			return;
		}

		$result = new StdClass();

		$result->channel_title = $channel->channel_title;

		if ($channel->title_field_label != 'Title')
		{
			$result->title_field_label = $channel->title_field_label;
		}

		// add it to the array early so that relationship can see
		// that it is already part of the set. That's also why these
		// are ids (that's what relationships store)
		$this->channels[$channel->getId()] = $result;

		if ($channel->StatusGroup)
		{
			$group = $this->exportStatusGroup(
				$channel->StatusGroup,
				($channel->StatusGroup->group_name != 'Default')
			);
			$result->status_group = $group->name;
		}

		if ($channel->FieldGroup)
		{
			$result->field_group = $this->exportFieldGroup($channel->FieldGroup);
		}

		if ($channel->getCategoryGroups())
		{
			$result->cat_groups = array();

			foreach ($channel->getCategoryGroups() as $group)
			{
				$group = $this->exportCategoryGroup($group);
				$result->cat_groups[] = $group->name;
			}
		}
	}

	/**
	 * Export a status group and its statuses
	 *
	 * @param Model $group Status group to export
	 * @param bool $include_defaults Whether to include Open and Closed
	 * @return StdClass Group description
	 */
	private function exportStatusGroup($group, $include_defaults = TRUE)
	{
		$result = new StdClass();
		$result->name = $group->group_name;

		$result->statuses = array();
		$statuses = $group->Statuses->sortBy('status_order');

		if ($include_defaults == FALSE)
		{
			$statuses = $statuses->filter(function($status) {
				return ( ! in_array($status->status, array('open', 'closed')));
			});
		}

		foreach ($statuses as $status)
		{
			$result->statuses[] = $this->exportStatus($status);
		}

		$this->status_groups[] = $result;

		return $result;
	}

	/**
	 * Export a status
	 *
	 * @param Model $status Status to export
	 * @return StdClass Status description
	 */
	private function exportStatus($status)
	{
		$result = new StdClass();

		$result->name = $status->status;
		$result->highlight = $status->highlight;

		return $result;
	}

	/**
	 * Export a category group and its categories
	 *
	 * @param Model $group Category group to export
	 * @return StdClass Category group description
	 */
	private function exportCategoryGroup($group)
	{
		$result = new StdClass();
		$result->name = $group->group_name;
		$result->sort_order = $group->sort_order;

		$result->categories = array();

		foreach ($group->Categories as $category)
		{
			$result->categories[] = $this->exportCategory($category);
		}

		$this->category_groups[] = $result;

		foreach ($group->CategoryFields as $field)
		{
			$this->exportField($field, $group->group_name, 'category');
		}

		return $result;
	}

	/**
	 * Export a category
	 *
	 * @param Model $category Category export
	 * @return String Category name
	 */
	private function exportCategory($category)
	{
		$fields = $category->getCustomFields();

		$cat = new StdClass();
		$cat->cat_name = $category->cat_name;
		$cat->cat_url_title = $category->cat_url_title;
		$cat->cat_description = $category->cat_description;
		$cat->cat_order = $category->cat_order;

		foreach ($fields as $field)
		{
			$field_name = $field->getShortName();
			$cat->$field_name = $field->getData();
		}

		return $cat;
	}

	/**
	 * Export a field group and its fields
	 *
	 * @param Model $group Field group to export
	 * @return String Field group name
	 */
	private function exportFieldGroup($group)
	{
		$name = $group->group_name;

		$fields = $group->ChannelFields;

		foreach ($fields as $field)
		{
			$this->exportField($field, $name);
		}

		return $name;
	}

	/**
	 * Export a field
	 *
	 * @param Model $field Field to export
	 * @param String $group Group name
	 * @return void
	 */
	private function exportField($field, $group, $type = 'custom')
	{
		$file = '/' . $type . '_fields/'.$group.'/'.$field->field_name.'.'.$field->field_type;

		$result = new StdClass();

		$result->label = $field->field_label;
		$result->order = $field->field_order;

		if ($field->hasProperty('field_instructions'))
		{
			$result->instructions = $field->field_instructions;
		}

		if ($field->field_required)
		{
			$result->required = 'y';
		}

		if ($field->hasProperty('field_search') && $field->field_search)
		{
			$result->search = 'y';
		}

		if ($field->hasProperty('field_is_hidden') && $field->field_is_hidden)
		{
			$result->is_hidden = 'y';
		}

		if ( ! $field->field_show_fmt)
		{
			$result->show_fmt = 'n';
		}

		if ($field->hasProperty('field_fmt') && $field->field_fmt != 'xhtml')
		{
			$result->fmt = $field->field_fmt;
		}

		if ($field->hasProperty('field_content_type') && $field->field_content_type != 'any')
		{
			$result->content_type = $field->field_content_type;
		}

		if ($field->field_list_items)
		{
			$result->list_items = explode("\n", trim($field->field_list_items));
		}

		if ($field->hasProperty('field_pre_populate') && $field->field_pre_populate)
		{
			$result->pre_populate   = 'y';
			$result->pre_channel_id = $field->field_pre_channel_id;
			$result->pre_field_id   = $field->field_pre_field_id;
		}

		if ($field->field_maxl && $field->field_maxl != 256)
		{
			$result->maxl = $field->field_maxl;
		}

		if ($field->field_text_direction && $field->field_text_direction != 'ltr')
		{
			$result->text_direction = $field->field_text_direction;
		}

		// fieldtype specific stuff
		// start by defining any that exist- then overwrite special cases
		if (isset($field->field_settings))
		{
			$result->settings = $field->field_settings;
		}

		if ($field->field_type == 'file')
		{
			$result->settings = $this->exportFileFieldSettings($field);
		}

		if ($field->field_type == 'grid')
		{
			$result->columns = $this->exportGridFieldColumns($field);
		}

		if ($field->field_type == 'relationship')
		{
			$result->settings = $this->exportRelationshipField($field);
		}

		if (in_array($field->field_type, array('textarea', 'rte')))
		{
			$result->ta_rows = $field->field_ta_rows;
		}

		$field_json = json_encode($result, JSON_PRETTY_PRINT);

		$this->zip->addFromString($file, $field_json);
	}

	/**
	 * Export an upload destination
	 *
	 * @param Integer $id Id of the destination (comes from the file field settings)
	 * @return String Upload destination name
	 */
	private function exportUploadDestination($id)
	{
		$dir = ee('Model')->get('UploadDestination', $id)->first();

		$result = new StdClass();
		$result->name = $dir->name;

		$this->upload_destinations[$dir->name] = $result;

		return $result->name;
	}

	/**
	 * Do some extra work for file field exports
	 *
	 * @param Model $field Channel field
	 * @return StdClass Extra settings
	 */
	private function exportFileFieldSettings($field)
	{
		$settings = $field->field_settings;

		$settings_obj = new StdClass();

		$settings_obj->num_existing = (isset($settings['num_existing']))
			? $settings['num_existing'] : 50;
		$settings_obj->show_existing = (isset($settings['show_existing']))
			? $settings['show_existing'] : 'y';
		$settings_obj->field_content_type = (isset($settings['field_content_type']))
			? $settings['field_content_type'] : 'all';
		$settings_obj->allowed_directories = (isset($settings['allowed_directories']))
			? $settings['allowed_directories'] : 'all';

		if ($settings_obj->allowed_directories != 'all')
		{
			$settings_obj->allowed_directories = $this->exportUploadDestination($settings['allowed_directories']);
		}

		return $settings_obj;
	}

	/**
	 * Do some extra work for grid field exports
	 *
	 * @param Model $grid Channel field
	 * @return [StdClass]() Array of grid columns
	 */
	private function exportGridFieldColumns($grid)
	{
		ee()->load->model('grid_model');

		$columns = ee()->grid_model->get_columns_for_field($grid->getId(), $grid->getContentType());

		$result = array();

		foreach ($columns as $column)
		{
			if ($column['col_type'] == 'relationship')
			{
				// @TODO Actually export these things in a non-complicated manner
				$column['col_settings']['categories'] = array();
				$column['col_settings']['authors'] = array();
				$column['col_settings']['statuses'] = array();

				if (isset($column['col_settings']['channels']))
				{
					$this->exportRelatedChannels($column['col_settings']['channels']);
					foreach ($column['col_settings']['channels'] as &$id)
					{
						$channel = $this->channels[$id];
						$id = $channel->channel_title;
					}
				}
			}

			$col = new StdClass();

			unset(
				$column['col_id'],
				$column['col_order'],
				$column['field_id'],
				$column['content_type']
			);

			if ($column['col_width'] == 0)
			{
				unset($column['col_width']);
			}

			foreach ($column as $key => $value)
			{
				$simple_key = preg_replace('/^col_/', '', $key);
				$col->$simple_key = $value;
			}

			$result[] = $col;
		}

		return $result;
	}

	/**
	 * Do some extra work for relationship field exports
	 *
	 * @param Model $field Channel field
	 * @return StdClass Relationship settings description
	 */
	private function exportRelationshipField($field)
	{
		$settings = $field->field_settings;

		$result = new StdClass();

		if ($settings['expired'])
		{
			$result->expired = 'y';
		}

		if ($settings['future'])
		{
			$result->future = 'y';
		}

		if ( ! $settings['allow_multiple'])
		{
			$result->allow_multiple = 'n';
		}

		if ($settings['limit'] != 100)
		{
			$result->limit = $settings['limit'];
		}

		if ($settings['order_field'] != 'title')
		{
			$result->order_field = $settings['order_field'];
		}

		if ($settings['order_dir'] != 'asc')
		{
			$result->order_dir = $settings['order_dir'];
		}

		if (isset($settings['channels']))
		{
			$this->exportRelatedChannels($settings['channels']);

			$result->channels = array();

			foreach ($settings['channels'] as $id)
			{
				$channel = $this->channels[$id];
				$result->channels[] = $channel->channel_title;
			}
		}

		return $result;
	}

	/**
	 * Loops through an array of channels (by id) and exports any that have not
	 * already been exported
	 *
	 * @param Array $channels an array of channel ids
	 * @return void
	 */
	private function exportRelatedChannels($channels)
	{
		$load_channels = array();

		foreach ($channels as $id)
		{
			if ( ! isset($this->channels[$id]))
			{
				$load_channels[] = $id;
			}
		}

		if ( ! empty($load_channels))
		{
			$channels = ee('Model')->get('Channel', $load_channels)->all();

			foreach ($channels as $channel)
			{
				$this->exportChannel($channel);
			}
		}
	}

}
