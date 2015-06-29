<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\Addons\FilePicker\FilePicker;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Textarea Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Textarea_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea',
		'version'	=> '1.0'
	);

	var $has_array_data = FALSE;

	// --------------------------------------------------------------------

	function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		if (isset($this->settings['field_show_formatting_btns'])
			&& $this->settings['field_show_formatting_btns'] == 'y'
			&& ! ee()->session->cache(__CLASS__, 'markitup_initialized'))
		{
			$member = ee('Model')->get('Member', ee()->session->userdata('member_id'))
				->first();
			$buttons = $member->getHTMLButtonsForSite(ee()->config->item('site_id'));

			$markItUp = array(
				'nameSpace' => 'html',
				'markupSet' => array()
			);

			foreach ($buttons as $button)
			{
				$markItUp['markupSet'][] = $button->prepForJSON();
			}

			ee()->javascript->set_global('markitup.settings', $markItUp);
			ee()->cp->add_js_script(array('plugin' => array('markitup')));
			ee()->javascript->output('$("textarea[data-markitup]").markItUp(EE.markitup.settings);');

			ee()->session->set_cache(__CLASS__, 'markitup_initialized', TRUE);
		}

		// Set a boolean telling if we're in Grid AND this textarea has
		// markItUp enabled
		$grid_markitup = ($this->content_type() == 'grid' &&
			isset($this->settings['show_formatting_buttons']) &&
			$this->settings['show_formatting_buttons'] == 1);

		if ($grid_markitup)
		{
			// Load the Grid cell display binding only once
			if ( ! ee()->session->cache(__CLASS__, 'grid_js_loaded'))
			{
				ee()->javascript->output('
					Grid.bind("textarea", "display", function(cell)
					{
						var textarea = $("textarea.markItUp", cell);

						// Only apply file browser trigger if a field was found
						if (textarea.size())
						{
							textarea.markItUp(EE.markitup.settings);
							EE.publish.file_browser.textarea(cell);
						}
					});
				');

				ee()->session->set_cache(__CLASS__, 'grid_js_loaded', TRUE);
			}
		}

		if (REQ == 'CP')
		{
			$class = ($grid_markitup) ? 'markItUp' : '';

			$toolbar = FALSE;

			$format_options = array(
				'field_show_smileys',
				'field_show_file_selector'
			);

			foreach ($format_options as $option)
			{
				if (isset($this->settings[$option])
					&& $this->settings[$option] == 'y')
				{
					$toolbar = TRUE;
					$class .= ' has-format-options';
					break;
				}
			}

			$format_options = array();

			if (isset($this->settings['field_show_fmt'])
				&& $this->settings['field_show_fmt'] == 'y')
			{
				ee()->load->model('addons_model');
				$format_options = ee()->addons_model->get_plugin_formatting(TRUE);
			}

			ee()->cp->get_installed_modules();

			ee()->load->helper('smiley');
			ee()->load->library('table');

			$smileys_enabled = (isset(ee()->cp->installed_modules['emoticon']) ? TRUE : FALSE);
			$smileys = '';

			if ($smileys_enabled)
			{
				$image_array = get_clickable_smileys(ee()->config->slash_item('emoticon_url'), $this->name());
				$col_array = ee()->table->make_columns($image_array, 8);
				$smileys = ee()->table->generate($col_array);
				ee()->table->clear();
			}

			$vars = array(
				'name'            => $this->name(),
				'settings'        => $this->settings,
				'value'           => $data,
				'class'           => trim($class),
				'toolbar'         => $toolbar,
				'format_options'  => $format_options,
				'smileys_enabled' => $smileys_enabled,
				'smileys'         => $smileys
			);

			if ($this->settings['field_show_file_selector']
				&& $this->settings['field_show_file_selector'] == 'y')
			{
				$fp = new FilePicker();
				$fp->inject(ee()->view);
				$vars['fp_url'] = cp_url($fp->controller, array('directory' => 'all'));

				ee()->cp->add_js_script(array(
					'file' => array('fields/textarea/cp'),
					'plugin' => array('ee_txtarea')
				));
			}

			return ee('View')->make('publish')->render($vars);
		}

		return form_textarea(array(
			'name'	=> $this->name(),
			'value'	=> $data,
			'rows'	=> $this->settings['field_ta_rows'],
			'dir'	=> $this->settings['field_text_direction'],
			'class' => ($grid_markitup) ? 'markItUp' : ''
		));
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		$field_fmt = ($this->content_type() == 'grid')
			? $this->settings['field_fmt'] : $this->row('field_ft_'.$this->field_id);

		return ee()->typography->parse_type(
			$data,
			array(
				'text_format'	=> $field_fmt,
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @param bool    Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_settings($data)
	{
		ee()->load->model('addons_model');
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$settings = array(
			array(
				'title' => 'textarea_height',
				'desc' => 'textarea_height_desc',
				'fields' => array(
					'field_maxl' => array(
						'type' => 'text',
						'value' => ($data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows']
					)
				)
			),
			array(
				'title' => 'field_fmt',
				'desc' => 'field_fmt_desc',
				'fields' => array(
					'field_fmt' => array(
						'type' => 'dropdown',
						'choices' => $format_options,
						'value' => $data['field_fmt'],
					)
				)
			),
			array(
				'title' => 'field_show_fmt',
				'desc' => 'field_show_fmt_desc',
				'fields' => array(
					'field_show_fmt' => array(
						'type' => 'yes_no',
						'value' => $data['field_show_fmt'] ?: 'n'
					)
				)
			),
			array(
				'title' => 'field_text_direction',
				'desc' => 'field_text_direction_desc',
				'fields' => array(
					'field_text_direction' => array(
						'type' => 'dropdown',
						'choices' => array(
							'ltr' => lang('field_text_direction_ltr'),
							'rtl' => lang('field_text_direction_rtl')
						),
						'value' => $data['field_text_direction'],
					)
				)
			)
		);

		// Return a subset of the text settings for category content type
		if ($this->content_type() == 'category' || $this->content_type() == 'member')
		{
			return $settings;
		}

		// Construct the rest of the settings form for Channel...
		$settings[] = array(
			'title' => 'field_tools',
			'desc' => 'field_tools_desc',
			'fields' => array(
				'field_show_formatting_btns' => array(
					'type' => 'checkbox',
					'choices' => array(
						'y' => lang('show_formatting_btns'),
					),
					'value' => isset($data['field_show_formatting_btns']) ? $data['field_show_formatting_btns'] : 'n'
				),
				'field_show_smileys' => array(
					'type' => 'checkbox',
					'choices' => array(
						'y' => lang('show_smileys'),
					),
					'value' => isset($data['field_show_smileys']) ? $data['field_show_smileys'] : 'n'
				),
				'field_show_file_selector' => array(
					'type' => 'checkbox',
					'choices' => array(
						'y' => lang('show_file_selector')
					),
					'value' => isset($data['field_show_file_selector']) ? $data['field_show_file_selector'] : 'n'
				)
			)
		);

		return array('field_options' => $settings);
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		return array(
			$this->grid_field_formatting_row($data),
			$this->grid_text_direction_row($data),
			$this->grid_textarea_max_rows_row($data),
			$this->grid_checkbox_row(
				lang('grid_show_fmt_btns'),
				'show_formatting_buttons',
				1,
				(isset($data['show_formatting_buttons']) && $data['show_formatting_buttons'] == 1)
			),
		);
	}
}

// END Textarea_ft class

/* End of file ft.textarea.php */
/* Location: ./system/expressionengine/fieldtypes/ft.textarea.php */
