<?php

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Base64SerializedComposite;

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
 * ExpressionEngine Channel Preferences Column
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ChannelPreferences extends Base64SerializedComposite {

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
}
