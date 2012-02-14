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

Class Image_rte {
	
	public $info = array(
		'name'			=> 'Image',
		'version'		=> '1.0',
		'description'	=> 'Inserts and manages image alignment in the RTE',
		'cp_only'		=> 'y'
	);
	
	private $EE;
	private $folders	= array();
	private $filedirs	= array();
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// load in the file locations
		$this->_get_file_locations();
	}

	/** -------------------------------------
	/**  Globals we need defined
	/** -------------------------------------*/
	function globals()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.image'	=> array(
				'add'			=> lang('img_add'),
				'delete'		=> lang('img_delete'),
				'align_left'	=> lang('img_align_left'),
				'align_center'	=> lang('img_align_center'),
				'align_right'	=> lang('img_align_right'),
				'wrap_left'		=> lang('img_wrap_left'),
				'wrap_none'		=> lang('img_wrap_none'),
				'wrap_right'	=> lang('img_wrap_right'),
				'caption_text'	=> lang('rte_image_caption'),
				'center_error'	=> lang('rte_center_error'),
				'folders'		=> $this->folders,
				'filedirs'		=> $this->filedirs
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
		$styles	= file_get_contents( 'rte.image.css', TRUE );
		$theme	= $this->EE->session->userdata('cp_theme');
		$theme	= $this->EE->config->item('theme_folder_url').'cp_themes/'.($theme ? $theme : 'default').'/';
		return str_replace('{theme_folder_url}', $theme, $styles);
	}

	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		# load the external file
		return file_get_contents( 'rte.image.js', TRUE );
	}
	
	/** -------------------------------------
	/**  Collect the folders
	/** -------------------------------------*/
	private function _get_file_locations()
	{
		$this->EE->load->model('file_upload_preferences_model');
		$dirs = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));
		
		$domain = $this->EE->config->item('site_url');
		
		foreach( $dirs as $d )
		{
			# create the filedir reference
			$filedir	= '{filedir_'.$d['id'].'}';
			$this->folders[$d['url']]	= $filedir;
			$this->filedirs[$filedir]	= $d['url'];
		}
	}

} // END Image_rte

/* End of file rte.image.php */
/* Location: ./system/expressionengine/rte_tools/image/rte.image.php */