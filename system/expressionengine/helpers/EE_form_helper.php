<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
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
		
		$action = ( strpos($action, '://') === FALSE) ? BASE.AMP.$action : $action;

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
		
		if ($CI->config->item('secure_forms') == 'y')
		{
			if ( ! is_array($hidden))
			{
				$hidden = array();
			}
			
			$hidden['XID'] = XID_SECURE_HASH;
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

/* End of file EE_form_helper.php */
/* Location: ./system/expressionengine/helpers/EE_form_helper.php */