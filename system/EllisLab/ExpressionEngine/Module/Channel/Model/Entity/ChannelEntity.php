<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelEntity extends Entity {
	// Structural definition stuff
	protected static $meta = array(
		'table_name' => 'channels',
		'primary_key' => 'channel_id',
		'related_entities' => array(
			'channel_id' => array(
				'entity' => 'ChannelTitleEntity',
				'key' => 'channel_id'
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			),
			'field_group' => array(
				'entity' => 'FieldGroupEntity',
				'key'	 => 'group_id'
			),
			'status_group' => array(
				'entity' => 'StatusGroupEntity',
				'key' => 'group_id'
			)
		)
	);	

	// Properties
	public $channel_id;
	public $site_id;
	public $channel_name;
	public $channel_title;
	public $channel_url;
	public $channel_description;
	public $channel_lang;
	public $total_entries;
	public $total_comments;
	public $last_entry_date;
	public $last_comment_date;
	public $cat_group;
	public $status_group;
	public $deft_status;
	public $field_group;
	public $search_excerpt;
	public $deft_category;
	public $deft_comments;
	public $channel_require_membership;
	public $channel_max_chars;
	public $channel_html_formatting;
	public $channel_allow_img_urls;
	public $channel_auto_link_urls;
	public $channel_notify;
	public $channel_notify_emails;
	public $comment_url;
	public $comment_system_enabled;
	public $comment_require_membership;
	public $comment_use_captcha;
	public $comment_moderate;
	public $comment_max_chars;
	public $comment_timelock;
	public $comment_require_email;
	public $comment_text_formatting;
	public $comment_html_formatting;
	public $comment_allow_img_urls;
	public $comment_auto_link_urls;
	public $comment_notify;
	public $comment_notify_authors;
	public $comment_notify_emails;
	public $comment_expiration;
	public $search_results_url;
	public $show_button_cluster;
	public $rss_url;
	public $enable_versioning;
	public $max_revisions;
	public $default_entry_title;
	public $url_title_prefix;
	public $live_look_template;

}
