<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: mod.referrer.php
-----------------------------------------------------
 Purpose: Referrer tracking class
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Referrer {

	var $return_data  = '';

	/**
	  *  Constructor
	  */
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
		
		$pop =  ($this->EE->TMPL->fetch_param('popup') == 'yes') ? ' target="_blank" ' : '';		
				

		//  Build query
		$sql = "SELECT * FROM exp_referrers ";

		$sql .= "WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' ORDER BY ref_id desc ";
		
	
		if ( ! $this->EE->TMPL->fetch_param('limit'))
		{
			$sql .= "LIMIT 100";
		}
		else
		{
			$sql .= "LIMIT ".$this->EE->TMPL->fetch_param('limit');
		}

		$query = $this->EE->db->query($sql);
		$site_url = $this->EE->config->item('site_url');
		
		//  Parse result
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$tagdata = $this->EE->TMPL->tagdata; 

				//  Parse "single" variables

				foreach ($this->EE->TMPL->var_single as $key => $val)
				{				
					//  parse {switch} variable
					if (strncmp($key, 'switch', 6) == 0)
					{
						$sparam = $this->EE->functions->assign_parameters($key);
						
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
						
						$tagdata = $this->EE->TMPL->swap_var_single($key, $sw, $tagdata);
					}
								
				
					//  {ref_from}
					
					if ($key == "ref_from")
					{
						$from = '<a href="'.$this->encode_ee_tags($row['ref_from']).'"'.$pop.'>'.$this->encode_ee_tags($row['ref_from']).'</a>';
					
						$tagdata = $this->EE->TMPL->swap_var_single($val, $from, $tagdata);
					}

					//  {ref_to}

					if ($key == "ref_to")
					{		
						$to_short = str_replace($site_url, '', $row['ref_to']);
					
						$to  = '<a href="'.$this->encode_ee_tags($row['ref_to']).'">'.$this->encode_ee_tags($to_short).'</a>';
					
						$tagdata = $this->EE->TMPL->swap_var_single($val, $to, $tagdata);
					}

					//  {ref_ip}

					if ($key == "ref_ip")
					{
						$ip = ( ! isset($row['ref_ip'])) ? '-' : $row['ref_ip'];
						
						$tagdata = $this->EE->TMPL->swap_var_single($val, $ip, $tagdata);
					}

					//  {ref_agent}

					if ($key == "ref_agent")
					{
						$agent = ( ! isset($row['ref_agent'])) ? '-' : $this->encode_ee_tags($row['ref_agent']);
						
						$tagdata = $this->EE->TMPL->swap_var_single($val, $agent, $tagdata);
					}

					//  {ref_agent_short}

					if ($key == "ref_agent_short")
					{
						$agent = ( ! isset($row['ref_agent'])) ? '-' : preg_replace("/(.+?)\s+.*/", "\\1", $this->encode_ee_tags($row['ref_agent']));
						
						$tagdata = $this->EE->TMPL->swap_var_single($val, $agent, $tagdata);
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
							$date = $this->EE->localize->decode_date($val, $row['ref_date']);
						}
						$tagdata = $this->EE->TMPL->swap_var_single($key, $date, $tagdata);
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