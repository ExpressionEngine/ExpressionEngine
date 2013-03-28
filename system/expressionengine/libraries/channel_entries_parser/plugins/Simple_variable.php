<?php
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
 * ExpressionEngine Channel Parser Plugin (Basic Varaibles)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_simple_variable_parser implements EE_Channel_parser_plugin {


	// @todo remove these two
	protected function starts_with($str, $tagname)
	{
		$tagname = $this->_prefix.$tagname;
		return strncmp($str, $tagname, strlen($tagname)) == 0;
	}

	public function replace_tag($search, $replace, $subject)
	{
		return str_replace(LD.$this->_prefix.$search.RD, $replace, $subject);
	}


	public function disabled(array $disabled)
	{
		return FALSE;
	}

	// Parse out $search_link for the {member_search_path} variable
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$prefix = $pre->prefix();

		$result_path = (preg_match("/".LD.$prefix."member_search_path\s*=(.*?)".RD."/s", $tagdata, $match)) ? $match[1] : 'search/results';
		$result_path = str_replace(array('"',"'"), "", $result_path);

		return get_instance()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.get_instance()->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $search_link)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();

		$data = $obj->row();
		$prefix = $obj->prefix();

		$this->_prefix = $prefix; // @todo remove

		// @todo
		$key = $tag;
		$val = $tag_options;

		// I decided to split the huge if statement into educated guesses
		// so we spend less time doing silly comparisons
		if (strpos($key, '_path') !== FALSE OR strpos($key, 'permalink') !== FALSE)
		{
			return $this->_paths($data, $tagdata, $key, $prefix);
		}

		if (strpos($key, 'url') !== FALSE)
		{
			return $this->_urls($data, $tagdata, $key, $val, $prefix);
		}

		//  parse {title}
		if ($prefix.$key == 'title')
		{
			$data['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $data['title']);

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->typography->format_characters($data['title']),
				$tagdata
			);
		}

		//  {author}
		elseif ($prefix.$key == "author")
		{
			$tagdata = str_replace(LD.$val.RD, ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'], $tagdata);
		}

		//  {channel}
		elseif ($prefix.$key == "channel")
		{
			$tagdata = str_replace(LD.$val.RD, $data['channel_title'], $tagdata);
		}

		//  {channel_short_name}
		elseif ($prefix.$key == "channel_short_name")
		{
			$tagdata = str_replace(LD.$val.RD, $data['channel_name'], $tagdata);
		}

		//  {relative_date}
		elseif ($prefix.$key ==  "relative_date")
		{
			$tagdata = str_replace(LD.$val.RD, timespan($data['entry_date']), $tagdata);
		}

		//  {signature}
		elseif ($prefix.$key == "signature")
		{
			if (get_instance()->session->userdata('display_signatures') == 'n' OR $data['signature'] == '' OR get_instance()->session->userdata('display_signatures') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD,
					get_instance()->typography->parse_type($data['signature'],
						array(
							'text_format'	=> 'xhtml',
							'html_format'	=> 'safe',
							'auto_links'	=> 'y',
							'allow_img_url' => get_instance()->config->item('sig_allow_img_hotlink')
						)
					),
					$tagdata
				);
			}
		}

		//  parse basic fields (username, screen_name, etc.)
		//  Use array_key_exists to handle null values

		else
		{
			$raw_val = str_replace($prefix, '', $val);

			if ($raw_val AND array_key_exists($raw_val, $data))
			{
				$tagdata = str_replace(LD.$val.RD, $data[$raw_val], $tagdata);
			}
		}

		return $tagdata;
	}


	protected function _paths($data, $tagdata, $key, $prefix)
	{
		//  parse profile path
		if ($this->starts_with($key, 'profile_path'))
		{
			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url(get_instance()->functions->extract_path($key).'/'.$data['member_id']),
				$tagdata
			 );
		}

		//  {member_search_path}
		elseif ($this->starts_with($key, 'member_search_path'))
		{
			$tagdata = str_replace(
				LD.$key.RD,
				$search_link.$data['member_id'],
				$tagdata
			);
		}


		//  parse comment_path
		elseif ($this->starts_with($key, 'comment_path') OR $this->starts_with($key, 'entry_id_path'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['entry_id'] : $data['entry_id'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path),
				$tagdata
			);
		}

		//  parse URL title path
		elseif ($this->starts_with($key, 'url_title_path'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['url_title'] : $data['url_title'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path),
				$tagdata
			);
		}

		//  parse title permalink
		elseif ($this->starts_with($key, 'title_permalink'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['url_title'] : $data['url_title'];
			
			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path, FALSE),
				$tagdata
			);
		}

		//  parse permalink
		elseif ($this->starts_with($key, 'permalink'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['entry_id'] : $data['entry_id'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path, FALSE),
				$tagdata
			);
		}

		//  {comment_auto_path}
		elseif ($prefix.$key == "comment_auto_path")
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(LD.$key.RD, $path, $tagdata);
		}

		//  {comment_url_title_auto_path}
		elseif ($prefix.$key == "comment_url_title_auto_path")
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(
				LD.$key.RD,
				reduce_double_slashes($path.'/'.$data['url_title']),
				$tagdata
			);
		}

		//  {comment_entry_id_auto_path}
		elseif ($prefix.$key == "comment_entry_id_auto_path")
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(
				LD.$key.RD,
				reduce_double_slashes($path.'/'.$data['entry_id']),
				$tagdata
			);
		}

		return $tagdata;
	}

	protected function _urls($data, $tagdata, $key, $val, $prefix)
	{
		//  {trimmed_url} - used by Atom feeds
		if ($prefix.$key == "trimmed_url")
		{
			$channel_url = (isset($data['channel_url']) AND $data['channel_url'] != '') ? $data['channel_url'] : '';

			$channel_url = str_replace(array('http://', 'www.'), '', $channel_url);
			$xe = explode("/", $channel_url);
			$channel_url = current($xe);

			$tagdata = str_replace(LD.$val.RD, $channel_url, $tagdata);
		}

		//  {relative_url} - used by Atom feeds
		elseif ($prefix.$key == "relative_url")
		{
			$channel_url = (isset($data['channel_url']) AND $data['channel_url'] != '') ? $data['channel_url'] : '';
			$channel_url = str_replace('http://', '', $channel_url);

			if ($x = strpos($channel_url, "/"))
			{
				$channel_url = substr($channel_url, $x + 1);
			}

			$channel_url = rtrim($channel_url, '/');

			$tagdata = str_replace(LD.$val.RD, $channel_url, $tagdata);
		}

		//  {url_or_email}
		elseif ($prefix.$key == "url_or_email")
		{
			$tagdata = str_replace(LD.$val.RD, ($data['url'] != '') ? $data['url'] : $data['email'], $tagdata);
		}

		//  {url_or_email_as_author}
		elseif ($prefix.$key == "url_or_email_as_author")
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			if ($data['url'] != '')
			{
				$tagdata = str_replace(LD.$val.RD, "<a href=\"".$data['url']."\">".$name."</a>", $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$val.RD, get_instance()->typography->encode_email($data['email'], $name), $tagdata);
			}
		}


		//  {url_or_email_as_link}
		elseif ($prefix.$key == "url_or_email_as_link")
		{
			if ($data['url'] != '')
			{
				$tagdata = str_replace(LD.$val.RD, "<a href=\"".$data['url']."\">".$data['url']."</a>", $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$val.RD, get_instance()->typography->encode_email($data['email']), $tagdata);
			}
		}


		elseif ($prefix.$key == "signature_image_url")
		{
			if (get_instance()->session->userdata('display_signatures') == 'n' OR $data['sig_img_filename'] == ''  OR get_instance()->session->userdata('display_signatures') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace_tag('signature_image_width', '', $tagdata);
				$tagdata = $this->replace_tag('signature_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('sig_img_url').$data['sig_img_filename'], $tagdata);
				$tagdata = $this->replace_tag('signature_image_width', $data['sig_img_width'], $tagdata);
				$tagdata = $this->replace_tag('signature_image_height', $data['sig_img_height'], $tagdata);
			}
		}

		elseif ($prefix.$key == "avatar_url")
		{
			if (get_instance()->session->userdata('display_avatars') == 'n' OR $data['avatar_filename'] == ''  OR get_instance()->session->userdata('display_avatars') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace_tag('avatar_image_width', '', $tagdata);
				$tagdata = $this->replace_tag('avatar_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('avatar_url').$data['avatar_filename'], $tagdata);
				$tagdata = $this->replace_tag('avatar_image_width', $data['avatar_width'], $tagdata);
				$tagdata = $this->replace_tag('avatar_image_height', $data['avatar_height'], $tagdata);
			}
		}

		elseif ($prefix.$key == "photo_url")
		{
			if (get_instance()->session->userdata('display_photos') == 'n' OR $data['photo_filename'] == ''  OR get_instance()->session->userdata('display_photos') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace_tag('photo_image_width', '', $tagdata);
				$tagdata = $this->replace_tag('photo_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('photo_url').$data['photo_filename'], $tagdata);
				$tagdata = $this->replace_tag('photo_image_width', $data['photo_width'], $tagdata);
				$tagdata = $this->replace_tag('photo_image_height', $data['photo_height'], $tagdata);
			}
		}

		return $tagdata;
	}
}
