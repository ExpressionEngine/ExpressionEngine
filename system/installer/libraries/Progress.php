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
 * ExpressionEngine Update Progress Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Progress {

	public $prefix = '';
	protected $_config = array();

	/**
	 * Updates the current state
	 *
	 * Ideally we could use memcached or apc - but we can't, so we're stuck
	 * with a file based solution.  Using native sessions to avoid file
	 * permission problems.
	 *
	 * @param	string
	 * @return	void
	 */
	public function update_state($new_state)
	{
		session_start();
		$_SESSION['_progress_state'] = $new_state;
		session_write_close();
	}

	// --------------------------------------------------------------------

	/**
	 * Get State
	 *
	 * Returns the current status message
	 *
	 * @return	string
	 */
	public function get_state()
	{
		session_start();
		return isset($_SESSION['_progress_state']) ? $this->prefix.$_SESSION['_progress_state'] : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Clear State
	 *
	 * Clears any latent status message still present in the PHP session
	 *
	 * @return	string
	 */
	public function clear_state()
	{
		session_start();
		unset ($_SESSION['_progress_state']);
		session_write_close();
	}

	// --------------------------------------------------------------------

	/**
	 * Gets the proper js and meta tag
	 *
	 * Use this on the intermediate page to make it non-js compatible
	 *
	 * @param	mixed
	 * @return	string
	 */
	public function fetch_progress_header($settings)
	{
		$EE =& get_instance();
		return $EE->load->view('progress_header', $settings, TRUE);
	}
}

// END Progress class

class ProgressIterator extends ArrayIterator {

	public function __construct($arr)
	{
		parent::__construct($arr);
		$this->EE =& get_instance();
	}

	public function current()
	{
		$current_step = $this->key();
		$total_steps = $this->count();

		ee()->progress->update_state("Step $current_step of $total_steps");

		return parent::current();
	}
}

// END ProgressIterator class


/* End of file Progress.php */
/* Location: ./system/expressionengine/installer/libraries/Progress.php */