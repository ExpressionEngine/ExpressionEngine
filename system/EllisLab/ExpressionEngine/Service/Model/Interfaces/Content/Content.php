<?php

namespace EllisLab\ExpressionEngine\Service\Model\Interfaces\Content;

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
 * ExpressionEngine Content Interface
 *
 * Represents a piece of content in ExpressionEngine (e.g. a Channel Entry)
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Content
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface Content {

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template);

	/**
	 * A link back to the owning Structure that defines the structure of this
	 * piece of content.  (A link back to Channel.)
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure();

	/**
	 * Saves this piece of content after being populated from a form.
	 *
	 * @return	void
	 *
	 * @throws	ContentInvalidException	If content fails to validate a
	 *						ContentInvalidException will be thrown with errors
	 *						on the exception object.
	 */
	public function save();

	/**
	 * Deletes a piece of content, removing it from the db.
	 *
	 * @return	void
	 */
	public function delete();

}
