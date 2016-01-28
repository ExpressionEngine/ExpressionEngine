<?php

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Native;
use EllisLab\ExpressionEngine\Service\Model\Column\CustomType;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Preferences Column
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ChannelPreferences extends CustomType {

	protected $auto_assign_cat_parents;
	protected $auto_convert_high_ascii;
	protected $comment_edit_time_limit;
	protected $comment_moderation_override;
	protected $comment_word_censoring;
	protected $enable_comments;
	protected $image_library_path;
	protected $image_resize_protocol;
	protected $new_posts_clear_caches;
	protected $reserved_category_word;
	protected $thumbnail_prefix;
	protected $use_category_name;
	protected $word_separator;

	/**
	* Called when the column is fetched from db
	*/
	public function unserialize($db_data)
	{
		return Base64Native::unserialize($db_data);
	}

	/**
	* Called before the column is written to the db
	*/
	public function serialize($data)
	{
		return Base64Native::serialize($data);
	}
}

// EOF
