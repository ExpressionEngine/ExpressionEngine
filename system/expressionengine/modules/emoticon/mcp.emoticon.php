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
 File: mcp.emoticon.php
-----------------------------------------------------
 Purpose: Emoticon class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Emoticon_mcp {

	/**
	  *  Constructor
	  */
	function Emoticon_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		//  Onward!

		if ($switch)
		{
			switch($this->EE->input->get_post('M'))
			{
				default :	$this->show_simileys();
					break;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Show installed smileys
	  *
	  * This function is in progress
	  */
	function show_simileys($message = '')
	{
		die('not implemented');
	}
}

// END CLASS

/* End of file mcp.emoticon.php */
/* Location: ./system/expressionengine/modules/emoticon/mcp.emoticon.php */