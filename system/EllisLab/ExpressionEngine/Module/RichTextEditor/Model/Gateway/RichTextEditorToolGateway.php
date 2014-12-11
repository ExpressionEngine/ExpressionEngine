<?php

namespace EllisLab\ExpressionEngine\Module\RichTextEditor\Model\Gateway;

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
 * ExpressionEngine Tool Gateway for the Rich Text Editor
 *
 * A gateway allowing persistance of a tool in the Rich Text Editor.
 *
 * @package		ExpressionEngine
 * @subpackage	Rich Text Editor Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RichTextEditorToolGateway extends Gateway {

	protected static $_primary_key = 'tool_id';
	protected static $_table_name = 'rte_tools';

	protected $tool_id;
	protected $name;
	protected $class;
	protected $enabled;
}
