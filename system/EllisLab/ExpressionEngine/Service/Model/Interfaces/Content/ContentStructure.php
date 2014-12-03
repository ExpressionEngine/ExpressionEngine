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
 * ExpressionEngine Content Structure
 *
 * Classes implementing this should define the structure of a collection of data.
 * For example, Channel is the structural element for ChannelEntries.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Content
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface ContentStructure {

	/**
	 * Display the CP form form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return String   HTML for the entry / edit form
	 */
	public function getPublishForm($content);

	/**
	 * Delete settings and all content
	 *
	 * @return void
	 */
	public function delete();

}
