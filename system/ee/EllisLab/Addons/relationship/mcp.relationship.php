<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

 /**
 * Relationship Fieldtype control panel
 */
class Relationship_mcp {

	public function ajaxFilter()
	{
		ee()->load->library('EntryList');
		ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
	}

}

// EOF
