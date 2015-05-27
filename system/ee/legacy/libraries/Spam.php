<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Spam {

	function __construct()
	{
		ee()->load->library('addons');
		$installed = ee()->addons->get_installed();

		if (empty($installed['spam']))
		{
			$this->installed = FALSE;
		}
		else
		{
			require_once PATH_MOD . 'spam/libraries/Spam_core.php';
			$this->spam = new Spam_core();
			$this->installed = TRUE;
		}
	}

	/**
	 * Expose the Spam modules classify method
	 * Will always return false if the Spam Module isn't installed
	 * 
	 * @param string  The content to classify
	 * @access public
	 * @return bool  Returns TRUE if content is spam
	 */
	public function classify($source)
	{
		if ($this->installed === FALSE)
		{
			// If the spam module isn't installed everything is ham!
			return FALSE;
		}

		return $this->spam->classify($source);
	}

	/**
	 * Expose the Spam Module's moderate method
	 * If Spam Module isn't installed do nothing.
	 * 
	 * @param mixed $class   Class to call for re-inserting false-positives
	 * @param mixed $method  Method to call for re-inserting false-positives
	 * @param mixed $data    Data to call as argument
	 * @param mixed $doc     The document that was classified as spam
	 * @access public
	 * @return void
	 */
	public function moderate($file, $class, $method, $data, $doc)
	{
		if ($this->installed === FALSE)
		{
			return;
		}

		$author = ee()->session->userdata('member_id');

		return $this->spam->moderate_content($file, $class, $method, $data, $doc, $author);
	}

}
// END CLASS

/* End of file Spam.php */
/* Location: ./system/expressionengine/libraries/Spam.php */
