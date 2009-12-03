<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2009, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.member.php
-----------------------------------------------------
 Purpose: Member management system - CP
 Note: Because member management is so tightly
 integrated into the core system, most of the
 member functions are contained in the core and cp
 files.
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Member_mcp {

	function Member_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
}
// END CLASS

/* End of file mcp.member.php */
/* Location: ./system/expressionengine/modules/member/mcp.member.php */