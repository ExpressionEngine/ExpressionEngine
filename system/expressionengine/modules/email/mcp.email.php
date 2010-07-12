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
 File: mcp.contact_form.php
-----------------------------------------------------
 Purpose: Email class - CP
-----------------------------------------------------
 Last Updated:  2004-03-09 14:27:00 
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Email_mcp {
	
	function Email_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

}
// END CLASS

/* End of file mcp.email.php */
/* Location: ./system/expressionengine/modules/email/mcp.email.php */