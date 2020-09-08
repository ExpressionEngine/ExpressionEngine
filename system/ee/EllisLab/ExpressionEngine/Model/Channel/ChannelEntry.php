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
	protected static $_table_name = 'channel_titles';
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
		'Status' => [
			'type' => 'belongsTo',
			'weak' => TRUE
		],
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
		),
		'Site' => array(
			'type' => 'belongsTo'
		),
	);

	protected static $_field_data = array(
		'field_model'     => 'ChannelField',
		'group_column'    => 'channel_id',
		'structure_model' => 'Channel',
	);

	protected static $_validation_rules = array(
		'author_id'          => 'required|isNatural|validateAuthorId',
		'channel_id'         => 'required|validateMaxEntries',
		'ip_address'         => 'ip_address',
		'title'              => 'required|maxLength[200]|limitHtml[b,cite,code,del,em,i,ins,markspan,strong,sub,sup]',
		'url_title'          => 'required|maxLength[URL_TITLE_MAX_LENGTH]|alphaDashPeriodEmoji|validateUniqueUrlTitle[channel_id]',
		'status'             => 'required',
		'entry_date'         => 'required',
		'versioning_enabled' => 'enum[y,n]',
		'allow_comments'     => 'enum[y,n]',
		'sticky'             => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeDelete',
		'beforeInsert',
		'beforeSave',
		'beforeUpdate',
		'afterDelete',
		'afterInsert',
		'afterSave',
		'afterUpdate'
	);

	protected $_default_fields;

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
	protected $status_id;
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
		$entry_timestamp = $this->stringToTimestamp($entry_date);
		$this->setRawProperty('entry_date', $entry_timestamp);

        // Day, Month, and Year Fields
        // @TODO un-break these windows: inject this dependency
		$this->setProperty('year', ee()->localize->format_date('%Y', $entry_timestamp));
		$this->setProperty('month', ee()->localize->format_date('%m', $entry_timestamp));
		$this->setProperty('day', ee()->localize->format_date('%d', $entry_timestamp));
    }

	public function set__expiration_date($expiration_date)
	{
        $this->setRawProperty('expiration_date', $this->stringToTimestamp($expiration_date));
	}

	public function set__comment_expiration_date($comment_expiration_date)
	{
        $this->setRawProperty('comment_expiration_date', $this->stringToTimestamp($comment_expiration_date));
	}

	private function stringToTimestamp($date)
	{
        if ( ! is_numeric($date))
        {
            // @TODO: DRY this out; this was copied from ft.date.php (still need to put this logic
			// somewhere both this Model and ft.date.php can use)
            // First we try with the configured date format
            $date = ee()->localize->string_to_timestamp($date, TRUE, ee()->localize->get_date_format());

            // If the date format didn't work, try something more fuzzy
            if ($date === FALSE)
            {
                $date = ee()->localize->string_to_timestamp($date);
            }
        }

		return $date;
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
		if ($this->Channel->max_entries == 0 OR ! $this->isNew())
		{
			return TRUE;
		}

		$total_entries = $this->getModelFacade()->get('ChannelEntry')
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
		$channel_permission = FALSE;

		if (ee()->session->userdata('member_id'))
		{
			// A super admin always has channel permission to post as themself
			$channel_permission = (ee()->session->userdata('group_id') == 1 && ($this->author_id == ee()->session->userdata('member_id'))) ? TRUE : FALSE;

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

		// If it's new or an edit AND they changed the author_id,
		// the author_id should either have permission to post to the channel or be in include_in_authorlist
		if ($this->getBackup('author_id') != $this->author_id && ! $channel_permission)
		{
			$assigned_channels = $this->Author->MemberGroup->AssignedChannels->pluck('channel_id');
			$channel_permission = (in_array($this->channel_id, $assigned_channels)) ? TRUE : FALSE;

			$authors = ee('Member')->getAuthors(NULL, FALSE);

			if ( ! $channel_permission && ! isset($authors[$this->author_id]))
			{
				return 'not_authorized';
			}
		}
		else
		{
			// Catch the rare database corruption

			if ( ! $this->author_id)
			{
				return 'not_authorized';
			}

			$member = ee('Model')->get('Member', $this->author_id)->first();

			if (is_null($member))
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
		// Strip emojis since they are safe and make a mess of the regex
		$regex = "/(?:\x{1F469}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469}|\x{1F469}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468}|\x{1F468}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468}|\x{1F469}\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467}|\x{1F468}\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467}|\x{1F468}\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F469}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469}|\x{1F469}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F468}\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F468}\x{200D}\x{1F469}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F469}\x{200D}\x{1F467}|\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F468}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F468}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F469}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F469}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467}|\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F645}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F645}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F646}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F646}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F468}\x{200D}\x{2695}\x{FE0F}\x{1F3FB}|\x{1F646}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F646}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F646}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F645}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F646}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F646}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F646}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F646}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F647}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F646}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F645}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F645}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F487}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F487}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F487}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F487}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F487}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F487}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F487}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F487}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F487}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F645}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F645}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F645}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F647}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F645}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F645}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F647}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F647}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F468}\x{200D}\x{2708}\x{FE0F}\x{1F3FE}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F647}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F647}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F647}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F647}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F647}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F647}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F487}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F486}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F486}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F468}\x{200D}\x{2695}\x{FE0F}\x{1F3FE}|\x{1F468}\x{200D}\x{2695}\x{FE0F}\x{1F3FD}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F468}\x{200D}\x{2695}\x{FE0F}\x{1F3FC}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F469}\x{200D}\x{2708}\x{FE0F}\x{1F3FF}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F471}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F471}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F471}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F471}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F471}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F471}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2708}\x{FE0F}\x{1F3FE}|\x{1F471}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2696}\x{FE0F}\x{1F3FF}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2708}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2708}\x{FE0F}\x{1F3FC}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F468}\x{200D}\x{2708}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2695}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2695}\x{FE0F}\x{1F3FC}|\x{1F469}\x{200D}\x{2695}\x{FE0F}\x{1F3FD}|\x{1F469}\x{200D}\x{2695}\x{FE0F}\x{1F3FE}|\x{1F469}\x{200D}\x{2695}\x{FE0F}\x{1F3FF}|\x{1F468}\x{200D}\x{2696}\x{FE0F}\x{1F3FE}|\x{1F469}\x{200D}\x{2708}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2696}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2696}\x{FE0F}\x{1F3FC}|\x{1F468}\x{200D}\x{2696}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2696}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2696}\x{FE0F}\x{1F3FC}|\x{1F469}\x{200D}\x{2696}\x{FE0F}\x{1F3FD}|\x{1F469}\x{200D}\x{2696}\x{FE0F}\x{1F3FE}|\x{1F469}\x{200D}\x{2696}\x{FE0F}\x{1F3FF}|\x{1F468}\x{200D}\x{2695}\x{FE0F}\x{1F3FF}|\x{1F469}\x{200D}\x{2708}\x{FE0F}\x{1F3FB}|\x{1F469}\x{200D}\x{2708}\x{FE0F}\x{1F3FC}|\x{1F471}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F471}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F486}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F482}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F481}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F481}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F481}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F481}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F482}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F482}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F482}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F482}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F482}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F441}\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F}|\x{1F482}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F481}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F482}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F482}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F482}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F486}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F486}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F486}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F486}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F486}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F486}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F486}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F481}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F481}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F471}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F473}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F473}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F473}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F473}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F473}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F473}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F473}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F473}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F473}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F473}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F477}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F481}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F477}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F477}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F477}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F477}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F477}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F477}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F477}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F477}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F477}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F481}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F481}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F468}\x{200D}\x{2708}\x{FE0F}\x{1F3FF}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F938}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F938}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F938}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F938}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F938}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F937}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F938}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F938}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F926}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F937}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F926}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F926}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F937}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F926}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F926}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F937}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F926}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F926}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F937}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F926}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F926}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F937}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F926}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F937}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F937}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F937}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F938}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F938}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F938}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F939}\x{200D}\x{2642}\x{FE0F}\x{1F3FB}|\x{1F939}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F939}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F939}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F939}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F939}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}\x{1F3FE}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F939}\x{200D}\x{2640}\x{FE0F}\x{1F3FF}|\x{1F937}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F939}\x{200D}\x{2640}\x{FE0F}\x{1F3FD}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}\x{1F3FE}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}\x{1F3FD}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}\x{1F3FC}|\x{1F939}\x{200D}\x{2640}\x{FE0F}\x{1F3FB}|\x{1F939}\x{200D}\x{2640}\x{FE0F}\x{1F3FC}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}\x{1F3FF}|\x{1F575}\x{FE0F}\x{200D}\x{2640}\x{FE0F}|\x{1F3CC}\x{FE0F}\x{200D}\x{2642}\x{FE0F}|\x{1F3CB}\x{FE0F}\x{200D}\x{2640}\x{FE0F}|\x{1F3CB}\x{FE0F}\x{200D}\x{2642}\x{FE0F}|\x{1F3CC}\x{FE0F}\x{200D}\x{2640}\x{FE0F}|\x{1F575}\x{FE0F}\x{200D}\x{2642}\x{FE0F}|\x{26F9}\x{FE0F}\x{200D}\x{2640}\x{FE0F}|\x{26F9}\x{FE0F}\x{200D}\x{2642}\x{FE0F}|\x{1F468}\x{200D}\x{1F4BC}\x{1F3FE}|\x{1F468}\x{200D}\x{1F33E}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3EB}\x{1F3FF}|\x{1F468}\x{200D}\x{1F4BC}\x{1F3FD}|\x{1F468}\x{200D}\x{1F33E}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3EB}\x{1F3FE}|\x{1F468}\x{200D}\x{1F527}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3EB}\x{1F3FD}|\x{1F468}\x{200D}\x{1F4BC}\x{1F3FF}|\x{1F468}\x{200D}\x{1F527}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3A8}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3A8}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3EB}\x{1F3FC}|\x{1F468}\x{200D}\x{1F527}\x{1F3FD}|\x{1F468}\x{200D}\x{1F3EB}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3A8}\x{1F3FF}|\x{1F468}\x{200D}\x{1F527}\x{1F3FE}|\x{1F468}\x{200D}\x{1F3A8}\x{1F3FE}|\x{1F468}\x{200D}\x{1F3A8}\x{1F3FD}|\x{1F468}\x{200D}\x{1F527}\x{1F3FF}|\x{1F468}\x{200D}\x{1F3A4}\x{1F3FF}|\x{1F468}\x{200D}\x{1F3A4}\x{1F3FD}|\x{1F468}\x{200D}\x{1F33E}\x{1F3FD}|\x{1F468}\x{200D}\x{1F393}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3ED}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3A4}\x{1F3FB}|\x{1F468}\x{200D}\x{1F3ED}\x{1F3FD}|\x{1F468}\x{200D}\x{1F393}\x{1F3FF}|\x{1F468}\x{200D}\x{1F3ED}\x{1F3FE}|\x{1F468}\x{200D}\x{1F393}\x{1F3FE}|\x{1F468}\x{200D}\x{1F3ED}\x{1F3FF}|\x{1F468}\x{200D}\x{1F393}\x{1F3FD}|\x{1F468}\x{200D}\x{1F4BB}\x{1F3FB}|\x{1F468}\x{200D}\x{1F393}\x{1F3FC}|\x{1F468}\x{200D}\x{1F4BB}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3ED}\x{1F3FB}|\x{1F468}\x{200D}\x{1F33E}\x{1F3FE}|\x{1F468}\x{200D}\x{1F4BB}\x{1F3FD}|\x{1F468}\x{200D}\x{1F373}\x{1F3FF}|\x{1F468}\x{200D}\x{1F3A4}\x{1F3FE}|\x{1F468}\x{200D}\x{1F373}\x{1F3FE}|\x{1F468}\x{200D}\x{1F4BB}\x{1F3FE}|\x{1F468}\x{200D}\x{1F373}\x{1F3FD}|\x{1F468}\x{200D}\x{1F4BB}\x{1F3FF}|\x{1F468}\x{200D}\x{1F4BC}\x{1F3FB}|\x{1F468}\x{200D}\x{1F373}\x{1F3FC}|\x{1F468}\x{200D}\x{1F4BC}\x{1F3FC}|\x{1F468}\x{200D}\x{1F3A4}\x{1F3FC}|\x{1F468}\x{200D}\x{1F33E}\x{1F3FF}|\x{1F468}\x{200D}\x{1F373}\x{1F3FB}|\x{1F469}\x{200D}\x{1F33E}\x{1F3FE}|\x{1F468}\x{200D}\x{1F52C}\x{1F3FB}|\x{1F469}\x{200D}\x{1F4BB}\x{1F3FC}|\x{1F469}\x{200D}\x{1F4BC}\x{1F3FE}|\x{1F469}\x{200D}\x{1F4BC}\x{1F3FD}|\x{1F469}\x{200D}\x{1F4BC}\x{1F3FC}|\x{1F469}\x{200D}\x{1F4BC}\x{1F3FB}|\x{1F469}\x{200D}\x{1F4BB}\x{1F3FF}|\x{1F469}\x{200D}\x{1F4BB}\x{1F3FE}|\x{1F469}\x{200D}\x{1F4BB}\x{1F3FD}|\x{1F469}\x{200D}\x{1F4BB}\x{1F3FB}|\x{1F469}\x{200D}\x{1F527}\x{1F3FB}|\x{1F469}\x{200D}\x{1F3ED}\x{1F3FF}|\x{1F469}\x{200D}\x{1F3ED}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3ED}\x{1F3FD}|\x{1F469}\x{200D}\x{1F3ED}\x{1F3FC}|\x{1F469}\x{200D}\x{1F3ED}\x{1F3FB}|\x{1F469}\x{200D}\x{1F3EB}\x{1F3FF}|\x{1F468}\x{200D}\x{1F52C}\x{1F3FC}|\x{1F469}\x{200D}\x{1F4BC}\x{1F3FF}|\x{1F469}\x{200D}\x{1F527}\x{1F3FC}|\x{1F469}\x{200D}\x{1F3EB}\x{1F3FC}|\x{1F469}\x{200D}\x{1F680}\x{1F3FD}|\x{1F469}\x{200D}\x{1F692}\x{1F3FF}|\x{1F469}\x{200D}\x{1F692}\x{1F3FE}|\x{1F469}\x{200D}\x{1F692}\x{1F3FD}|\x{1F469}\x{200D}\x{1F692}\x{1F3FC}|\x{1F469}\x{200D}\x{1F692}\x{1F3FB}|\x{1F469}\x{200D}\x{1F680}\x{1F3FF}|\x{1F469}\x{200D}\x{1F680}\x{1F3FE}|\x{1F469}\x{200D}\x{1F680}\x{1F3FC}|\x{1F469}\x{200D}\x{1F527}\x{1F3FD}|\x{1F469}\x{200D}\x{1F680}\x{1F3FB}|\x{1F469}\x{200D}\x{1F52C}\x{1F3FF}|\x{1F469}\x{200D}\x{1F52C}\x{1F3FE}|\x{1F469}\x{200D}\x{1F52C}\x{1F3FD}|\x{1F469}\x{200D}\x{1F52C}\x{1F3FC}|\x{1F469}\x{200D}\x{1F52C}\x{1F3FB}|\x{1F469}\x{200D}\x{1F527}\x{1F3FF}|\x{1F469}\x{200D}\x{1F527}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3EB}\x{1F3FD}|\x{1F469}\x{200D}\x{1F3EB}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3EB}\x{1F3FB}|\x{1F468}\x{200D}\x{1F692}\x{1F3FC}|\x{1F469}\x{200D}\x{1F3A8}\x{1F3FF}|\x{1F469}\x{200D}\x{1F33E}\x{1F3FD}|\x{1F469}\x{200D}\x{1F33E}\x{1F3FC}|\x{1F469}\x{200D}\x{1F33E}\x{1F3FB}|\x{1F468}\x{200D}\x{1F692}\x{1F3FF}|\x{1F468}\x{200D}\x{1F692}\x{1F3FE}|\x{1F468}\x{200D}\x{1F692}\x{1F3FD}|\x{1F468}\x{200D}\x{1F692}\x{1F3FB}|\x{1F469}\x{200D}\x{1F373}\x{1F3FC}|\x{1F468}\x{200D}\x{1F680}\x{1F3FF}|\x{1F468}\x{200D}\x{1F680}\x{1F3FE}|\x{1F468}\x{200D}\x{1F680}\x{1F3FD}|\x{1F468}\x{200D}\x{1F680}\x{1F3FC}|\x{1F468}\x{200D}\x{1F680}\x{1F3FB}|\x{1F468}\x{200D}\x{1F52C}\x{1F3FF}|\x{1F468}\x{200D}\x{1F52C}\x{1F3FE}|\x{1F468}\x{200D}\x{1F52C}\x{1F3FD}|\x{1F469}\x{200D}\x{1F373}\x{1F3FB}|\x{1F469}\x{200D}\x{1F33E}\x{1F3FF}|\x{1F469}\x{200D}\x{1F373}\x{1F3FD}|\x{1F469}\x{200D}\x{1F393}\x{1F3FF}|\x{1F469}\x{200D}\x{1F3A8}\x{1F3FB}|\x{1F469}\x{200D}\x{1F3A4}\x{1F3FF}|\x{1F469}\x{200D}\x{1F3A4}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3A4}\x{1F3FD}|\x{1F469}\x{200D}\x{1F3A4}\x{1F3FC}|\x{1F469}\x{200D}\x{1F3A8}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3A4}\x{1F3FB}|\x{1F469}\x{200D}\x{1F393}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3A8}\x{1F3FD}|\x{1F469}\x{200D}\x{1F393}\x{1F3FD}|\x{1F469}\x{200D}\x{1F393}\x{1F3FC}|\x{1F469}\x{200D}\x{1F393}\x{1F3FB}|\x{1F469}\x{200D}\x{1F373}\x{1F3FF}|\x{1F469}\x{200D}\x{1F373}\x{1F3FE}|\x{1F469}\x{200D}\x{1F3A8}\x{1F3FC}|\x{1F3F3}\x{FE0F}\x{200D}\x{1F308}|\x{1F3C4}\x{200D}\x{2640}\x{FE0F}|\x{1F468}\x{200D}\x{2695}\x{FE0F}|\x{1F3C3}\x{200D}\x{2640}\x{FE0F}|\x{1F3C4}\x{200D}\x{2642}\x{FE0F}|\x{1F468}\x{200D}\x{2708}\x{FE0F}|\x{1F3CA}\x{200D}\x{2640}\x{FE0F}|\x{1F3CA}\x{200D}\x{2642}\x{FE0F}|\x{1F468}\x{200D}\x{2696}\x{FE0F}|\x{1F3C3}\x{200D}\x{2642}\x{FE0F}|\x{1F93D}\x{200D}\x{2642}\x{FE0F}|\x{1F6B4}\x{200D}\x{2642}\x{FE0F}|\x{1F93C}\x{200D}\x{2642}\x{FE0F}|\x{1F487}\x{200D}\x{2642}\x{FE0F}|\x{1F937}\x{200D}\x{2642}\x{FE0F}|\x{1F6B5}\x{200D}\x{2642}\x{FE0F}|\x{1F93E}\x{200D}\x{2640}\x{FE0F}|\x{1F6B4}\x{200D}\x{2640}\x{FE0F}|\x{1F645}\x{200D}\x{2640}\x{FE0F}|\x{1F6B6}\x{200D}\x{2640}\x{FE0F}|\x{1F645}\x{200D}\x{2642}\x{FE0F}|\x{1F938}\x{200D}\x{2640}\x{FE0F}|\x{1F646}\x{200D}\x{2640}\x{FE0F}|\x{1F6A3}\x{200D}\x{2642}\x{FE0F}|\x{1F926}\x{200D}\x{2640}\x{FE0F}|\x{1F93D}\x{200D}\x{2640}\x{FE0F}|\x{1F93C}\x{200D}\x{2640}\x{FE0F}|\x{1F6B5}\x{200D}\x{2640}\x{FE0F}|\x{1F939}\x{200D}\x{2640}\x{FE0F}|\x{1F646}\x{200D}\x{2642}\x{FE0F}|\x{1F647}\x{200D}\x{2640}\x{FE0F}|\x{1F647}\x{200D}\x{2642}\x{FE0F}|\x{1F64B}\x{200D}\x{2640}\x{FE0F}|\x{1F939}\x{200D}\x{2642}\x{FE0F}|\x{1F64B}\x{200D}\x{2642}\x{FE0F}|\x{1F938}\x{200D}\x{2642}\x{FE0F}|\x{1F64D}\x{200D}\x{2640}\x{FE0F}|\x{1F6B6}\x{200D}\x{2642}\x{FE0F}|\x{1F64D}\x{200D}\x{2642}\x{FE0F}|\x{1F6A3}\x{200D}\x{2640}\x{FE0F}|\x{1F64E}\x{200D}\x{2640}\x{FE0F}|\x{1F487}\x{200D}\x{2640}\x{FE0F}|\x{1F64E}\x{200D}\x{2642}\x{FE0F}|\x{1F486}\x{200D}\x{2642}\x{FE0F}|\x{1F477}\x{200D}\x{2642}\x{FE0F}|\x{1F471}\x{200D}\x{2640}\x{FE0F}|\x{1F46F}\x{200D}\x{2642}\x{FE0F}|\x{1F471}\x{200D}\x{2642}\x{FE0F}|\x{1F473}\x{200D}\x{2640}\x{FE0F}|\x{1F926}\x{200D}\x{2642}\x{FE0F}|\x{1F46F}\x{200D}\x{2640}\x{FE0F}|\x{1F46E}\x{200D}\x{2642}\x{FE0F}|\x{1F46E}\x{200D}\x{2640}\x{FE0F}|\x{1F473}\x{200D}\x{2642}\x{FE0F}|\x{1F477}\x{200D}\x{2640}\x{FE0F}|\x{1F93E}\x{200D}\x{2642}\x{FE0F}|\x{1F469}\x{200D}\x{2708}\x{FE0F}|\x{1F481}\x{200D}\x{2642}\x{FE0F}|\x{1F486}\x{200D}\x{2640}\x{FE0F}|\x{1F482}\x{200D}\x{2642}\x{FE0F}|\x{1F469}\x{200D}\x{2696}\x{FE0F}|\x{1F937}\x{200D}\x{2640}\x{FE0F}|\x{1F481}\x{200D}\x{2640}\x{FE0F}|\x{1F482}\x{200D}\x{2640}\x{FE0F}|\x{1F469}\x{200D}\x{2695}\x{FE0F}|\x{1F468}\x{200D}\x{1F680}|\x{1F469}\x{200D}\x{1F52C}|\x{1F468}\x{200D}\x{1F3A8}|\x{1F468}\x{200D}\x{1F373}|\x{1F469}\x{200D}\x{1F692}|\x{1F468}\x{200D}\x{1F466}|\x{1F468}\x{200D}\x{1F4BB}|\x{1F468}\x{200D}\x{1F393}|\x{1F469}\x{200D}\x{1F3EB}|\x{1F469}\x{200D}\x{1F373}|\x{1F468}\x{200D}\x{1F3ED}|\x{1F468}\x{200D}\x{1F4BC}|\x{1F469}\x{200D}\x{1F680}|\x{1F468}\x{200D}\x{1F3A4}|\x{1F468}\x{200D}\x{1F467}|\x{1F468}\x{200D}\x{1F33E}|\x{1F469}\x{200D}\x{1F527}|\x{1F468}\x{200D}\x{1F692}|\x{1F469}\x{200D}\x{1F393}|\x{1F468}\x{200D}\x{1F52C}|\x{1F469}\x{200D}\x{1F3A4}|\x{1F468}\x{200D}\x{1F3EB}|\x{1F469}\x{200D}\x{1F4BB}|\x{1F469}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F4BC}|\x{1F469}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F3A8}|\x{1F468}\x{200D}\x{1F527}|\x{1F469}\x{200D}\x{1F3ED}|\x{1F469}\x{200D}\x{1F33E}|\x{0039}\x{FE0F}\x{20E3}|\x{0030}\x{FE0F}\x{20E3}|\x{0037}\x{FE0F}\x{20E3}|\x{0036}\x{FE0F}\x{20E3}|\x{0023}\x{FE0F}\x{20E3}|\x{002A}\x{FE0F}\x{20E3}|\x{0038}\x{FE0F}\x{20E3}|\x{0034}\x{FE0F}\x{20E3}|\x{0031}\x{FE0F}\x{20E3}|\x{0033}\x{FE0F}\x{20E3}|\x{0035}\x{FE0F}\x{20E3}|\x{0032}\x{FE0F}\x{20E3}|\x{1F1F0}\x{1F1FF}|\x{1F1EE}\x{1F1F9}|\x{1F1F0}\x{1F1F3}|\x{1F1F1}\x{1F1F0}|\x{1F1F0}\x{1F1F7}|\x{1F1F0}\x{1F1FC}|\x{1F1EF}\x{1F1EA}|\x{1F930}\x{1F3FE}|\x{1F1F1}\x{1F1EE}|\x{1F1EF}\x{1F1F2}|\x{1F1F1}\x{1F1F8}|\x{1F1EF}\x{1F1F4}|\x{1F1F1}\x{1F1F7}|\x{1F933}\x{1F3FB}|\x{1F1EF}\x{1F1F5}|\x{1F1F1}\x{1F1E6}|\x{1F1F0}\x{1F1F2}|\x{1F1F0}\x{1F1EA}|\x{1F1F0}\x{1F1EC}|\x{1F933}\x{1F3FC}|\x{1F1F0}\x{1F1FE}|\x{1F1F1}\x{1F1E8}|\x{1F930}\x{1F3FF}|\x{1F1F0}\x{1F1ED}|\x{1F1F0}\x{1F1EE}|\x{1F1F0}\x{1F1F5}|\x{1F1F1}\x{1F1E7}|\x{1F918}\x{1F3FF}|\x{1F1EE}\x{1F1F8}|\x{1F1EB}\x{1F1F7}|\x{1F1EC}\x{1F1F1}|\x{1F1EC}\x{1F1EE}|\x{1F1EC}\x{1F1ED}|\x{1F1EC}\x{1F1EC}|\x{1F6C0}\x{1F3FC}|\x{1F1EC}\x{1F1EB}|\x{1F1EC}\x{1F1EA}|\x{1F1EC}\x{1F1E9}|\x{1F1EC}\x{1F1E7}|\x{1F1EC}\x{1F1E6}|\x{1F6C0}\x{1F3FB}|\x{1F1EB}\x{1F1F4}|\x{1F1EC}\x{1F1F3}|\x{1F1EB}\x{1F1F2}|\x{1F1EB}\x{1F1F0}|\x{1F1EB}\x{1F1EF}|\x{1F933}\x{1F3FF}|\x{1F934}\x{1F3FB}|\x{1F934}\x{1F3FC}|\x{1F934}\x{1F3FD}|\x{1F934}\x{1F3FE}|\x{1F934}\x{1F3FF}|\x{1F935}\x{1F3FB}|\x{1F935}\x{1F3FC}|\x{1F1EC}\x{1F1F2}|\x{1F1EC}\x{1F1F5}|\x{1F1EE}\x{1F1F7}|\x{1F1ED}\x{1F1F3}|\x{1F1EE}\x{1F1F6}|\x{1F1EE}\x{1F1F4}|\x{1F1EE}\x{1F1F3}|\x{1F1EE}\x{1F1F2}|\x{1F1EE}\x{1F1F1}|\x{1F1EE}\x{1F1EA}|\x{1F1EE}\x{1F1E9}|\x{1F1EE}\x{1F1E8}|\x{1F1ED}\x{1F1FA}|\x{1F1ED}\x{1F1F9}|\x{1F1ED}\x{1F1F7}|\x{1F1ED}\x{1F1F2}|\x{1F1EC}\x{1F1F6}|\x{1F933}\x{1F3FD}|\x{1F1ED}\x{1F1F0}|\x{1F1EC}\x{1F1FE}|\x{1F1EC}\x{1F1FA}|\x{1F1EC}\x{1F1F9}|\x{1F933}\x{1F3FE}|\x{1F6C0}\x{1F3FF}|\x{1F6C0}\x{1F3FE}|\x{1F6C0}\x{1F3FD}|\x{1F1EC}\x{1F1F8}|\x{1F1EC}\x{1F1F7}|\x{1F1EC}\x{1F1FC}|\x{1F1F2}\x{1F1F5}|\x{1F1F1}\x{1F1F9}|\x{1F1F5}\x{1F1EC}|\x{1F1F5}\x{1F1FE}|\x{1F1F5}\x{1F1FC}|\x{1F1F5}\x{1F1F9}|\x{1F1F5}\x{1F1F8}|\x{1F1F5}\x{1F1F7}|\x{1F1F5}\x{1F1F3}|\x{1F1F5}\x{1F1F2}|\x{1F1F5}\x{1F1F1}|\x{1F1F5}\x{1F1F0}|\x{1F1F5}\x{1F1ED}|\x{1F1F5}\x{1F1EB}|\x{1F1F7}\x{1F1EA}|\x{1F918}\x{1F3FC}|\x{1F918}\x{1F3FB}|\x{1F91C}\x{1F3FB}|\x{1F91C}\x{1F3FC}|\x{1F91C}\x{1F3FD}|\x{1F91C}\x{1F3FE}|\x{1F91C}\x{1F3FF}|\x{1F91E}\x{1F3FB}|\x{1F91E}\x{1F3FC}|\x{1F91E}\x{1F3FD}|\x{1F1F6}\x{1F1E6}|\x{1F1F7}\x{1F1F4}|\x{1F91E}\x{1F3FF}|\x{1F1F8}\x{1F1F1}|\x{1F1F8}\x{1F1FF}|\x{1F1F8}\x{1F1FE}|\x{1F1F8}\x{1F1FD}|\x{1F1F8}\x{1F1FB}|\x{1F1F8}\x{1F1F9}|\x{1F1F8}\x{1F1F8}|\x{1F1F8}\x{1F1F7}|\x{1F1F8}\x{1F1F4}|\x{1F1F8}\x{1F1F3}|\x{1F1F8}\x{1F1F2}|\x{1F1F8}\x{1F1F0}|\x{1F1F7}\x{1F1F8}|\x{1F1F8}\x{1F1EF}|\x{1F1F8}\x{1F1EE}|\x{1F1F8}\x{1F1ED}|\x{1F1F8}\x{1F1EC}|\x{1F1F8}\x{1F1EA}|\x{1F1F8}\x{1F1E9}|\x{1F1F8}\x{1F1E8}|\x{1F1F8}\x{1F1E7}|\x{1F1F8}\x{1F1E6}|\x{1F1F7}\x{1F1FC}|\x{1F1F7}\x{1F1FA}|\x{1F91E}\x{1F3FE}|\x{1F926}\x{1F3FB}|\x{1F1F1}\x{1F1FA}|\x{1F1F2}\x{1F1ED}|\x{1F1F2}\x{1F1F6}|\x{1F1F2}\x{1F1F4}|\x{1F1F2}\x{1F1F3}|\x{1F1F2}\x{1F1F2}|\x{1F1F2}\x{1F1F1}|\x{1F930}\x{1F3FB}|\x{1F930}\x{1F3FC}|\x{1F930}\x{1F3FD}|\x{1F6CC}\x{1F3FF}|\x{1F1F2}\x{1F1F0}|\x{1F1F2}\x{1F1EC}|\x{1F1F2}\x{1F1F8}|\x{1F1F2}\x{1F1EB}|\x{1F1F2}\x{1F1EA}|\x{1F6CC}\x{1F3FE}|\x{1F6CC}\x{1F3FD}|\x{1F6CC}\x{1F3FC}|\x{1F6CC}\x{1F3FB}|\x{1F1F2}\x{1F1E9}|\x{1F1F2}\x{1F1E8}|\x{1F1F2}\x{1F1E6}|\x{1F1F1}\x{1F1FE}|\x{1F1F1}\x{1F1FB}|\x{1F1F2}\x{1F1F7}|\x{1F1F2}\x{1F1F9}|\x{1F1F5}\x{1F1EA}|\x{1F926}\x{1F3FD}|\x{1F1F5}\x{1F1E6}|\x{1F1F4}\x{1F1F2}|\x{1F1F3}\x{1F1FF}|\x{1F1F3}\x{1F1FA}|\x{1F926}\x{1F3FC}|\x{1F1F3}\x{1F1F7}|\x{1F1F3}\x{1F1F5}|\x{1F1F3}\x{1F1F4}|\x{1F1F3}\x{1F1F1}|\x{1F1F3}\x{1F1EE}|\x{1F1F3}\x{1F1EC}|\x{1F1F2}\x{1F1FA}|\x{1F1F3}\x{1F1EB}|\x{1F1F3}\x{1F1EA}|\x{1F1F3}\x{1F1E8}|\x{1F1F3}\x{1F1E6}|\x{1F926}\x{1F3FE}|\x{1F1F2}\x{1F1FF}|\x{1F1F2}\x{1F1FE}|\x{1F1F2}\x{1F1FD}|\x{1F1F2}\x{1F1FC}|\x{1F1F2}\x{1F1FB}|\x{1F926}\x{1F3FF}|\x{1F6B6}\x{1F3FF}|\x{1F1E9}\x{1F1EF}|\x{1F6B6}\x{1F3FE}|\x{1F647}\x{1F3FD}|\x{1F64C}\x{1F3FB}|\x{1F64B}\x{1F3FF}|\x{1F1E8}\x{1F1E8}|\x{1F64B}\x{1F3FE}|\x{1F64B}\x{1F3FD}|\x{1F1E8}\x{1F1E6}|\x{1F64B}\x{1F3FC}|\x{1F64B}\x{1F3FB}|\x{1F647}\x{1F3FF}|\x{1F647}\x{1F3FE}|\x{1F647}\x{1F3FC}|\x{1F64C}\x{1F3FD}|\x{1F647}\x{1F3FB}|\x{1F646}\x{1F3FF}|\x{1F646}\x{1F3FE}|\x{1F646}\x{1F3FD}|\x{1F646}\x{1F3FC}|\x{1F646}\x{1F3FB}|\x{1F645}\x{1F3FF}|\x{1F645}\x{1F3FE}|\x{1F645}\x{1F3FD}|\x{1F645}\x{1F3FC}|\x{1F64C}\x{1F3FC}|\x{1F64C}\x{1F3FE}|\x{1F91B}\x{1F3FB}|\x{1F64F}\x{1F3FD}|\x{1F93D}\x{1F3FD}|\x{1F93D}\x{1F3FE}|\x{1F93D}\x{1F3FF}|\x{1F93E}\x{1F3FB}|\x{1F93E}\x{1F3FC}|\x{1F93E}\x{1F3FD}|\x{1F93E}\x{1F3FE}|\x{1F93E}\x{1F3FF}|\x{1F64F}\x{1F3FF}|\x{1F64F}\x{1F3FE}|\x{1F64F}\x{1F3FC}|\x{1F64C}\x{1F3FF}|\x{1F64F}\x{1F3FB}|\x{1F64E}\x{1F3FF}|\x{1F64E}\x{1F3FE}|\x{1F64E}\x{1F3FD}|\x{1F64E}\x{1F3FC}|\x{1F64E}\x{1F3FB}|\x{1F64D}\x{1F3FF}|\x{1F64D}\x{1F3FE}|\x{1F64D}\x{1F3FD}|\x{1F64D}\x{1F3FC}|\x{1F64D}\x{1F3FB}|\x{1F645}\x{1F3FB}|\x{1F91B}\x{1F3FE}|\x{1F93D}\x{1F3FB}|\x{1F1E6}\x{1F1EC}|\x{1F1E6}\x{1F1FA}|\x{1F1E6}\x{1F1F9}|\x{1F1E6}\x{1F1F8}|\x{1F91A}\x{1F3FB}|\x{1F1E6}\x{1F1F7}|\x{1F1E6}\x{1F1F6}|\x{1F1E6}\x{1F1F4}|\x{1F1E6}\x{1F1F2}|\x{1F1E6}\x{1F1F1}|\x{1F1E6}\x{1F1EE}|\x{1F1E6}\x{1F1EB}|\x{1F1E6}\x{1F1FD}|\x{1F1E6}\x{1F1EA}|\x{1F1E6}\x{1F1E9}|\x{1F91B}\x{1F3FF}|\x{1F919}\x{1F3FF}|\x{1F919}\x{1F3FE}|\x{1F919}\x{1F3FD}|\x{1F919}\x{1F3FC}|\x{1F919}\x{1F3FB}|\x{1F918}\x{1F3FD}|\x{1F1E6}\x{1F1E8}|\x{1F918}\x{1F3FE}|\x{1F1E6}\x{1F1FC}|\x{1F91A}\x{1F3FC}|\x{1F91A}\x{1F3FF}|\x{1F1E7}\x{1F1F2}|\x{1F1E7}\x{1F1FF}|\x{1F1E7}\x{1F1FE}|\x{1F1E7}\x{1F1FC}|\x{1F1E7}\x{1F1FB}|\x{1F1E7}\x{1F1F9}|\x{1F1E7}\x{1F1F8}|\x{1F1E7}\x{1F1F7}|\x{1F1E7}\x{1F1F6}|\x{1F1E7}\x{1F1F4}|\x{1F1E7}\x{1F1F3}|\x{1F1E7}\x{1F1F1}|\x{1F1E6}\x{1F1FF}|\x{1F91A}\x{1F3FE}|\x{1F1E7}\x{1F1EF}|\x{1F1E7}\x{1F1EE}|\x{1F1E7}\x{1F1ED}|\x{1F1E7}\x{1F1EC}|\x{1F1E7}\x{1F1EB}|\x{1F91A}\x{1F3FD}|\x{1F1E7}\x{1F1EA}|\x{1F1E7}\x{1F1E9}|\x{1F1E7}\x{1F1E7}|\x{1F1E7}\x{1F1E6}|\x{1F93D}\x{1F3FC}|\x{1F939}\x{1F3FF}|\x{1F6B6}\x{1F3FD}|\x{1F57A}\x{1F3FD}|\x{1F6B5}\x{1F3FB}|\x{1F575}\x{1F3FE}|\x{1F575}\x{1F3FF}|\x{1F1E9}\x{1F1F2}|\x{1F1E9}\x{1F1F0}|\x{1F1F9}\x{1F1E8}|\x{1F1E9}\x{1F1EC}|\x{1F1E9}\x{1F1EA}|\x{1F57A}\x{1F3FB}|\x{1F57A}\x{1F3FC}|\x{1F57A}\x{1F3FE}|\x{1F575}\x{1F3FC}|\x{1F57A}\x{1F3FF}|\x{1F1E8}\x{1F1FF}|\x{1F1E8}\x{1F1FE}|\x{1F1E8}\x{1F1FD}|\x{1F935}\x{1F3FE}|\x{1F1E8}\x{1F1FC}|\x{1F1E8}\x{1F1FB}|\x{1F1E8}\x{1F1FA}|\x{1F590}\x{1F3FB}|\x{1F590}\x{1F3FC}|\x{1F575}\x{1F3FD}|\x{1F575}\x{1F3FB}|\x{1F590}\x{1F3FD}|\x{1F1EA}\x{1F1ED}|\x{1F6B6}\x{1F3FC}|\x{1F6B6}\x{1F3FB}|\x{1F935}\x{1F3FD}|\x{1F6B5}\x{1F3FF}|\x{1F1EB}\x{1F1EE}|\x{1F1EA}\x{1F1FA}|\x{1F1EA}\x{1F1F9}|\x{1F1EA}\x{1F1F8}|\x{1F1EA}\x{1F1F7}|\x{1F6B5}\x{1F3FE}|\x{1F1EA}\x{1F1EC}|\x{1F1E9}\x{1F1F4}|\x{1F1EA}\x{1F1EA}|\x{1F1EA}\x{1F1E8}|\x{1F1EA}\x{1F1E6}|\x{1F6B5}\x{1F3FD}|\x{1F1E9}\x{1F1FF}|\x{1F574}\x{1F3FB}|\x{1F574}\x{1F3FC}|\x{1F574}\x{1F3FD}|\x{1F574}\x{1F3FE}|\x{1F6B5}\x{1F3FC}|\x{1F574}\x{1F3FF}|\x{1F6B4}\x{1F3FF}|\x{1F590}\x{1F3FE}|\x{1F939}\x{1F3FE}|\x{1F938}\x{1F3FB}|\x{1F936}\x{1F3FB}|\x{1F936}\x{1F3FC}|\x{1F936}\x{1F3FD}|\x{1F936}\x{1F3FE}|\x{1F936}\x{1F3FF}|\x{1F937}\x{1F3FB}|\x{1F937}\x{1F3FC}|\x{1F937}\x{1F3FD}|\x{1F937}\x{1F3FE}|\x{1F937}\x{1F3FF}|\x{1F938}\x{1F3FC}|\x{1F1E8}\x{1F1E9}|\x{1F938}\x{1F3FD}|\x{1F6A3}\x{1F3FF}|\x{1F6A3}\x{1F3FE}|\x{1F6A3}\x{1F3FD}|\x{1F6A3}\x{1F3FC}|\x{1F6A3}\x{1F3FB}|\x{1F938}\x{1F3FE}|\x{1F938}\x{1F3FF}|\x{1F939}\x{1F3FB}|\x{1F939}\x{1F3FC}|\x{1F939}\x{1F3FD}|\x{1F935}\x{1F3FF}|\x{1F1E8}\x{1F1EB}|\x{1F590}\x{1F3FF}|\x{1F596}\x{1F3FC}|\x{1F1E8}\x{1F1F7}|\x{1F595}\x{1F3FB}|\x{1F6B4}\x{1F3FE}|\x{1F595}\x{1F3FC}|\x{1F595}\x{1F3FD}|\x{1F595}\x{1F3FE}|\x{1F595}\x{1F3FF}|\x{1F1E8}\x{1F1F5}|\x{1F6B4}\x{1F3FD}|\x{1F596}\x{1F3FB}|\x{1F596}\x{1F3FD}|\x{1F1E8}\x{1F1EC}|\x{1F596}\x{1F3FE}|\x{1F596}\x{1F3FF}|\x{1F6B4}\x{1F3FC}|\x{1F1E8}\x{1F1F4}|\x{1F1E8}\x{1F1F3}|\x{1F1E8}\x{1F1F2}|\x{1F1E8}\x{1F1F1}|\x{1F1E8}\x{1F1F0}|\x{1F6B4}\x{1F3FB}|\x{1F1E8}\x{1F1EE}|\x{1F1E8}\x{1F1ED}|\x{1F1F9}\x{1F1E6}|\x{1F44A}\x{1F3FF}|\x{1F1F9}\x{1F1E9}|\x{1F44B}\x{1F3FC}|\x{1F44D}\x{1F3FF}|\x{1F44D}\x{1F3FE}|\x{1F44D}\x{1F3FD}|\x{1F44D}\x{1F3FC}|\x{1F44D}\x{1F3FB}|\x{1F44C}\x{1F3FF}|\x{1F44C}\x{1F3FE}|\x{1F44C}\x{1F3FD}|\x{1F44C}\x{1F3FC}|\x{1F44C}\x{1F3FB}|\x{1F44B}\x{1F3FF}|\x{1F44B}\x{1F3FE}|\x{1F44B}\x{1F3FD}|\x{1F44B}\x{1F3FB}|\x{1F44E}\x{1F3FC}|\x{1F91B}\x{1F3FD}|\x{1F44A}\x{1F3FE}|\x{1F44A}\x{1F3FD}|\x{1F44A}\x{1F3FC}|\x{1F44A}\x{1F3FB}|\x{1F449}\x{1F3FF}|\x{1F449}\x{1F3FE}|\x{1F449}\x{1F3FD}|\x{1F449}\x{1F3FC}|\x{1F449}\x{1F3FB}|\x{1F448}\x{1F3FF}|\x{1F448}\x{1F3FE}|\x{1F448}\x{1F3FD}|\x{1F44E}\x{1F3FB}|\x{1F44E}\x{1F3FD}|\x{1F448}\x{1F3FB}|\x{1F466}\x{1F3FE}|\x{1F469}\x{1F3FC}|\x{1F469}\x{1F3FB}|\x{1F468}\x{1F3FF}|\x{1F468}\x{1F3FE}|\x{1F468}\x{1F3FD}|\x{1F468}\x{1F3FC}|\x{1F468}\x{1F3FB}|\x{1F467}\x{1F3FF}|\x{1F467}\x{1F3FE}|\x{1F467}\x{1F3FD}|\x{1F467}\x{1F3FC}|\x{1F467}\x{1F3FB}|\x{1F466}\x{1F3FF}|\x{1F466}\x{1F3FD}|\x{1F44E}\x{1F3FE}|\x{1F466}\x{1F3FC}|\x{1F466}\x{1F3FB}|\x{1F450}\x{1F3FF}|\x{1F450}\x{1F3FE}|\x{1F450}\x{1F3FD}|\x{1F450}\x{1F3FC}|\x{1F450}\x{1F3FB}|\x{1F44F}\x{1F3FF}|\x{1F44F}\x{1F3FE}|\x{1F44F}\x{1F3FD}|\x{1F44F}\x{1F3FC}|\x{1F44F}\x{1F3FB}|\x{1F44E}\x{1F3FF}|\x{1F448}\x{1F3FC}|\x{1F447}\x{1F3FF}|\x{1F469}\x{1F3FE}|\x{1F3C3}\x{1F3FE}|\x{1F3CA}\x{1F3FC}|\x{1F3CA}\x{1F3FB}|\x{1F3C7}\x{1F3FF}|\x{1F3C7}\x{1F3FE}|\x{1F3C7}\x{1F3FD}|\x{1F3C7}\x{1F3FC}|\x{1F3C7}\x{1F3FB}|\x{1F3C4}\x{1F3FF}|\x{1F3C4}\x{1F3FE}|\x{1F3C4}\x{1F3FD}|\x{1F3C4}\x{1F3FC}|\x{1F3C4}\x{1F3FB}|\x{1F3C3}\x{1F3FF}|\x{1F3C3}\x{1F3FD}|\x{1F3CA}\x{1F3FE}|\x{1F3C3}\x{1F3FC}|\x{1F3C3}\x{1F3FB}|\x{1F3C2}\x{1F3FF}|\x{1F3C2}\x{1F3FE}|\x{1F3C2}\x{1F3FD}|\x{1F1F9}\x{1F1EB}|\x{1F3C2}\x{1F3FC}|\x{1F3C2}\x{1F3FB}|\x{1F385}\x{1F3FF}|\x{1F385}\x{1F3FE}|\x{1F385}\x{1F3FD}|\x{1F385}\x{1F3FC}|\x{1F385}\x{1F3FB}|\x{1F3CA}\x{1F3FD}|\x{1F3CA}\x{1F3FF}|\x{1F447}\x{1F3FE}|\x{1F442}\x{1F3FF}|\x{1F447}\x{1F3FD}|\x{1F447}\x{1F3FC}|\x{1F447}\x{1F3FB}|\x{1F446}\x{1F3FF}|\x{1F446}\x{1F3FE}|\x{1F446}\x{1F3FD}|\x{1F446}\x{1F3FC}|\x{1F446}\x{1F3FB}|\x{1F443}\x{1F3FF}|\x{1F443}\x{1F3FE}|\x{1F443}\x{1F3FD}|\x{1F443}\x{1F3FC}|\x{1F443}\x{1F3FB}|\x{1F442}\x{1F3FE}|\x{1F3CB}\x{1F3FB}|\x{1F442}\x{1F3FD}|\x{1F442}\x{1F3FC}|\x{1F442}\x{1F3FB}|\x{1F1F9}\x{1F1ED}|\x{1F3CC}\x{1F3FF}|\x{1F3CC}\x{1F3FE}|\x{1F3CC}\x{1F3FD}|\x{1F3CC}\x{1F3FC}|\x{1F3CC}\x{1F3FB}|\x{1F3CB}\x{1F3FF}|\x{1F3CB}\x{1F3FE}|\x{1F3CB}\x{1F3FD}|\x{1F3CB}\x{1F3FC}|\x{1F469}\x{1F3FD}|\x{1F91B}\x{1F3FC}|\x{1F469}\x{1F3FF}|\x{1F486}\x{1F3FE}|\x{1F1FC}\x{1F1F8}|\x{1F1FD}\x{1F1F0}|\x{1F1FE}\x{1F1EA}|\x{1F1FE}\x{1F1F9}|\x{1F1FF}\x{1F1E6}|\x{1F1FF}\x{1F1F2}|\x{1F1FF}\x{1F1FC}|\x{1F487}\x{1F3FF}|\x{1F487}\x{1F3FE}|\x{1F487}\x{1F3FD}|\x{1F487}\x{1F3FC}|\x{1F487}\x{1F3FB}|\x{1F486}\x{1F3FF}|\x{1F486}\x{1F3FD}|\x{1F1FB}\x{1F1FA}|\x{1F486}\x{1F3FC}|\x{1F486}\x{1F3FB}|\x{1F485}\x{1F3FF}|\x{1F485}\x{1F3FE}|\x{1F485}\x{1F3FD}|\x{1F485}\x{1F3FC}|\x{1F485}\x{1F3FB}|\x{1F483}\x{1F3FF}|\x{1F483}\x{1F3FE}|\x{1F483}\x{1F3FD}|\x{1F483}\x{1F3FC}|\x{1F483}\x{1F3FB}|\x{1F482}\x{1F3FF}|\x{1F1FC}\x{1F1EB}|\x{1F1FB}\x{1F1F3}|\x{1F482}\x{1F3FD}|\x{1F1FA}\x{1F1EC}|\x{1F46E}\x{1F3FB}|\x{1F1F9}\x{1F1EC}|\x{1F1F9}\x{1F1F0}|\x{1F1F9}\x{1F1F1}|\x{1F1F9}\x{1F1F2}|\x{1F1F9}\x{1F1F3}|\x{1F1F9}\x{1F1F4}|\x{1F1F9}\x{1F1F7}|\x{1F1F9}\x{1F1F9}|\x{1F1F9}\x{1F1FB}|\x{1F1F9}\x{1F1FC}|\x{1F1F9}\x{1F1FF}|\x{1F1FA}\x{1F1E6}|\x{1F1FA}\x{1F1F2}|\x{1F1FB}\x{1F1EE}|\x{1F1FA}\x{1F1F3}|\x{1F1FA}\x{1F1F8}|\x{1F1FA}\x{1F1FE}|\x{1F1FA}\x{1F1FF}|\x{1F1FB}\x{1F1E6}|\x{1F4AA}\x{1F3FF}|\x{1F4AA}\x{1F3FE}|\x{1F4AA}\x{1F3FD}|\x{1F4AA}\x{1F3FC}|\x{1F4AA}\x{1F3FB}|\x{1F1FB}\x{1F1E8}|\x{1F1FB}\x{1F1EA}|\x{1F1FB}\x{1F1EC}|\x{1F482}\x{1F3FE}|\x{1F1F9}\x{1F1EF}|\x{1F482}\x{1F3FC}|\x{1F472}\x{1F3FC}|\x{1F474}\x{1F3FF}|\x{1F474}\x{1F3FE}|\x{1F474}\x{1F3FD}|\x{1F474}\x{1F3FC}|\x{1F474}\x{1F3FB}|\x{1F473}\x{1F3FF}|\x{1F473}\x{1F3FE}|\x{1F473}\x{1F3FD}|\x{1F473}\x{1F3FC}|\x{1F482}\x{1F3FB}|\x{1F472}\x{1F3FF}|\x{1F472}\x{1F3FE}|\x{1F472}\x{1F3FD}|\x{1F472}\x{1F3FB}|\x{1F475}\x{1F3FC}|\x{1F471}\x{1F3FF}|\x{1F471}\x{1F3FE}|\x{1F471}\x{1F3FD}|\x{1F471}\x{1F3FC}|\x{1F471}\x{1F3FB}|\x{1F470}\x{1F3FF}|\x{1F470}\x{1F3FE}|\x{1F470}\x{1F3FD}|\x{1F470}\x{1F3FC}|\x{1F470}\x{1F3FB}|\x{1F46E}\x{1F3FF}|\x{1F46E}\x{1F3FE}|\x{1F46E}\x{1F3FD}|\x{1F46E}\x{1F3FC}|\x{1F475}\x{1F3FB}|\x{1F473}\x{1F3FB}|\x{1F475}\x{1F3FD}|\x{1F478}\x{1F3FB}|\x{1F481}\x{1F3FF}|\x{1F481}\x{1F3FE}|\x{1F481}\x{1F3FD}|\x{1F481}\x{1F3FB}|\x{1F47C}\x{1F3FF}|\x{1F47C}\x{1F3FE}|\x{1F47C}\x{1F3FD}|\x{1F47C}\x{1F3FC}|\x{1F47C}\x{1F3FB}|\x{1F478}\x{1F3FF}|\x{1F478}\x{1F3FE}|\x{1F478}\x{1F3FD}|\x{1F478}\x{1F3FC}|\x{1F481}\x{1F3FC}|\x{1F477}\x{1F3FF}|\x{1F477}\x{1F3FD}|\x{1F477}\x{1F3FC}|\x{1F477}\x{1F3FB}|\x{1F476}\x{1F3FF}|\x{1F476}\x{1F3FE}|\x{1F476}\x{1F3FD}|\x{1F476}\x{1F3FC}|\x{1F476}\x{1F3FB}|\x{1F477}\x{1F3FE}|\x{1F475}\x{1F3FF}|\x{1F475}\x{1F3FE}|\x{270D}\x{1F3FD}|\x{270C}\x{1F3FF}|\x{270D}\x{1F3FB}|\x{270D}\x{1F3FC}|\x{261D}\x{1F3FD}|\x{270D}\x{1F3FE}|\x{270D}\x{1F3FF}|\x{261D}\x{1F3FF}|\x{261D}\x{1F3FE}|\x{270C}\x{1F3FD}|\x{261D}\x{1F3FC}|\x{261D}\x{1F3FB}|\x{270C}\x{1F3FE}|\x{270B}\x{1F3FC}|\x{270C}\x{1F3FC}|\x{270C}\x{1F3FB}|\x{270B}\x{1F3FF}|\x{270B}\x{1F3FE}|\x{270B}\x{1F3FD}|\x{270B}\x{1F3FB}|\x{270A}\x{1F3FF}|\x{270A}\x{1F3FE}|\x{270A}\x{1F3FD}|\x{270A}\x{1F3FC}|\x{26F9}\x{1F3FB}|\x{270A}\x{1F3FB}|\x{26F9}\x{1F3FC}|\x{26F9}\x{1F3FD}|\x{1F004}\x{FE0F}|\x{26F9}\x{1F3FF}|\x{1F202}\x{FE0F}|\x{1F237}\x{FE0F}|\x{1F21A}\x{FE0F}|\x{1F22F}\x{FE0F}|\x{26F9}\x{1F3FE}|\x{1F170}\x{FE0F}|\x{1F3CB}\x{FE0F}|\x{1F171}\x{FE0F}|\x{1F17F}\x{FE0F}|\x{1F17E}\x{FE0F}|\x{1F575}\x{FE0F}|\x{1F3CC}\x{FE0F}|\x{1F3F3}\x{FE0F}|\x{269B}\x{FE0F}|\x{2699}\x{FE0F}|\x{269C}\x{FE0F}|\x{2697}\x{FE0F}|\x{2696}\x{FE0F}|\x{25AB}\x{FE0F}|\x{2694}\x{FE0F}|\x{2195}\x{FE0F}|\x{2196}\x{FE0F}|\x{26A1}\x{FE0F}|\x{2693}\x{FE0F}|\x{2197}\x{FE0F}|\x{267F}\x{FE0F}|\x{2198}\x{FE0F}|\x{267B}\x{FE0F}|\x{26A0}\x{FE0F}|\x{26BD}\x{FE0F}|\x{26AA}\x{FE0F}|\x{203C}\x{FE0F}|\x{26F9}\x{FE0F}|\x{26F5}\x{FE0F}|\x{26F3}\x{FE0F}|\x{26F2}\x{FE0F}|\x{26EA}\x{FE0F}|\x{26D4}\x{FE0F}|\x{00AE}\x{FE0F}|\x{2049}\x{FE0F}|\x{26AB}\x{FE0F}|\x{26C5}\x{FE0F}|\x{2122}\x{FE0F}|\x{2139}\x{FE0F}|\x{2194}\x{FE0F}|\x{26C4}\x{FE0F}|\x{26BE}\x{FE0F}|\x{26B1}\x{FE0F}|\x{26B0}\x{FE0F}|\x{2199}\x{FE0F}|\x{2666}\x{FE0F}|\x{2668}\x{FE0F}|\x{2611}\x{FE0F}|\x{21AA}\x{FE0F}|\x{231A}\x{FE0F}|\x{231B}\x{FE0F}|\x{2328}\x{FE0F}|\x{261D}\x{FE0F}|\x{2618}\x{FE0F}|\x{24C2}\x{FE0F}|\x{2615}\x{FE0F}|\x{2614}\x{FE0F}|\x{260E}\x{FE0F}|\x{2622}\x{FE0F}|\x{2604}\x{FE0F}|\x{2603}\x{FE0F}|\x{2602}\x{FE0F}|\x{2601}\x{FE0F}|\x{2600}\x{FE0F}|\x{25FE}\x{FE0F}|\x{25AA}\x{FE0F}|\x{25FC}\x{FE0F}|\x{25FB}\x{FE0F}|\x{25C0}\x{FE0F}|\x{2620}\x{FE0F}|\x{2623}\x{FE0F}|\x{25B6}\x{FE0F}|\x{264C}\x{FE0F}|\x{2665}\x{FE0F}|\x{2663}\x{FE0F}|\x{2660}\x{FE0F}|\x{2653}\x{FE0F}|\x{2652}\x{FE0F}|\x{2651}\x{FE0F}|\x{2650}\x{FE0F}|\x{264F}\x{FE0F}|\x{264E}\x{FE0F}|\x{264D}\x{FE0F}|\x{264B}\x{FE0F}|\x{2626}\x{FE0F}|\x{264A}\x{FE0F}|\x{2649}\x{FE0F}|\x{2648}\x{FE0F}|\x{263A}\x{FE0F}|\x{2639}\x{FE0F}|\x{2638}\x{FE0F}|\x{21A9}\x{FE0F}|\x{262F}\x{FE0F}|\x{262E}\x{FE0F}|\x{262A}\x{FE0F}|\x{25FD}\x{FE0F}|\x{2934}\x{FE0F}|\x{00A9}\x{FE0F}|\x{27A1}\x{FE0F}|\x{2B1C}\x{FE0F}|\x{2B1B}\x{FE0F}|\x{26FA}\x{FE0F}|\x{2B06}\x{FE0F}|\x{2B05}\x{FE0F}|\x{2935}\x{FE0F}|\x{2764}\x{FE0F}|\x{2B55}\x{FE0F}|\x{2763}\x{FE0F}|\x{2757}\x{FE0F}|\x{2747}\x{FE0F}|\x{2744}\x{FE0F}|\x{2734}\x{FE0F}|\x{2733}\x{FE0F}|\x{2B50}\x{FE0F}|\x{3030}\x{FE0F}|\x{271D}\x{FE0F}|\x{0033}\x{20E3}|\x{0039}\x{20E3}|\x{0038}\x{20E3}|\x{0037}\x{20E3}|\x{0036}\x{20E3}|\x{0035}\x{20E3}|\x{0034}\x{20E3}|\x{0032}\x{20E3}|\x{303D}\x{FE0F}|\x{0031}\x{20E3}|\x{0030}\x{20E3}|\x{002A}\x{20E3}|\x{0023}\x{20E3}|\x{3299}\x{FE0F}|\x{3297}\x{FE0F}|\x{2721}\x{FE0F}|\x{2B07}\x{FE0F}|\x{2716}\x{FE0F}|\x{2714}\x{FE0F}|\x{2712}\x{FE0F}|\x{26FD}\x{FE0F}|\x{2702}\x{FE0F}|\x{270F}\x{FE0F}|\x{270D}\x{FE0F}|\x{2708}\x{FE0F}|\x{270C}\x{FE0F}|\x{2709}\x{FE0F}|\x{1F988}|\x{1F98B}|\x{1F98A}|\x{1F989}|\x{1F91D}|\x{1F91E}|\x{1F920}|\x{1F987}|\x{1F986}|\x{1F985}|\x{1F984}|\x{1F98D}|\x{1F921}|\x{1F98C}|\x{1F91C}|\x{1F98E}|\x{1F98F}|\x{1F990}|\x{1F991}|\x{1F9C0}|\x{1F923}|\x{1F942}|\x{1F941}|\x{1F940}|\x{1F93E}|\x{1F93D}|\x{1F938}|\x{1F93C}|\x{1F93A}|\x{1F3EE}|\x{1F922}|\x{1F983}|\x{1F924}|\x{1F95A}|\x{1F94A}|\x{1F95B}|\x{1F94B}|\x{1F950}|\x{1F951}|\x{1F952}|\x{1F959}|\x{1F949}|\x{1F958}|\x{1F957}|\x{1F934}|\x{1F953}|\x{1F954}|\x{1F935}|\x{1F956}|\x{1F933}|\x{1F936}|\x{1F925}|\x{1F927}|\x{1F926}|\x{1F955}|\x{1F982}|\x{1F981}|\x{1F980}|\x{1F95E}|\x{1F930}|\x{1F948}|\x{1F95D}|\x{1F937}|\x{1F943}|\x{1F944}|\x{1F945}|\x{1F95C}|\x{1F947}|\x{1F939}|\x{1F615}|\x{1F91B}|\x{1F400}|\x{1F40A}|\x{1F409}|\x{1F408}|\x{1F407}|\x{1F406}|\x{1F405}|\x{1F404}|\x{1F403}|\x{1F402}|\x{1F401}|\x{1F3FF}|\x{1F40C}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB}|\x{1F3FA}|\x{1F3F9}|\x{1F3F8}|\x{1F3F7}|\x{1F3F5}|\x{1F3F4}|\x{1F40B}|\x{1F40D}|\x{1F3F0}|\x{1F41B}|\x{1F425}|\x{1F424}|\x{1F423}|\x{1F422}|\x{1F421}|\x{1F420}|\x{1F41F}|\x{1F41E}|\x{1F41D}|\x{1F41C}|\x{1F41A}|\x{1F40E}|\x{1F419}|\x{1F418}|\x{1F417}|\x{1F416}|\x{1F415}|\x{1F414}|\x{1F413}|\x{1F412}|\x{1F411}|\x{1F410}|\x{1F40F}|\x{1F3F3}|\x{1F3EF}|\x{1F427}|\x{1F3C7}|\x{1F3D1}|\x{1F3D0}|\x{1F3CF}|\x{1F3CE}|\x{1F3CD}|\x{1F3CC}|\x{1F3CB}|\x{1F3CA}|\x{1F3C9}|\x{1F3C8}|\x{1F3C6}|\x{1F3D3}|\x{1F3C5}|\x{1F3C4}|\x{1F3C3}|\x{1F3C2}|\x{1F3C1}|\x{1F3C0}|\x{1F3BF}|\x{1F3BE}|\x{1F3BD}|\x{1F3BC}|\x{1F3D2}|\x{1F3D4}|\x{1F3ED}|\x{1F3E2}|\x{1F3EC}|\x{1F3EB}|\x{1F3EA}|\x{1F3E9}|\x{1F3E8}|\x{1F3E7}|\x{1F3E6}|\x{1F3E5}|\x{1F3E4}|\x{1F3E3}|\x{1F3E1}|\x{1F3D5}|\x{1F3E0}|\x{1F3DF}|\x{1F3DE}|\x{1F3DD}|\x{1F3DC}|\x{1F3DB}|\x{1F3DA}|\x{1F3D9}|\x{1F3D8}|\x{1F3D7}|\x{1F3D6}|\x{1F426}|\x{1F428}|\x{1F3BA}|\x{1F46B}|\x{1F475}|\x{1F474}|\x{1F473}|\x{1F472}|\x{1F471}|\x{1F470}|\x{1F46F}|\x{1F46E}|\x{1F46D}|\x{1F46C}|\x{1F46A}|\x{1F477}|\x{1F469}|\x{1F468}|\x{1F467}|\x{1F466}|\x{1F465}|\x{1F464}|\x{1F463}|\x{1F462}|\x{1F461}|\x{1F460}|\x{1F476}|\x{1F478}|\x{1F45E}|\x{1F486}|\x{1F490}|\x{1F48F}|\x{1F48E}|\x{1F48D}|\x{1F48C}|\x{1F48B}|\x{1F48A}|\x{1F489}|\x{1F488}|\x{1F487}|\x{1F485}|\x{1F479}|\x{1F484}|\x{1F483}|\x{1F482}|\x{1F481}|\x{1F480}|\x{1F47F}|\x{1F47E}|\x{1F47D}|\x{1F47C}|\x{1F47B}|\x{1F47A}|\x{1F45F}|\x{1F45D}|\x{1F429}|\x{1F436}|\x{1F91A}|\x{1F43F}|\x{1F43E}|\x{1F43D}|\x{1F43C}|\x{1F43B}|\x{1F43A}|\x{1F439}|\x{1F438}|\x{1F437}|\x{1F435}|\x{1F442}|\x{1F434}|\x{1F433}|\x{1F432}|\x{1F431}|\x{1F430}|\x{1F42F}|\x{1F42E}|\x{1F42D}|\x{1F42C}|\x{1F42B}|\x{1F42A}|\x{1F441}|\x{1F443}|\x{1F45C}|\x{1F451}|\x{1F45B}|\x{1F45A}|\x{1F459}|\x{1F458}|\x{1F457}|\x{1F456}|\x{1F455}|\x{1F454}|\x{1F453}|\x{1F452}|\x{1F450}|\x{1F444}|\x{1F44F}|\x{1F44E}|\x{1F44D}|\x{1F44C}|\x{1F44B}|\x{1F44A}|\x{1F449}|\x{1F448}|\x{1F447}|\x{1F446}|\x{1F445}|\x{1F3BB}|\x{1F3B9}|\x{1F492}|\x{1F320}|\x{1F32C}|\x{1F32B}|\x{1F32A}|\x{1F329}|\x{1F328}|\x{1F327}|\x{1F326}|\x{1F325}|\x{1F324}|\x{1F321}|\x{1F31F}|\x{1F32E}|\x{1F31E}|\x{1F31D}|\x{1F31C}|\x{1F31B}|\x{1F31A}|\x{1F319}|\x{1F318}|\x{1F317}|\x{1F316}|\x{1F315}|\x{1F32D}|\x{1F32F}|\x{1F313}|\x{1F33D}|\x{1F347}|\x{1F346}|\x{1F345}|\x{1F344}|\x{1F343}|\x{1F342}|\x{1F341}|\x{1F340}|\x{1F33F}|\x{1F33E}|\x{1F33C}|\x{1F330}|\x{1F33B}|\x{1F33A}|\x{1F339}|\x{1F338}|\x{1F337}|\x{1F336}|\x{1F335}|\x{1F334}|\x{1F333}|\x{1F332}|\x{1F331}|\x{1F314}|\x{1F312}|\x{1F349}|\x{1F195}|\x{1F232}|\x{1F22F}|\x{1F21A}|\x{1F202}|\x{1F201}|\x{1F19A}|\x{1F199}|\x{1F198}|\x{1F197}|\x{1F196}|\x{1F194}|\x{1F234}|\x{1F193}|\x{1F192}|\x{1F191}|\x{1F18E}|\x{1F17F}|\x{1F17E}|\x{1F171}|\x{1F170}|\x{1F0CF}|\x{1F004}|\x{1F233}|\x{1F235}|\x{1F311}|\x{1F306}|\x{1F310}|\x{1F30F}|\x{1F30E}|\x{1F30D}|\x{1F30C}|\x{1F30B}|\x{1F30A}|\x{1F309}|\x{1F308}|\x{1F307}|\x{1F305}|\x{1F236}|\x{1F304}|\x{1F303}|\x{1F302}|\x{1F301}|\x{1F300}|\x{1F251}|\x{1F250}|\x{1F23A}|\x{1F239}|\x{1F238}|\x{1F237}|\x{1F348}|\x{1F34A}|\x{1F3B8}|\x{1F38D}|\x{1F39A}|\x{1F399}|\x{1F397}|\x{1F396}|\x{1F393}|\x{1F392}|\x{1F391}|\x{1F390}|\x{1F38F}|\x{1F38E}|\x{1F38C}|\x{1F39E}|\x{1F38B}|\x{1F38A}|\x{1F389}|\x{1F388}|\x{1F387}|\x{1F386}|\x{1F385}|\x{1F384}|\x{1F383}|\x{1F382}|\x{1F39B}|\x{1F39F}|\x{1F380}|\x{1F3AD}|\x{1F3B7}|\x{1F3B6}|\x{1F3B5}|\x{1F3B4}|\x{1F3B3}|\x{1F3B2}|\x{1F3B1}|\x{1F3B0}|\x{1F3AF}|\x{1F3AE}|\x{1F3AC}|\x{1F3A0}|\x{1F3AB}|\x{1F3AA}|\x{1F3A9}|\x{1F3A8}|\x{1F3A7}|\x{1F3A6}|\x{1F3A5}|\x{1F3A4}|\x{1F3A3}|\x{1F3A2}|\x{1F3A1}|\x{1F381}|\x{1F37F}|\x{1F34B}|\x{1F358}|\x{1F362}|\x{1F361}|\x{1F360}|\x{1F35F}|\x{1F35E}|\x{1F35D}|\x{1F35C}|\x{1F35B}|\x{1F35A}|\x{1F359}|\x{1F357}|\x{1F364}|\x{1F356}|\x{1F355}|\x{1F354}|\x{1F353}|\x{1F352}|\x{1F351}|\x{1F350}|\x{1F34F}|\x{1F34E}|\x{1F34D}|\x{1F34C}|\x{1F363}|\x{1F365}|\x{1F37E}|\x{1F373}|\x{1F37D}|\x{1F37C}|\x{1F37B}|\x{1F37A}|\x{1F379}|\x{1F378}|\x{1F377}|\x{1F376}|\x{1F375}|\x{1F374}|\x{1F372}|\x{1F366}|\x{1F371}|\x{1F370}|\x{1F36F}|\x{1F36E}|\x{1F36D}|\x{1F36C}|\x{1F36B}|\x{1F36A}|\x{1F369}|\x{1F368}|\x{1F367}|\x{1F491}|\x{1F440}|\x{1F493}|\x{1F625}|\x{1F62F}|\x{1F62E}|\x{1F62D}|\x{1F62C}|\x{1F62B}|\x{1F62A}|\x{1F629}|\x{1F628}|\x{1F627}|\x{1F626}|\x{1F624}|\x{1F631}|\x{1F623}|\x{1F622}|\x{1F621}|\x{1F620}|\x{1F61F}|\x{1F61E}|\x{1F61D}|\x{1F61C}|\x{1F61B}|\x{1F61A}|\x{1F630}|\x{1F632}|\x{1F618}|\x{1F640}|\x{1F64A}|\x{1F649}|\x{1F648}|\x{1F647}|\x{1F646}|\x{1F645}|\x{1F644}|\x{1F643}|\x{1F642}|\x{1F641}|\x{1F63F}|\x{1F633}|\x{1F63E}|\x{1F63D}|\x{1F63C}|\x{1F63B}|\x{1F63A}|\x{1F639}|\x{1F638}|\x{1F637}|\x{1F636}|\x{1F494}|\x{1F634}|\x{1F619}|\x{1F617}|\x{1F64C}|\x{1F5D1}|\x{1F5F3}|\x{1F5EF}|\x{1F5E8}|\x{1F5E3}|\x{1F5E1}|\x{1F5DE}|\x{1F5DD}|\x{1F5DC}|\x{1F5D3}|\x{1F5D2}|\x{1F5C4}|\x{1F5FB}|\x{1F5C3}|\x{1F5C2}|\x{1F5BC}|\x{1F5B2}|\x{1F5B1}|\x{1F5A8}|\x{1F5A5}|\x{1F5A4}|\x{1F596}|\x{1F595}|\x{1F5FA}|\x{1F5FC}|\x{1F616}|\x{1F60A}|\x{1F614}|\x{1F613}|\x{1F612}|\x{1F611}|\x{1F610}|\x{1F60F}|\x{1F60E}|\x{1F60D}|\x{1F60C}|\x{1F60B}|\x{1F609}|\x{1F5FD}|\x{1F608}|\x{1F607}|\x{1F606}|\x{1F605}|\x{1F604}|\x{1F603}|\x{1F602}|\x{1F601}|\x{1F600}|\x{1F5FF}|\x{1F5FE}|\x{1F64B}|\x{1F64D}|\x{1F58D}|\x{1F6C0}|\x{1F6CF}|\x{1F6CE}|\x{1F6CD}|\x{1F6CC}|\x{1F6CB}|\x{1F6C5}|\x{1F6C4}|\x{1F6C3}|\x{1F6C2}|\x{1F6C1}|\x{1F6BF}|\x{1F6D1}|\x{1F6BE}|\x{1F6BD}|\x{1F6BC}|\x{1F6BB}|\x{1F6BA}|\x{1F6B9}|\x{1F6B8}|\x{1F6B7}|\x{1F6B6}|\x{1F6B5}|\x{1F6D0}|\x{1F6D2}|\x{1F6B3}|\x{1F6F6}|\x{1F919}|\x{1F918}|\x{1F917}|\x{1F916}|\x{1F915}|\x{1F914}|\x{1F913}|\x{1F912}|\x{1F911}|\x{1F910}|\x{1F6F5}|\x{1F6E0}|\x{1F6F4}|\x{1F6F3}|\x{1F6F0}|\x{1F6EC}|\x{1F6EB}|\x{1F6E9}|\x{1F6E5}|\x{1F6E4}|\x{1F6E3}|\x{1F6E2}|\x{1F6E1}|\x{1F6B4}|\x{1F6B2}|\x{1F64E}|\x{1F68B}|\x{1F695}|\x{1F694}|\x{1F693}|\x{1F692}|\x{1F691}|\x{1F690}|\x{1F68F}|\x{1F68E}|\x{1F68D}|\x{1F68C}|\x{1F68A}|\x{1F697}|\x{1F689}|\x{1F688}|\x{1F687}|\x{1F686}|\x{1F685}|\x{1F684}|\x{1F683}|\x{1F682}|\x{1F681}|\x{1F680}|\x{1F64F}|\x{1F696}|\x{1F698}|\x{1F6B1}|\x{1F6A6}|\x{1F6B0}|\x{1F6AF}|\x{1F6AE}|\x{1F6AD}|\x{1F6AC}|\x{1F6AB}|\x{1F6AA}|\x{1F6A9}|\x{1F6A8}|\x{1F6A7}|\x{1F6A5}|\x{1F699}|\x{1F6A4}|\x{1F6A3}|\x{1F6A2}|\x{1F6A1}|\x{1F6A0}|\x{1F69F}|\x{1F69E}|\x{1F69D}|\x{1F69C}|\x{1F69B}|\x{1F69A}|\x{1F590}|\x{1F635}|\x{1F58C}|\x{1F4D6}|\x{1F4E0}|\x{1F4DF}|\x{1F4DE}|\x{1F4DD}|\x{1F4DC}|\x{1F4DB}|\x{1F4DA}|\x{1F4D9}|\x{1F4D8}|\x{1F4D7}|\x{1F4D5}|\x{1F4E2}|\x{1F4D4}|\x{1F4D3}|\x{1F4D2}|\x{1F4D1}|\x{1F4D0}|\x{1F4CF}|\x{1F4CE}|\x{1F4CD}|\x{1F4CC}|\x{1F4CB}|\x{1F4E1}|\x{1F4E3}|\x{1F4C9}|\x{1F4F1}|\x{1F4FB}|\x{1F4FA}|\x{1F4F9}|\x{1F4F8}|\x{1F4F7}|\x{1F4F6}|\x{1F4F5}|\x{1F4F4}|\x{1F4F3}|\x{1F4F2}|\x{1F4F0}|\x{1F4E4}|\x{1F4EF}|\x{1F4EE}|\x{1F4ED}|\x{1F4EC}|\x{1F4EB}|\x{1F4EA}|\x{1F4E9}|\x{1F4E8}|\x{1F4E7}|\x{1F4E6}|\x{1F4E5}|\x{1F4CA}|\x{1F4C8}|\x{1F4FD}|\x{1F4A1}|\x{1F58B}|\x{1F4AA}|\x{1F4A9}|\x{1F4A8}|\x{1F4A7}|\x{1F4A6}|\x{1F4A5}|\x{1F4A4}|\x{1F4A3}|\x{1F4A2}|\x{1F4A0}|\x{1F4AD}|\x{1F49F}|\x{1F49E}|\x{1F49D}|\x{1F49C}|\x{1F49B}|\x{1F49A}|\x{1F499}|\x{1F498}|\x{1F497}|\x{1F496}|\x{1F495}|\x{1F4AC}|\x{1F4AE}|\x{1F4C7}|\x{1F4BC}|\x{1F4C6}|\x{1F4C5}|\x{1F4C4}|\x{1F4C3}|\x{1F4C2}|\x{1F4C1}|\x{1F4C0}|\x{1F4BF}|\x{1F4BE}|\x{1F4BD}|\x{1F4BB}|\x{1F4AF}|\x{1F4BA}|\x{1F4B9}|\x{1F4B8}|\x{1F4B7}|\x{1F4B6}|\x{1F4B5}|\x{1F4B4}|\x{1F4B3}|\x{1F4B2}|\x{1F4B1}|\x{1F4B0}|\x{1F4FC}|\x{1F4AB}|\x{1F4FF}|\x{1F54D}|\x{1F558}|\x{1F557}|\x{1F556}|\x{1F555}|\x{1F554}|\x{1F553}|\x{1F552}|\x{1F551}|\x{1F550}|\x{1F54E}|\x{1F54C}|\x{1F55A}|\x{1F54B}|\x{1F54A}|\x{1F549}|\x{1F53D}|\x{1F53C}|\x{1F53B}|\x{1F53A}|\x{1F539}|\x{1F538}|\x{1F537}|\x{1F559}|\x{1F55B}|\x{1F535}|\x{1F573}|\x{1F500}|\x{1F58A}|\x{1F587}|\x{1F57A}|\x{1F579}|\x{1F578}|\x{1F577}|\x{1F576}|\x{1F575}|\x{1F574}|\x{1F570}|\x{1F55C}|\x{1F567}|\x{1F566}|\x{1F565}|\x{1F564}|\x{1F563}|\x{1F562}|\x{1F561}|\x{1F560}|\x{1F55F}|\x{1F55E}|\x{1F55D}|\x{1F536}|\x{1F56F}|\x{1F534}|\x{1F50D}|\x{1F517}|\x{1F516}|\x{1F515}|\x{1F514}|\x{1F513}|\x{1F512}|\x{1F511}|\x{1F510}|\x{1F50F}|\x{1F50E}|\x{1F50C}|\x{1F519}|\x{1F50B}|\x{1F50A}|\x{1F509}|\x{1F508}|\x{1F506}|\x{1F505}|\x{1F504}|\x{1F503}|\x{1F502}|\x{1F533}|\x{1F501}|\x{1F518}|\x{1F507}|\x{1F51A}|\x{1F527}|\x{1F531}|\x{1F51B}|\x{1F532}|\x{1F530}|\x{1F52F}|\x{1F52E}|\x{1F52C}|\x{1F52B}|\x{1F52A}|\x{1F529}|\x{1F528}|\x{1F52D}|\x{1F51D}|\x{1F51C}|\x{1F51E}|\x{1F526}|\x{1F51F}|\x{1F521}|\x{1F520}|\x{1F522}|\x{1F523}|\x{1F524}|\x{1F525}|\x{262F}|\x{2620}|\x{262E}|\x{262A}|\x{2626}|\x{2623}|\x{2622}|\x{2602}|\x{2614}|\x{261D}|\x{2618}|\x{2615}|\x{2611}|\x{260E}|\x{2604}|\x{2639}|\x{2603}|\x{2638}|\x{2650}|\x{263A}|\x{2651}|\x{2668}|\x{2600}|\x{2666}|\x{2665}|\x{2663}|\x{2660}|\x{2653}|\x{2652}|\x{264F}|\x{2640}|\x{264E}|\x{264D}|\x{264C}|\x{264B}|\x{264A}|\x{2649}|\x{2648}|\x{2642}|\x{2601}|\x{2328}|\x{25FE}|\x{2197}|\x{23CF}|\x{231B}|\x{231A}|\x{21AA}|\x{21A9}|\x{2199}|\x{2198}|\x{2196}|\x{23EA}|\x{2195}|\x{2194}|\x{2139}|\x{2122}|\x{2049}|\x{203C}|\x{00AE}|\x{267F}|\x{23E9}|\x{23EB}|\x{25FD}|\x{23FA}|\x{25FC}|\x{25FB}|\x{25C0}|\x{25B6}|\x{25AB}|\x{25AA}|\x{24C2}|\x{23F9}|\x{23EC}|\x{23F8}|\x{23F3}|\x{23F2}|\x{23F1}|\x{23F0}|\x{23EF}|\x{23EE}|\x{23ED}|\x{267B}|\x{2728}|\x{2692}|\x{2744}|\x{2757}|\x{2755}|\x{2754}|\x{2753}|\x{274E}|\x{274C}|\x{2747}|\x{2734}|\x{2764}|\x{2733}|\x{2721}|\x{271D}|\x{2716}|\x{2714}|\x{2712}|\x{270F}|\x{270D}|\x{2763}|\x{2795}|\x{270B}|\x{2B1B}|\x{3299}|\x{3297}|\x{303D}|\x{3030}|\x{2B55}|\x{2B50}|\x{2B1C}|\x{2B07}|\x{2796}|\x{2B06}|\x{2B05}|\x{2935}|\x{2934}|\x{27BF}|\x{27B0}|\x{27A1}|\x{2797}|\x{270C}|\x{270A}|\x{2693}|\x{26AA}|\x{26C5}|\x{26C4}|\x{26BE}|\x{26BD}|\x{26B1}|\x{26B0}|\x{26AB}|\x{26A1}|\x{26CE}|\x{26A0}|\x{269C}|\x{269B}|\x{2699}|\x{2697}|\x{2696}|\x{2695}|\x{2694}|\x{26C8}|\x{26CF}|\x{2709}|\x{26F5}|\x{2708}|\x{2705}|\x{2702}|\x{26FD}|\x{26FA}|\x{26F9}|\x{26F8}|\x{26F7}|\x{26F4}|\x{26D1}|\x{26F3}|\x{26F2}|\x{26F1}|\x{26F0}|\x{26EA}|\x{26E9}|\x{26D4}|\x{26D3}|\x{00A9})/u";

		$value = preg_replace($regex, '', $value);

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

		$entry = $this->getModelFacade()->get('ChannelEntry')
			->fields('entry_id', 'title')
			->filter('entry_id', '!=', $this->getId())
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

	public function onBeforeSave()
	{
		// Set allow_comments to the channel default if not set
		if (empty($this->allow_comments))
		{
			$this->allow_comments = $this->Channel->deft_comments;
		}
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		$this->Autosaves->delete();

		$this->updateEntryStats();
		$this->saveTabData();
		$this->saveVersion();

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
		parent::onAfterInsert();
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

	public function onBeforeInsert()
	{
		$this->ensureStatusSynced(TRUE);
	}

	public function onBeforeUpdate($changed)
	{
		$this->ensureStatusSynced(isset($changed['status']));
	}

	private function ensureStatusSynced($update_by_name)
	{
		if ($update_by_name)
		{
			$this->Status = $this->getModelFacade()->get('Status')
				->filter('status', $this->getProperty('status'))
				->first();
		}
		else
		{
			$this->setProperty('status', $this->Status->status);
		}
	}

	public function onAfterUpdate($changed)
	{
		parent::onAfterUpdate($changed);

		if (array_key_exists('author_id', $changed))
		{
			$this->Author->updateAuthorStats();
		}
	}

	public function onBeforeDelete()
	{
		$this->getAssociation('Channel')->markForReload();
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

		$last_author = $this->getModelFacade()->get('Member', $this->Author->member_id)->first();
		$this->Author = NULL;

		$last_author->updateAuthorStats();
		$this->updateEntryStats();
	}

	public function saveTabData()
	{
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

					if ($this->$property)
					{
						$values[$field] = $this->$property;
					}
					elseif ($this->hasCustomField($property))
					{
						$values[$field] = $this->getCustomField($property)->getData();
					}
					else
					{
						$values[$field] = NULL;
					}
				}

				$OBJ->save($this, $values);
			}

			// restore our package and view paths
			ee()->load->remove_package_path($info->getPath());
		}
	}

	public function saveVersion()
	{
		if ( ! $this->getProperty('versioning_enabled'))
		{
			return;
		}

		$data = $_POST ?: $this->getValues();

		$last_version = $this->Versions->sortBy('version_date')->reverse()->first();

		if ( ! empty($last_version) && $data == $last_version->version_data)
		{
			return;
		}

        if ($this->Versions->count() >= $this->Channel->max_revisions)
        {
            $diff = $this->Versions->count() - $this->Channel->max_revisions;
            $diff++; // We are going to add one, so remove one more

            $versions = $this->Versions->sortBy('version_date')->asArray();
            $versions = array_slice($versions, 0, $diff);

            foreach ($versions as $version)
            {
                $version->delete();
            }
        }

		$data = array(
			'entry_id'     => $this->entry_id,
			'channel_id'   => $this->channel_id,
			'author_id'    => ee()->session->userdata('member_id') ?: 1,
			'version_date' => ee()->localize->now,
			'version_data' => $data
		);

		$version = $this->getModelFacade()->make('ChannelEntryVersion', $data)->save();
	}

	private function updateEntryStats()
	{

		if(ee()->config->item('ignore_entry_stats') == 'y') {
			return;
		}

		$site_id = ($this->site_id) ?: ee()->config->item('site_id');
		$now = ee()->localize->now;

		$entries = $this->getModelFacade()->get('ChannelEntry')
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

		$entry = $entries->first();

		$last_entry_date = ($entry) ? $entry->entry_date : 0;

		$stats = $this->getModelFacade()->get('Stats')
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
		$modules = ee()->session->cache(__CLASS__, __METHOD__);

		if ($modules === FALSE)
		{
			$modules = [];
			$providers = ee('App')->getProviders();
			$installed_modules = $this->getModelFacade()->get('Module')
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

			ee()->session->set_cache(__CLASS__, __METHOD__, $modules);
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
		return isset($this->versioning_enabled)
			? $this->versioning_enabled : $this->Channel->enable_versioning;
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

		if ($this->isNew() OR empty($categories))
		{
			$this->Categories = NULL;
		}

		if (empty($categories))
		{
			foreach ($cat_groups as $cat_group)
			{
				$this->setRawProperty('cat_group_id_'.$cat_group, '');
				$this->getCustomField('categories[cat_group_id_'.$cat_group.']')->setData('');
			}

			return;
		}

		$cat_groups = array_filter($cat_groups, function($cat_group_id) use ($categories) {
			return array_key_exists('cat_group_id_'.$cat_group_id, $categories);
		});

		if (empty($cat_groups))
		{
			return;
		}

		$category_ids = array();

		// Set the data on the fields in case we come back from a validation error
		foreach ($cat_groups as $cat_group)
		{
			$group_cats = $categories['cat_group_id_'.$cat_group];

			$category_ids = array_merge($category_ids, $group_cats);

			$this->setRawProperty('cat_group_id_'.$cat_group, implode('|', $group_cats));
			$this->getCustomField('categories[cat_group_id_'.$cat_group.']')->setData(implode('|', $group_cats));
		}

		$cat_objects = $this->getModelFacade()
			->get('Category')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('cat_id', 'IN', $category_ids)
			->all();

		$set_cats = $cat_objects->asArray();

		if (ee()->config->item('auto_assign_cat_parents') == 'y')
		{
			$category_ids = $cat_objects->pluck('cat_id');
			foreach ($set_cats as $cat)
			{
				while ($cat->Parent !== NULL)
				{
					$cat = $cat->Parent;
					if ( ! in_array($cat->getId(), $category_ids))
					{
						$category_ids[] = $cat->getId();
						$set_cats[] = $cat;
					}
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
		if (empty($this->_default_fields))
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
					'field_instructions'	=> lang('alphadash_desc'),
					'field_show_fmt'		=> 'n',
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'text',
					'field_maxl'			=> URL_TITLE_MAX_LENGTH
				),
				'entry_date' => array(
					'field_id'				=> 'entry_date',
					'field_label'			=> lang('entry_date'),
					'field_required'		=> 'y',
					'field_type'			=> 'date',
					'field_text_direction'	=> 'ltr',
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
					'field_type'			=> 'radio',
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
					'field_type'			=> 'radio',
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
					'field_type'			=> 'radio',
					'field_list_items'      => array(),
					'field_maxl'			=> 100,
					'populateCallback'		=> array($this, 'populateAuthors'),
					'filter_url' 			=> ! INSTALLER
						? ee('CP/URL')->make('publish/author-list')->compile()
						: '',
					'no_results'			=> ['text' => sprintf(lang('no_found'), lang('members'))]
				),
				'sticky' => array(
					'field_id'				=> 'sticky',
					'field_label'			=> lang('sticky'),
					'field_required'		=> 'n',
					'field_show_fmt'		=> 'n',
					'field_instructions'	=> lang('sticky_desc'),
					'field_text_direction'	=> 'ltr',
					'field_type'			=> 'toggle',
					'yes_no'				=> TRUE,
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
					'field_type'			=> 'toggle',
					'yes_no'				=> TRUE,
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
					'field_type'			=> 'toggle',
					'yes_no'				=> TRUE,
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
				$cat_groups = $this->getModelFacade()->get('CategoryGroup')
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

				if ( ! $this->Channel->comment_system_enabled OR ! bool_config_item('enable_comments'))
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

			$this->_default_fields = $default_fields;
		}

		return $this->_default_fields;
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

		$my_fields = $this->Channel->getAllCustomFields()->pluck('field_id');
		$my_statuses = $this->Channel->Statuses->getIds();

		$channel_filter_options = array();

		$channels = $this->getModelFacade()->get('Channel', $allowed_channel_ids)
			->with('Statuses', 'CustomFields', ['FieldGroups' => 'ChannelFields'])
			->filter('site_id', ee()->config->item('site_id'))
			// Include custom field information because it may be cached for later calls
			->fields('channel_id', 'channel_title', 'ChannelFields.*', 'CustomFields.*')
			->all();

		foreach ($channels as $channel)
		{
			if ($my_fields == $channel->getAllCustomFields()->pluck('field_id') &&
				$my_statuses == $channel->Statuses->getIds())
			{
				$channel_filter_options[$channel->channel_id] = $channel->channel_title;
			}
		}

		$field->setItem('field_list_items', $channel_filter_options);
	}


 	/**
	 * Populate the Authors dropdown
	 *
	 * @param   object  $field  ChannelEntry object
	 * @return	void    Sets author field metaddata
	 *
	 * The following are included in the author list regardless of
	 * their channel posting permissions (assuming the user has permission to assign entries to others):
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

		if ($author)
		{
			$author_options[$author->getId()] = $author->getMemberName();
		}

		if (ee('Permission')->has('can_assign_post_authors'))
		{
			if ( ! $author OR ($author->getId() != ee()->session->userdata('member_id')))
			{
				$author_options[ee()->session->userdata('member_id')] =
				ee()->session->userdata('screen_name') ?: ee()->session->userdata('username');
			}

			$author_options += ee('Member')->getAuthors();
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
		// This generates an inscrutable error when installing the default theme, bail out
		$all_statuses = ! INSTALLER ? $this->Channel->Statuses->sortBy('status_order') : [];

		$status_options = array();

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

			$status_options[] = $status->getOptionComponent();
		}

		$field->setItem('field_list_items', $status_options);
	}

	public function getAuthorName()
	{
		return ($this->author_id && $this->Author) ? $this->Author->getMemberName() : '';
	}

	public function getModChannelResultsArray()
	{
		$data = array_merge($this->getValues(), $this->Channel->getRawValues(), $this->Author->getValues());
		$data['entry_site_id'] = $this->site_id;
		if ($this->edit_date)
		{
			$data['edit_date'] = $this->edit_date->format('U');
		}
		if ($this->recent_comment_date)
		{
			$data['recent_comment_date'] = $this->recent_comment_date->format('U');
		}

		foreach ($this->getStructure()->getAllCustomFields() as $field)
		{
			$key = 'field_id_' . $field->getId();

			if ( ! array_key_exists($key, $data))
			{
				$data[$key] = NULL;
			}
		}

		foreach (['versioning_enabled', 'allow_comments', 'sticky'] as $key)
		{
			$data[$key] = ($data[$key]) ? 'y' : 'n';
		}

		$cat_ids = [];
		foreach ($this->Categories as $cat)
		{
			$cat_ids[] = $cat->getId();
		}
		$data['cat_id'] = $cat_ids;

		return $data;
	}

	public function hasPageURI()
	{
		$pages = $this->Site->site_pages;
		return isset($pages[$this->site_id]['uris'][$this->getId()]);
	}

	public function getPageURI()
	{
		if ( ! $this->hasPageURI())
		{
			return NULL;
		}

		return $this->Site->site_pages[$this->site_id]['uris'][$this->getId()];
	}

	public function getPageTemplateID()
	{
		if ( ! $this->hasPageURI())
		{
			return NULL;
		}

		return $this->Site->site_pages[$this->site_id]['templates'][$this->getId()];
	}

	public function isLivePreviewable()
	{
		if ($this->Channel->preview_url)
		{
			return TRUE;
		}

		$pages_module = ee('Addon')->get('pages');
		if ($pages_module && $pages_module->isInstalled())
		{
			return TRUE;
		}

		if ($this->hasPageURI())
		{
		    return TRUE;
		}

		return FALSE;
	}

	public function hasLivePreview()
	{
		if ($this->Channel->preview_url || $this->hasPageURI())
		{
			return TRUE;
		}

		return FALSE;
	}

}

// EOF
