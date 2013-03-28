<?php


class EE_Channel_date_parser implements EE_Channel_parser_plugin {

	public function understands($tag)
	{
		return TRUE;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		// @todo
		$key = $tag;
		$val = $tag_options;

		extract($obj->preparsed()->date_vars);

		//  parse entry date
		if (isset($entry_date[$key]))
		{
			$val = str_replace($entry_date[$key], get_instance()->localize->format_date($entry_date[$key], $data['entry_date']), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  Recent Comment Date
		elseif (isset($recent_comment_date[$key]))
		{
			if ($data['recent_comment_date'] != 0)
			{
				$val = str_replace($recent_comment_date[$key], get_instance()->localize->format_date($recent_comment_date[$key], $data['recent_comment_date']), $val);

				$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
			}
		}

		//  GMT date - entry date in GMT
		elseif (isset($gmt_entry_date[$key]))
		{
			$val = str_replace($gmt_entry_date[$key], get_instance()->localize->format_date($gmt_entry_date[$key], $data['entry_date'], FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		elseif (isset($gmt_date[$key]))
		{
			$val = str_replace($gmt_date[$key], get_instance()->localize->format_date($gmt_date[$key], $data['entry_date'], FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  parse "last edit" date
		elseif (isset($edit_date[$key]))
		{
			$val = str_replace($edit_date[$key], get_instance()->localize->format_date($edit_date[$key], mysql_to_unix($data['edit_date'])), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  "last edit" date as GMT
		elseif (isset($gmt_edit_date[$key]))
		{
			$val = str_replace($gmt_edit_date[$key], get_instance()->localize->format_date($gmt_edit_date[$key], mysql_to_unix($data['edit_date']), FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}


		//  parse expiration date
		elseif (isset($expiration_date[$key]))
		{
			if ($data['expiration_date'] != 0)
			{
				$val = str_replace($expiration_date[$key], get_instance()->localize->format_date($expiration_date[$key], $data['expiration_date']), $val);

				$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, "", $tagdata);
			}
		}


		//  "week_date"
		elseif (isset($week_date[$key]))
		{
			// Subtract the number of days the entry is "into" the week to get zero (Sunday)
			// If the entry date is for Sunday, and Monday is being used as the week's start day,
			// then we must back things up by six days

			$offset = 0;

			if (strtolower(get_instance()->TMPL->fetch_param('start_day')) == 'monday')
			{
				$day_of_week = get_instance()->localize->format_date('%w', $data['entry_date']);

				if ($day_of_week == '0')
				{
					$offset = -518400; // back six days
				}
				else
				{
					$offset = 86400; // plus one day
				}
			}

			$week_start_date = $data['entry_date'] - (get_instance()->localize->format_date('%w', $data['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

			$val = str_replace($week_date[$key], get_instance()->localize->format_date($week_date[$key], $week_start_date), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		return $tagdata;
	}
}
