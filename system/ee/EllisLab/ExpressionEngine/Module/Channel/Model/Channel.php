<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Content\StructureModel;

class Channel extends StructureModel {

	protected static $_primary_key = 'channel_id';
	protected static $_table_name = 'channels';

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
		'show_button_cluster'        => 'boolString',
		'enable_versioning'          => 'boolString',
	);

	protected static $_relationships = array(
		'FieldGroup' => array(
			'type' => 'belongsTo',
			'model' => 'ChannelFieldGroup',
			'from_key' => 'field_group',
			'to_key' => 'group_id'
		),
		'StatusGroup' => array(
			'type' => 'belongsTo',
			'from_key' => 'status_group',
			'to_key' => 'group_id'
		),
		'CustomFields' => array(
			'type' => 'hasMany',
			'model' => 'ChannelField',
			'from_key' => 'field_group',
			'to_key' => 'group_id'
		),
		'Entries' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntry'
		),
		'ChannelFormSettings' => array(
			'type' => 'hasOne'
		),
		'LiveLookTemplate' => array(
			'type' => 'hasOne',
			'model' => 'Template',
			'from_key' => 'live_look_template',
			'to_key' => 'template_id'
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
		)
	);

	protected static $_validation_rules = array(
		'site_id'                    => 'required|isNatural',
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
		'show_button_cluster'        => 'enum[y,n]',
		'enable_versioning'          => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeSave',
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
	protected $total_comments;
	protected $last_entry_date;
	protected $last_comment_date;
	protected $cat_group;
	protected $status_group;
	protected $deft_status;
	protected $field_group;
	protected $search_excerpt;
	protected $deft_category;
	protected $deft_comments;
	protected $channel_require_membership;
	protected $channel_max_chars;
	protected $channel_html_formatting;
	protected $channel_allow_img_urls;
	protected $channel_auto_link_urls;
	protected $channel_notify;
	protected $channel_notify_emails;
	protected $comment_url;
	protected $comment_system_enabled;
	protected $comment_require_membership;
	protected $comment_moderate;
	protected $comment_max_chars;
	protected $comment_timelock;
	protected $comment_require_email;
	protected $comment_text_formatting;
	protected $comment_html_formatting;
	protected $comment_allow_img_urls;
	protected $comment_auto_link_urls;
	protected $comment_notify;
	protected $comment_notify_authors;
	protected $comment_notify_emails;
	protected $comment_expiration;
	protected $search_results_url;
	protected $show_button_cluster;
	protected $rss_url;
	protected $enable_versioning;
	protected $max_revisions;
	protected $default_entry_title;
	protected $title_field_label;
	protected $url_title_prefix;
	protected $live_look_template;


	public function getContentType()
	{
		return 'channel';
	}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content = NULL)
	{
		if ( ! isset($content))
		{
			$content = $this->getFrontend()->make('ChannelEntry');
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
							'total_comments', 'last_entry_date', 'last_comment_date');

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
				case 'status_group':
				case 'field_group':
					if ( ! isset($this->{$property}))
					{
						$this->setRawProperty($property, $channel->{$property});
					}
					elseif ($this->{$property} == '')
					{
						 $this->setRawProperty($property, NULL);
					}
					break;
				case 'deft_status':
					if ( ! isset($this->status_group) OR $this->status_group == $channel->status_group )
					{
						$this->setRawProperty($property, $channel->{$property});
					}
					break;
				case 'search_excerpt':
					if ( ! isset($this->field_group) OR $this->field_group == $channel->field_group )
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

	public function onBeforeDelete()
	{
		// Delete Pages URIs for this Channel
		$site_pages = ee()->config->item('site_pages');
		$site_id = ee()->config->item('site_id');

		$entries = $this->getFrontend()->get('ChannelEntry')
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

		// Update author stats
		foreach ($entries->pluck('author_id') as $author_id)
		{
			$total_entries = $this->getFrontend()->get('ChannelEntry')
				->filter('author_id', $author_id)
				->count();

			$total_comments = $this->getFrontend()->get('Comment')
				->filter('author_id', $author_id)
				->count();

			$author = $this->getFrontend()->get('Member', $author_id)->first();
			$author->total_entries = $entries;
			$author->total_comments = $total_comments;
			$author->save();
		}

		// Reset stats
		$now = ee()->localize->now;
		$entries = $this->getFrontend()->get('ChannelEntry')
			->fields('entry_date', 'channel_id')
			->filter('site_id', $site_id)
			->filter('entry_date', '<', $now)
			->filter('status', '!=', 'closed')
			->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', $now)
			->endFilterGroup()
			->order('entry_date', 'desc');

		$total_entries = $entries->count();
		$last_entry_date = ($entries->first()) ? $entries->first()->entry_date : 0;

		$comments = $this->getFrontend()->get('Comment')
			->filter('site_id', $site_id);

		$total_comments = $comments->count();

		$comments->filter('status', 'o')
			->fields('comment_date')
			->order('comment_date', 'desc')
			->first();

		$last_comment_date = ($comments) ? $comments->comment_date : 0;

		$stats = $this->getFrontend()->get('Stats')
			->filter('site_id', $site_id)
			->first();

		$stats->total_entries = $total_entries;
		$stats->last_entry_date = $last_entry_date;
		$stats->total_comments = $total_comments;
		$stats->last_comment_date = $last_comment_date;
		$stats->save();
	}
}
