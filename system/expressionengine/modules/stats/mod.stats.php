<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Stats Module 
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Stats Module
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Stats {

	public $return_data = '';

	/**
	 *  Constructor
	 */
	public function Stats()
	{
		$this->EE =& get_instance();

		$this->EE->stats->load_stats();

		// Limit stats by channel
		// You can limit the stats by any combination of channels
		if ($channel_name = $this->EE->TMPL->fetch_param('channel'))
		{
			$sql = "SELECT	total_entries, 
							total_comments,
							last_entry_date,
							last_comment_date
					FROM exp_channels 
					WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

			$sql .= $this->EE->functions->sql_andor_string($channel_name, 'exp_channels.channel_name');

			$cache_sql = md5($sql);

			if ( ! isset($this->EE->stats->stats_cache[$cache_sql]))
			{ 			
				$query = $this->EE->db->query($sql);
				
				$sdata = array(
									'total_entries'			=> 0,
									'total_comments'		=> 0,
									'last_entry_date'		=> 0,
									'last_comment_date'		=> 0
							  );

				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{ 
						foreach ($sdata as $key => $val)
						{
							if (substr($key, 0, 5) == 'last_')
							{
								if ($row[$key] > $val)
								{
									$sdata[$key] = $row[$key];
								}
							}
							else
							{
								$sdata[$key] = $sdata[$key] + $row[$key];
							}
						}
					}

					foreach ($sdata as $key => $val)
					{
						$this->EE->stats->set_statdata($key, $val);
						
						$this->EE->stats->stats_cache[$cache_sql][$key] = $val;
					} 
				}
			}
			else
			{
				foreach($this->EE->stats->stats_cache[$cache_sql] as $key => $val)
				{
					$this->EE->stats->set_statdata($key, $val);
				}
			}
		}

		//  Parse stat fields
		$fields = array('total_members', 'total_entries', 'total_forum_topics', 
						'total_forum_replies', 'total_forum_posts', 'total_comments', 
						'most_visitors', 'total_logged_in', 'total_guests', 'total_anon');
		$cond	= array();
		
		foreach ($fields as $field)
		{
			if ( isset($this->EE->TMPL->var_single[$field]))
			{
				$cond[$field] = $this->EE->stats->statdata($field);
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($field, $this->EE->stats->statdata($field), $this->EE->TMPL->tagdata);
			}
		}
		
		if (count($cond) > 0)
		{
			$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $cond);
		}
		
		//  Parse dates
		$dates = array('last_entry_date', 'last_forum_post_date', 
						'last_comment_date', 'last_visitor_date', 'most_visitor_date');
		
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{	
			foreach ($dates as $date)
			{
				if (strncmp($key, $date, strlen($date)) == 0)
				{
					$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
												$key, 
												( ! $this->EE->stats->statdata($date) 
													OR $this->EE->stats->statdata($date) == 0) ? '--' : 
												$this->EE->localize->decode_date($val, 
																$this->EE->stats->statdata($date)), 
												$this->EE->TMPL->tagdata
											 );
				}
			}
		}

		//  Online user list

		$names = '';

		if ($this->EE->stats->statdata('current_names'))
		{
			$chunk = $this->EE->TMPL->fetch_data_between_var_pairs($this->EE->TMPL->tagdata, 
																	'member_names');	  
			
			$backspace = '';
			
			if ( ! preg_match("/".LD."member_names.*?backspace=[\"|'](.+?)[\"|']/", 
					$this->EE->TMPL->tagdata, $match))
			{
				if (preg_match("/".LD."name.*?backspace=[\"|'](.+?)[\"|']/", 
					$this->EE->TMPL->tagdata, $match))
				{
					$backspace = $match['1'];
				}
			}
			else
			{
				$backspace = $match['1'];
			}

			// Load the string helper
			$this->EE->load->helper('string');

			$member_path = (preg_match("/".LD."member_path=(.+?)".RD."/", 
							$this->EE->TMPL->tagdata, $match)) ? $match['1'] : '';
			$member_path = str_replace("\"", "", $member_path);
			$member_path = str_replace("'",  "", $member_path);
			$member_path = trim_slashes($member_path);
					
			foreach ($this->EE->stats->statdata('current_names') as $k => $v)
			{
				$temp = $chunk;
			
				if ($v['1'] == 'y')
				{
					if ($this->EE->session->userdata('group_id') == 1)
					{
						$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'].'*', $temp);
					}
					elseif ($this->EE->session->userdata('member_id') == $k)
					{
						$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'].'*', $temp);
					}
					else
					{
						continue;
					}
				}
				else
				{
					$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'], $temp);
				}
				
				
				$path = $this->EE->functions->create_url($member_path.'/'.$k);	
				
				$temp = preg_replace("/".LD."member_path=(.+?)".RD."/", $path, $temp);
				
				$names .= $temp;
			}
			
			
			if (is_numeric($backspace))
			{
				$names = substr(trim($names), 0, - $backspace);
			}
			
		}
				
		$names = str_replace(LD.'name'.RD, '', $names);

		$this->EE->TMPL->tagdata = preg_replace("/".LD.'member_names'.".*?".RD."(.*?)".LD.'\/'.'member_names'.RD."/s", $names, $this->EE->TMPL->tagdata);

		//  {if member_names}

		if ($names != '')
		{
			$this->EE->TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.'\/'.'if'.RD."/s", "\\1", $this->EE->TMPL->tagdata);
		}
		else
		{
			$this->EE->TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.'\/'.'if'.RD."/s", "", $this->EE->TMPL->tagdata);
		}
		
		$this->return_data = $this->EE->TMPL->tagdata;
	}

}
// END CLASS

/* End of file mod.stats.php */
/* Location: ./system/expressionengine/modules/stats/mod.stats.php */