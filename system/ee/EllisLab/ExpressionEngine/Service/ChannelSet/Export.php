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
		$location = PATH_CACHE.'cset/name.zip';

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

		// JSON_PRETTY_PRINT was added in PHP 5.4
		$json_flags = (is_php('5.4.0')) ? JSON_PRETTY_PRINT : 0;
		$cset_json  = json_encode($base, $json_flags);

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

		// add it to the array early so that relationship can see
		// that it is already part of the set. That's also why these
		// are ids (that's what relationships store)
		$this->channels[$channel->getId()] = $result;

		if ($channel->StatusGroup && $channel->StatusGroup->group_name != 'Default')
		{
			$group = $this->exportStatusGroup($channel->StatusGroup);
			$result->status_group = $group->name;
		}

		if ($channel->FieldGroup)
		{
			$result->field_group = $this->exportFieldGroup($channel->FieldGroup);
		}

		if ($channel->CategoryGroups)
		{
			$result->cat_groups = array();

			foreach ($channel->CategoryGroups as $group)
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
	 * @return StdClass Group description
	 */
	private function exportStatusGroup($group)
	{
		$result = new StdClass();
		$result->name = $group->group_name;

		$result->statuses = array();
		$statuses = $group->Statuses->sortBy('status_order');

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
		return $category->cat_name;
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
	private function exportField($field, $group)
	{
		$file = '/custom_fields/'.$group.'/'.$field->field_name.'.'.$field->field_type;

		$result = new StdClass();

		$result->label = $field->field_label;
		$result->instructions = $field->field_instructions;
		$result->order = $field->field_order;

		if ($field->field_required)
		{
			$result->required = 'y';
		}

		if ($field->field_search)
		{
			$result->search = 'y';
		}

		if ($field->field_is_hidden)
		{
			$result->is_hidden = 'y';
		}

		if ( ! $field->field_show_fmt)
		{
			$result->show_fmt = 'n';
		}

		if ($field->field_fmt != 'xhtml')
		{
			$result->fmt = $field->field_fmt;
		}

		if ($field->field_content_type != 'any')
		{
			$result->content_type = $field->field_content_type;
		}

		if ($field->field_list_items)
		{
			$result->list_items = explode("\n", trim($field->field_list_items));
		}

		// fieldtype specific stuff
		if ($field->field_type == 'textarea')
		{
			$result->ta_rows = $field->field_ta_rows;
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

		$settings_obj->num_existing = $settings['num_existing'];
		$settings_obj->show_existing = $settings['show_existing'];
		$settings_obj->field_content_type = $settings['field_content_type'];
		$settings_obj->allowed_directories = $settings['allowed_directories'];

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
			$load_channels = array();

			foreach ($settings['channels'] as $id)
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

			$result->channels = array();

			foreach ($settings['channels'] as $id)
			{
				$channel = $this->channels[$id];
				$result->channels[] = $channel->channel_title;
			}
		}

		return $result;
	}
}
