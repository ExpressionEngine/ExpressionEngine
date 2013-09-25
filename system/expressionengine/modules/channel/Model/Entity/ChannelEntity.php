<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelEntity extends Entity {
	// Structural definition stuff
	protected $id_name = 'channel_id';
	protected $table_name = 'exp_channels';
	protected $relations = array(
		
	);
	

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

}
