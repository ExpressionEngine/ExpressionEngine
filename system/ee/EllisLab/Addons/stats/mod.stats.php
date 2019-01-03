<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Stats Module
 */
class Stats {

	public $return_data = '';

	/**
	 *  Constructor
	 */
	public function __construct()
	{
		ee()->stats->load_stats();

		// Limit stats by channel
		// You can limit the stats by any combination of channels
		if ($channel_name = ee()->TMPL->fetch_param('channel'))
		{
			$sql = "SELECT	total_entries,
							total_comments,
							last_entry_date,
							last_comment_date
					FROM exp_channels
					WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

			$sql .= ee()->functions->sql_andor_string($channel_name, 'exp_channels.channel_name');

			$cache_sql = md5($sql);

			if ( ! isset(ee()->stats->stats_cache[$cache_sql]))
			{
				$query = ee()->db->query($sql);

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
						ee()->stats->set_statdata($key, $val);

						ee()->stats->stats_cache[$cache_sql][$key] = $val;
					}
				}
			}
			else
			{
				foreach(ee()->stats->stats_cache[$cache_sql] as $key => $val)
				{
					ee()->stats->set_statdata($key, $val);
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
			if ( isset(ee()->TMPL->var_single[$field]))
			{
				$cond[$field] = ee()->stats->statdata($field);
				ee()->TMPL->tagdata = ee()->TMPL->swap_var_single($field, ee()->stats->statdata($field), ee()->TMPL->tagdata);
			}
		}

		if (count($cond) > 0)
		{
			ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cond);
		}

		//  Parse dates
		$dates = array('last_entry_date', 'last_forum_post_date',
						'last_comment_date', 'last_visitor_date', 'most_visitor_date');

		foreach (ee()->TMPL->var_single as $key => $val)
		{
			foreach ($dates as $date)
			{
				if (strncmp($key, $date, strlen($date)) == 0)
				{
					ee()->TMPL->tagdata = ee()->TMPL->swap_var_single(
												$key,
												( ! ee()->stats->statdata($date)
													OR ee()->stats->statdata($date) == 0) ? '--' :
												ee()->localize->format_date($val,
																ee()->stats->statdata($date)),
												ee()->TMPL->tagdata
											 );
				}
			}
		}

		//  Online user list

		$names = '';

		if (ee()->stats->statdata('current_names'))
		{
			$chunk = ee()->TMPL->fetch_data_between_var_pairs(ee()->TMPL->tagdata,
																	'member_names');

			$backspace = '';

			if ( ! preg_match("/".LD."member_names.*?backspace=[\"|'](.+?)[\"|']/",
					ee()->TMPL->tagdata, $match))
			{
				if (preg_match("/".LD."name.*?backspace=[\"|'](.+?)[\"|']/",
					ee()->TMPL->tagdata, $match))
				{
					$backspace = $match['1'];
				}
			}
			else
			{
				$backspace = $match['1'];
			}

			$member_path = (preg_match("/".LD."member_path=(.+?)".RD."/",
							ee()->TMPL->tagdata, $match)) ? $match['1'] : '';
			$member_path = str_replace("\"", "", $member_path);
			$member_path = str_replace("'",  "", $member_path);
			$member_path = trim_slashes($member_path);

			foreach (ee()->stats->statdata('current_names') as $k => $v)
			{
				$temp = $chunk;

				if ($v['1'] == 'y')
				{
					if (ee()->session->userdata('group_id') == 1)
					{
						$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'].'*', $temp);
					}
					elseif (ee()->session->userdata('member_id') == $k)
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


				$path = ee()->functions->create_url($member_path.'/'.$k);

				$temp = preg_replace("/".LD."member_path=(.+?)".RD."/", $path, $temp);

				$names .= $temp;
			}


			if (is_numeric($backspace))
			{
				$names = substr(trim($names), 0, - $backspace);
			}

		}

		$names = str_replace(LD.'name'.RD, '', $names);

		ee()->TMPL->tagdata = preg_replace("/".LD.'member_names'.".*?".RD."(.*?)".LD.'\/'.'member_names'.RD."/s", $names, ee()->TMPL->tagdata);

		//  {if member_names}

		if ($names != '')
		{
			ee()->TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.'\/'.'if'.RD."/s", "\\1", ee()->TMPL->tagdata);
		}
		else
		{
			ee()->TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.'\/'.'if'.RD."/s", "", ee()->TMPL->tagdata);
		}

		$this->return_data = ee()->TMPL->tagdata;
	}

}
// END CLASS

// EOF
