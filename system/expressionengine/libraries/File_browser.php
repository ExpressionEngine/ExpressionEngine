<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine File_browser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	File_browser
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class File_browser {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_css();
		$this->_javascript();
		$this->render();
	}
	
	public function render($config = array())
	{
		// Are we on the publish page? If so, go ahead and load up the publish
		// page javascript files
		if (empty($config) OR $config['publish'] === TRUE)
		{
			$this->EE->javascript->set_global(array(
				'filebrowser' => array(
					'publish' => TRUE
				)
			));
			
			$this->EE->cp->add_js_script(array(
				'file' => array(
					'files/publish_fields'
				)
			));
		}
		// No? Hmm, well this is an odd situation, we want our devs to have
		// the control here, so we're going to need a few things from them
		// - *Trigger*, obviously we need to know what we're listening for
		// - Field Name, the field we're dicking with, but my assumption is we leave this the hell alone
		// - *Settings*, if you need to restrict it to a particular directory or type
		// - *Callback*, what to do when a file is selected
		else
		{
			
		}
	}
	
	private function _css()
	{
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/file_browser.css'));
	}

	private function _javascript($endpoint_url = 'C=content_publish&M=filemanager_actions')
	{
		// Include dependencies
		$this->EE->cp->add_js_script(array(
			'file'		=> array(
				'underscore'
			),
			'plugin'	=> array(
				'scrollable',
				'scrollable.navigator',
				'ee_filebrowser',
				'ee_fileuploader',
				'tmpl'
			)
		));
		
		$this->EE->load->helper('html');
		
		$this->EE->javascript->set_global(array(
			'lang' => array(
				'resize_image'		=> $this->EE->lang->line('resize_image'),
				'or'				=> $this->EE->lang->line('or'),
				'return_to_publish'	=> $this->EE->lang->line('return_to_publish')
			),
			'filebrowser' => array(
				'endpoint_url'		=> $endpoint_url,
				'window_title'		=> lang('file_manager'),
				'next'				=> anchor(
					'#', 
					img(
						$this->EE->cp->cp_theme_url . 'images/pagination_next_button.gif',
						array(
							'alt' => lang('next'),
							'width' => 13,
							'height' => 13
						)
					),
					array(
						'class' => 'next'
					)
				),
				'previous'			=> anchor(
					'#', 
					img(
						$this->EE->cp->cp_theme_url . 'images/pagination_prev_button.gif',
						array(
							'alt' => lang('previous'),
							'width' => 13,
							'height' => 13
						)
					),
					array(
						'class' => 'previous'
					)
				)
			),
			'fileuploader' => array(
				'window_title'		=> lang('file_upload'),
				'delete_url'		=> 'C=content_files&M=delete_files'
			)
		));
	}

}

// END File_browser class

/* End of file File_browser.php */
/* Location: ./system/expressionengine/libraries/File_browser.php */