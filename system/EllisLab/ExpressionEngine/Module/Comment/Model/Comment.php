<?php
namespace EllisLab\ExpressionEngine\Module\Comment\Model;

use EllisLab\ExpresionEngine\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Model
 *
 * A model representing a comment on a Channel entry.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Comment extends Model {
	protected static $_primary_key = 'comment_id';
	protected static $_gateway_names = array('CommentGateway');

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		),
		'Entry' => array(
			'type' => 'many_to_one',
			'model' => 'ChannelEntry'
		),
		'Channel' => array(
			'type' => 'many_to_one'
		),
		'Author' => array(
			'type' => 'many_to_one',
			'model' => 'Member'
		)
	);

	protected $comment_id;
	protected $site_id;
	protected $entry_id;
	protected $channel_id;
	protected $author_id;
	protected $status;
	protected $name;
	protected $email;
	protected $url;
	protected $location;
	protected $ip_address;
	protected $comment_date;
	protected $edit_date;
	protected $comment;

	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}

	public function getEntry()
	{
		return $this->getRelated('Entry');
	}

	public function setEntry(ChannelEntry $entry)
	{
		return $this->setRelated('Entry', $entry);
	}

	public function getChannel()
	{
		return $this->getRelated('Channel');
	}

	public function setChannel(Channel $channel)
	{
		return $this->setRelated('Channel', $channel);
	}

	public function getAuthor()
	{
		return $this->getRelated('Author');
	}

	public function setAuthor(Member $author)
	{
		return $this->setRelated('Author', $author);
	}

}
