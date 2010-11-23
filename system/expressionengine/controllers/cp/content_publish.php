<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Publishing Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Content_publish extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		// currently simply calls channel_select_list,
		// can be combined into one
		
		// @todo move ajax call from homepage elsewhere?
		// shouldn't need to parse this entire file to get that
	}
	
	// --------------------------------------------------------------------

	/**
	 * Entry Form
	 *
	 * Handles new and existing entries. Self submits to save.
	 *
	 * @access	public
	 * @return	void
	 */
	function entry_form()
	{
		/*
		check_permissions();
		
		load_channel_data();
		
		set_field_settings();
		set_field_validation();
		
		// @todo setup validation for categories, etc?
		// @todo third party tabs
		
		if (run_validation() === TRUE)
		{
			merge_post_and_channel_data();
			// @todo look up how saving works for revisions
			save();
			exit;
		}
		
		check_for_id();
			check_for_autosave();
			check_for_revisions();
		
		prep_field_output();
		
		setup_layout();
		
		setup_view_vars();
		setup_javascript_vars();
		
		show_form();
		*/
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Autosave
	 *
	 * @access	public
	 * @return	void
	 */
	function autosave()
	{
		/*
		check_permissions();
		
		load_channel_data();	// @todo consider revisions?
		set_field_settings();	// @todo consider third party tabs
		
		save();
		*/
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Save Layout
	 *
	 * @access	public
	 * @return	void
	 */
	function save_layout()
	{
		// self explanatory - works ok
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * View Entry
	 *
	 * @access	public
	 * @return	void
	 */
	function view_entry()
	{
		// 
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Filemanager Endpoint
	 *
	 * Handles all file actions.
	 *
	 * @access	public
	 * @return	void
	 */
	function filemanager_endpoint()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Ajax Update Categories
	 *
	 * @access	public
	 * @return	void
	 */
	function ajax_update_cat_fields()
	{
		
	} // @confirm, need to be here?
	
	
	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Spellcheck iFrame
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck_iframe()
	{
		
	}
	
	// --------------------------------------------------------------------
	
	
}