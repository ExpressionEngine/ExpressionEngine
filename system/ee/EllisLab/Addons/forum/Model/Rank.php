<?php

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Rank Model for the Forum
 *
 * A model representing a rank in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Rank extends Model {

	protected static $_primary_key = 'rank_id';
	protected static $_table_name = 'forum_ranks';

	protected static $_typed_columns = array(
		'rank_min_posts' => 'int'
	);

	protected static $_validation_rules = array(
		'rank_title'     => 'required',
		'rank_min_posts' => 'required',
		'rank_stars'     => 'required',
	);

	protected $rank_id;
	protected $rank_title;
	protected $rank_min_posts;
	protected $rank_stars;

}

// EOF
