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
 * ExpressionEngine Form Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------


/**
 * Form Declaration
 *
 * Creates the opening portion of the form, EE CP Style
 *
 * @access	public
 * @param	string	the URI segments of the form destination
 * @param	array	a key/value pair of attributes
 * @param	array	a key/value pair hidden data
 * @return	string
 */

if (REQ == 'CP')
{
	function form_open($action = '', $attributes = array(), $hidden = array())
	{
		$CI =& get_instance();

		if (strpos($action, '://') === FALSE && strpos($action, BASE) !== 0)
		{
			$action = BASE.AMP.$action;
		}

		$action = ee()->uri->reformat($action);

		$form = '<form action="'.$action.'"';

		if (is_array($attributes))
		{
			if ( ! isset($attributes['method']))
			{
				$form .= ' method="post"';
			}

			foreach ($attributes as $key => $val)
			{
				$form .= ' '.$key.'="'.$val.'"';
			}
		}
		else
		{
			$form .= ' method="post" '.$attributes;
		}

		$form .= ">\n";

		if ( ! bool_config_item('disable_csrf_protection'))
		{
			if ( ! is_array($hidden))
			{
				$hidden = array();
			}

			$hidden['csrf_token'] = CSRF_TOKEN;
		}

		if (is_array($hidden) AND count($hidden > 0))
		{
			$form .= form_hidden($hidden)."\n";
		}

		return $form;
	}
}

// ------------------------------------------------------------------------

if (REQ == 'CP')
{
	/**
	 * Yes / No radio buttons
	 *
	 * Creates the typical EE yes/no options for a form
	 *
	 * @access	public
	 * @param	string	    the name of the input
	 * @param	string|bool checked state as 'y/n' or true/false
	 * @return	string      form inputs
	 */
	function form_yes_no_toggle($name, $value)
	{
		$insertion_point = strcspn($name, '['); // add y/n flag before arrays
		$name_no  = substr_replace($name, '_n', $insertion_point, 0);
		$name_yes = substr_replace($name, '_y', $insertion_point, 0);

		$value = is_bool($value) ? $value : $value == 'y';

		return
			form_radio($name, 'y', $value, 'id="'.$name_yes.'"').
			NBS.
			lang('yes', $name_yes).
			NBS.NBS.NBS.NBS.NBS.
			form_radio($name, 'n', ( ! $value), 'id="'.$name_no.'"').
			NBS.
			lang('no', $name_no);
	}
}

// ------------------------------------------------------------------------

/**
 * Parses the data from ee()->config->prep_view_vars() and returns
 * the appropriate form control.
 *
 * @access	public
 * @param	string	$name	The name of the field
 * @param	mixed[]	$details	The details related to the field
 *  	e.g.	'type'     => 'r'
 *  			'value'    => 'us'
 *  			'subtext'  => ''
 *  			'selected' => 'us'
 * @return	string	form input
 */
function form_preference($name, $details)
{
	$pref = '';
	switch ($details['type'])
	{
		// Select
		case 's':
			if (is_array($details['value']))
			{
				$pref = form_dropdown($name, $details['value'], $details['selected'], 'id="'.$name.'"');
			}
			else
			{
				$pref = '<span class="notice">'.lang('not_available').'</span>';
			}

			break;
		// Multi-Select
		case 'ms':
			$pref = form_multiselect($name.'[]', $details['value'], $details['selected'], 'id="'.$name.'" size="8"');
			break;
		// Radio
		case 'r':
			if (is_array($details['value']))
			{
				foreach ($details['value'] as $options)
				{
					$pref .= form_radio($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
				}
			}
			else
			{
				$pref = '<span class="notice">'.lang('not_available').'</span>';
			}

			break;
		// Textarea
		case 't':
			$pref = form_textarea($details['value']);
			break;
		// Input
		case 'i':
			$extra = ($name == 'license_number' && IS_CORE) ? array('value' => 'CORE LICENSE', 'disabled' => 'disabled') : array();
			$pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120), $extra));
			break;
		// Password
		case 'p':
			$pref = form_password(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120)));
			break;
		// Checkbox
		case 'c':
			foreach ((array)$details['value'] as $options)
			{
				$pref .= form_checkbox($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
			}
			break;
		// Pass the raw value through
		case 'v':
			$pref = $details['value'];
			break;
	}
	return $pref;
}

/* End of file EE_form_helper.php */
/* Location: ./system/expressionengine/helpers/EE_form_helper.php */