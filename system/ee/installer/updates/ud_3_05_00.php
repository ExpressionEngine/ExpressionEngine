<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.5.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'addEmailSettings'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addEmailSettings()
	{
		$email_newline = $this->setEmailNewlineSafely(ee()->config->item('email_newline'));
		$email_smtp_crypto = (string) ee()->config->item('email_smtp_crypto');

		ee()->config->update_site_prefs(array(
			'email_newline' => $email_newline,
			'email_smtp_crypto' => $email_smtp_crypto
			),
			'all'
		);
	}

	/**
	 * see Config::setEmailNewline() which sets the value on *load*.
	 * This is basically the opposite, saving it a safe and portable single-quoted format
	 *
	 * @param string $newline Newline character(s)
	 * @param string $default default Newline character
	 * @return string Single-quoted newline character representation for storage
	 */
	private function setEmailNewlineSafely($newline, $default = '\n')
	{
		switch ($newline)
		{
			case '\n':
			case "\n":
				return '\n';
			case '\r\n':
			case "\r\n":
				return '\r\n';
			case '\r':
			case "\r":
				return '\r';
			default:
				return $default;
		}
	}
}

// EOF
