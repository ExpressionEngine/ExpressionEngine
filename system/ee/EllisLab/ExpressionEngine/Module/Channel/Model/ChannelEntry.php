<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;

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
		)
	);

	protected static $_validation_rules = array(
		'channel_id'         => 'required',
		'ip_address'         => 'ip_address',
		'title'              => 'required',
		'url_title'          => 'required',
		'status'             => 'required',
		'entry_date'         => 'required',
		'versioning_enabled' => 'enum[y,n]',
		'allow_comments'     => 'enum[y,n]',
		'sticky'             => 'enum[y,n]',
	);

	protected static $_events = array(
		'afterDelete',
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
		// Day, Month, and Year Fields
		// @TODO un-break these windows: inject this dependency
		$this->setProperty('year', ee()->localize->format_date('%Y', $entry_date));
		$this->setProperty('month', ee()->localize->format_date('%m', $entry_date));
		$this->setProperty('day', ee()->localize->format_date('%d', $entry_date));
	}

	public function onAfterSave()
	{
		$this->Autosaves->delete();
	}

	public function onAfterDelete()
	{
		$this->Autosaves->delete();
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

		return parent::getDisplay($layout);
	}

	protected function initializeCustomFields()
	{
		parent::initializeCustomFields();

		// Here comes the ugly! @TODO don't do this
		ee()->legacy_api->instantiate('channel_fields');
		$module_tabs = ee()->api_channel_fields->get_module_fields($this->channel_id, $this->entry_id);

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

	/* HACK ALERT! @TODO */

	protected function populateDefaultFields()
	{
		// Channels
		$allowed_channel_ids = (ee()->session->userdata['group_id'] == 1) ? NULL : array_keys(ee()->session->userdata['assigned_channels']);

		$channel_filter_options = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('field_group', $this->Channel->field_group)
			->all()
			->getDictionary('channel_id', 'channel_title');

		$this->getCustomField('channel_id')->setItem('field_list_items', $channel_filter_options);

		// Statuses
		$statuses = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', $this->Channel->status_group);

		$status_options = array();

		foreach ($statuses->all() as $status)
		{
			$status_name = ($status->status == 'closed' OR $status->status == 'open') ?  lang($status->status) : $status->status;
			$status_options[$status->status] = $status_name;
		}

		$this->getCustomField('status')->setItem('field_list_items', $status_options);


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
			foreach ($group->Members as $member)
			{
				$author_options[$member->member_id] = $member->getMemberName();
			}
		}

		$this->getCustomField('author_id')->setItem('field_list_items', $author_options);

		// Categories
		$categories = ee('Model')->get('Category')
			->with('CategoryGroup')
			->filter('CategoryGroup.group_id', 'IN', explode('|', $this->Channel->cat_group))
			->filter('CategoryGroup.site_id', ee()->config->item('site_id'))
			->filter('Category.parent_id', 0)
			->all();

		$category_list = $this->buildCategoryList($categories);
		$set_categories = $this->Categories->pluck('cat_name');

		$this->getCustomField('categories')->setItem('field_list_items', $category_list);
		$this->getCustomField('categories')->setData(implode('|', $set_categories));

		// Comment expiration date
		$this->getCustomField('comment_expiration_date')->setItem(
			'default_offset',
			$this->Channel->comment_expiration * 86400
		);
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

	/**
	 * Category setter for convenience to intercept the
	 * 'categories' post array.
	 */
	public function set__categories($categories)
	{
		// annoyingly needed to trigger validation on the field
		$this->setRawProperty('categories', implode('|', $categories));

		if (empty($categories))
		{
			$this->getCustomField('categories')->setData('');
			$this->Categories = NULL;
			return;
		}

		$this->Categories = $this
			->getFrontend()
			->get('Category')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('cat_id', 'IN', $categories)
			->all();

		$this->getCustomField('categories')->setData(implode('|', $this->Categories->pluck('cat_name')));
	}

	/**
	 * Create a list of default fields to simplify rendering
	 */
	protected function getDefaultFields()
	{
		return array(
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
				'field_instructions'	=> lang('url_title_desc'),
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
				'default_offset'		=> 0, // @see populateDefaultFields
				'selected'				=> 'y',
			),
			'channel_id' => array(
				'field_id'				=> 'channel_id',
				'field_label'			=> lang('channel'),
				'field_required'		=> 'n',
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> lang('channel_desc'),
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'select',
				'field_list_items'      => array(), // @see populateDefaultFields
				'field_maxl'			=> 100
			),
			'status' => array(
				'field_id'				=> 'status',
				'field_label'			=> lang('entry_status'),
				'field_required'		=> 'n',
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> lang('entry_status_desc'),
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'select',
				'field_list_items'      => array(), // @see populateDefaultFields
				'field_maxl'			=> 100
			),
			'author_id' => array(
				'field_id'				=> 'author_id',
				'field_label'			=> lang('author'),
				'field_required'		=> 'n',
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> lang('author_desc'),
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'select',
				'field_list_items'      => array(), // @see populateDefaultFields
				'field_maxl'			=> 100
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
			),
			'categories' => array(
				'field_id'				=> 'categories',
				'field_label'			=> lang('categories'),
				'field_required'		=> 'n',
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> lang('categories_desc'),
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'checkboxes',
				'string_override'		=> '', // @see populateDefaultFields
				'field_list_items'      => '',
				'field_maxl'			=> 100
			),
		);
	}
}
