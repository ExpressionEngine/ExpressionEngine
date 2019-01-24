<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Template;

/**
 * Template Variables Service
 */
abstract class Variables {

	/**
	 * @var array of variable names to allow form prepped versions to be set (typically set by user input)
	 */
	protected $form_vars = array();

	/**
	 * @var array parsed variables for this type
	 */
	protected $variables = array();

	/**
	 * Constructor
	 *
	 * 	Child classes should always parent::__construct()
	 *
	 */
	public function __construct()
	{
		// Load the typography class
		ee()->load->library('typography');
		ee()->typography->initialize();
	}

	/**
	 * Get Template Variables
	 *
	 *   Child classes must implement this to be useful, and should follow
	 *   the pattern below to prevent cycles if requested multiple
	 *   times from the same instance.
	 *
	 * @return array Array of variables for the Template parser
	 */
	public function getTemplateVariables()
	{
		if ( ! empty($this->variables))
		{
			return $this->variables;
		}

		// set variables here in child classes
		$this->variables = array();

		return $this->variables;
	}

	/**
	 * Get one variable
	 *
	 *   This allows reaching into the variables by the caller, without
	 *   having to know how we are storing the variables under the hood
	 *   and without having to request all of the variables.
	 *
	 * @return mixed value of the variable, false if it doesn't exist
	 */
	public function getVariable($name)
	{
		$variables = $this->getTemplateVariables();
		return (array_key_exists($name, $variables)) ? $variables[$name] : FALSE;
	}

	/**
	 * Set Form Variables, e.g. from POST
	 *
	 *   Child classes must whitelist what variables are accepted in the $form_vars property
	 *   so malicious users cannot override other template variables
	 *
	 * @param  array $variables A key => val array of data, e.g. POST
	 * @return array Template Variables array with Form Prepped values
	 */
	public function setFormVariables($variables)
	{
		ee()->load->helper('form');

		foreach ($this->form_vars as $name => $value)
		{
			$this->form_vars[$name] = (isset($variables[$name])) ? $this->formPrep($variables[$name]) : '';
		}

		return $this->form_vars;
	}

	/**
	 * Get Form Variables
	 *
	 *   If merged with getTemplateVariables, make sure to merge
	 *   this last, so it overwrites variables with the same name
	 *   that are prepped for rendering, and not for use in forms
	 *
	 * @return array Template Variables array with Form Prepped values
	 */
	public function getFormVariables()
	{
		return array();
	}

	/**
	 * protect
	 * - makes content safe for output: disallows HTML & ExpressionEngine tags
	 * @param  string  $str         contents to protect
	 * @return string               protected string
	 */
	protected function protect($str)
	{
		return (string) ee('Format')->make('Text', $str)->convertToEntities()->encodeEETags();
	}

	/**
	 * encode ExpressionEngine tags
	 * @param  string $str contents to encode
	 * @return string      contents with { } encoded as HTML entities
	 */
	protected function encodeEETags($str)
	{
		return (string) ee('Format')->make('Text', $str)->encodeEETags();
	}

	/**
	 * Form prep
	 *
	 * Time saver security method. form_prep() on its own is also used in the CP, but on the
	 * front end, we need to encode EE tags as well.
	 *
	 * @param  string  $str            contents to prep for form inputs
	 * @param  boolean $encode_ee_tags Whether or not to encode ExpressionEngine tags
	 * @return return                  contents prepped for use in form inputs
	 */
	protected function formPrep($str, $encode_ee_tags = TRUE)
	{
		return (string) ee('Format')->make('Text', form_prep($str))->encodeEETags();
	}

	/**
	 * date
	 * @param  mixed $date DateTime object or int timestamp
	 * @return int timestamp
	 */
	protected function date($date)
	{
		return is_object($date) ? $date->getTimestamp() : intval($date);
	}

	/**
	 * typography
	 * @param  string $str content to perform typography on
	 * @return string parsed contents
	 */
	protected function typography($str, $typography_prefs)
	{
		$str = ee()->typography->parse_type($str, $typography_prefs);

		if (bool_config_item('enable_censoring'))
		{
			$str = ee('Format')->make('Text', $str)->censor();
		}

		return $str;
	}

	/**
	 * url
	 * @param  string $url Unvalided URL, possibly missing protocol
	 * @return string prepped and valid URL
	 */
	protected function url($url)
	{
		return (string) ee('Format')->make('Text', $url)->url();
	}

	/**
	 * urlSlug
	 * @param  string $str contents
	 * @return string URL slug, built with site prefs
	 */
	protected function urlSlug($str)
	{
		return (string) ee('Format')->make('Text', $str)->urlSlug();
	}

	/**
	 * pathVariable
	 * @param  string $append String to add to the end of the URL
	 * @return array a Template::parse_variables() path variable array
	 */
	protected function pathVariable($append)
	{
		return array($append, array('path_variable' => TRUE));
	}

	/**
	 * action
	 * @param string $class action class
	 * @param string $method action method
	 * @param array $params optional URL parameters
	 * @return string ACTion URL
	 */
	protected function action($class, $method, $params)
	{
		if ( ! isset($params['return']) OR $params['return'] === '')
		{
			$params['return'] = ee()->uri->uri_string;
		}

		if ($params['return'] === FALSE)
		{
			unset($params['return']);
		}

		if ( ! isset($params['token']))
		{
			$params['token'] = CSRF_TOKEN;
		}

		if ($params['token'] === FALSE)
		{
			unset($params['token']);
		}

		$query_string = http_build_query($params);
		$base = ee()->functions->fetch_site_index(0,0);

		if (strpos($base, '?') === FALSE)
		{
			$base .= '?';
		}
		else
		{
			$base .= '&';
		}

		return $base.'ACT='.ee()->functions->fetch_action_id($class, $method).'&'.$query_string;
	}
}
// END CLASS

// EOF
