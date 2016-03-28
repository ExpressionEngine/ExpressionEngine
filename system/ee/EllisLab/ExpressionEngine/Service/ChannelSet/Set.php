<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

class Set {

	private $channels = array();
	private $field_groups = array(); // group => [fields...]
	private $category_groups = array(); // group => [fields...]
	private $status_groups = array(); // group => [fields...]

	private $aliases = array();

	private $site_id = 1;

	public function __construct($path)
	{
		$this->path = rtrim($path, '/');
		$this->result = new ImportResult();
	}

	public function getPath()
	{
		return $this->path;
	}

	public function validate()
	{
		$this->load();

		if ( ! $this->result->isValid())
		{
			return $this->result;
		}

		foreach (array('channels', 'field_groups', 'status_groups', 'category_groups') as $model)
		{
			$human_name = rtrim(ucwords(str_replace('_', ' ', $model)), 's');

			foreach ($this->$model as $item)
			{
				$result = $item->validate();

				if ($result->failed())
				{
					foreach ($result->getFailed() as $field => $rules)
					{
						$this->result->addModelError($human_name, $item, $field, $rules);
					}
				}
			}
		}

		return $this->result;
	}

	public function save()
	{
		foreach (array('field_groups', 'channels', 'status_groups', 'category_groups') as $model)
		{
			foreach ($this->$model as $item)
			{
				$item->save();
			}
		}

		@unlink($this->path);
	}

	public function setAliases($aliases)
	{
		$this->aliases = $aliases;
	}

	private function load()
	{
		$data = json_decode(file_get_contents($this->path.'/channel_set.json'));

		try
		{
			$this->loadFieldsAndGroups();
			$this->loadStatusGroups($data->status_groups);
			$this->loadCategoryGroups($data->category_groups);
			$this->loadChannels($data->channels);
		}
		catch (\Exception $e)
		{
			$this->result->addError($e->getMessage());
		}
	}

	private function setAliasOverrides($model, $original_name)
	{
		$model_name = $model->getName();

		if (isset($this->aliases[$model_name][$original_name]))
		{
			$aliases = $this->aliases[$model_name][$original_name];

			foreach ($aliases as $field => $value)
			{
				$model->$field = $value;
			}
		}
	}

	private function loadChannels($channels)
	{
		foreach ($channels as $channel_data)
		{
			$channel = ee('Model')->make('Channel');

			$channel->title_field_label = lang('title');
			$channel->site_id = $this->site_id;
			$channel->channel_name = strtolower($channel_data->channel_title);
			$channel->channel_title = $channel_data->channel_title;
			$channel->channel_lang = 'en';

			$this->setAliasOverrides($channel, $channel->channel_name);

			if (isset($channel_data->field_group))
			{
				$channel->FieldGroup = $this->field_groups[$channel_data->field_group];
			}

			if (isset($channel_data->status_group))
			{
				$channel->StatusGroup = $this->status_groups[$channel_data->status_group];
			}

			if (isset($channel_data->cat_group))
			{
				$channel->CategoryGroups[] = $this->category_groups[$channel_data->cat_group];
			}

			foreach ($channel_data as $pref_key => $pref_value)
			{
				$channel->$pref_key = $pref_value;
			}

			$this->channels[] = $channel;
		}
	}

	private function loadCategoryGroups($category_groups)
	{
		foreach ($category_groups as $category_group_data)
		{
			$group_name = $category_group_data->name;

			$cat_group = ee('Model')->make('CategoryGroup');
			$cat_group->site_id = $this->site_id;
			$cat_group->sort_order = (isset($category_group_data->sort_order))
				? $category_group_data->sort_order
				: 'a';
			$cat_group->group_name = $group_name;

			foreach ($category_group_data->categories as $index => $category_name)
			{
				$category = ee('Model')->make('Category');
				$category->site_id = $this->site_id;
				$category->cat_name = $category_name;
				$category->cat_url_title = strtolower(str_replace(' ', '-', $category_name));
				$category->parent_id = 0;

				if ($cat_group->sort_order == 'c')
				{
					$category->cat_order = $index + 1;
				}

				$cat_group->Categories[] = $category;
			}

			$this->category_groups[$group_name] = $cat_group;
		}
	}

	private function loadStatusGroups($status_groups)
	{
		foreach ($status_groups as $status_group_data)
		{
			$group_name = $status_group_data->name;

			$status_group = ee('Model')->make('StatusGroup');
			$status_group->site_id = $this->site_id;
			$status_group->group_name = $group_name;

			foreach ($status_group_data->statuses as $status_data)
			{
				$status = ee('Model')->make('Status');
				$status->site_id = $this->site_id;
				$status->status = $status_data->status;

				if ( ! empty($status_data->highlight))
				{
					$status->highlight = $status_data->highlight;
				}

				$status_group->Statuses[] = $status;
			}

			$this->status_groups[$group_name] = $status_group;
		}
	}


	private function loadFieldsAndGroups()
	{
		$it = new \RecursiveDirectoryIterator(
			$this->path.'/custom_fields',
			\FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
		);

		foreach ($it as $item)
		{
			// fieldgroups are directories
			if ($item->isDir())
			{
				$this->loadFieldGroup($it);
			}
			/* lone fields for future compatibility
			elseif ($item->isFile())
			{
				$this->fields[] = $this->loadField($item);
			}
			*/
		}
	}

	private function loadFieldGroup($it)
	{
		$group_name = $it->getFilename();

		$group = ee('Model')->make('ChannelFieldGroup');
		$group->site_id = $this->site_id;
		$group->group_name = $group_name;

		$this->setAliasOverrides($group, $group_name);

		foreach ($it->getChildren() as $field)
		{
			if ($field->isFile())
			{
				$group->ChannelFields[] = $this->loadField($field);
			}
		}

		$this->field_groups[$group_name] = $group;
	}

	private function loadField(\SplFileInfo $file)
	{
		$name = $file->getFilename();

		if (substr_count($name, '.') !== 1)
		{
			throw new ImportException("Invalid field definition: {$name}");
		}

		list($name, $type) = explode('.', $name);

		$data = json_decode(file_get_contents($file->getRealPath()), TRUE);

		// unusual item that has no defaults
		if ( ! isset($data['field_list_items']))
		{
			$data['field_list_items'] = '';
		}

		$field = ee('Model')->make('ChannelField');
		$field->site_id = $this->site_id;
		$field->field_name = $name;
		$field->field_type = $type;

		foreach ($data as $key => $value)
		{
			$data['field_'.$key] = $value;
		}

		$field->set($data);

		return $field;
	}
}
