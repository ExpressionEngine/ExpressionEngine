<?PHP
namespace EllisLab\ExpressionEngine\Module\Channel\

class ChannelTitleEntity extends Entity {
	// Structural definition stuff
	protected $id_name = 'entry_id';
	protected $table_name = 'exp_channel_titles';
	protected $relations = array(

	);

	public $entry_id;
	public $site_id;
	public $channel_id;
	public $author_id;
	public $forum_topic_id;
	public $ip_address;
	public $title;
	public $url_title;
	public $status;
	public $versioning_enabled;
	public $view_count_one;
	public $view_count_two;
	public $view_count_three;
	public $view_count_four;
	public $allow_comments;
	public $sticky;
	public $entry_date;
	public $year;
	public $month;
	public $day;
	public $expiration_date;
	public $comment_expiration_date;
	public $edit_date;
	public $recent_comment_date;
	public $comment_total;						

}
