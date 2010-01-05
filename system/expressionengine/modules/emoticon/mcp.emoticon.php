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
 http://expressionengine.com/docs/license.html
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
		$path = $this->EE->config->item('emoticon_path');

		$title = $this->EE->lang->line('emoticon_heading');

		$r = $this->EE->dsp->heading($title);

		$r .= $message;

		$r .= $this->EE->dsp->table('tableBorder', '0', '', '100%').
			  $this->EE->dsp->tr().
			  $this->EE->dsp->table_qcell('tableHeading',
								array(  NBS,
										$this->EE->lang->line('emoticon_glyph'),
										$this->EE->lang->line('emoticon_image'),
										$this->EE->lang->line('emoticon_width'),
										$this->EE->lang->line('emoticon_height'),
										$this->EE->lang->line('emoticon_alt')
									 )
								).
			  $this->EE->dsp->tr_c();

		require PATH_MOD.'emoticon/emoticons'.EXT;

		$i = 0;

		foreach ($smileys as $key => $val)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

			$r .= $this->EE->dsp->tr();

			$img = "<img src=\"".$path.$val['0']."\" width=\"".$val['1']."\" height=\"".$val['2']."\" alt=\"".$val['3']."\" border=\"0\" />";

			$r .= $this->EE->dsp->table_qcell($style, $img);
			$r .= $this->EE->dsp->table_qcell($style, $key);
			$r .= $this->EE->dsp->table_qcell($style, $val['0']);
			$r .= $this->EE->dsp->table_qcell($style, $val['1']);
			$r .= $this->EE->dsp->table_qcell($style, $val['2']);
			$r .= $this->EE->dsp->table_qcell($style, $val['3']);

			$r .= $this->EE->dsp->tr_c();
		}

		$r .= $this->EE->dsp->table_c();

		$this->EE->dsp->body = $r;
	}
}

// END CLASS

/* End of file mcp.emoticon.php */
/* Location: ./system/expressionengine/modules/emoticon/mcp.emoticon.php */