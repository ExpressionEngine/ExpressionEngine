<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model as Model;
use EllisLab\ExpressionEngine\Service\Model\Interfaces\Content\ContentStructure
	as ContentStructure;


class Channel extends Model implements ContentStructure {

	protected static $_primary_key = 'channel_id';
	protected static $_gateway_names = array('ChannelGateway');

	protected static $_relationships = array(
		'ChannelFieldGroup' => array(
			'type' => 'belongsTo',
			'key' => 'field_group'
		),
		'ChannelEntries'	=> array(
			'type' => 'hasMany',
			'model' => 'ChannelEntry'
		)
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
	protected $comment_use_captcha;
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
	protected $url_title_prefix;
	protected $live_look_template;


	public function getChannelFieldGroup()
	{
		return $this->getRelated('ChannelFieldGroup');
	}

	public function setChannelFieldGroup($field_group)
	{
		return $this->setRelated('ChannelFieldGroup', $field_group);
	}

	public function getChannelEntries()
	{
		return $this->getRelated('ChannelEntries');
	}

	public function setChannelEntries(array $entries)
	{
		return $this->setRelated('ChannelEntries', $entries);
	}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content)
	{
		$form_elements = array();
		// populate from custom fields

		return $form_elements;
	}

	/**
	 * Duplicate another Channel's preferences
	 */
	public function duplicatePreferences(Channel $channel)
	{
		$exceptions = array('channel_id', 'site_id', 'channel_name', 'channel_title', 'total_entries',
							'total_comments', 'last_entry_date', 'last_comment_date');

		foreach(get_object_vars($this) as $property => $value)
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
						$this->{$property} = $channel->{$property};
					}
					break;
				case 'status_group':
				case 'field_group':
					if ( ! isset($this->{$property}))
					{
						$this->{$property} = $channel->{$property};
					}
					elseif ($this->{$property} == '')
					{
						 $this->{$property} = NULL;
					}
					break;
				case 'deft_status':
				case 'deft_status':
					if ( ! isset($this->status_group) OR $this->status_group == $channel->status_group )
					{
						$this->{$property} = $channel->{$property};
					}
					break;
				case 'search_excerpt':
					if ( ! isset($this->field_group) OR $this->field_group == $channel->field_group )
					{
						$this->{$property} = $channel->{$property};
					}
					break;
				case 'deft_category':
					if ( ! isset($this->cat_group) OR count(array_diff(explode('|', $this->cat_group), explode('|', $channel->cat_group ))) == 0)
					{
						$this->{$property} = $channel->{$property};
					}
					break;
				default:
					$this->{$property} = $channel->{$property};
					break;
			}
		}
	}

	public function testPrint($depth='')
	{
		if ($depth == "\t\t\t")
		{
			return;
		}
		$primary_key = static::getMetaData('primary_key');
		$model_name = substr(get_class($this), strrpos(get_class($this), '\\')+1);
		echo $depth . '=====' . $model_name . ': ' . '(' . $this->{$primary_key} . ') ' . $this->channel_title . ' OBJ(' . spl_object_hash($this) .')' . "=====\n";
		foreach($this->_related_models as $relationship_name=>$models)
		{
			echo $depth . '----Relationship: ' . $relationship_name . "----\n";
			foreach($models as $model)
			{
				$model->testPrint($depth . "\t");
			}
			echo $depth . '---- END Relationship: ' . $relationship_name . "----\n";
		}
		echo $depth . '===== END ' . $model_name . ': ' . '(' . $this->{$primary_key} . ') ' . $this->channel_title . "=====\n";
		echo "\n";

	}

}
