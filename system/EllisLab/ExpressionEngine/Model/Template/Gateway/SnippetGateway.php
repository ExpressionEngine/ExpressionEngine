<?php
namespace EllisLab\ExpressionEngine\Model\Template\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

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
 * ExpressionEngine Snippet Table
 *
 * @package		ExpressionEngine
 * @subpackage	Template\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SnippetGateway extends Gateway {

	protected static $_table_name = 'snippets';
	protected static $_primary_key = 'snippet_id';

	protected $snippet_id;
	protected $site_id;
	protected $snippet_name;
	protected $snippet_contents;

}
