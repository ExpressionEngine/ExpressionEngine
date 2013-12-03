<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\FieldDataContentModel;

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
class ChannelEntry extends FieldDataContentModel {
	protected static $_meta = array(
		'primary_key' => 'entry_id',
		'gateway_names' => array('ChannelTitleGateway', 'ChannelDataGateway'),
		'key_map' => array(
			'entry_id' => 'ChannelTitleGateway',
			'channel_id' => 'ChannelTitleGateway',
			'site_id' => 'ChannelTitleGateway',
			'author_id' => 'ChannelTitleGateway'
		),
		'field_content_class' => 'ChannelFieldContent',
		'field_content_gateway' => 'ChannelDataGateway'		
	);

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

	public function getChannel()
	{
		return $this->manyToOne('Channel', 'Channel', 'channel_id', 'channel_id');
	}

	public function setChannel(Channel $channel)
	{
		$this->setRelated('Channel', $channel);
		$this->channel_id = $channel->channel_id;
		return $this;
	}

	public function getAuthor()
	{
		return $this->manyToOne('Author', 'Member', 'author_id', 'member_id');
	}

	public function setAuthor(Member $author)
	{
		$this->setRelated('Author', $author);
		$this->author_id = $author->member_id;
		return $this;
	}

	public function getCategories()
	{
		return $this->manyToMany('Categories', 'Category', 'entry_id', 'cat_id');
	}

	public function setCategories(array $categories)
	{
		$this->setRelated('Categories', $categories);
		return $this;
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure()
	{
		return $this->getChannel();
	}


	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{
	}

}
