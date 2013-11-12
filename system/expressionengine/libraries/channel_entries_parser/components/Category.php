<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Categories)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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
					ee()->functions->assign_parameters($matches[1][$j]),
					$matches[0][$j]
				);
			}
  		}

		return $cat_chunk;
	}

	// ------------------------------------------------------------------------

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

					ee()->load->library('file_field');
					$cat_image = ee()->file_field->parse_field($v[3]);

					$cat_vars = array(
						'category_name'			=> $v[2],
						'category_url_title'	=> $v[6],
						'category_description'	=> (isset($v[4])) ? $v[4] : '',
						'category_group'		=> (isset($v[5])) ? $v[5] : '',
						'category_image'		=> $cat_image['url'],
						'category_id'			=> $v[0],
						'parent_id'				=> $v[1],
						'active'				=> ($active_cat == $v[0] || $active_cat == $v[6])
					);

					// add custom fields for conditionals prep
					foreach ($obj->channel()->catfields as $cv)
					{
						$cat_vars[$cv['field_name']] = ( ! isset($v['field_id_'.$cv['field_id']])) ? '' : $v['field_id_'.$cv['field_id']];
					}

					$temp = ee()->functions->prep_conditionals($temp, $cat_vars);

					$temp = str_replace(
						array(
							LD."category_id".RD,
							LD."category_name".RD,
							LD."category_url_title".RD,
							LD."category_image".RD,
							LD."category_group".RD,
							LD.'category_description'.RD,
							LD.'parent_id'.RD
						),
						array($v[0],
							ee()->functions->encode_ee_tags($v[2]),
							$v[6],
							$cat_image['url'],
							(isset($v[5])) ? $v[5] : '',
							(isset($v[4])) ? ee()->functions->encode_ee_tags($v[4]) : '',
							$v[1]
						),
						$temp
					);

					foreach($obj->channel()->catfields as $cv2)
					{
						if (isset($v['field_id_'.$cv2['field_id']]) AND $v['field_id_'.$cv2['field_id']] != '')
						{
							$field_content = ee()->typography->parse_type(
								$v['field_id_'.$cv2['field_id']],
								array(
									'text_format'		=> $v['field_ft_'.$cv2['field_id']],
									'html_format'		=> $v['field_html_formatting'],
									'auto_links'		=> 'n',
									'allow_img_url'	=> 'y'
								)
							);

							$temp = str_replace(LD.$cv2['field_name'].RD, $field_content, $temp);
						}
						else
						{
							// garbage collection
							$temp = str_replace(LD.$cv2['field_name'].RD, '', $temp);
						}

						$temp = reduce_double_slashes($temp);
					}

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
			$tagdata = preg_replace('/{'.$tagname.'[^}]*}(.+?){\/'.$tagname.'[^}]*}/is', '', $tagdata);
		}

		return $tagdata;
	}
}