<?php

namespace EllisLab\ExpressionEngine\Module\RichTextEditor\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Tool Model for the Rich Text Editor
 *
 * A model representing a tool in the Rich Text Editor.
 *
 * @package		ExpressionEngine
 * @subpackage	Rich Text Editor Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RichTextEditorTool extends Model {

	static protected $_primary_key = 'tool_id';
	static protected $_gateway_names = array('RichTextEditorToolGateway');

	protected $tool_id;
	protected $name;
	protected $class;
	protected $enabled;
}
