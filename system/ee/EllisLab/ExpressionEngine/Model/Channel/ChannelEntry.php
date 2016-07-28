<?php

namespace EllisLab\ExpressionEngine\Model\Channel;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;
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

	protected static $_hook_id = 'channel_entry';

	protected static $_typed_columns = array(
		'versioning_enabled'      => 'boolString',
		'allow_comments'          => 'boolString',
		'sticky'                  => 'boolString',
		'entry_date'              => 'int',
		'expiration_date'         => 'int',
		'comment_expiration_date' => 'int',
		'author_id'               => 'int',
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
		'Comments' => array(
			'type' => 'hasMany',
			'model' => 'Comment'
		),
		'CommentSubscriptions' => array(
			'type' => 'hasMany',
			'model' => 'CommentSubscription'
		)
	);

	protected static $_validation_rules = array(
		'author_id'          => 'required|isNatural|validateAuthorId',
		'channel_id'         => 'required|validateMaxEntries',
		'ip_address'         => 'ip_address',
		'title'              => 'required|limitHtml[b,strong,i,em,span,sup,sub,code,ins,del]',
		'url_title'          => 'required|validateUrlTitle|validateUniqueUrlTitle[channel_id]',
		'status'             => 'required',
		'entry_date'         => 'required',
		'versioning_enabled' => 'enum[y,n]',
		'allow_comments'     => 'enum[y,n]',
		'sticky'             => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeDelete',
		'afterDelete',
		'afterInsert',
		'afterUpdate',
		'afterSave',
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

		// Some Tabs might call ee()->api_channel_fields
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			ee()->load->add_package_path($info->getPath(), FALSE);

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

			// restore our package and view paths
			ee()->load->remove_package_path($info->getPath());
		}

		return $result;
	}

	/**
	 * Validate entry count for this channel against channel's
	 * max_entries setting
	 */
	public function validateMaxEntries($key, $value, $params, $rule)
	{
		if ($this->Channel->max_entries === '0')
		{
			return TRUE;
		}

		$total_entries = $this->getFrontend()->get('ChannelEntry')
			->fields('entry_id', 'title')
			->filter('channel_id', $value)
			->count();

		if ($total_entries >= $this->Channel->max_entries)
		{
			return sprintf(lang('entry_limit_reached_desc'), $this->Channel->max_entries);
		}

		return TRUE;
	}

	/**
	 * Validate the author ID for permissions
	 */
	public function validateAuthorId($key, $value, $params, $rule)
	{
		if (ee()->session->userdata('member_id'))
		{
			if ($this->author_id != ee()->session->userdata('member_id') && ee()->session->userdata('can_edit_other_entries') != 'y')
			{
				return 'not_authorized';
			}

			if ( ! $this->isNew() && $this->getBackup('author_id') != $this->author_id &&
				(ee()->session->userdata('can_edit_other_entries') != 'y' OR ee()->session->userdata('can_assign_post_authors') != 'y'))
			{
				return 'not_authorized';
			}
		}
		else
		{
			if ( ! $this->isNew() && $this->getBackup('author_id') != $this->author_id &&
				($this->Author->MemberGroup->can_edit_other_entries != 'y' OR $this->Author->MemberGroup->can_assign_post_authors != 'y'))
			{
				return 'not_authorized';
			}
		}

		return TRUE;
	}

	/**
	 * Validate the URL title for any disallowed characters; it's basically an alhpa-dash rule plus periods
	 */
	public function validateUrlTitle($key, $value)
	{
		if ( ! (bool) preg_match("/^([-a-z0-9_.-])+$/i", $value))
		{
			return 'alpha_dash_period';
		}

		return TRUE;
	}

	/**
	 * Validate that the url title is unique for this site and return a custom
	 * error with the channel entry title if it is not.
	 */
	public function validateUniqueUrlTitle($key, $value, $params, $rule)
	{
		$channel_id = $this->getProperty($params[0]);

		$entry = $this->getFrontend()->get('ChannelEntry')
			->fields('entry_id', 'title')
			->filter('channel_id', $channel_id)
			->filter('url_title', $value)
			->first();

		if ($entry)
		{
			if (defined('REQ') && REQ == 'CP')
			{
				$edit_link = ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);
				return sprintf(lang('url_title_not_unique'), $edit_link, htmlentities($entry->title, ENT_QUOTES, 'UTF-8'));
			}

			return lang('url_title_not_unique_frontend');
		}

		return TRUE;
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		$this->Autosaves->delete();

		$this->updateEntryStats();

		// Some Tabs might call ee()->api_channel_fields
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			ee()->load->add_package_path($info->getPath(), FALSE);

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

			// restore our package and view paths
			ee()->load->remove_package_path($info->getPath());
		}

		if ($this->versioning_enabled)
		{
			$this->saveVersion();
		}

		// clear caches
		if (ee()->config->item('new_posts_clear_caches') == 'y')
		{
			ee()->functions->clear_caching('all');
		}
		else
		{
			ee()->functions->clear_caching('sql');
		}
	}

	public function onAfterInsert()
	{
		$this->Author->updateAuthorStats();

		if ($this->Channel->channel_notify == 'y' && $this->Channel->channel_notify_emails != '')
		{
			ee()->load->library('notifications');
			ee()->notifications->send_admin_notification(
				$this->Channel->channel_notify_emails,
				$this->Channel->getId(),
				$this->getId()
			);
		}
	}

	public function onAfterUpdate($changed)
	{
		$this->saveVersion();
	}

	public function onBeforeDelete()
	{
		parent::onBeforeDelete();

		// Some Tabs might call ee()->api_channel_fields
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			ee()->load->add_package_path($info->getPath(), FALSE);

			include_once($info->getPath() . '/tab.' . $name . '.php');
			$class_name = ucfirst($name) . '_tab';
			$OBJ = new $class_name();

			if (method_exists($OBJ, 'delete') === TRUE)
			{
				$OBJ->delete(array($this->entry_id));
			}

			// restore our package and view paths
			ee()->load->remove_package_path($info->getPath());
		}
	}

	public function onAfterDelete()
	{
		// store the author and dissociate. otherwise saving the author will
		// attempt to save this entry to ensure relationship integrity.
		// TODO make sure everything is already dissociated when we hit this
		$last_author = $this->Author;
		$this->Author = NULL;

		$last_author->updateAuthorStats();
		$this->updateEntryStats();
	}

	public function saveVersion()
	{
		if ( ! $this->getProperty('versioning_enabled'))
		{
			return;
		}

		if ($this->Versions->count() == $this->Channel->max_revisions)
		{
			$version = $this->Versions->sortBy('version_date')->first();
			if ($version)
			{
				$version->delete();
			}
		}

		$data = array(
			'entry_id'     => $this->entry_id,
			'channel_id'   => $this->channel_id,
			'author_id'    => $this->author_id ?: 1,
			'version_date' => ee()->localize->now,
			'version_data' => $this->getValues()
		);

		$version = $this->getFrontend()->make('ChannelEntryVersion', $data)->save();
	}

	private function updateEntryStats()
	{
		$site_id = ($this->site_id) ?: ee()->config->item('site_id');
		$now = ee()->localize->now;

		$entries = $this->getFrontend()->get('ChannelEntry')
			->fields('entry_date', 'channel_id')
			->filter('site_id', $site_id)
			->filter('entry_date', '<=', $now)
			->filter('status', '!=', 'closed')
			->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', $now)
			->endFilterGroup()
			->order('entry_date', 'desc');

		$total_entries = $entries->count();
		$last_entry_date = ($entries->first()) ? $entries->first()->entry_date : 0;

		$stats = $this->getFrontend()->get('Stats')
			->filter('site_id', $site_id)
			->first();

		$stats->total_entries = $total_entries;
		$stats->last_entry_date = $last_entry_date;
		$stats->save();

		$this->Channel->updateEntryStats();
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link back to the Structure object that defines
	 *						this Content's structure.
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

		$this->getCustomField('title')->setItem(
			'field_label',
			htmlentities($this->Channel->title_field_label, ENT_QUOTES, 'UTF-8')
		);

		$this->usesCustomFields();

		$fields = $this->getCustomFields();

		uasort($fields, function($a, $b) {
			if ($a->getItem('field_order') == $b->getItem('field_order'))
			{
				return ($a->getId() < $b->getId()) ? -1 : 1;
			}

			return ($a->getItem('field_order') < $b->getItem('field_order')) ? -1 : 1;
		});

		$fields = array_map(
			function($field) { return new FieldDisplay($field); },
			$fields
		);

		$layout = $layout ?: new DefaultLayout();

		return $layout->transform($fields);
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
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_fields');

		foreach ($this->getModulesWithTabs() as $name => $info)
		{
			ee()->load->add_package_path($info->getPath(), FALSE);

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

			// restore our package and view paths
			ee()->load->remove_package_path($info->getPath());
		}

		return $module_tabs;
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
				$this->setRawProperty('cat_group_id_'.$cat_group, '');
				$this->getCustomField('categories[cat_group_id_'.$cat_group.']')->setData('');
			}

			$this->Categories = NULL;

			return;
		}

		$set_cats = array();

		// Set the data on the fields in case we come back from a validation error
		foreach ($cat_groups as $cat_group)
		{
			if (array_key_exists('cat_group_id_'.$cat_group, $categories))
			{
				$group_cats = $categories['cat_group_id_'.$cat_group];

				$cats = implode('|', $group_cats);

				$this->setRawProperty('cat_group_id_'.$cat_group, $cats);
				$this->getCustomField('categories[cat_group_id_'.$cat_group.']')->setData($cats);

				$group_cat_objects = $this->getModelFacade()
					->get('Category')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('cat_id', 'IN', $group_cats)
					->all();

				foreach ($group_cat_objects as $cat)
				{
					$set_cats[] = $cat;
				}
			}
		}

		$this->Categories = $set_cats;
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
					'field_maxl'			=> 200
				),
				'url_title' => array(
					'field_id'				=> 'url_title',
					'field_label'			=> lang('url_title'),
					'field_required'		=> 'y',
					'field_fmt'				=> 'xhtml',
					'field_instructions'	=> lang('alphadash_desc'),
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> 200
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
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateAllowComments')
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

			if ($this->Channel)
			{
				$cat_groups = ee('Model')->get('CategoryGroup')
					->filter('group_id', 'IN', explode('|', $this->Channel->cat_group))
					->all();

				foreach ($cat_groups as $cat_group)
				{
					$metadata = $cat_group->getFieldMetadata();
					$metadata['categorized_object'] = $this;

					if ($cat_groups->count() == 1)
					{
						$metadata['field_label'] = lang('categories');
					}

					$default_fields['categories[cat_group_id_'.$cat_group->getId().']'] = $metadata;
				}

				if ( ! $this->Channel->comment_system_enabled)
				{
					unset($default_fields['comment_expiration_date'], $default_fields['allow_comments']);
				}
			}

			$module_tabs = $this->getTabFields();

			foreach ($module_tabs as $tab_id => $fields)
			{
				foreach ($fields as $key => $field)
				{
					$default_fields[$tab_id . '__' . $key] = $field;
				}
			}
		}

		return $default_fields;
	}

	/**
	 * Populate the Allow Comments checkbox
	 */
	public function populateAllowComments($field)
	{
		// Validation error?
		if (ee()->input->post('allow_comments'))
		{
			return $field->setItem('field_data', ee()->input->post('allow_comments'));
		}
		// New entry? Go off channel default
		else if ($this->isNew())
		{
			return $field->setItem('field_data', $this->Channel->deft_comments ? 'y' : 'n');
		}
		// We're editing
		else
		{
			return $field->setItem('field_data', $this->allow_comments ? 'y' : 'n');
		}
	}


	public function populateChannels($field)
	{
		$allowed_channel_ids = (ee()->session->userdata('member_id') == 0
			OR ee()->session->userdata('group_id') == 1
			OR ! is_array(ee()->session->userdata('assigned_channels')))
			? NULL : array_keys(ee()->session->userdata('assigned_channels'));

		$channel_filter_options = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('field_group', $this->Channel->field_group)
			->fields('channel_id', 'channel_title')
			->all()
			->getDictionary('channel_id', 'channel_title');

		$field->setItem('field_list_items', $channel_filter_options);
	}


 	/**
	 * Populate the Authors dropdown
	 *
	 * @param   object  $field  ChannelEntry object
	 * @return	void    Sets author field metaddata
	 *
	 * The following are included in the author list regardless of
	 * their channel posting permissions:
	 *	  The current user
	 *	  The current author (if editing)
	 *	  Anyone in a group set to 'include_in_authorlist'
	 *    Any individual member 'in_authorlist'
	 *
	 */
	public function populateAuthors($field)
	{
		$author_options = array();

		// Default author
		$author = $this->Author;

		if ( ! $author)
		{
			$field->setItem('field_list_items', $author_options);
			return;
		}

		$author_options[$author->getId()] = $author->getMemberName();

		if ($author->getId() != ee()->session->userdata('member_id'))
		{
			$author_options[ee()->session->userdata('member_id')] =
			ee()->session->userdata('screen_name') ?: ee()->session->userdata('username');
		}

		// First, get member groups who should be in the list
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('include_in_authorlist', 'y')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		// Then authors who are individually selected to appear in author list
		$authors = ee('Model')->get('Member')
			->fields('username', 'screen_name')
			->filter('in_authorlist', 'y');

		// Then grab any members that are part of the member groups we found
		if ($member_groups->count())
		{
			$authors->orFilter('group_id', 'IN', $member_groups->pluck('group_id'));
		}

		$authors->order('screen_name');
		$authors->order('username');

		foreach ($authors->all() as $author)
		{
			$author_options[$author->getId()] = $author->getMemberName();
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
			->with('NoAccess')
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

		$member_group_id = ee()->session->userdata('group_id');

		foreach ($all_statuses as $status)
		{
			if ($member_group_id != 1 && in_array($member_group_id, $status->NoAccess->pluck('group_id')))
			{
				continue;
			}

			$status_name = ($status->status == 'closed' OR $status->status == 'open') ?  lang($status->status) : $status->status;
			$status_options[$status->status] = $status_name;
		}

		$field->setItem('field_list_items', $status_options);
	}

	public function getAuthorName()
	{
		return ($this->author_id && $this->Author) ? $this->Author->getMemberName() : '';
	}
}

// EOF
