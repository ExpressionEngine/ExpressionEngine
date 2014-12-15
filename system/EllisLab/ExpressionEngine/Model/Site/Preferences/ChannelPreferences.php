<?php

namespace EllisLab\ExpressionEngine\Model\Site\Preferences;

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
 * ExpressionEngine Channel Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ChannelPreferences extends ConcretePreferences {

	protected $image_resize_protocol;
	protected $image_library_path;
	protected $thumbnail_prefix;
	protected $word_separator;
	protected $use_category_name;
	protected $reserved_category_word;
	protected $auto_convert_high_ascii;
	protected $new_posts_clear_caches;
	protected $auto_assign_cat_parents;
}
