<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;

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
		)
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

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getStructure()
	{
		return $this->getChannel();
	}

	/**
	 *
	 */
	public function getCustomFieldPrefix()
	{
		return 'field_id_';
	}

	protected function initializeCustomFields()
	{
		parent::initializeCustomFields();

		// Here comes the ugly! @TODO don't do this
		ee()->legacy_api->instantiate('channel_fields');
		$module_tabs = ee()->api_channel_fields->get_module_fields($this->channel_id, $this->entry_id);

		foreach ($module_tabs as $tab_id => $fields)
		{
			foreach ($fields as $key => $field)
			{
				$this->addFacade($field['field_id'], $field);
			}
		}
	}

	protected function fillCustomFields($data)
	{
		parent::fillCustomFields($data);

		foreach ($data as $name => $value)
		{
			if (strpos($name, 'field_ft_') === 0)
			{
				$name = str_replace('field_ft_', 'field_id_', $name);

				if ($this->hasCustomField($name))
				{
					$this->getCustomField($name)->setFormat($value);
				}
			}
		}
	}

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{
	}

	/* HACK ALERT! @TODO */

	protected function populateDefaultFields()
	{
		// Channels
		$allowed_channel_ids = (ee()->session->userdata['group_id'] == 1) ? NULL : array_keys(ee()->session->userdata['assigned_channels']);
		$channels = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('field_group', $this->getChannel()->field_group)
			->all();

		$channel_filter_options = array();
		foreach ($channels as $channel)
		{
			$channel_filter_options[$channel->channel_id] = $channel->channel_title;
		}

		$this->getCustomField('channel_id')->setItem('field_list_items', $channel_filter_options);

		// Statuses
		$statuses = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', $this->getChannel()->status_group);

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
		foreach ($this->getChannel()->getAssignedMemberGroups() as $group)
		{
			foreach ($group->getMembers() as $member)
			{
				$author_options[$member->member_id] = $member->getMemberName();
			}
		}

		$this->getCustomField('author_id')->setItem('field_list_items', $author_options);

		// Categories
		$category_group_ids = ee('Model')->get('CategoryGroup', explode('|', $this->getChannel()->cat_group))
			->filter('site_id', ee()->config->item('site_id'))
			->filter('exclude_group', '!=', 1)
			->all()
			->pluck('group_id');

		if (empty($category_group_ids))
		{
			$categories = array();
		}
		else
		{
			$categories = ee('Model')->get('Category')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('group_id', 'IN', $category_group_ids)
				->filter('parent_id', 0)
				->all();
		}

		$category_string_override = '<div class="scroll-wrap pr">';
		$set_categories = $this->getCategories()->pluck('cat_id');

		// If this doesn't make Pascal angry I need to try harder!
		// @TODO Make Pascal happy
		foreach ($categories as $category)
		{
			$class = 'choice block';
			$checked = '';
			if (in_array($category->cat_id, $set_categories))
			{
				$class .= ' chosen';
				$checked = ' checked="checked"';
			}

			$category_string_override .= '<label class="' . $class . '">';
			$category_string_override .= '<input type="checkbox" name="categories[]" vlaue="' . $category->cat_id .'"' . $checked . '>' . $category->cat_name;
			$category_string_override .= '</label>';

			// Recursion would be much better
			foreach ($category->getChildren() as $child_category)
			{
				$class = 'choice block child';
				$checked = '';
				if (in_array($child_category->cat_id, $set_categories))
				{
					$class .= ' chosen';
					$checked = ' checked="checked"';
				}

				$category_string_override .= '<label class="' . $class . '">';
				$category_string_override .= '<input type="checkbox" name="categories[]" vlaue="' . $child_category->cat_id .'"' . $checked . '>' . $child_category->cat_name;
				$category_string_override .= '</label>';
			}
		}

		$category_string_override .= '</div>';

		$this->getCustomField('categories')->setItem('string_override', $category_string_override);


		// Comment expiration date
		$this->getCustomField('comment_expiration_date')->setItem(
			'default_offset',
			$this->getChannel()->comment_expiration * 86400
		);
	}

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