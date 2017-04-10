<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.5
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Relationship {

	/**
	 * AJAX endpoint for filtering a Relationship field on the publish form
	 */
	public function entryList()
	{
		ee()->load->library('EntryList');
		ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
	}
}

// EOF
