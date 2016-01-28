<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Variables Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Variables extends ProfilerSection {

	/**
	 * @var userdata bits that we don't want to display
	 */
	protected $skip = array('password', 'salt', 'unique_id', 'session_id', 'fingerprint');

	/**
	 * Get a brief text summary (used for tabs, labels, etc.)
	 *
	 * @return  string  the section summary
	 **/
	public function getSummary()
	{
		return lang('profiler_'.$this->section_name);
	}

	/**
	 * Gets the view name needed to render the section
	 *
	 * @return string  the view/name
	 **/
	public function getViewName()
	{
		return 'profiler/section/var-list';
	}

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData($data)
	{
		extract($data);

		$data['server'] = $this->prepServerData($server);
		$data['cookie'] = $this->prepData($cookie);
		$data['get'] = $this->prepData($get);
		$data['post'] = $this->prepData($post);
		$data['userdata'] = $this->prepData($userdata);

		$this->data = array('performance' => $data);
	}

	private function prepServerData($server)
	{
		$prepped_data = array();

		foreach(array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD', 'HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR') as $header)
		{
			$prepped_data[$header] = (isset($server[$header])) ? htmlspecialchars($server[$header]) : '';
		}

		return $prepped_data;
	}

	private function prepData($data)
	{
		$prepped_data = array();

		foreach ($data as $key => $val)
		{
			if (in_array($key, $this->skip))
			{
				continue;
			}

			$prepped_data[$key] = htmlspecialchars(stripslashes(print_r($val, TRUE)));
		}

		return $prepped_data;
	}
}

// EOF
