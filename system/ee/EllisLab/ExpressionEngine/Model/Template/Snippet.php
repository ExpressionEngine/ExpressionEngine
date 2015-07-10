<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Snippet Model
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Snippet extends Model {

	protected static $_primary_key = 'snippet_id';
	protected static $_table_name = 'snippets';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected $snippet_id;
	protected $site_id;
	protected $snippet_name;
	protected $snippet_contents;

}
