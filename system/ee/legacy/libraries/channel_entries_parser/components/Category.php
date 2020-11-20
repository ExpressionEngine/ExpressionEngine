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
 * Channel Parser Component (Categories)
 */
class EE_Channel_category_parser implements EE_Channel_parser_component {

	/**
	 * Check if categories are enabled and requested in the template.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('categories', $disabled) OR ! $pre->has_tag_pair('categories');
	}

	/**
	 * Before the parser runs, this will gather all category tag pairs that
	 * need processing.
	 *
	 * The returned chunks will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	The found category chunks
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return $this->_get_cat_chunks($tagdata, $pre->prefix());
	}

	/**
	 * Find any {category} {/category} tag pair chunks in the template and
	 * extract them.
	 *
	 * @param String	The tagdata to be parsed
	 * @param String	Prefix used in current channel parsing
	 * @return Array	The found category chunks
	 */
	protected function _get_cat_chunks($tagdata, $prefix)
	{
		$cat_chunk = array();

		if (preg_match_all("/".LD.$prefix."categories(.*?)".RD."(.*?)".LD.'\/'.$prefix.'categories'.RD."/s", $tagdata, $matches))
		{
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$cat_chunk[] = array(
					$matches[2][$j],
					ee('Variables/Parser')->parseTagParameters($matches[1][$j]),
					$matches[0][$j]
				);
			}
  		}

		return $cat_chunk;
	}

	/**
	 * Replace all of the category pairs with the correct data.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $cat_chunk)
	{
		$data = $obj->row();
		$prefix = $obj->prefix();

		$categories = $obj->data('categories', array());

		$tagname = $prefix.'categories';

		// Check to see if the category chunks still exist; if not, check
		// the tagdata in case they've been modified since pre-processing
		foreach ($cat_chunk as $chunk)
		{
			if (strpos($tagdata, $chunk[2]) === FALSE)
			{
				$cat_chunk = $this->_get_cat_chunks($tagdata, $prefix);

				$obj->preparsed()->set_once_data($this, $cat_chunk);
				break;
			}
		}

		if (isset($categories[$data['entry_id']]) AND is_array($categories[$data['entry_id']]) AND count($cat_chunk) > 0)
		{
			// Get category ID from URL for {if active} conditional
			ee()->load->helper('segment');
			$active_cat = ($obj->channel()->pagination->dynamic_sql && $obj->channel()->cat_request) ? parse_category($obj->channel()->query_string) : FALSE;

			foreach ($cat_chunk as $catkey => $catval)
			{
				$cats = '';
				$i = 0;

				$not_these		  = array();
				$these			  = array();
				$not_these_groups = array();
				$these_groups	  = array();

				if (isset($catval[1]['show']))
				{
					if (strncmp($catval[1]['show'], 'not ', 4) == 0)
					{
						$not_these = explode('|', trim(substr($catval[1]['show'], 3)));
					}
					else
					{
						$these = explode('|', trim($catval[1]['show']));
					}
				}

				if (isset($catval[1]['show_group']))
				{
					if (strncmp($catval[1]['show_group'], 'not ', 4) == 0)
					{
						$not_these_groups = explode('|', trim(substr($catval[1]['show_group'], 3)));
					}
					else
					{
						$these_groups = explode('|', trim($catval[1]['show_group']));
					}
				}

				$filtered_categories = array();

				foreach ($categories[$data['entry_id']] as $k => $v)
				{
					if (in_array($v[0], $not_these) OR (isset($v[5]) && in_array($v[5], $not_these_groups)))
					{
						continue;
					}
					elseif( (count($these) > 0 && ! in_array($v[0], $these)) OR
					 		(count($these_groups) > 0 && isset($v[5]) && ! in_array($v[5], $these_groups)))
					{
						continue;
					}

					$filtered_categories[$k] = $v;
				}

				$count = 0;
				$total_results = count($filtered_categories);

				foreach ($filtered_categories as $k => $v)
				{
					$temp = $catval[0];

					if (preg_match_all("#".LD."path=(.+?)".RD."#", $temp, $matches))
					{
						foreach ($matches[1] as $match)
						{
							if ($obj->channel()->use_category_names == TRUE)
							{
								$temp = preg_replace("#".LD."path=.+?".RD."#", reduce_double_slashes(ee()->functions->create_url($match).'/'.$obj->channel()->reserved_cat_segment.'/'.$v[6]), $temp, 1);
							}
							else
							{
								$temp = preg_replace("#".LD."path=.+?".RD."#", reduce_double_slashes(ee()->functions->create_url($match).'/C'.$v[0]), $temp, 1);
							}
						}
					}
					else
					{
						$temp = preg_replace("#".LD."path=.+?".RD."#", ee()->functions->create_url("SITE_INDEX"), $temp);
					}

					// super confusing, so documenting this legacy array here:
					// $v[0] = cat_id
					// $v[1] = parent_id
					// $v[2] = cat_name
					// $v[3] = cat_image
					// $v[4] = cat_description
					// $v[5] = group_id
					// $v[6] = cat_url_title

					ee()->load->library('file_field');
					$cat_image = ee()->file_field->parse_field($v[3]);

					$cat_vars = array(
						'category_count'         => ++$count,
						'category_reverse_count' => $total_results - $count + 1,
						'category_total_results' => $total_results,
						'category_name'          => ee()->typography->format_characters(
							ee()->functions->encode_ee_tags($v[2])
						),
						'category_url_title'     => $v[6],
						'category_description'   => (isset($v[4])) ? ee()->functions->encode_ee_tags($v[4]) : '',
						'category_group'         => (isset($v[5])) ? $v[5] : '',
						'category_image'         => (isset($cat_image['url'])) ? $cat_image['url'] : '',
						'category_id'            => $v[0],
						'parent_id'              => $v[1],
						'active'                 => ($active_cat == $v[0] || $active_cat == $v[6])
					);

					$cond = $cat_vars;

					// add custom fields for conditionals prep and parsing
					foreach ($obj->channel()->catfields as $cv)
					{
						$cond[$cv['field_name']] = ( ! isset($v['field_id_'.$cv['field_id']])) ? '' : $v['field_id_'.$cv['field_id']];
						$cat_vars[$cv['field_name']] = $cond[$cv['field_name']];
					}

					$temp = ee()->functions->prep_conditionals($temp, $cond);

					$variables = ee('Variables/Parser')->extractVariables($temp);
					$temp = $obj->channel()->parseCategoryFields($v[0], array_merge($v, $cat_vars), $temp, array_keys($variables['var_single']));

					$cats .= $temp;

					if (is_array($catval[1]) && isset($catval[1]['limit']) && $catval[1]['limit'] == ++$i)
					{
						break;
					}
				}

				if (is_array($catval[1]) AND isset($catval[1]['backspace']))
				{
					$cats = substr($cats, 0, - $catval[1]['backspace']);
				}

				// Check to see if we need to parse {filedir_n}
				if (strpos($cats, '{filedir_') !== FALSE)
				{
					ee()->load->library('file_field');
					$cats = ee()->file_field->parse_string($cats);
				}

				$tagdata = str_replace($catval[2], $cats, $tagdata);
			}
		}
		else
		{
			$replacement = '';

			if (strpos($tagdata, 'if no_results') !== FALSE
				&& preg_match('/'.LD.'if no_results'.RD.'(.*?)'.LD.'\/if'.RD.'/s', $tagdata, $match)) {
				$replacement = $match[1];
			}

			$tagdata = preg_replace(
				'/{'.$tagname.'[^}]*}(.+?){\/'.$tagname.'[^}]*}/is',
				$replacement,
				$tagdata
			);
		}

		return $tagdata;
	}
}

// EOF
