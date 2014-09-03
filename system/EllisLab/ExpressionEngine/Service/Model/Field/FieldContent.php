<?php
namespace EllisLab\ExpressionEngine\Service\Model\Field;

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
 * ExpressionEngine Field Content Interface
 *
 * The content for a single field instance.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Field
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface FieldContent {

	/**
	 * Renders this field's content by replacing tags in a template.
	 *
	 * @param	ParsedTemplate|string	$template	The template, either a
	 *						ParsedTemplate object or a tagdata string, in which
	 *						this FieldContent will be rendered.
	 *
	 * @return	ParsedTemplate|string	The ParsedTemplate or tagdata string
	 *						with the relevant tags replaced.
	 */
	public function render($template);

	/**
	 * A link back to the FieldStructure that describes the structure of this
	 * piece of FieldContent.
	 *
	 * @return	FieldStructure	The FieldStructure object that describes this
	 *						FieldContent's structure (and stores its settings.)
	 */
	public function getFieldStructure();

	/**
	 * Save this piece of field content to the database.
	 *
	 * @return	void
	 *
	 * @throws	FieldContentInvalidException	If validation fails, then a
	 * 						FieldContentInvalidException will be thrown with
	 * 						errors.
	 */
	public function save();

	/**
	 * Validate this piece of field content to ensure that it is valid for
	 * saving.  On failure, throw an exception containing all error
	 * information.
 	 *
	 * @return void
	 *
 	 * @throws	FieldContentInvalidException	On validation failure a
	 * 						FieldContentInvalidException will be thrown with
	 * 						all relevant errors.
	 */
	public function validate();

	/**
	 * Delete this piece of FieldContent from the database.
	 *
	 * @return void
  	 */
	public function delete();
}
