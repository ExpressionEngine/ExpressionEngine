<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Channel Entry
 *
 * An entry in a content channel.  May have multiple custom fields in
 * addition to a number of built in fields.  Is content and may be
 * rendered on the front end.  Has a publish form that includes its
 * many fields as sub publish elements.
 *
 * Related to Channel which defines the structure of this content.
 */
class ChannelEntry extends ContentModel {

	protected static $_primary_key = 'entry_id';
	protected static $_gateway_names = array('ChannelTitleGateway', 'ChannelDataGateway');

	protected static $_typed_columns = array(
		'versioning_enabled'      => 'boolString',
		'allow_comments'          => 'boolString',
		'sticky'                  => 'boolString',
		'entry_date'              => 'int',
		'expiration_date'         => 'int',
		'comment_expiration_date' => 'int',
		'edit_date'               => 'timestamp',
		'recent_comment_date'     => 'timestamp',
	);

	protected static $_relationships = array(
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'Author'	=> array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' 	=> 'author_id'
		),
		'Categories' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Category',
			'pivot' => array(
				'table' => 'category_posts',
				'left' => 'entry_id',
				'right' => 'cat_id'
			)
		),
		'Autosaves' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntryAutosave',
			'to_key' => 'original_entry_id'
		),
		'Parents' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelEntry',
			'pivot' => array(
				'table' => 'relationships',
				'left' => 'child_id',
				'right' => 'parent_id'
			)
		),
		'Children' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelEntry',
			'pivot' => array(
				'table' => 'relationships',
				'left' => 'parent_id',
				'right' => 'child_id'
			)
		),
		'Versions' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntryVersion'
		),
	);

	protected static $_validation_rules = array(
		'channel_id'         => 'required',
		'ip_address'         => 'ip_address',
		'title'              => 'required',
		'url_title'          => 'required|alphaDash',
		'status'             => 'required',
		'entry_date'         => 'required',
		'versioning_enabled' => 'enum[y,n]',
		'allow_comments'     => 'enum[y,n]',
		'sticky'             => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeDelete',
		'afterSave'
	);

	// Properties
	protected $entry_id;
	protected $site_id;
	protected $channel_id;
	protected $author_id;
	protected $forum_topic_id;
	protected $ip_address;
	protected $title;
	protected $url_title;
	protected $status;
	protected $versioning_enabled;
	protected $view_count_one;
	protected $view_count_two;
	protected $view_count_three;
	protected $view_count_four;
	protected $allow_comments;
	protected $sticky;
	protected $entry_date;
	protected $year;
	protected $month;
	protected $day;
	protected $expiration_date;
	protected $comment_expiration_date;
	protected $edit_date;
	protected $recent_comment_date;
	protected $comment_total;

	public function set__entry_date($entry_date)
	{
		$this->setRawProperty('entry_date', $entry_date);

		// Day, Month, and Year Fields
		// @TODO un-break these windows: inject this dependency
		$this->setProperty('year', ee()->localize->format_date('%Y', $entry_date));
		$this->setProperty('month', ee()->localize->format_date('%m', $entry_date));
		$this->setProperty('day', ee()->localize->format_date('%d', $entry_date));
	}

	public function validate()
	{
		$result = parent::validate();

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			include_once($info->getPath() . '/tab.' . $name . '.php');
			$class_name = ucfirst($name) . '_tab';
			$OBJ = new $class_name();

			if (method_exists($OBJ, 'validate') === TRUE)
			{
				$fields = $OBJ->display($this->channel_id, $this->entry_id);

				$values = array();
				foreach(array_keys($fields) as $field)
				{
					$property = $name . '__' . $field;
					$values[$field] = $this->$property;
				}

				$tab_result = $OBJ->validate($this, $values);

				if ($tab_result instanceOf ValidationResult && $tab_result->failed())
				{
					foreach ($tab_result->getFailed() as $field => $rules)
					{
						foreach ($rules as $rule)
						{
							$result->addFailed($name . '__' . $field, $rule);
						}
					}
				}
			}
		}

		return $result;
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		$this->Autosaves->delete();

		$this->saveVersion();

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			include_once($info->getPath() . '/tab.' . $name . '.php');
			$class_name = ucfirst($name) . '_tab';
			$OBJ = new $class_name();

			if (method_exists($OBJ, 'save') === TRUE)
			{
				$fields = $OBJ->display($this->channel_id, $this->entry_id);

				$values = array();
				foreach(array_keys($fields) as $field)
				{
					$property = $name . '__' . $field;
					$values[$field] = $this->$property;
				}

				$OBJ->save($this, $values);
			}
		}
	}

	public function saveVersion()
	{
		if ( ! $this->getProperty('versioning_enabled'))
		{
			return;
		}

		if ($this->Versions->count() == $this->Channel->max_revisions)
		{
			$this->Versions->order('version_date')->first()->delete();
		}

		$data = array(
			'entry_id'     => $this->entry_id,
			'channel_id'   => $this->channel_id,
			'author_id'    => $this->author_id,
			'version_date' => ee()->localize->now,
			'version_data' => $this->getValues()
		);

		$version = $this->getFrontend()->make('ChannelEntryVersion', $data)->save();
	}

	public function onBeforeDelete()
	{
		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			include_once($info->getPath() . '/tab.' . $name . '.php');
			$class_name = ucfirst($name) . '_tab';
			$OBJ = new $class_name();

			if (method_exists($OBJ, 'delete') === TRUE)
			{
				$OBJ->delete(array($this->entry_id));
			}
		}
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getStructure()
	{
		return $this->Channel;
	}

	/**
	 * Modify the default layout for channels
	 */
	public function getDisplay(LayoutInterface $layout = NULL)
	{
		$layout = $layout ?: new Display\DefaultChannelLayout($this->channel_id, $this->entry_id);

		$this->_field_facades['title']->setItem('field_label', $this->Channel->title_field_label);

		return parent::getDisplay($layout);
	}

	protected function getModulesWithTabs()
	{

		$modules = array();
		$providers = ee('App')->getProviders();
		$installed_modules = $this->getFrontend()->get('Module')
			->all()
			->pluck('module_name');

		foreach (array_keys($providers) as $name)
		{
			try
			{
				$info = ee('App')->get($name);
				if (file_exists($info->getPath() . '/tab.' . $name . '.php')
					&& in_array(ucfirst($name), $installed_modules))
				{
					$modules[$name] = $info;
				}
			}
			catch (\Exception $e)
			{
				continue;
			}
		}

		return $modules;
	}

	protected function getTabFields()
	{
		$module_tabs = array();

		// Some Tabs might call ee()->api_channel_fields
		ee()->legacy_api->instantiate('channel_fields');

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			include_once($info->getPath() . '/tab.' . $name . '.php');
			$class_name = ucfirst($name) . '_tab';
			$OBJ = new $class_name();

			if (method_exists($OBJ, 'display') === TRUE)
			{
				// fetch the content
				$fields = $OBJ->display($this->channel_id, $this->entry_id);

				// There's basically no way this *won't* be set, but let's check it anyhow.
				// When we find it, we'll append the module's classname to it to prevent
				// collission with other modules with similarly named fields. This namespacing
				// gets stripped as needed when the module data is processed in get_module_methods()
				// This function is called for insertion and editing of entries.

				foreach ($fields as $key => $field)
				{
					if (isset($field['field_id']))
					{
						$fields[$key]['field_id'] = $name.'__'.$field['field_id']; // two underscores
					}
				}

				$module_tabs[$name] = $fields;
			}
		}

		return $module_tabs;
	}

	protected function initializeCustomFields()
	{
		parent::initializeCustomFields();

		$module_tabs = $this->getTabFields();

		if ($module_tabs)
		{
			foreach ($module_tabs as $tab_id => $fields)
			{
				foreach ($fields as $key => $field)
				{
					$this->addFacade($field['field_id'], $field);
				}
			}
		}
	}

	public function get__versioning_enabled()
	{
		return (isset($this->versioning_enabled)) ?: $this->Channel->enable_versioning;
	}

	/**
	 * Category setter for convenience to intercept the
	 * 'categories' post array.
	 */
	public function set__categories($categories)
	{
		if (empty($categories))
		{
			$categories = array();
		}

		if ( ! is_array($categories))
		{
			$categories = array($categories);
		}

		// annoyingly needed to trigger validation on the field
		$this->setRawProperty('categories', implode('|', $categories));

		// Currently cannot get multiple category groups through relationships
		$cat_groups = array();
		if ($this->Channel->cat_group)
		{
			$cat_groups = explode('|', $this->Channel->cat_group);
		}

		if (empty($categories))
		{
			foreach ($cat_groups as $cat_group)
			{
				$this->getCustomField('cat_group_id_'.$cat_group)->setData('');
			}
			$this->Categories = NULL;
			return;
		}

		$this->Categories = $this
			->getFrontend()
			->get('Category')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('cat_id', 'IN', $categories)
			->all();

		// Set the data on the fields in case we come back from a validation error
		foreach ($cat_groups as $cat_group)
		{
			$cats_in_group = $this->Categories->filter(function($category) use ($cat_group)
			{
				return $category->group_id == $cat_group;
			});

			$this->getCustomField('cat_group_id_'.$cat_group)->setData(implode('|', $this->Categories->pluck('cat_name')));
		}
	}

	/**
	 * Create a list of default fields to simplify rendering
	 */
	protected function getDefaultFields()
	{
		static $default_fields = array();

		if (empty($default_fields))
		{
			$default_fields = array(
				'title' => array(
					'field_id'				=> 'title',
					'field_label'			=> lang('title'),
					'field_required'		=> 'y',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> '',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 100
				),
				'url_title' => array(
					'field_id'				=> 'url_title',
					'field_label'			=> lang('url_title'),
					'field_required'		=> 'n',
					'field_fmt'				=> 'xhtml',
					'field_instructions'	=> lang('alphadash_desc'),
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 75
				),
				'entry_date' => array(
					'field_id'				=> 'entry_date',
					'field_label'			=> lang('entry_date'),
					'field_required'		=> 'y',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_fmt'				=> 'text',
					'field_instructions'	=> lang('entry_date_desc'),
					'field_show_fmt'		=> 'n',
					'always_show_date'		=> 'y',
					'default_offset'		=> 0,
					'selected'				=> 'y',
				),
				'expiration_date' => array(
					'field_id'				=> 'expiration_date',
					'field_label'			=> lang('expiration_date'),
					'field_required'		=> 'n',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_fmt'				=> 'text',
					'field_instructions'	=> lang('expiration_date_desc'),
					'field_show_fmt'		=> 'n',
					'default_offset'		=> 0,
					'selected'				=> 'y',
				),
				'comment_expiration_date' => array(
					'field_id'				=> 'comment_expiration_date',
					'field_label'			=> lang('comment_expiration_date'),
					'field_required'		=> 'n',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
					'field_fmt'				=> 'text',
					'field_instructions'	=> lang('comment_expiration_date_desc'),
					'field_show_fmt'		=> 'n',
					'default_offset'		=> 0,
					'selected'				=> 'y',
					'populateCallback'		=> array($this, 'populateCommentExpiration')
				),
				'channel_id' => array(
					'field_id'				=> 'channel_id',
					'field_label'			=> lang('channel'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('channel_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'select',
					'field_list_items'      => array(),
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateChannels')
				),
				'status' => array(
					'field_id'				=> 'status',
					'field_label'			=> lang('entry_status'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('entry_status_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'select',
					'field_list_items'      => array(),
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateStatus')
				),
				'author_id' => array(
					'field_id'				=> 'author_id',
					'field_label'			=> lang('author'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('author_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'select',
					'field_list_items'      => array(),
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateAuthors')
				),
				'sticky' => array(
					'field_id'				=> 'sticky',
					'field_label'			=> lang('sticky'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('sticky_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'radio',
					'field_list_items'      => array('y' => lang('yes'), 'n' => lang('no')),
					'field_maxl'			=> 100
				),
				'allow_comments' => array(
					'field_id'				=> 'allow_comments',
					'field_label'			=> lang('allow_comments'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('allow_comments_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'radio',
					'field_list_items'      => array('y' => lang('yes'), 'n' => lang('no')),
					'field_maxl'			=> 100
				)
			);

			if ($this->Channel && $this->Channel->enable_versioning)
			{
				$default_fields['versioning_enabled'] = array(
					'field_id'				=> 'versioning_enabled',
					'field_label'			=> lang('versioning_enabled'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> sprintf(lang('versioning_enabled_desc'), $this->Channel->max_revisions),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'radio',
					'field_list_items'      => array('y' => lang('yes'), 'n' => lang('no')),
					'field_maxl'			=> 100
				);
				$default_fields['revisions'] = array(
					'field_id'				=> 'revisions',
					'field_label'			=> lang('revisions'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> '',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 100,
					'field_wide'            => TRUE
				);
			}

			$cat_groups = ee('Model')->get('CategoryGroup')
				->filter('group_id', 'IN', explode('|', $this->Channel->cat_group))
				->all();

			foreach ($cat_groups as $cat_group)
			{
				$default_fields['cat_group_id_'.$cat_group->getId()] = array(
					'field_id'				=> 'categories',
					'cat_group_id'			=> $cat_group->getId(),
					'field_label'			=> ($cat_groups->count() > 1) ? $cat_group->group_name : lang('categories'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('categories_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'checkboxes',
					'string_override'		=> '',
					'field_list_items'      => '',
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateCategories')
				);
			};

			$module_tabs = $this->getTabFields();

			foreach ($module_tabs as $tab_id => $fields)
			{
				foreach ($fields as $key => $field)
					$default_fields[$tab_id . '__' . $key] = $field;
			}
		}

		return $default_fields;
	}

	public function populateChannels($field)
	{
		// Channels
		$allowed_channel_ids = (ee()->session->userdata['group_id'] == 1) ? NULL : array_keys(ee()->session->userdata['assigned_channels']);

		$channel_filter_options = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('field_group', $this->Channel->field_group)
			->all()
			->getDictionary('channel_id', 'channel_title');

		$field->setItem('field_list_items', $channel_filter_options);
	}

	public function populateAuthors($field)
	{
		// Authors
		$author_options = array();

		// Get all admins
		$authors = ee('Model')->get('Member')
			->filter('group_id', 1)
			->all();

		foreach ($authors as $author)
		{
			$author_options[$author->member_id] = $author->getMemberName();
		}

		// Get all members assigned to this channel
		foreach ($this->Channel->AssignedMemberGroups as $group)
		{
			if ($group->include_in_authorlist === TRUE)
			{
				foreach ($group->Members as $member)
				{
					$author_options[$member->member_id] = $member->getMemberName();
				}
			}
		}

		$field->setItem('field_list_items', $author_options);
	}

	public function populateCommentExpiration($field)
	{
		// Comment expiration date
		$field->setItem(
			'default_offset',
			$this->Channel->comment_expiration * 86400
		);
	}

	public function populateStatus($field)
	{
		$statuses = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', $this->Channel->status_group);

		$status_options = array();

		$all_statuses = $statuses->all();

		if ( ! count($all_statuses))
		{
			$status_options = array(
				'open' => lang('open'),
				'closed' => lang('closed')
			);
		}

		foreach ($all_statuses as $status)
		{
			$status_name = ($status->status == 'closed' OR $status->status == 'open') ?  lang($status->status) : $status->status;
			$status_options[$status->status] = $status_name;
		}

		$field->setItem('field_list_items', $status_options);
	}

	public function populateCategories($field)
	{
		// Rename the field so that we get the proper field facade later
		$field->setName('categories');

		$categories = ee('Model')->get('Category')
			->with(array('Children as C0' => array('Children as C1' => 'Children as C2')))
			->with('CategoryGroup')
			->filter('CategoryGroup.group_id', $field->getItem('cat_group_id'))
			->filter('Category.parent_id', 0)
			->order('Category.cat_order')
			->all();

		$category_list = $this->buildCategoryList($categories);
		$field->setItem('field_list_items', $category_list);

		$set_categories = $this->Categories->pluck('cat_name');
		$field->setData(implode('|', $set_categories));
	}

	/**
	 * Turn the categories collection into a nested array of ids => names
	 */
	protected function buildCategoryList($categories)
	{
		$list = array();

		foreach ($categories as $category)
		{
			$children = $category->Children;

			if (count($children))
			{
				$list[$category->cat_id] = array(
					'name' => $category->cat_name,
					'children' => $this->buildCategoryList($children)
				);

				continue;
			}

			$list[$category->cat_id] = $category->cat_name;
		}

		return $list;
	}
}
