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
 File: mcp.rss.php
-----------------------------------------------------
 Purpose: Rss class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Rss_mcp {

	function Rss_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
}
// END CLASS

/* End of file mcp.rss.php */
/* Location: ./system/expressionengine/modules/rss/mcp.rss.php */