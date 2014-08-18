<?php
namespace EllisLab\ExpressionEngine\Module\RichTextEditor\Model;

use EllisLab\ExpressionEngine\Model\Model;

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
 * ExpressionEngine Toolset Model for the Rich Text Editor
 *
 * A model representing a user toolset in the Rich Text Editor.
 *
 * @package		ExpressionEngine
 * @subpackage	Rich Text Editor Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RichTextEditorToolset extends Model {
	protected $_primary_key = 'toolset_id';
	protected $_gateway_names = array('RichTextEditorToolsetGateway');

	protected $_relationships = array(
		'Member' => array(
			'type' => 'many_to_one'
		)
	);

	protected $toolset_id;
	protected $member_id;
	protected $name;
	protected $tools;
	protected $enabled;
}
