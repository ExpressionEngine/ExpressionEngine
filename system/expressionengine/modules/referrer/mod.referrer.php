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
 * ExpressionEngine Referrer Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Referrer {

	var $return_data  = '';

	function Referrer()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->referrer_rows();
	}

	// --------------------------------------------------------------------

	/**
	  *  Show referers
	  */
	function referrer_rows()
	{
		$switch = array();
		
		$pop =  (ee()->TMPL->fetch_param('popup') == 'yes') ? ' target="_blank" ' : '';		
				

		//  Build query
		$sql = "SELECT * FROM exp_referrers ";

		$sql .= "WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' ORDER BY ref_id desc ";
		
	
		if ( ! ee()->TMPL->fetch_param('limit'))
		{
			$sql .= "LIMIT 100";
		}
		else
		{
			$sql .= "LIMIT ".ee()->TMPL->fetch_param('limit');
		}

		$query = ee()->db->query($sql);
		$site_url = ee()->config->item('site_url');
		
		//  Parse result
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$tagdata = ee()->TMPL->tagdata; 

				//  Parse "single" variables

				foreach (ee()->TMPL->var_single as $key => $val)
				{				
					//  parse {switch} variable
					if (strncmp($key, 'switch', 6) == 0)
					{
						$sparam = ee()->functions->assign_parameters($key);
						
						$sw = '';
	
						if (isset($sparam['switch']))
						{
							$sopt = explode("|", $sparam['switch']);
							
							if (count($sopt) == 2)
							{
								if (isset($switch[$sparam['switch']]) AND $switch[$sparam['switch']] == $sopt['0'])
								{
									$switch[$sparam['switch']] = $sopt['1'];
									
									$sw = $sopt['1'];									
								}
								else
								{
									$switch[$sparam['switch']] = $sopt['0'];
									
									$sw = $sopt['0'];									
								}
							}
						}
						
						$tagdata = ee()->TMPL->swap_var_single($key, $sw, $tagdata);
					}
								
				
					//  {ref_from}
					
					if ($key == "ref_from")
					{
						$from = '<a href="'.$this->encode_ee_tags($row['ref_from']).'"'.$pop.'>'.$this->encode_ee_tags($row['ref_from']).'</a>';
					
						$tagdata = ee()->TMPL->swap_var_single($val, $from, $tagdata);
					}

					//  {ref_to}

					if ($key == "ref_to")
					{		
						$to_short = str_replace($site_url, '', $row['ref_to']);
					
						$to  = '<a href="'.$this->encode_ee_tags($row['ref_to']).'">'.$this->encode_ee_tags($to_short).'</a>';
					
						$tagdata = ee()->TMPL->swap_var_single($val, $to, $tagdata);
					}

					//  {ref_ip}

					if ($key == "ref_ip")
					{
						$ip = ( ! isset($row['ref_ip'])) ? '-' : $row['ref_ip'];
						
						$tagdata = ee()->TMPL->swap_var_single($val, $ip, $tagdata);
					}

					//  {ref_agent}

					if ($key == "ref_agent")
					{
						$agent = ( ! isset($row['ref_agent'])) ? '-' : $this->encode_ee_tags($row['ref_agent']);
						
						$tagdata = ee()->TMPL->swap_var_single($val, $agent, $tagdata);
					}

					//  {ref_agent_short}

					if ($key == "ref_agent_short")
					{
						$agent = ( ! isset($row['ref_agent'])) ? '-' : preg_replace("/(.+?)\s+.*/", "\\1", $this->encode_ee_tags($row['ref_agent']));
						
						$tagdata = ee()->TMPL->swap_var_single($val, $agent, $tagdata);
					}

					//  {ref_date}

					if (strncmp($key, 'ref_date', 8) == 0)
					{
						if ( ! isset($row['ref_date']) OR $row['ref_date'] == 0)
						{
							$date = '-';
						}
						else
  						{
							$date = ee()->localize->format_date($val, $row['ref_date']);
						}
						$tagdata = ee()->TMPL->swap_var_single($key, $date, $tagdata);
					}					
				}
				
				$this->return_data .= $tagdata;
			}

		}
		
	}

	
	
	/**
	  *  Encode EE Tags
	  */
	function encode_ee_tags($str)
	{
		if ($str != '')
		{
			$str = str_replace('{', '&#123;', $str);
			$str = str_replace('}', '&#125;', $str);
		}
		
		return $str;
	}



}
// END CLASS

/* End of file mod.referrer.php */
/* Location: ./system/expressionengine/modules/referrer/mod.referrer.php */