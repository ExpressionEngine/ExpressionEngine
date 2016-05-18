<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license     https://expressionengine.com/license
 * @link        https://ellislab.com
 * @since       Version 3.2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine URL Fieldtype Class
 *
 * @package     ExpressionEngine
 * @subpackage  Fieldtypes
 * @category    Fieldtypes
 * @author      EllisLab Dev Team
 * @link        https://ellislab.com
 */
class Url_Ft extends EE_Fieldtype {

	/**
	 * @var array $info Legacy Fieldtype info array
	 */
	public $info = array(
		'name'    => 'URL',
		'version' => '1.0.0'
	);

	/**
	 * @var bool $has_array_data Whether or not this Fieldtype is setup to parse as a tag pair
	 */
	public $has_array_data = FALSE;

	/**
	 * Validate Field
	 *
	 * 	Note, we can't use filter_var() here, as FILTER_VALIDATE_URL will not
	 *  validate internationalized URLs that contain non-ASCII characters.
	 *  Plus, FILTER_VALIDATE_URL uses parse_url() internally for some bits
	 *  anyway, go figure.
	 *
	 * @param  array  $data  Field data
	 * @return mixed  TRUE when valid, an error string when not
	 */
	public function validate($data)
	{
		ee()->lang->loadfile('fieldtypes');

		if ($data == '')
		{
			return TRUE;
		}

		// we will save the URL ready to use in HTML attributes, so validate on how it will be stored
		$data = $this->prepForStorage($data);

		// nothing gets past this
		if ( ! $parsed_url = parse_url($data))
		{
			return lang('url_ft_invalid_url');
		}

		// is the scheme valid?
		if ( ! isset($parsed_url['host']) OR ! isset($parsed_url['scheme']))
		{
			// check for protocol relativity allowance before bailing
			if (
				in_array('//', $this->get_setting('allowed_url_schemes'))
				&& strncasecmp($data, '//', 2) === 0
				)
			{
				// I'll allow it!
				return TRUE;
			}

			return sprintf(lang('url_ft_invalid_url_scheme'), '<code>'.implode('</code>, <code>', $this->get_setting('allowed_url_schemes')).'</code>');
		}

		$scheme = $parsed_url['scheme'].'://';

		if ( ! in_array($scheme, $this->get_setting('allowed_url_schemes')))
		{
			return sprintf(lang('url_ft_invalid_url_scheme'), '<code>'.implode('</code>, <code>', $this->get_setting('allowed_url_schemes')).'</code>');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field
	 *
	 * @param  array   $data  Field data
	 * @return string  Prepped Form field
	 */
	public function save($data)
	{
		return $this->prepForStorage($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 *
	 * @param  array   $data  Field data
	 * @return string  Form field
	 */
	public function display_field($data)
	{
		$default_scheme = $this->get_setting('url_scheme_placeholder');

		$field = array(
			'name'        => $this->field_name,
			'value'       => $data,
			'placeholder' => $default_scheme
		);

		if ($this->get_setting('field_disabled'))
		{
			$field['disabled'] = 'disabled';
		}

		return form_input($field);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 *
	 * @param  string  $data     The URL
	 * @param  array   $params   Variable tag parameters
	 * @param  mixed   $tagdata  The tagdata if a var pair, FALSE if not
	 * @return string  Parsed string
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return ee()->functions->encode_ee_tags($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Settings
	 *
	 * @param  array  $data  Field Settings
	 * @return array  Field options
	 */
	public function display_settings($data)
	{
		ee()->lang->loadfile('fieldtypes');

		$settings = array(
			array(
				'title' => 'url_ft_allowed_url_schemes',
				'fields' => array(
					'allowed_url_schemes' => array(
						'type' => 'checkbox',
						'choices' => $this->getSchemes(),
						'value' => (isset($data['allowed_url_schemes'])) ? $data['allowed_url_schemes'] : $this->getSchemes(TRUE),
						'required' => TRUE
					)
				)
			),
			array(
				'title' => 'url_ft_url_scheme_placeholder',
				'desc' => 'url_ft_url_scheme_placeholder_desc',
				'fields' => array(
					'url_scheme_placeholder' => array(
						'type' => 'select',
						'choices' => $this->getSchemes(),
						'value' => (isset($data['url_scheme_placeholder'])) ? $data['url_scheme_placeholder'] : '',
						'required' => TRUE
					)
				)
			)
		);

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_url' => array(
			'label'    => 'field_options',
			'group'    => 'url',
			'settings' => $settings
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 *
	 * @param  array  $data  Field data
	 * @return array  Settings to save
	 */
	public function save_settings($data)
	{
		$defaults = array(
			'allowed_url_schemes' => $this->getSchemes(TRUE),
			'url_scheme_placeholder' => ''
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	// --------------------------------------------------------------------

	/**
	 * Accept all content types.
	 *
	 * @param  string  The name of the content type
	 * @return bool    Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Schemes
	 *
	 * @param  bool   $only_defaults  Whether or not to only return the default set
	 * @return array  Valid URL Scheme Options
	 */
	private function getSchemes($only_defaults = FALSE)
	{
		$protocols = array(
			'http://'  => 'http://',
			'https://' => 'https://'
		);

		if ($only_defaults)
		{
			return $protocols;
		}

		$protocols += array(
			'//'      => '// ('.lang('url_ft_protocol_relative_url').')',
			'ftp://'  => 'ftp://',
			'sftp://' => 'sftp://',
			'ssh://'  => 'ssh://',
		);

		return $protocols;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep For Storage
	 *
	 * @param  string  $url  The URL to store
	 * @return string  A sanitized string, ready for storage and use in HTML attributes
	 */
	private function prepForStorage($url)
	{
		// disable $double_encode so entities don't bubble out of control on edits
		return htmlspecialchars($url, ENT_QUOTES, 'UTF-8', FALSE);
	}

	// --------------------------------------------------------------------
}
// END CLASS

// EOF
