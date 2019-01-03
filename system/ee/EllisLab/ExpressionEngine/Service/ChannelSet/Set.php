<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

use Closure;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Channel Set Service: Set
 */
class Set {

	/**
	 * @var Int Id of the site to import to
	 */
	private $site_id = 1;

	/**
	 * @var Array of channels [channel_title => ChannelModel, ...]
	 */
	private $channels = array();

	/**
	 * @var Array of fields [group_name => ChannelFieldModel, ...]
	 */
	private $fields = array();

	/**
	 * @var Array of field groups [group_name => FieldGroupModel, ...]
	 */
	private $field_groups = array();

	/**
	 * @var Array of category groups [group_name => CatGroupModel, ...]
	 */
	private $category_groups = array();

	/**
	 * @var Array of statuses [statuses => StatusModel, ...]
	 */
	private $statuses = array();

	/**
	 * @var Array of statuses [status group name => [StatusModel, ...]]
	 */
	private $status_groups = array();

	/**
	 * @var Array of upload destinations [name => UploadDestinationModel, ...]
	 */
	private $upload_destinations = array();

	/**
	 * @var Array of model relationships to be assigned after the saves
	 */
	private $assignments = array(
		'channel_field_groups' => array(),
		'channel_fields'       => array(),
		'field_group_fields'   => array(),
		'statuses'             => array(),
	);

	/**
	 * @var Array of top level containers. These are the properties of this
	 *      class that we have to loop through for validation and save. Order
	 *      matters - upload destinations must be in place for fields.
	 */
	private $top_level_elements = array(
		'upload_destinations',
		'channels',
		'fields',
		'field_groups',
		'statuses',
		'category_groups'
	);

    /**
     * @var String containing the path to the channel set
     */
    private $path;

    /**
     * @var ImportResult containing the result of the import
     */
    private $result;

    /**
     * @var Array A queue of closures to call after all the saving
     */
    private $post_save_queue = array();

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
	 * @var Associative array of top level element types and the IDs of the
	 *      newly-created elements
	 */
	private $insert_ids = [];

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
	 * Deletes the source files used in the import
	 */
	public function cleanUpSourceFiles()
	{
		$filesystem = new Filesystem();
		$filesystem->delete($this->getPath());
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
			$this->insert_ids[$property] = [];

			foreach ($this->$property as $model)
			{
				$model->save();
				$this->insert_ids[$property][] = $model->getId();
			}
		}

		$this->assignFieldsToFieldGroups();
		$this->assignFieldGroupsToChannels();
		$this->assignFieldsToChannels();
		$this->assignStatusesToChannels();

        foreach ($this->post_save_queue as $fn)
        {
            if ($fn instanceOf Closure)
            {
                $fn();
            }
        }
	}

	/**
	 * Get array of IDs for newly-inserted items
	 *
	 * @param string $element_type Element type to grab IDs for
	 * @return array Array of database IDs for given element type
	 */
	public function getIdsForElementType($element_type)
	{
		if (empty($this->insert_ids[$element_type]))
		{
			return [];
		}

		return $this->insert_ids[$element_type];
	}

	/**
	 * Saves the Channel -> FieldGroups relationshp
	 */
	private function assignFieldGroupsToChannels()
	{
		foreach ($this->assignments['channel_field_groups'] as $channel_title => $field_groups)
		{
			$channel = $this->channels[$channel_title];

			$field_group_ids = array();
			foreach ($field_groups as $field_group)
			{
				$field_group_ids[] = $field_group->getId();
			}

			$channel->FieldGroups = ee('Model')->get('ChannelFieldGroup', $field_group_ids)->all();
			$channel->save();
		}
	}

	/**
	 * Saves the Channel -> CustomFields relationshp
	 */
	private function assignFieldsToChannels()
	{
		foreach ($this->assignments['channel_fields'] as $channel_title => $fields)
		{
			$channel = $this->channels[$channel_title];

			$field_ids = array();
			foreach ($fields as $field_name)
			{
				$field = $this->getFieldByName($field_name);
				$field_ids[] = $field->getId();
			}

			$channel->CustomFields = ee('Model')->get('ChannelField', $field_ids)->all();
			$channel->save();
		}
	}

	/**
	 * Saves the FieldGroup -> CustomFields relationshp
	 */
	private function assignFieldsToFieldGroups()
	{
		foreach ($this->assignments['field_group_fields'] as $group_name => $fields)
		{
			$field_group = $this->field_groups[$group_name];

			$field_ids = array();
			foreach ($fields as $field_name)
			{
				$field = $this->getFieldByName($field_name);
				$field_ids[] = $field->getId();
			}

			$field_group->ChannelFields = ee('Model')->get('ChannelField', $field_ids)->all();
			$field_group->save();
		}
	}

	/**
	 * Saves the Channel -> Statuses relationshp
	 */
	private function assignStatusesToChannels()
	{
		$statuses_to_assign = ['open', 'closed'];

		foreach ($this->assignments['statuses'] as $channel_name => $statuses)
		{
			$channel = $this->channels[$channel_name];
			$channel->Statuses = ee('Model')->get('Status')
				->filter('status', 'IN', array_merge($statuses_to_assign, $statuses))
				->all();
			$channel->save();
		}
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
			$this->result->addError(lang('channel_set_invalid'));
			return;
		}

		$data = json_decode(file_get_contents($this->path.'/channel_set.json'));
		$field_groups = (isset($data->field_groups)) ? $data->field_groups : [];

		// Pre-4.0 sets will have status groups, post-4.0 sets will only have statuses
		$status_groups = isset($data->status_groups) ? $data->status_groups : [];
		$statuses = isset($data->statuses) ? $data->statuses : [];

		// Version check: v3 installs cannot import v4 exports
		$version = (isset($data->version)) ? $data->version : '3.0.0';
		$version = explode('.', $version);

		$app_version = explode('.', ee()->config->item('app_version'));
		if ($app_version[0] == 3 && $version[0] > $app_version[0])
		{
			$this->result->addError(sprintf(lang('channel_set_incompatible'), $version[0]));
			return;
		}

		try
		{
			$this->loadUploadDestinations($data->upload_destinations);
			$this->loadFieldsAndGroups($field_groups);
			$this->loadStatusGroups($status_groups);
			$this->loadStatuses($statuses);
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

	private function getFieldByName($field_name)
	{
		if (isset($this->aliases['ee:ChannelField'][$field_name]['field_name']))
		{
			$field_name = $this->aliases['ee:ChannelField'][$field_name]['field_name'];
		}

		return $this->fields[$field_name];
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
		// @TODO Use the Format service's urlSlug instead
		ee()->load->helper('url_helper');

		foreach ($channels as $channel_data)
		{
			$channel = ee('Model')->make('Channel');
			$channel_title = $channel_data->channel_title;
			$channel_name = (isset($channel_data->channel_name)) ? $channel_data->channel_name : strtolower(str_replace(' ', '_', $channel_data->channel_title));

			$channel->title_field_label = (isset($channel_data->title_field_label))
				? $channel_data->title_field_label
				: lang('title');
			$channel->site_id = $this->site_id;
			$channel->channel_name = $channel_name;
			$channel->channel_title = $channel_data->channel_title;
			$channel->channel_lang = 'en';

			$this->assignments['statuses'][$channel_title] = [];

			foreach ($channel_data as $pref_key => $pref_value)
			{
				if ( ! $channel->hasProperty($pref_key))
				{
					continue;
				}

				$channel->$pref_key = $pref_value;
			}

			$this->applyOverrides($channel, $channel->channel_name);

			$field_groups = array();

			if (isset($channel_data->field_group))
			{
				$field_group_name = $channel_data->field_group;
				if (isset($this->aliases['ee:ChannelFieldGroup'][$field_group_name]))
				{
					$field_group_name = $this->aliases['ee:ChannelFieldGroup'][$field_group_name]['group_name'];
				}

				$field_groups[] = $this->field_groups[$field_group_name];
			}

			if (isset($channel_data->field_groups))
			{
				foreach ($channel_data->field_groups as $field_group)
				{
					if (is_null($field_group))
					{
						continue;
					}

					if (isset($this->aliases['ee:ChannelFieldGroup'][$field_group]))
					{
						$field_group = $this->aliases['ee:ChannelFieldGroup'][$field_group]['group_name'];
					}
					$field_groups[] = $this->field_groups[$field_group];
				}
			}

			if ( ! empty($field_groups))
			{
				$this->assignments['channel_field_groups'][$channel_title] = $field_groups;
			}

			if (isset($channel_data->fields))
			{
				$this->assignments['channel_fields'][$channel_title] = $channel_data->fields;
			}

			if (isset($channel_data->status_group))
			{
				$this->assignments['statuses'][$channel_title] = $this->status_groups[$channel_data->status_group];
			}

			if (isset($channel_data->statuses))
			{
				$this->assignments['statuses'][$channel_title] = $channel_data->statuses;
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

					$channel->cat_group = rtrim(implode('|', $cat_group_ids), '|');
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
							if (isset($category_data->{$field->field_name}))
							{
								$category->$property = $category_data->{$field->field_name};
							}
						}
					};

					$category->on('beforeInsert', $fn);
				}

				$cat_group->Categories[] = $category;
			}

			$this->applyOverrides($cat_group, $group_name);

			$this->category_groups[$group_name] = $cat_group;
		}
	}

	/**
	 * Import statuses nested inside legacy status group structure
	 *
	 * @param Array $status_groups Status groups as described in channel_set.json
	 * @return void
	 */
	private function loadStatusGroups($status_groups)
	{
		foreach ($status_groups as $status_group_data)
		{
			$this->status_groups[$status_group_data->name] = $this->loadStatuses($status_group_data->statuses);
		}
	}

	/**
	 * Import status data into model objects
	 *
	 * @param Array $statuses Statuses as described in channel_set.json
	 * @return void
	 */
	private function loadStatuses($statuses)
	{
		$existing_statuses = ee('Model')->get('Status')->all()->pluck('status');

		// Keep track of statuses brought in by this single call to map them
		// to old channel sets that contain status groups
		$status_group = [];

		foreach ($statuses as $status_data)
		{
			$status_group[] = $status_data->name;

			if (in_array($status_data->name, $existing_statuses))
			{
				continue;
			}

			$status = ee('Model')->make('Status');
			$status->status = $status_data->name;

			if ( ! empty($status_data->highlight))
			{
				$status->highlight = $status_data->highlight;
			}

			$this->statuses[] = $status;
		}

		return $status_group;
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
	 * Instantiate the field and field group models
	 *
	 * @return void
	 */
	private function loadFieldsAndGroups($field_groups = array())
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
				$group_name = $it->getFilename();
				$group = $this->loadFieldGroup($group_name);
				$fields = array();

				foreach ($it->getChildren() as $field)
				{
					if ($field->isFile())
					{
						$field_model = $this->loadChannelField($field);
						$this->fields[$field_model->field_name] = $field_model;
						$fields[] = $field_model;
					}
				}

				$fn = function() use ($group, $fields)
				{
					$field_ids = array();
					foreach ($fields as $field)
					{
						$field_ids[] = $field->getId();
					}
					$group->ChannelFields = ee('Model')->get('ChannelField', $field_ids)->all();
					$group->save();
				};

				$group->on('afterInsert', $fn);
			}
			elseif ($item->isFile())
			{
				$field_model = $this->loadChannelField($item);
				$this->fields[$field_model->field_name] = $field_model;
			}
		}

		foreach ($field_groups as $field_group)
		{
			$group = $this->loadFieldGroup($field_group->name);
			$this->assignments['field_group_fields'][$group->group_name] = $field_group->fields;
		}
	}

	/**
	 * Instantiate a field group model
	 *
	 * @param String $group_name The name of the group to be added
	 * @return ChannelFieldGroupModel
	 */
	private function loadFieldGroup($group_name)
	{
		$group = ee('Model')->make('ChannelFieldGroup');
		$group->site_id = 0;
		$group->group_name = $group_name;

		$this->applyOverrides($group, $group_name);

		$this->field_groups[$group->group_name] = $group;
		return $group;
	}

	/**
	 * Instantiate a field model
	 *
	 * @param SplFileInfo $file File instance for the field.fieldtype file
	 * @return ChannelFieldModel
	 */
	private function loadChannelField(\SplFileInfo $file)
	{
		static $fieldtypes = array();

		if (empty($fieldtypes))
		{
			$fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');
		}

		$name = $file->getFilename();

		if (substr_count($name, '.') !== 1)
		{
			throw new ImportException("Invalid field definition: {$name}");
		}

		list($name, $type) = explode('.', $name);

		if ( ! in_array($type, $fieldtypes))
		{
			throw new ImportException("Fieldtype not installed: {$type}");
		}

		$data = json_decode(file_get_contents($file->getRealPath()), TRUE);

		// unusual item that has no defaults
		if ( ! isset($data['list_items']))
		{
			$data['list_items'] = '';
		}

		$field = ee('Model')->make('ChannelField');
		$field->site_id = 0;
		$field->field_name = $name;
		$field->field_type = $type;

		$field_data = array();

		foreach ($data as $key => $value)
		{
			if (($type == 'grid' || $type == 'file_grid') && $key == 'columns')
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
        elseif ($type == 'fluid_field')
		{
			$this->importFluidFieldField($field, $field_data);
		}

		$field->set($field_data);

		$this->applyOverrides($field, $name);

		return $field;
	}

	/**
	 * Instantiate a field model
	 *
	 * @param SplFileInfo $file File instance for the field.fieldtype file
	 * @return CategoryFieldModel
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
	 * @return array Modified $field_data
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

	/**
	 * Helper function for fluid field imports. We need to associate the correct field
	 * ids to our fluid field field. Since those don't exist until after saving has begun,
	 * we'll just capture the identifying names in a closure and query for 'em.
	 *
	 * @param ChannelFieldModel $field Field instance
	 * @param Array $field_data The field data that will be set() on the field
	 * @return void
	 */
	private function importFluidFieldField($field, $field_data)
	{
		$fn = function() use ($field, $field_data)
		{
			$settings = $field_data;

			if ($field_data['field_channel_fields'])
			{
				$settings['field_channel_fields'] = ee('Model')->get('ChannelField')
					->fields('field_id')
					->filter('field_name', 'IN', $field_data['field_channel_fields'])
					->all()
					->pluck('field_id');
			}

			$field->set($settings);
			$field->save();
		};

        $this->post_save_queue[] = $fn;
	}
}
