<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: mcp.query.php
-----------------------------------------------------
 Purpose: Query class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Query_mcp {

	var $version = '1.0';

	function Query_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
}
// END CLASS

/* End of file mcp.query.php */
/* Location: ./system/expressionengine/modules/query/mcp.query.php */