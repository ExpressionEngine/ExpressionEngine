<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_5_0;

/**
 * Update
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
		$steps = new \ProgressIterator(
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

		$msm_config = new \MSM_Config();
		$msm_config->update_site_prefs(array(
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
