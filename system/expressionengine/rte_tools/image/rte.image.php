<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2004 - 2011 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: rte.image.php
-----------------------------------------------------
 Purpose: Image RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_tool_name'				=> 'Image',
	'rte_tool_version'			=> '1.0',
	'rte_tool_author'			=> 'Aaron Gustafson',
	'rte_tool_author_url'		=> 'http://easy-designs.net/',
	'rte_tool_description'		=> 'Inserts and manages image alignment in the RTE',
	'rte_tool_definition'		=> Image_rte::definition()
);

Class Image_rte {
	
	private $EE;
	
	# should this be shown on the frontend?
	public	$frontend = 'n';
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/** -------------------------------------
	/**  Globals we need defined
	/** -------------------------------------*/
	function globals()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.image'	=> array(
				'add'			=> lang('insert_img'),
				'caption_text'	=> lang('rte_image_caption'),
				'center_error'	=> lang('rte_center_error')
			)
		);
	}
	
	/** -------------------------------------
	/**  Libraries we need loaded
	/** -------------------------------------*/
	function libraries()
	{
		return array(
			'plugin'	=> 'ee_filebrowser',
			'ui'		=> 'dialog'
		);
	}
	
	/** -------------------------------------
	/**  Styles we need loaded
	/** -------------------------------------*/
	function styles()
	{
		# load the external file
		return file_get_contents( 'rte.image.css', TRUE );
	}

	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		# load the external file
		return file_get_contents( 'rte.image.js', TRUE );
	}

} // END Image_rte

/* End of file rte.image.php */
/* Location: ./system/expressionengine/rte_tools/image/rte.image.php */