<?PHP
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelTitleEntity extends Entity {
	// Structural definition stuff
	protected static $meta = array(
		'table_name' 		=> 'channel_titles',
		'primary_key' 		=> 'entry_id',
		'related_entities' 	=> array(
			'entry_id' => array(
				'Categories'=>array(
					'entity' => 'CategoryEntity',
					'key'	 => 'cat_id',
					'pivot_table' => 'category_posts',
					'pivot_key' => 'entry_id',
					'pivot_foreign_key' => 'cat_id'
				)
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			),
			'channel_id' => array(
				'entity' => 'ChannelEntity',
				'key'    => 'channel_id'
			),
			'author_id' => array(
				'entity' => 'MemberEntity',
				'key'	=> 'member_id'
			)
		)
	);

	// Properties
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
