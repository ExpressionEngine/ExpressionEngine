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
 File: mcp.search.php
-----------------------------------------------------
 Purpose: Search class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

/**
  *  Constructor
  */
class Search_mcp {
	
	function Search_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
}
// END CLASS

/* End of file mcp.search.php */
/* Location: ./system/expressionengine/modules/search/mcp.search.php */