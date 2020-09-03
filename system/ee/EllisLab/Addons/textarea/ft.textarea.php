<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\Addons\FilePicker\FilePicker;

/**
 * Textarea Fieldtype
 */
class Textarea_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea',
		'version'	=> '1.0.0'
	);

	var $has_array_data = FALSE;

	function validate($data)
	{
		return TRUE;
	}

	function display_field($data)
	{
		if (isset($this->settings['field_show_formatting_btns'])
			&& $this->settings['field_show_formatting_btns'] == 'y'
			&& ! ee()->session->cache(__CLASS__, 'markitup_initialized'))
		{
			$member = ee('Model')->get('Member', ee()->session->userdata('member_id'))
				->fields('member_id')
				->first();

			// channel form only uses formatting buttons in the {custom_fields}{/custom_fields}
			// tag pair which does NOT call this method. This method is still called with {field:my_textarea}, though,
			// so we do need to at least avoid a fatal error if that tag exists and the form allows guest authors.
			if ($member)
			{
				$buttons = $member->getHTMLButtonsForSite(ee()->config->item('site_id'));
			}
			else
			{
				$buttons = ee('Model')->get('HTMLButton')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('member_id', 0)
					->order('tag_order')
					->all();
			}

			$markItUp = array(
				'nameSpace' => 'html',
				'markupSet' => array()
			);

			foreach ($buttons as $button)
			{
				// Don't let markItUp handle this button
				if ($button->classname == 'html-upload')
				{
					$button->tag_open = '';
				}
				$markItUp['markupSet'][] = $button->prepForJSON();
			}

			ee()->javascript->set_global('markitup.settings', $markItUp);
			ee()->cp->add_js_script(array('plugin' => array('markitup')));
			ee()->javascript->output('
				$("textarea[data-markitup]")
					.not(".grid-textarea textarea, .fluid-field-templates textarea")
					.markItUp(EE.markitup.settings);

				$("li.html-upload").addClass("m-link").attr({
					rel: "modal-file",
					href: "'.ee('CP/URL')->make('addons/settings/filepicker/modal', array('directory' => 'all')).'"
				});

				Grid.bind("textarea", "display", function(cell)
				{
					$("textarea[data-markitup]", cell).markItUp(EE.markitup.settings);

					$("li.html-upload", cell).addClass("m-link").attr({
						rel: "modal-file",
						href: "'.ee('CP/URL')->make('addons/settings/filepicker/modal', array('directory' => 'all')).'"
					});
				});

				FluidField.on("textarea", "add", function(field)
				{
					$("textarea[data-markitup]", field).markItUp(EE.markitup.settings);

					$("li.html-upload", field).addClass("m-link").attr({
						rel: "modal-file",
						href: "'.ee('CP/URL')->make('addons/settings/filepicker/modal', array('directory' => 'all')).'"
					});

					$(".textarea-field-filepicker").FilePicker({callback: EE.filePickerCallback});

				});
			');

			ee()->session->set_cache(__CLASS__, 'markitup_initialized', TRUE);
		}

		if (REQ == 'CP')
		{
			$class = '';

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

			if ((isset($this->settings['field_show_file_selector'])
				&& $this->settings['field_show_file_selector'] == 'y') OR
				(isset($this->settings['field_show_formatting_btns'])
				&& $this->settings['field_show_formatting_btns'] == 'y'))
			{
				$fp = new FilePicker();
				$fp->inject(ee()->view);
				$vars['fp_url'] = ee('CP/URL')->make($fp->controller, array('directory' => 'all'));

				ee()->cp->add_js_script(array(
					'file' => array('fields/textarea/cp'),
					'plugin' => array('ee_txtarea')
				));
			}

			return ee('View')->make('textarea:publish')->render($vars);
		}

		$params = array(
			'name'     => $this->name(),
			'value'    => $data,
			'rows'     => $this->settings['field_ta_rows'],
			'dir'      => $this->settings['field_text_direction']
		);

		if (isset($this->settings['field_show_formatting_btns']) &&
			$this->settings['field_show_formatting_btns'] == 'y')
		{
			$params['data-markitup'] = 'yes';
		}

		if ($this->get_setting('field_disabled'))
		{
			$params['disabled'] = 'disabled';
		}

		return form_textarea($params);
	}

	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		ee()->load->library('typography');
		return ee()->typography->parse_type(
			$data,
			array(
				'text_format'	=> $this->get_format(),
				'html_format'	=> $this->row('channel_html_formatting', 'all'),
				'auto_links'	=> $this->row('channel_auto_link_urls', 'n'),
				'allow_img_url' => $this->row('channel_allow_img_urls', 'y')
			)
		);
	}

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

	function display_settings($data)
	{
		ee()->load->model('addons_model');
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$settings = array(
			array(
				'title' => 'textarea_height',
				'desc' => 'textarea_height_desc',
				'fields' => array(
					'field_ta_rows' => array(
						'type' => 'text',
						'value' => ( ! isset($data['field_ta_rows']) OR $data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows']
					)
				)
			),
			array(
				'title' => 'field_fmt',
				'fields' => array(
					'field_fmt' => array(
						'type' => 'radio',
						'choices' => $format_options,
						'value' => isset($data['field_fmt']) ? $data['field_fmt'] : 'none',
						'note' => form_label(
							form_checkbox('update_formatting', 'y')
							.lang('update_existing_fields')
						)
					)
				)
			)
		);

		// Only show the update existing fields note when editing.
		if ( ! $this->field_id)
		{
			unset($settings[1]['fields']['field_fmt']['note']);
		}

		if ($this->content_type() != 'grid')
		{
			$settings[] = array(
				'title' => 'field_show_fmt',
				'desc' => 'field_show_fmt_desc',
				'fields' => array(
					'field_show_fmt' => array(
						'type' => 'yes_no',
						'value' => $data['field_show_fmt'] ?: 'n'
					)
				)
			);
		}

		$settings[] = array(
			'title' => 'field_text_direction',
			'fields' => array(
				'field_text_direction' => array(
					'type' => 'radio',
					'choices' => array(
						'ltr' => lang('field_text_direction_ltr'),
						'rtl' => lang('field_text_direction_rtl')
					),
					'value' => isset($data['field_text_direction']) ? $data['field_text_direction'] : 'ltr'
				)
			)
		);

		$settings[] = array(
			'title' => 'db_column_type',
			'desc' => 'db_column_type_desc',
			'fields' => array(
				'db_column_type' => array(
					'type' => 'radio',
					'choices' => [
						'text' => lang('TEXT'),
						'mediumtext' => lang('MEDIUMTEXT')
					],
					'value' => isset($data['db_column_type']) ? $data['db_column_type'] : 'text'
				)
			)
		);

		// Return a subset of the text settings for category content type
		if ($this->content_type() != 'category' && $this->content_type() != 'member')
		{
			// Construct the rest of the settings form for Channel...
			$field_tools = array(
				'title' => 'field_tools',
				'desc' => '',
				'fields' => array(
					'field_show_formatting_btns' => array(
						'type' => 'checkbox',
						'scalar' => TRUE,
						'choices' => array(
							'y' => lang('show_formatting_btns'),
						),
						'value' => isset($data['field_show_formatting_btns']) ? $data['field_show_formatting_btns'] : 'n'
					),
					'field_show_smileys' => array(
						'type' => 'checkbox',
						'scalar' => TRUE,
						'choices' => array(
							'y' => lang('show_smileys'),
						),
						'value' => isset($data['field_show_smileys']) ? $data['field_show_smileys'] : 'n'
					),
					'field_show_file_selector' => array(
						'type' => 'checkbox',
						'scalar' => TRUE,
						'choices' => array(
							'y' => lang('show_file_selector')
						),
						'value' => isset($data['field_show_file_selector']) ? $data['field_show_file_selector'] : 'n'
					)
				)
			);

			if ( ! ee('Addon')->get('emoticon')->isInstalled())
			{
				unset($field_tools['fields']['field_show_smileys']);
			}

			$settings[] = $field_tools;
		}

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_textarea' => array(
			'label' => 'field_options',
			'group' => 'textarea',
			'settings' => $settings
		));
	}

	function grid_save_settings($data)
	{
		return array_merge($this->save_settings($data), $data);
	}

	function save_settings($data)
	{
		$defaults = array(
			'field_show_file_selector' => 'n',
			'db_column_type'       => 'text',
			'field_show_smileys' => 'n',
			'field_show_formatting_btns' => 'n'
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}

	/**
	 * Modify DB column
	 *
	 * @param Array $data
	 * @return Array
	 */
	public function settings_modify_column($data)
	{
		return $this->get_column_type($data);
	}

	/**
	 * Modify DB grid column
	 *
	 * @param array $data The field data
	 * @return array  [column => column_definition]
	 */
	public function grid_settings_modify_column($data)
	{
		return $this->get_column_type($data, TRUE);
	}

	/**
	 * Helper method for column definitions
	 *
	 * @param array $data The field data
	 * @param bool  $grid Is grid field?
	 * @return array  [column => column_definition]
	 */
	protected function get_column_type($data, $grid = FALSE)
	{
		$column = ($grid) ? 'col' : 'field';

		$settings = ($grid) ? $data : $data[$column . '_settings'];
		$field_content_type = isset($settings['db_column_type']) ? $settings['db_column_type'] : 'text';

		$fields = [
			$column . '_id_' . $data[$column . '_id'] => [
				'type'		=> $field_content_type,
				'null'		=> TRUE
			]
		];

		return $fields;
	}
}

// END Textarea_ft class

// EOF
