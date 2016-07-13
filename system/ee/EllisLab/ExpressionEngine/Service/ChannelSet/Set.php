<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

class Set {

	/**
	 * @var Int Id of the site to import to
	 */
	private $site_id = 1;

	/**
	 * @var Array of channels
	 */
	private $channels = array();

	/**
	 * @var Array of field groups [group_name => FieldGroupModel, ...]
	 */
	private $field_groups = array();

	/**
	 * @var Array of category groups [group_name => CatGroupModel, ...]
	 */
	private $category_groups = array();

	/**
	 * @var Array of status groups [group_name => StatusGroupModel, ...]
	 */
	private $status_groups = array();

	/**
	 * @var Array of upload destinations [name => UploadDestinationModel, ...]
	 */
	private $upload_destinations = array();

	/**
	 * @var Array of top level containers. These are the properties of this
	 *      class that we have to loop through for validation and save. Order
	 *      matters - upload destinations must be in place for fields.
	 */
	private $top_level_elements = array(
		'upload_destinations',
		'channels',
		'field_groups',
		'status_groups',
		'category_groups'
	);

	/**
	 * @var Array of things that would create duplicates and need to be renamed
	 *
	 * Looks like so:
	 *		[model => [shortname] => [field_to_change => newvalue]]
	 *
	 * The shortname will always be the name as specified in the channel set
	 * definition so that we can relate entities by name. The _original_ shortname
	 * is the key on the above arrays. Tread carefully, in this class aliases should
	 * never be used for identification. Do not trust `$model->shortname`.
	 */
	private $aliases = array();

	/**
	 * @param String $path Path to the channel set
	 */
	public function __construct($path)
	{
		$this->path = rtrim($path, '/');
		$this->result = new ImportResult();
	}

	/**
	 * Get path to directory
	 *
	 * @return String Filesystem path to this set
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set the site id
	 *
	 * @param Int Id of the site we're on
	 * @return void
	 */
	public function setSiteId($site_id)
	{
		$this->site_id = $site_id;
	}

	/**
	 * Validate the set before import
	 *
	 * @return ImportResult
	 */
	public function validate()
	{
		$this->load();

		if ( ! $this->result->isValid())
		{
			return $this->result;
		}

		foreach ($this->top_level_elements as $property)
		{
			foreach ($this->$property as $model)
			{
				$this->validateOne($model);
			}
		}

		return $this->result;
	}

	/**
	 * Consider this private. It's for relationship use only.
	 */
	public function getIdsForChannels(array $titles)
	{
		$channels = array();

		foreach ($titles as $title)
		{
			if (isset($this->channels[$title]))
			{
				$channel = $this->channels[$title];
				$channels[$title] = $channel->getId();
			}
		}

		return $channels;
	}

	/**
	 * Validate a model and look for descendents as described by the structure
	 * struct (hah).
	 *
	 * @param Model $model Thing to validate
	 * @return void
	 */
	private function validateOne($model)
	{
		$result = $model->validate();

		if ($result->failed())
		{
			$section = Structure::getHumanName($model);

			foreach ($result->getFailed() as $field => $rules)
			{
				$this->result->addModelError($section, $model, $field, $rules);
			}
		}

		foreach (Structure::getValidateRelationships($model) as $relation)
		{
			foreach ($model->$relation as $other)
			{
				$this->validateOne($other);
			}
		}
	}

	/**
	 * Save all of the set entities
	 *
	 * @return void
	 */
	public function save()
	{
		foreach ($this->top_level_elements as $property)
		{
			foreach ($this->$property as $model)
			{
				$model->save();
			}
		}

		@unlink($this->path);
	}

	/**
	 * Set manual overrides
	 *
	 * @return void
	 */
	public function setAliases($aliases)
	{
		$this->aliases = $aliases;
	}

	/**
	 * Read all the files and load up a big graph of models. Sweet!
	 *
	 * @return void
	 */
	private function load()
	{
		if ( ! file_exists($this->path.'/channel_set.json'))
		{
			$this->result->addError('Not a valid channel set. Missing channel_set.json file.');
			return;
		}

		$data = json_decode(file_get_contents($this->path.'/channel_set.json'));

		try
		{
			$this->loadUploadDestinations($data->upload_destinations);
			$this->loadFieldsAndGroups();
			$this->loadStatusGroups($data->status_groups);
			$this->loadCategoryGroups($data->category_groups);
			$this->loadCategoryFields();
			$this->loadChannels($data->channels);
		}
		catch (\Exception $e)
		{
			$this->result->addError($e->getMessage());
		}
	}

	/**
	 * Apply the custom alias overrides
	 *
	 * @param Model $model Thing to apply overrides to
	 * @param String $original_name Identifying name for the model
	 * @return void
	 */
	private function applyOverrides($model, $original_name)
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

	/**
	 * Instantiate the upload destination models
	 *
	 * @param Array $destinations Destinations as described in channel_set.json
	 * @return void
	 */
	private function loadUploadDestinations($destinations)
	{
		foreach ($destinations as $upload_data)
		{
			$destination = ee('Model')->make('UploadDestination');
			$destination->site_id = $this->site_id;
			$destination->name = $upload_data->name;

			$this->applyOverrides($destination, $upload_data->name);

			$this->upload_destinations[$upload_data->name] = $destination;
		}
	}

	/**
	 * Instantiate the channel models
	 *
	 * @param Array $channels Channels as described in channel_set.json
	 * @return void
	 */
	private function loadChannels($channels)
	{
		foreach ($channels as $channel_data)
		{
			$channel = ee('Model')->make('Channel');
			$channel_title = $channel_data->channel_title;

			$channel->title_field_label = (isset($channel_data->title_field_label))
				? $channel_data->title_field_label
				: lang('title');
			$channel->site_id = $this->site_id;
			$channel->channel_name = strtolower(str_replace(' ', '_', $channel_data->channel_title));
			$channel->channel_title = $channel_data->channel_title;
			$channel->channel_lang = 'en';

			foreach ($channel_data as $pref_key => $pref_value)
			{
				if ( ! $channel->hasProperty($pref_key))
				{
					continue;
				}

				$channel->$pref_key = $pref_value;
			}

			$this->applyOverrides($channel, $channel->channel_name);

			if (isset($channel_data->field_group))
			{
				$channel->FieldGroup = $this->field_groups[$channel_data->field_group];
			}

			if (isset($channel_data->status_group))
			{
				$channel->StatusGroup = $this->status_groups[$channel_data->status_group];
			}

			if (isset($channel_data->cat_groups))
			{
				$cat_groups = $this->category_groups;
				$fn = function() use ($channel, $channel_data, $cat_groups)
				{
					$cat_group_ids = array();
					foreach ($cat_groups as $cat_group)
					{
						$cat_group_ids[] = $cat_group->getId();
					}

					$channel->cat_group = implode('|', $cat_group_ids);
					$channel->save();
				};

				foreach ($channel_data->cat_groups as $cat_group)
				{
					$this->category_groups[$cat_group]->on('afterInsert', $fn);
				}
			}

			$this->channels[$channel_title] = $channel;
		}
	}

	/**
	 * Instantiate the category group models
	 *
	 * @param Array $category_groups Category groups as described in channel_set.json
	 * @return void
	 */
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

			foreach ($category_group_data->categories as $index => $category_data)
			{
				$category = ee('Model')->make('Category');
				$category->site_id = $this->site_id;
				$category->parent_id = 0;

				if (is_string($category_data))
				{
					$category->cat_name = $category_data;
					$category->cat_url_title = strtolower(str_replace(' ', '-', $category_data));

					if ($cat_group->sort_order == 'c')
					{
						$category->cat_order = $index + 1;
					}
				}
				else
				{
					$category->cat_name = $category_data->cat_name;
					$category->cat_url_title = $category_data->cat_url_title;
					$category->cat_description = $category_data->cat_description;
					$category->cat_order = $category_data->cat_order;

					$fn = function() use ($category, $category_data)
					{
						$fields = get_object_vars($category_data);

						foreach ($category->CategoryGroup->CategoryFields as $field)
						{
							$property = 'field_id_' . $field->getId();
							$category->$property = $category_data->{$field->field_name};
						}
					};

					$category->on('beforeInsert', $fn);
				}

				$cat_group->Categories[] = $category;
			}

			$this->category_groups[$group_name] = $cat_group;
		}
	}

	/**
	 * Instantiate the status group models
	 *
	 * @param Array $status_groups Status groups as described in channel_set.json
	 * @return void
	 */
	private function loadStatusGroups($status_groups)
	{
		foreach ($status_groups as $status_group_data)
		{
			$group_name = $status_group_data->name;

			if ($group_name == 'Default')
			{
				$status_group = $this->getDefaultStatusGroup();
			}
			else
			{
				$status_group = ee('Model')->make('StatusGroup');
				$status_group->site_id = $this->site_id;
				$status_group->group_name = $group_name;
			}

			foreach ($status_group_data->statuses as $status_data)
			{
				// Ensure status doesn't already exist
				if ($group_name == 'Default')
				{
					$statuses = $status_group->Statuses->pluck('status');

					if (in_array($status_data->name, $statuses))
					{
						continue;
					}
				}

				$status = ee('Model')->make('Status');
				$status->site_id = $this->site_id;
				$status->status = $status_data->name;

				if ( ! empty($status_data->highlight))
				{
					$status->highlight = $status_data->highlight;
				}

				$status_group->Statuses[] = $status;
			}

			$this->status_groups[$group_name] = $status_group;
		}
	}

	private function loadCategoryFields()
	{
		if ( ! is_dir($this->path.'/category_fields'))
		{
			return;
		}

		$it = new \RecursiveDirectoryIterator(
			$this->path.'/category_fields',
			\FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
		);

		foreach ($it as $item)
		{
			if ($item->isDir())
			{
				$category_group = $this->category_groups[$it->getFilename()];

				foreach ($it->getChildren() as $field)
				{
					if ($field->isFile())
					{
						$category_group->CategoryFields[] = $this->loadCategoryField($field);
					}
				}
			}
		}
	}

	/**
	 * Gets the default status group for this site, and if it isn't there we'll
	 * create it
	 *
	 * @return obj A Status Group object
	 */
	private function getDefaultStatusGroup()
	{
		$status_group = ee('Model')->get('StatusGroup')
			->filter('group_name', 'Default')
			->filter('site_id', $this->site_id)
			->first();

		if ( ! $status_group)
		{
			$site = ee('Model')->get('Site', $this->site_id)->first();
			$site->createDefaultStatuses();
			return $this->getDefaultStatusGroup(); // recursion FTW!
		}

		return $status_group;
	}

	/**
	 * Instantiate the field and field group models
	 *
	 * @return void
	 */
	private function loadFieldsAndGroups()
	{
		if ( ! is_dir($this->path.'/custom_fields'))
		{
			return;
		}

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

	/**
	 * Instantiate a field group model
	 *
	 * @param Iterator $it Filesystem iterator with its cursor on the field group folder
	 * @return void
	 */
	private function loadFieldGroup($it)
	{
		$group_name = $it->getFilename();

		$group = ee('Model')->make('ChannelFieldGroup');
		$group->site_id = $this->site_id;
		$group->group_name = $group_name;

		$this->applyOverrides($group, $group_name);

		foreach ($it->getChildren() as $field)
		{
			if ($field->isFile())
			{
				$group->ChannelFields[] = $this->loadChannelField($field);
			}
		}

		$this->field_groups[$group_name] = $group;
	}

	/**
	 * Instantiate a field model
	 *
	 * @param SplFileInfo $file File instance for the field.fieldtype file
	 * @return ChannelFieldModel
	 */
	private function loadChannelField(\SplFileInfo $file)
	{
		$name = $file->getFilename();

		if (substr_count($name, '.') !== 1)
		{
			throw new ImportException("Invalid field definition: {$name}");
		}

		list($name, $type) = explode('.', $name);

		$data = json_decode(file_get_contents($file->getRealPath()), TRUE);

		// unusual item that has no defaults
		if ( ! isset($data['list_items']))
		{
			$data['list_items'] = '';
		}

		$field = ee('Model')->make('ChannelField');
		$field->site_id = $this->site_id;
		$field->field_name = $name;
		$field->field_type = $type;

		$field_data = array();

		foreach ($data as $key => $value)
		{
			if ($type == 'grid' && $key == 'columns')
			{
				$this->importGrid($field, $value);

				continue;
			}

			if ($key == 'settings')
			{
				$field_data = array_merge($field_data, $value);

				if ($type == 'file')
				{
					$this->importFileField($field, $field_data);
				}

				continue;
			}

			if ($key == 'list_items' && is_array($value))
			{
				$value = implode("\n", $value);
			}

			$field_data['field_'.$key] = $value;
		}

		if ($type == 'relationship')
		{
			$field_data = $this->importRelationshipField($field, $field_data);
		}

		$field->set($field_data);

		$this->applyOverrides($field, $name);

		return $field;
	}

	/**
	 * Instantiate a field model
	 *
	 * @param SplFileInfo $file File instance for the field.fieldtype file
	 * @return ChannelFieldModel
	 */
	private function loadCategoryField(\SplFileInfo $file)
	{
		$name = $file->getFilename();

		if (substr_count($name, '.') !== 1)
		{
			throw new ImportException("Invalid field definition: {$name}");
		}

		list($name, $type) = explode('.', $name);

		$data = json_decode(file_get_contents($file->getRealPath()), TRUE);

		// unusual item that has no defaults
		if ( ! isset($data['list_items']))
		{
			$data['list_items'] = '';
		}

		$field = ee('Model')->make('CategoryField');
		$field->site_id = $this->site_id;
		$field->field_name = $name;
		$field->field_type = $type;

		$field_data = array();

		foreach ($data as $key => $value)
		{
			if ($key == 'list_items' && is_array($value))
			{
				$value = implode("\n", $value);
			}

			$field_data['field_'.$key] = $value;
		}

		$field->set($field_data);

		$this->applyOverrides($field, $name);

		return $field;
	}

	/**
	 * Helper function for grid import. We modify POST in a hook to make sure
	 * we get the right data for each field even though we're going to save
	 * several of them at once.
	 *
	 * @param ChannelFieldModel $field Field instance
	 * @param Array $columns The columns defined in the field.type file
	 * @return void
	 */
	private function importGrid($field, $columns)
	{
		$that = $this;
		$fn = function() use ($columns, $that)
		{
			unset($_POST['grid']);

			// grid[cols][new_0][col_label]
			foreach ($columns as $i => $column)
			{
				if ($column['type'] == 'relationship')
				{
					if (isset($column['settings']['channels']))
					{
						$channel_ids = $that->getIdsForChannels($column['settings']['channels']);
						$column['settings']['channels'] = $channel_ids;
					}
				}

				foreach ($column as $col_label => $col_value)
				{
					// Grid is expecting a POSTed checkbox, so if it's in POST at all
					// this value will be set to 'y'
					// @todo Fieldtypes should receive data, not reach into POST
					if ($col_label == 'required' && $col_value == 'n')
					{
						continue;
					}

					$_POST['grid']['cols']["new_{$i}"]['col_'.$col_label] = $col_value;
				}
			}
		};

		$field->on('beforeValidate', $fn);
		$field->on('beforeInsert', $fn);
	}

	/**
	 * Helper function for file imports. We need to associate the correct upload
	 * id to our file field. Since those don't exist until after saving has begun,
	 * we'll just capture the identifying names in a closure and query for 'em.
	 *
	 * Not the fastest thing. Might be able to capture `$this` instead as we do
	 * with relationships.
	 *
	 * @param ChannelFieldModel $field Field instance
	 * @param Array $field_data The field data that will be set() on the field
	 * @return void
	 */
	private function importFileField($field, $field_data)
	{
		$allowed = $field_data['allowed_directories'];

		if ($allowed != 'all')
		{
			$dir = $this->upload_destinations[$allowed];
			$dir_name = $dir->name; // using the alias if there is one

			$fn = function() use ($field, $dir_name, $field_data)
			{
				$settings = $field_data;

				$dest = ee('Model')->get('UploadDestination')
					->fields('id')
					->filter('name', $dir_name)
					->first();

				$settings['allowed_directories'] = $dest->getId();
				$field->set($settings);
			};

			$field->on('beforeInsert', $fn);
		}
	}

	/**
	 * Helper function for relationship imports. We need to associate the correct
	 * channel id to our relationship field. Since those don't exist until after
	 * saving has begun, we'll capture this class and grab the data we want directly
	 * from it.
	 *
	 * @param ChannelFieldModel $field Field instance
	 * @param Array $field_data The field data that will be set() on the field
	 * @return Array Modified $field_data
	 */
	private function importRelationshipField($field, $field_data)
	{
		$defaults['channels'] = array();
		$defaults['authors'] = array();
		$defaults['categories'] = array();
		$defaults['statuses'] = array();
		$defaults['limit'] = 100;

		$defaults['expired'] = 'n';
		$defaults['future'] = 'n';
		$defaults['allow_multiple'] = 'n';

		$defaults['order_field'] = 'title';
		$defaults['order_dir'] = 'asc';

		$field_data = array_merge($defaults, $field_data);

		// rewrite any that might be wonky after that rather heavy conversion
		$field_data['expired']        = (int) ($field_data['expired'] === 'y');
		$field_data['future']         = (int) ($field_data['future'] === 'y');
		$field_data['allow_multiple'] = (int) ($field_data['allow_multiple'] === 'y');

		if (isset($field_data['channels']))
		{
			$that = $this;

			$fn = function() use ($field, $field_data, $that)
			{
				$settings = $field_data;

				$channel_ids = $that->getIdsForChannels($settings['channels']);
				$settings['channels'] = $channel_ids;

				$field->set($settings);
			};

			$field->on('beforeInsert', $fn);
		}

		return $field_data;

	}
}
