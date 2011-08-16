<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


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

/**
 * Parse the form attributes
 *
 * Helper function used by some of the form helpers
 *
 * @access	private
 * @param	array
 * @param	array
 * @return	string
 */
function _parse_form_attributes($attributes, $default)
{
	if (is_array($attributes))
	{
		foreach ($default as $key => $val)
		{
			if (isset($attributes[$key]))
			{
				$default[$key] = $attributes[$key];
				unset($attributes[$key]);
			}
		}

		if (count($attributes) > 0)
		{
			$default = array_merge($default, $attributes);
		}
	}
	
	// EE addition
	if (isset($default['name']) && ! isset($default['id']))
	{
		$default['id'] = $default['name'];
	}

	$att = '';

	foreach ($default as $key => $val)
	{
		if ($key == 'value')
		{
			$val = form_prep($val, $default['name']);
		}

		$att .= $key . '="' . $val . '" ';
	}

	return $att;
}

// ------------------------------------------------------------------------

/* End of file EE_form_helper.php */
/* Location: ./system/expressionengine/helpers/EE_form_helper.php */