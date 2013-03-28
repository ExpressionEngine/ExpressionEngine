<?php

class EE_Channel_preparser {

	public $pairs = array();
	public $singles = array();

	public $cat_chunks = array();
	public $pfield_chunks = array();

	public $search_link = '';
	public $custom_date_fields = array();
	public $modified_conditionals = array();

	public $date_vars = array(
		'entry_date' 		=> array(),
		'gmt_date' 			=> array(),
		'gmt_entry_date'	=> array(),
		'edit_date' 		=> array(),
		'gmt_edit_date'		=> array(),
		'expiration_date'	=> array(),
		'week_date'			=> array()
	);

	public $subscriber_totals = array();

	protected $_prefix;
	protected $_tagdata;

	protected $_parser;
	protected $_channel;

	public function __construct(Channel $channel, EE_Channel_parser $parser)
	{
		$this->_parser = $parser;
		$this->_channel = $channel;

		$this->_prefix = $parser->prefix();
		$this->_tagdata = $parser->tagdata();

		$this->date_vars			 = $this->_find_date_variables();
		$this->cat_chunks			 = $this->_find_category_pairs();
		$this->custom_date_fields	 = $this->_find_custom_date_fields();
		$this->pfield_chunks		 = $this->_find_custom_field_pairs();
		$this->modified_conditionals = $this->_find_modified_conditionals();
		$this->search_link			 = $this->_member_search_link();

		$this->pairs	= $this->_extract_prefixed(get_instance()->TMPL->var_pair);
		$this->singles	= $this->_extract_prefixed(get_instance()->TMPL->var_single);

		$this->subscriber_totals	= $this->_subscriber_totals();
	}

	public function tagdata()
	{
		return $this->_tagdata;
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function channel()
	{
		return $this->_channel;
	}

	public function parser()
	{
		return $this->_parser;
	}

	public function has_tag($tagname)
	{
		return strpos($this->_tagdata, LD.$this->_prefix.$tagname) !== FALSE;
	}

	public function has_tag_pair($tagname)
	{
		$start = strpos($this->_tagdata, LD.$this->_prefix.$tagname);

		if ($start === FALSE)
		{
			return FALSE;
		}

		$end = strpos($this->_tagdata, LD.'/'.$this->_prefix.$tagname, $start);

		return $end !== FALSE;
	}

	protected function _subscriber_totals()
	{
		$subscribers = array();
		
		if (strpos($this->_tagdata, LD.'comment_subscriber_total'.RD) !== FALSE
			&& isset(get_instance()->session->cache['channel']['entry_ids'])
			)
		{
			get_instance()->load->library('subscription');
			get_instance()->subscription->init('comment');
			$subscribers = get_instance()->subscription->get_subscription_totals('entry_id', get_instance()->session->cache['channel']['entry_ids']);
		}

		return $subscribers;
	}

	protected function _extract_prefixed(array $data)
	{
		if ( ! $this->_prefix)
		{
			return $data;
		}

		$filtered = array();

		$regex_prefix = '/^'.preg_quote($this->_prefix, '/').'[^:]+( |$)/';

		foreach (preg_grep($regex_prefix, array_keys($data)) as $key)
		{
			$filtered[$key] = $data[$key];
		}

		return $filtered;
	}

	protected function _find_category_pairs()
	{
		$cat_chunk = array();
		$prefix = preg_quote($this->_prefix, '/');

		if ($this->has_tag_pair('categories'))
		{
			if (preg_match_all("/".LD.$prefix."categories(.*?)".RD."(.*?)".LD.'\/'.$prefix.'categories'.RD."/s", $this->_tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$cat_chunk[] = array(
						$matches[2][$j],
						get_instance()->functions->assign_parameters($matches[1][$j]),
						$matches[0][$j]
					);
				}
	  		}
		}

		return $cat_chunk;
	}


	protected function _find_date_variables()
	{
		$prefix = $this->_prefix;

		$entry_date 		= array();
		$gmt_date 			= array();
		$gmt_entry_date		= array();
		$edit_date 			= array();
		$gmt_edit_date		= array();
		$expiration_date	= array();
		$week_date			= array();

		$date_vars = array('entry_date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date', 'expiration_date', 'recent_comment_date', 'week_date');

		get_instance()->load->helper('date');

		foreach ($date_vars as $val)
		{
			if ( ! $this->has_tag($val))
			{
				continue;
			}

			$full_val = $prefix.$val;

			if (preg_match_all("/".LD.$full_val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $this->_tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

					switch ($val)
					{
						case 'entry_date': 
							$entry_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_date':
							$gmt_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_entry_date':
							$gmt_entry_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'edit_date':
							$edit_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_edit_date':
							$gmt_edit_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'expiration_date':
							$expiration_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'recent_comment_date':
							$recent_comment_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'week_date':
							$week_date[$matches[0][$j]] = $matches[2][$j];
							break;
					}
				}
			}
		}

		return call_user_func_array('compact', $date_vars);
	}



	protected function _find_custom_date_fields()
	{
		$prefix = $this->_prefix;
		$custom_date_fields = array();

		if (count($this->_channel->dfields) > 0)
		{
			foreach ($this->_channel->dfields as $site_id => $dfields)
			{
	  			foreach($dfields as $key => $value)
	  			{
	  				if ( ! $this->has_tag($key))
	  				{
	  					continue;
	  				}

	  				$key = $prefix.$key;

					if (preg_match_all("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $this->_tagdata, $matches))
					{
						for ($j = 0; $j < count($matches[0]); $j++)
						{
							$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

							$custom_date_fields[$matches[0][$j]] = $matches[1][$j];
						}
					}
				}
			}
		}

		return $custom_date_fields;
	}

	protected function _find_custom_field_pairs()
	{
		if (count($this->_channel->pfields) == 0)
		{
			return array();
		}

		$prefix = $this->_prefix;
		$pfield_chunk = array();

		foreach ($this->_channel->pfields as $site_id => $pfields)
		{
			$pfield_names = array_intersect($this->_channel->cfields[$site_id], array_keys($pfields));

			foreach($pfield_names as $field_name => $field_id)
			{
				if ( ! $this->has_tag_pair($field_name))
				{
					continue;
				}

				$offset = 0;
				$field_name = $prefix.$field_name;
				
				while (($end = strpos($this->_tagdata, LD.'/'.$field_name.RD, $offset)) !== FALSE)
				{
					// This hurts soo much. Using custom fields as pair and single vars in the same
					// channel tags could lead to something like this: {field}...{field}inner{/field}
					// There's no efficient regex to match this case, so we'll find the last nested
					// opening tag and re-cut the chunk.

					if (preg_match("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'."{$field_name}(.*?)".RD."/s", $this->_tagdata, $matches, 0, $offset))
					{
						$chunk = $matches[0];
						$params = $matches[1];
						$inner = $matches[2];

						// We might've sandwiched a single tag - no good, check again (:sigh:)
						if ((strpos($chunk, LD.$field_name, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}(.*?)".RD."/s", $chunk, $match))
						{
							// Let's start at the end
							$idx = count($match[0]) - 1;
							$tag = $match[0][$idx];
							
							// Reassign the parameter
							$params = $match[1][$idx];

							// Cut the chunk at the last opening tag (PHP5 could do this with strrpos :-( )
							while (strpos($chunk, $tag, 1) !== FALSE)
							{
								$chunk = substr($chunk, 1);
								$chunk = strstr($chunk, LD.$field_name);
								$inner = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
							}
						}
						
						$chunk_array = array($inner, get_instance()->functions->assign_parameters($params), $chunk);
						
						// Grab modifier if it exists and add it to the chunk array
						if (substr($params, 0, 1) == ':')
						{
							$chunk_array[] = str_replace(':', '', $params);
						}
						
						$pfield_chunk[$site_id][$field_name][] = $chunk_array;
					}
					
					$offset = $end + 1;
				}
			}
		}

		return $pfield_chunk;
	}

	public function _find_modified_conditionals()
	{
		$prefix = $this->_prefix;
		$all_field_names = array();

		if (strpos($this->_tagdata, LD.'if') === FALSE)
		{
			return array();
		}

		foreach($this->_channel->cfields as $site_id => $fields)
		{
			$all_field_names = array_unique(array_merge($all_field_names, $fields));
		}

		$modified_field_options = $prefix.implode('|'.$prefix, array_keys($all_field_names));
		$modified_conditionals = array();

		if (preg_match_all("/".preg_quote(LD)."((if:(else))*if)\s+(($modified_field_options):(\w+))(.*?)".preg_quote(RD)."/s", $this->_tagdata, $matches))
		{
			foreach($matches[5] as $match_key => $field_name)
			{
				$modified_conditionals[$field_name][] = $matches[6][$match_key];
			}
		}
		
		return array_map('array_unique', $modified_conditionals);
	}

	// We use this with the {member_search_path} variable
	protected function _member_search_link()
	{
		$prefix = $this->_prefix;

		$result_path = (preg_match("/".LD.$prefix."member_search_path\s*=(.*?)".RD."/s", $this->_tagdata, $match)) ? $match[1] : 'search/results';
		$result_path = str_replace(array('"',"'"), "", $result_path);

		return get_instance()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.get_instance()->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';
	}
}