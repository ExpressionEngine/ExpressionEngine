<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Spam_ext {

	var $name			= 'Spam Filter';
	var $version		= M_E;
	var $settings_exist	= 'n';
	var $docs_url		= '';

	private $EE;
	private $module = 'spam';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Filter content for spam
	 * 
	 * @access public
	 * @return void
	 */
	public function filter_spam()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 * This extension is automatically installed with the Rich Text Editor module
	 */
	public function activate_extension()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 * This extension is automatically updated with the Rich Text Editor module
	 */
	public function update_extension( $current = FALSE )
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 * This extension is automatically disabled with the Rich Text Editor module
	 */
	public function disable_extension()
	{
		return TRUE;
	}

		// --------------------------------------------------------------------

	/**
	 * Uninstall Extension
	 * This extension is automatically uninstalled with the Rich Text Editor module
	 */
	public function uninstall_extension()
	{
		return TRUE;
	}

}

/* End of file ext.spam.php */
/* Location: ./system/expressionengine/modules/spam/ext.spam.php */
