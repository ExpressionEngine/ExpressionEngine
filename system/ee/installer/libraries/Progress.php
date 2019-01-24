<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Update Progress
 */
class Progress {

	public $prefix = '';
	protected $_config = array();

	public function __construct()
	{
		ee()->load->library('view');
	}

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
		return ee()->load->view('progress_header', $settings, TRUE);
	}
}

// END Progress class

class ProgressIterator extends ArrayIterator {

	public function __construct($arr)
	{
		parent::__construct($arr);
	}

	public function current()
	{
		$current_step = $this->key();
		$total_steps = $this->count();

		if (isset(ee()->progress) && php_sapi_name() !== 'cli')
		{
			ee()->progress->update_state("Step $current_step of $total_steps");
		}

		return parent::current();
	}
}

// END ProgressIterator class

// EOF
