<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Content\ContentStructure
	as ContentStructure;


class Channel extends Model implements ContentStructure {
	protected static $_meta = array(
		'primary_key' => 'channel_id',
		'gateway_names' => array('ChannelGateway'),
		'key_map' => array(
			'channel_id' => 'ChannelGateway',
			'site_id' => 'ChannelGateway',
			'field_group' => 'ChannelGateway'
		)	
	);

	// Properties
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

	/**
	 * Relationship to the FieldGroup for this Channel.
	 */
	public function getChannelFieldGroup()
	{
		return $this->manyToOne(
			'ChannelFieldGroup', 'ChannelFieldGroup', 'field_group', 'group_id');	
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getChannelEntries()
	{
		return $this->oneToMany(
			'ChannelEntries', 'ChannelEntry', 'channel_id', 'channel_id');
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
