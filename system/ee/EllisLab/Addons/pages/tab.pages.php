<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Pages_tab {

	/**
	 * Creates the fields that will be displayed on the publish page.
	 *
	 * @param int $channel_id Channel ID where the entry is being created or
	 *   edited
	 * @param int $enry_id Entry ID if this is an edit, empty otherwise
	 * @return array A multidimensional associative array specifying the display
	 *   settings and values associated with each of your fields.
	 */
	public function display($channel_id, $entry_id = '')
	{
		ee()->lang->loadfile('pages');

		$site_id			= ee()->config->item('site_id');
		$settings 			= array();

		$no_templates 		= NULL;
		$pages 				= ee()->config->item('site_pages');

		$pages_template_id 	= 0;
		$pages_dropdown 	= array();
		$pages_uri 			= '';

		if ($entry_id !== 0)
		{
			if (isset($pages[$site_id]['uris'][$entry_id]))
			{
				$pages_uri = $pages[$site_id]['uris'][$entry_id];
			}

			if (isset($pages[$site_id]['templates'][$entry_id]))
			{
				$pages_template_id = $pages[$site_id]['templates'][$entry_id];
			}
		}
		else
		{
			$qry = ee()->db->select('configuration_value')
								->where('configuration_name', 'template_channel_'.$channel_id)
								->where('site_id', (int) $site_id)
								->get('pages_configuration');

			if ($qry->num_rows() > 0)
			{
				$pages_template_id = (int) $qry->row('configuration_value');
			}
		}

		if ($pages_uri == '')
		{
			$qry = ee()->db->select('configuration_value')
								->where('configuration_name', 'template_channel_'.$channel_id)
								->where('site_id', (int) $site_id)
								->get('pages_configuration');

			if ($qry->num_rows() > 0)
			{
				$pages_template_id = (int) $qry->row('configuration_value');
			}
		}

		ee()->load->model('template_model');
		$templates = ee()->template_model->get_templates($site_id);

		foreach ($templates->result() as $template)
		{
			$pages_dropdown[$template->group_name][$template->template_id] = $template->template_name;
		}

		if ($templates->num_rows() === 0)
		{
			ee()->lang->load('design');
			$no_templates = ee('View')->make('ee:_shared/form/no_results')->render(array(
				'text' => sprintf(lang('no_found'), lang('pages_templates')),
				'link_text' => lang('create_new_template'),
				'link_href' => ee('CP/URL', 'design'),
			));
		}

		$settings = array(
			'pages_uri'				=> array(
				'field_id'				=> 'pages_uri',
				'field_label'			=> lang('pages_uri'),
				'field_type'			=> 'text',
				'field_required'		=> 'n',
				'field_data'			=> $pages_uri,
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> 100,
				'field_instructions'	=> '',
				'field_placeholder'     => lang('example_uri')
			),
			'pages_template_id'		=> array(
				'field_id'				=> 'pages_template_id',
				'field_label'			=> lang('template'),
				'field_type'			=> 'select',
				'field_required'		=> 'n',
				'field_pre_populate'	=> 'n',
				'field_list_items'		=> $pages_dropdown,
				'field_data'			=> $pages_template_id,
				'options'				=> $pages_dropdown,
				'selected'				=> $pages_template_id,
				'field_text_direction'	=> 'ltr',
				'field_maxl'			=> 100,
				'field_instructions'	=> '',
				'string_override'		=> $no_templates,
			),
		);

		foreach ($settings as $k => $v)
		{
			ee()->api_channel_fields->set_settings($k, $v);
		}

		return $settings;
	}

	/**
	 * Validates our publish tab data
	 *
	 * @param EllisLab\ExpressionEngine\Model\Channel\ChannelEntry $entry
	 * @param array $values An associative array of field => value
	 * @return EllisLab\ExpressionEngine\Service\Validation\Result A result
	 *  object.
	 */
	public function validate($entry, $values)
	{
		$validator = ee('Validation')->make(array(
			'pages_template_id' => 'validTemplate',
			'pages_uri' => 'validURI|validSegmentCount|notDuplicated',
		));

		$validator->defineRule('validTemplate', $this->makeValidTemplateRule($values));
		$validator->defineRule('validURI', $this->makeValidURIRule());
		$validator->defineRule('validSegmentCount', $this->makeValidSegmentCountRule());
		$validator->defineRule('notDuplicated', $this->makeNotDuplicatedRule($entry));

		return $validator->validate($values);
	}

	/**
	 * Generates and returns a Closure suitable for a Validation Rule that
	 * ensures the submitted value (a Template ID) is both set and is a number,
	 * provided that * a Page URI was also set.
	 *
	 * @returns Closure The logic needed to validate the data.
	 */
	private function makeValidTemplateRule($values)
	{
		return function($field, $value) use($values)
		{
			$pages_uri = (isset($values['pages_uri'])) ? $values['pages_uri'] : '';
	        $pages = ee()->config->item('site_pages');

	        if ($pages !== FALSE && $pages_uri != '' && $pages_uri !== lang('example_uri'))
	        {
	        	if ( ! isset($value) OR
	        	     ! is_numeric($value))
	        	{
					return 'invalid_template';
	        	}
	        }

			return TRUE;
		};
	}

	/**
	 * Generates and returns a Closure suitable for a Validation Rule that
	 * ensures the submitted value (a URI) contains only letters, numbers,
	 * underscores, dashes, or periods.
	 *
	 * @returns Closure The logic needed to validate the data.
	 */
	private function makeValidURIRule()
	{
		return function($field, $value)
		{
	        $c_page_uri = preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '',
	                    str_replace(ee()->config->item('site_url'), '', $value));

	        if ($c_page_uri !== $value)
	        {
	            return 'invalid_page_uri';
	        }

			return TRUE;
		};
	}

	/**
	 * Generates and returns a Closure suitable for a Validation Rule that
	 * ensures the submitted value (a URI) has no more than 8 segments.
	 *
	 * @returns Closure The logic needed to validate the data.
	 */
	private function makeValidSegmentCountRule()
	{
		return function($field, $value)
		{
	    	$value_segs = substr_count(trim($value, '/'), '/');

	    	// More than 9 pages URI segs?  goodbye
	    	if ($value_segs > 8)
	    	{
	    		return 'invalid_page_num_segs';
	    	}

			return TRUE;
		};
	}

	/**
	 * Generates and returns a Closure suitable for a Validation Rule that
	 * ensures the submitted value (a URI) does not already exist
	 *
	 * @returns Closure The logic needed to validate the data.
	 */
	private function makeNotDuplicatedRule($entry)
	{
		return function($field, $value) use($entry)
		{
	    	$static_pages = ee()->config->item('site_pages');
	    	$uris = $static_pages[ee()->config->item('site_id')]['uris'];

			if ( ! isset($entry->entry_id))
			{
				$entry->entry_id == 0;
			}
			elseif ($entry->entry_id !== 0)
			{
				if ( ! isset($uris[$entry->entry_id]) && in_array($value, $uris))
				{
		    		return 'duplicate_page_uri';
				}
			}
			elseif (in_array($value, $uris))
	    	{
	    		return 'duplicate_page_uri';
	    	}

			return TRUE;
		};
	}

	/**
	 * Saves the page's publish form data. This function is called in the
	 * ChannelEntry's afterSave() event.
	 *
	 * @param EllisLab\ExpressionEngine\Model\Channel\ChannelEntry $entry
	 *  An instance of the ChannelEntry entity.
	 * @param array $values An associative array of field => value
	 * @return 	void
	 */
	public function save($entry, $values)
	{
	    $site_id    = ee()->config->item('site_id');
	    $site_pages = ee()->config->item('site_pages');

        if ($site_pages !== FALSE
            && isset($values['pages_uri'])
            && $values['pages_uri'] != lang('example_uri')
   			&& $values['pages_uri'] != '')
        {
            if (isset($values['pages_template_id'])
                && is_numeric($values['pages_template_id']))
            {
				$page = preg_replace("#[^a-zA-Z0-9_\-/\.]+$#i", '',
				                    str_replace(ee()->config->item('site_url'), '',
				                                $values['pages_uri']));

				$page = '/' . trim($page, '/');

				$site_pages[$site_id]['uris'][$entry->entry_id] = $page;
				$site_pages[$site_id]['templates'][$entry->entry_id] = preg_replace("#[^0-9]+$#i", '',
		                                            						$values['pages_template_id']);

				if ($site_pages[$site_id]['uris'][$entry->entry_id] == '//')
				{
					$site_pages[$site_id]['uris'][$entry->entry_id] = '/';
				}

				ee()->config->set_item('site_pages', $site_pages);
				$site = ee('Model')->get('Site', $site_id)->first();
				$site->site_pages = $site_pages;
				$site->save();
            }
        }
	}

	/**
	 * Removes pages from the site_pages structure. This function is called in the
	 * ChannelEntry's beforeDelete() event.
	 *
	 * @param int[] $entry_ids An array of entry IDs that were deleted
	 * @return 	void
	 */
	public function delete($entry_ids)
	{
		$site_pages = ee()->config->item('site_pages');
		$site_id	= ee()->config->item('site_id');

		foreach ($entry_ids as $entry_id)
		{
			unset($site_pages[$site_id]['uris'][$entry_id]);
			unset($site_pages[$site_id]['templates'][$entry_id]);
		}

		$site = ee('Model')->get('Site', $site_id)->first();
		$site->site_pages = $site_pages;
		$site->save();
	}

}

// EOF
