<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model as Model;

class ChannelEntryAutosave extends Model {

	protected static $_primary_key = 'entry_id';
	protected static $_table_name = 'channel_entries_autosave';

	protected static $_relationships = array(
		'ChannelEntry' => array(
			'type' => 'belongsTo',
			'key' => 'original_entry_id'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'Author'	=> array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' 	=> 'author_id'
		),
	);

	// Properties
	protected $entry_id;
	protected $original_entry_id;
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
	protected $entry_data;

	public function set__entry_data($entry_data)
	{
		$this->entry_data = json_encode($entry_data);
	}

	public function get__entry_data()
	{
		return json_decode($this->entry_data, TRUE);
	}

}
