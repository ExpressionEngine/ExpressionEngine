<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Channel;

use EllisLab\ExpressionEngine\Model\Content\StructureModel;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Channel Model
 */
class Channel extends StructureModel {

	protected static $_primary_key = 'channel_id';
	protected static $_table_name = 'channels';

	protected static $_hook_id = 'channel';

	protected static $_typed_columns = array(
		'deft_comments'              => 'boolString',
		'channel_require_membership' => 'boolString',
		'channel_allow_img_urls'     => 'boolString',
		'channel_auto_link_urls'     => 'boolString',
		'channel_notify'             => 'boolString',
		'comment_system_enabled'     => 'boolString',
		'comment_require_membership' => 'boolString',
		'comment_moderate'           => 'boolString',
		'comment_require_email'      => 'boolString',
		'comment_allow_img_urls'     => 'boolString',
		'comment_auto_link_urls'     => 'boolString',
		'comment_notify'             => 'boolString',
		'comment_notify_authors'     => 'boolString',
		'enable_versioning'          => 'boolString',
		'extra_publish_controls'     => 'boolString',
		'search_excerpt'             => 'int'
	);

	protected static $_relationships = array(
		'FieldGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelFieldGroup',
			'pivot' => array(
				'table' => 'channels_channel_field_groups'
			),
			'weak' => TRUE,
		),
		'Statuses' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Status',
			'pivot' => array(
				'table' => 'channels_statuses'
			),
			'weak' => TRUE,
		),
		'CustomFields' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelField',
			'pivot' => array(
				'table' => 'channels_channel_fields'
			),
			'weak' => TRUE
		),
		'Entries' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntry'
		),
		'Comments' => array(
			'type' => 'hasMany',
			'model' => 'Comment'
		),
		'ChannelFormSettings' => array(
			'type' => 'hasOne'
		),
		'AssignedMemberGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'channel_member_groups'
			)
		),
		'ChannelLayouts' => array(
			'type' => 'hasMany',
			'model' => 'ChannelLayout'
		),
		'Site' => array(
			'type' => 'belongsTo'
		),
		'SearchExcerpt' => array(
			'type' => 'belongsTo',
			'model' => 'ChannelField',
			'from_key' => 'search_excerpt',
			'weak' => TRUE
		),
		'ChannelEntryAutosaves' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntryAutosave',
			'key' => 'channel_id',
			'to_key' => 'channel_id'
		),
	);

	protected static $_validation_rules = array(
		'site_id'                    => 'required|isNatural',
		'channel_title'              => 'required|unique[site_id]|xss',
		'channel_name'               => 'required|unique[site_id]|alphaDash',
		'channel_url'                => 'xss',
		'preview_url'                => 'xss|validatePreviewURL',
		'comment_url'                => 'xss',
		'channel_description'        => 'xss',
		'deft_comments'              => 'enum[y,n]',
		'channel_require_membership' => 'enum[y,n]',
		'channel_allow_img_urls'     => 'enum[y,n]',
		'channel_auto_link_urls'     => 'enum[y,n]',
		'channel_notify'             => 'enum[y,n]',
		'comment_system_enabled'     => 'enum[y,n]',
		'comment_require_membership' => 'enum[y,n]',
		'comment_moderate'           => 'enum[y,n]',
		'comment_require_email'      => 'enum[y,n]',
		'comment_allow_img_urls'     => 'enum[y,n]',
		'comment_auto_link_urls'     => 'enum[y,n]',
		'comment_notify'             => 'enum[y,n]',
		'comment_notify_authors'     => 'enum[y,n]',
		'enable_versioning'          => 'enum[y,n]',
		'max_entries'                => 'isNatural',
		'max_revisions'              => 'isNatural',
		'max_characters'             => 'isNatural',
		'comment_max_chars'          => 'isNatural',
		'comment_timelock'           => 'isNatural',
		'comment_expiration'         => 'isNatural',
		'search_results_url'         => 'xss',
		'rss_url'                    => 'xss',
		'default_entry_title'        => 'xss',
		'url_title_prefix'           => 'alphaDash|xss',
		'channel_notify_emails'      => 'validateEmails',
		'comment_notify_emails'      => 'validateEmails'
	);

	protected static $_events = array(
		'beforeSave',
		'afterInsert',
		'afterUpdate',
		'beforeDelete'
	);

	// Properties

	/**
	 * This is the primary key id.
	 *
	 * @type int
	 */
	protected $channel_id;
	protected $site_id;
	protected $channel_name;
	protected $channel_title;
	protected $channel_url;
	protected $channel_description;
	protected $channel_lang;
	protected $total_entries;
	protected $total_records;
	protected $total_comments;
	protected $last_entry_date;
	protected $last_comment_date;
	protected $cat_group;
	protected $deft_status = 'open';
	protected $search_excerpt;
	protected $deft_category;
	protected $deft_comments = TRUE;
	protected $channel_require_membership = TRUE;
	protected $channel_max_chars;
	protected $channel_html_formatting = 'all';
	protected $extra_publish_controls = FALSE;
	protected $channel_allow_img_urls = TRUE;
	protected $channel_auto_link_urls = FALSE;
	protected $channel_notify = FALSE;
	protected $channel_notify_emails;
	protected $comment_url;
	protected $comment_system_enabled = TRUE;
	protected $comment_require_membership = FALSE;
	protected $comment_moderate = FALSE;
	protected $comment_max_chars = 5000;
	protected $comment_timelock = 0;
	protected $comment_require_email = TRUE;
	protected $comment_text_formatting = 'xhtml';
	protected $comment_html_formatting = 'safe';
	protected $comment_allow_img_urls = FALSE;
	protected $comment_auto_link_urls = TRUE;
	protected $comment_notify = FALSE;
	protected $comment_notify_authors = FALSE;
	protected $comment_notify_emails;
	protected $comment_expiration;
	protected $search_results_url;
	protected $rss_url;
	protected $enable_versioning = FALSE;
	protected $max_revisions = 10;
	protected $default_entry_title;
	protected $title_field_label;
	protected $url_title_prefix;
	protected $max_entries;
	protected $preview_url;

	/**
	 * Custom validation callback to validate a comma-separated list of email
	 * addresses
	 */
	public function validateEmails($key, $value, $params, $rule)
	{
		if (empty($value))
		{
			return TRUE;
		}

		$emails = explode(',', $value);

		foreach ($emails as $email)
		{
			if ($email != filter_var($email, FILTER_SANITIZE_EMAIL) OR ! filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				$rule->stop();
				return 'valid_email';
			}
		}

		return TRUE;
	}

	/**
	 * Custom validation callback to validate preview URL- needs to be relative URL
	 */
	public function validatePreviewURL($key, $value, $params, $rule)
	{
		if (empty($value))
		{
			return TRUE;
		}

		$parsed_url = parse_url($value);

		if (strpos($value, '{base_url}') !== FALSE OR isset($parsed_url['scheme']))
		{
			return lang('channel_preview_url_invalid');
		}

		return TRUE;
	}


	/**
	 * Parses URL properties for any config variables
	 *
	 * @param str $name The name of the property to fetch
	 * @return mixed The value of the property
	 */
	public function __get($name)
	{
		// Fake the CategoryGroups relationship since it's stored weird
		if ($name == 'CategoryGroups')
		{
			return $this->getCategoryGroups();
		}

		$value = parent::__get($name);

		if (in_array($name, array('channel_url', 'comment_url', 'search_results_url', 'rss_url')))
		{
			$overrides = array();

			if ($this->getProperty('site_id') != ee()->config->item('site_id'))
			{
				$overrides = ee()->config->get_cached_site_prefs($this->getProperty('site_id'));
			}

			$value = parse_config_variables((string) $value, $overrides);
		}

		return $value;
	}

	public function getContentType()
	{
		return 'channel';
	}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content = NULL)
	{
		if ( ! isset($content))
		{
			$content = $this->getModelFacade()->make('ChannelEntry');
			$content->setChannel($this);
		}
		elseif ($content->getChannel()->channel_id != $this->channel_id)
		{
			// todo
			exit('Given channel entry does not belong to this channel.');
		}

		return $content->getForm();
	}

	/**
	 * Duplicate another Channel's preferences
	 */
	public function duplicatePreferences(Channel $channel)
	{
		$exceptions = array('channel_id', 'site_id', 'channel_name', 'channel_title', 'total_entries',
							'total_comments', 'last_entry_date', 'last_comment_date', 'total_records');

		foreach (get_object_vars($this) as $property => $value)
		{
			// don't duplicate fields that are unique to each channel
			if ( in_array($property, $exceptions) || strpos($property, '_') === 0)
			{
				continue;
			}

			switch ($property)
			{
				// category, field, and status fields should only be duped
				// if both channels are assigned to the same group of each
				case 'cat_group':
					// allow to implicitly set category group to "None"
					if (empty($this->{$property}))
					{
						$this->setRawProperty($property, $channel->{$property});
					}
					break;
				case 'deft_category':
					if ( ! isset($this->cat_group) OR count(array_diff(explode('|', $this->cat_group), explode('|', $channel->cat_group ))) == 0)
					{
						$this->setRawProperty($property, $channel->{$property});
					}
					break;
				default:
					$this->setRawProperty($property, $channel->{$property});
					break;
			}
		}

        foreach (['FieldGroups', 'CustomFields', 'Statuses', 'ChannelFormSettings'] as $rel)
        {
            if ($channel->$rel)
            {
                $this->$rel = clone $channel->$rel;
            }
        }
	}

	public function onBeforeSave()
	{
		foreach (array('channel_url', 'channel_lang') as $column)
		{
			$value = $this->getProperty($column);

			if (empty($value))
			{
				$this->setProperty($column, '');
			}
		}
	}

	public function onAfterInsert()
	{
		$statuses = $this->Statuses->pluck('status');

		// Ensure default statuses are assigned
		if ( ! in_array('open', $statuses) OR ! in_array('closed', $statuses))
		{
			$this->Statuses[] = $this->getModelFacade()->get('Status')
				->filter('status', 'IN', ['open', 'closed'])
				->all();

			$this->save();
		}
	}

	public function onAfterUpdate($previous)
	{
		// Only synchronize if the category groups changed and we have a layout
		if (isset($previous['cat_group']) && count($this->ChannelLayouts))
		{
			$this->syncCatGroupsWithLayouts();
		}

		if (isset($previous['enable_versioning']) && count($this->ChannelLayouts))
		{
			if ($this->getProperty('enable_versioning'))
			{
				$this->addRevisionTab();
			}
			else
			{
				$this->removeRevisionTab();
			}
		}

		foreach ($this->ChannelLayouts as $layout)
		{
			$layout->synchronize($this->getAllCustomFields());
		}
	}

	/**
	 * We offer a discrete field per category group. Layouts save the the field
	 * order and tab location of all fields. When a category group is added to,
	 * or removed from a Channel, we need to update all of its Layouts, either
	 * adding a field or removing one.
	 */
	private function syncCatGroupsWithLayouts()
	{
		$cat_groups = array();

		foreach (explode('|', $this->cat_group) as $group_id)
		{
			$cat_groups['categories[cat_group_id_'.$group_id.']'] = TRUE;
		}

		foreach ($this->ChannelLayouts as $channel_layout)
		{
			$field_layout = $channel_layout->field_layout;
			$new_cat_tab = 0;

			foreach ($field_layout as $i => $section)
			{
				if ($section['id'] == 'categories' && $section['visible'])
				{
					$new_cat_tab = $i;
				}

				foreach ($section['fields'] as $j => $field_info)
				{
					// All category fields begin with "categories"
					if (strpos($field_info['field'], 'categories') === 0)
					{
						$field_name = $field_info['field'];

						// Is it already accounted for?
						if (in_array($field_name, array_keys($cat_groups)))
						{
							unset($cat_groups[$field_name]);
						}

						// If not, it was removed and needs to be deleted
						else
						{
							unset($field_layout[$i]['fields'][$j]);

							// Re-index to ensure flat, zero-indexed array
							$field_layout[$i]['fields'] = array_values($field_layout[$i]['fields']);
						}
					}
				}
			}

			// Add the new category groups
			foreach (array_keys($cat_groups) as $cat_group)
			{
				$field_info = array(
					'field' => $cat_group,
					'visible' => TRUE,
					'collapsed' => FALSE
				);
				$field_layout[$new_cat_tab]['fields'][] = $field_info;
			}

			$channel_layout->field_layout = $field_layout;
			$channel_layout->save();
		}
	}

	private function addRevisionTab()
	{
		foreach ($this->ChannelLayouts as $channel_layout)
		{
			$field_layout = $channel_layout->field_layout;
			$field_layout[] = array(
				'id' => 'revisions',
				'name' => 'revisions',
				'visible' => TRUE,
				'fields' => array(
					array(
						'field' => 'versioning_enabled',
						'visible' => TRUE,
						'collapsed' => FALSE
					),
					array(
						'field' => 'revisions',
						'visible' => TRUE,
						'collapsed' => FALSE
					)
				)
			);
			$channel_layout->field_layout = $field_layout;
			$channel_layout->save();
		}
	}

	private function removeRevisionTab()
	{
		foreach ($this->ChannelLayouts as $channel_layout)
		{
			$field_layout = $channel_layout->field_layout;

			foreach ($field_layout as $i => $section)
			{
				if ($section['name'] == 'revisions')
				{
					array_splice($field_layout, $i, 1);
					break;
				}
			}

			$channel_layout->field_layout = $field_layout;
			$channel_layout->save();
		}
	}

	public function onBeforeDelete()
	{
		// Delete Pages URIs for this Channel
		$site_pages = ee()->config->item('site_pages');
		$site_id = ee()->config->item('site_id');

		$entries = $this->getModelFacade()->get('ChannelEntry')
			->fields('entry_id', 'author_id')
			->filter('channel_id', $this->channel_id)
			->all();

		if ($site_pages !== FALSE && $entries)
		{
			if (count($site_pages[$site_id]) > 0)
			{
				foreach ($entries as $entry)
				{
					unset($site_pages[$site_id]['uris'][$entry->entry_id]);
					unset($site_pages[$site_id]['templates'][$entry->entry_id]);
				}

				ee()->config->set_item('site_pages', $site_pages);

				$this->Site->site_pages = $site_pages;
				$this->Site->save();
			}
		}
	}

	public function getCategoryGroups()
	{
		$groups = explode('|', $this->cat_group);
		return $this->getModelFacade()->get('CategoryGroup', $groups)->all();
	}

	/**
	 * Updates total_records, total_entries, and last_entry_date
	 */
	public function updateEntryStats()
	{
		$entries = $this->getModelFacade()->get('ChannelEntry')
			->fields('entry_id', 'entry_date')
			->filter('channel_id', $this->getId());

		// Total records is unfiltered
		$this->setProperty('total_records', $entries->count());

		// Total entries should only account for open, non-expired entries
		$entries = $entries->filter('entry_date', '<=', ee()->localize->now)
			->filter('status', '!=', 'closed')
			->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', ee()->localize->now)
			->endFilterGroup()
			->order('entry_date', 'desc');

		$last_entry = $entries->first();

		$this->setProperty('total_entries', $entries->count());
		$last_entry_date = ($last_entry) ? $last_entry->entry_date : 0;
		$this->setProperty('last_entry_date', $last_entry_date);
		$this->save();
	}

	/**
	 * Returns a collection of all the channel fields available for this channel
	 *
	 * @return Collection A collection of fields
	 */
	public function getAllCustomFields()
	{
		$fields = $this->CustomFields->indexBy('field_name');

		$cache_key = "ChannelFieldGroups/{$this->getId()}/";
		if (($field_groups = ee()->session->cache(__CLASS__, $cache_key, FALSE)) == FALSE)
		{
			$field_groups = $this->FieldGroups;
		}

		foreach ($field_groups as $field_group)
		{
			foreach($field_group->ChannelFields as $field)
			{
				$fields[$field->field_name] = $field;
			}
		}

		ee()->session->set_cache(__CLASS__, $cache_key, $field_groups);

		return new Collection($fields);
	}

	public function maxEntriesLimitReached()
	{
		return ($this->max_entries != 0 && $this->total_records >= $this->max_entries);
	}
}

// EOF
