<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
