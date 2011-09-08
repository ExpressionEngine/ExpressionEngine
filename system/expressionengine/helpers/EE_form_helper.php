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

/* End of file EE_form_helper.php */
/* Location: ./system/expressionengine/helpers/EE_form_helper.php */