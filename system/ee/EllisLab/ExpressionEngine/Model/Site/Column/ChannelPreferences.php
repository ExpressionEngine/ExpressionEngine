<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Serialized\Base64Native;
use EllisLab\ExpressionEngine\Service\Model\Column\CustomType;

/**
 * Channel Preferences Column
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
