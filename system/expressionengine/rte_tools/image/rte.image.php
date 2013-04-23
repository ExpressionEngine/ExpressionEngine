<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Image RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Image_rte {
	
	public $info = array(
		'name'			=> 'Image',
		'version'		=> '1.0',
		'description'	=> 'Inserts and manages image alignment in the RTE',
		'cp_only'		=> 'y'
	);
	
	private $EE;
	private $folders	= array();
	private $filedirs	= array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Globals we need
	 *
	 * @access	public
	 */
	function globals()
	{
		ee()->lang->loadfile('rte');
		return array(
			'rte.image'	=> array(
				'add'			=> lang('img_add'),
				'remove'		=> lang('img_remove'),
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
		return array();

		// @todo The following should already be loaded in the CP...
/*
		return array(
			'plugin'	=> 'ee_filebrowser',
			'ui'		=> 'dialog'
		);
*/
	}
	
	// --------------------------------------------------------------------

	/**
	 * Styles we need
	 *
	 * @access	public
	 */
	function styles()
	{
		# load the external file
		$styles	= file_get_contents( 'rte.image.css', TRUE );
		$theme	= ee()->session->userdata('cp_theme');
		$theme	= ee()->config->item('theme_folder_url').'cp_themes/'.($theme ? $theme : 'default').'/';
		return str_replace('{theme_folder_url}', $theme, $styles);
	}

	// --------------------------------------------------------------------

	/**
	 * JS Defintion
	 *
	 * @access	public
	 */
	function definition()
	{
		# load the external file
		return file_get_contents( 'rte.image.js', TRUE );
	}
	
} // END Image_rte

/* End of file rte.image.php */
/* Location: ./system/expressionengine/rte_tools/image/rte.image.php */