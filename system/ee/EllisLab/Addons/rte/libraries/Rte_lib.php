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
 * RTE Module Library
 */
class Rte_lib {

	// We consider the editor empty in these cases
	public $_empty = array(
		'',
		'<br>',
		'<br/>',
		'<br />',
		'<p></p>',
		'<p>​</p>' // Zero-width character
	);

	public function __construct()
	{
		ee()->lang->loadfile('rte');
	}

	/**
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset($toolset_id = FALSE)
	{
		if ($toolset_id === FALSE)
		{
			$toolset_id = ee()->input->get_post('toolset_id');
		}

		if ( ! is_numeric($toolset_id))
		{
			show_404();
		}

		ee()->load->model(array('rte_toolset_model','rte_tool_model'));
		ee()->output->enable_profiler(FALSE);

		ee()->load->library('form_validation');
		ee()->form_validation->setCallbackObject($this);
		ee()->form_validation->set_rules(
			'toolset_name',
			'lang:tool_set_name',
			'required|callback__valid_name|callback__unique_name'
		);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			return $this->save_toolset();
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('toolset_error'))
				->addToBody(lang('toolset_error_desc'))
				->now();
		}

		// new toolset?
		if ($toolset_id == 0)
		{
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/rte/new_toolset');
			$vars['cp_page_title_alt'] = lang('create_tool_set_header');
			$toolset['tools'] = array();
			$toolset_name = '';
		}
		else
		{
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $toolset_id));
			$vars['cp_page_title_alt'] = lang('edit_tool_set_header');

			// make sure user can access the existing toolset
			if ( ! ee()->rte_toolset_model->member_can_access($toolset_id))
			{
				show_404(lang('toolset_edit_failed'));
			}

			// grab the toolset
			$toolset = ee()->rte_toolset_model->get($toolset_id);
			$toolset_name = $toolset['name'];
		}

		$tools = array();
		foreach (ee()->rte_tool_model->get_tool_list(TRUE) as $tool)
		{
			$name_key = strtolower($tool['class']);
			$desc_key = $name_key . '_desc';

			$tool_name = (lang($name_key) != $name_key) ? lang($name_key) : $tool['name'];
			$tool_desc = (lang($desc_key) != $desc_key) ? lang($desc_key) : '';

			$tools[$tool['tool_id']] = [
				'label' => $tool_name,
				'instructions' => $tool_desc
			];
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'tool_set_name',
					'fields' => array(
						'toolset_name' => array(
							'type' => 'text',
							'value' => $toolset_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'choose_tools',
					'fields' => array(
						'tools' => array(
							'type' => 'checkbox',
							'choices' => $tools,
							'value' => $toolset['tools'],
							'no_results' => ['text' => sprintf(lang('no_found'), lang('tools'))]
						)
					)
				)
			)
		);

		$vars['ajax_validate'] = TRUE;
		$vars['buttons'] = [
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save',
				'text' => 'save',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_new',
				'text' => 'save_and_new',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_close',
				'text' => 'save_and_close',
				'working' => 'btn_saving'
			]
		];

		return ee('View')->make('ee:_shared/form')->render($vars);
	}

	/**
	 * Saves a toolset
	 *
	 * @access	private
	 * @return	void
	 */
	private function save_toolset()
	{
		ee()->output->enable_profiler(FALSE);

		ee()->load->model('rte_toolset_model');

		// get the toolset
		$toolset_id = ee()->input->get_post('toolset_id');

		if ($toolset_id)
		{
			$error_url = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $toolset_id));
		}
		else
		{
			$error_url = ee('CP/URL')->make('addons/settings/rte/new_toolset');
		}

		if (ee('Request')->post('submit') == 'save_and_new')
		{
			$success_url = ee('CP/URL')->make('addons/settings/rte/new_toolset');
		}
		elseif (ee()->input->post('submit') == 'save_and_close')
		{
			$success_url = ee('CP/URL')->make('addons/settings/rte');
		}
		else
		{
			$success_url = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $toolset_id));
		}


		$toolset = array(
			'name'      => ee()->input->post('toolset_name'),
			'tools'     => implode('|', ee()->input->post('tools')),
		);

		// is this an individual’s private toolset?
		$is_members = (ee()->input->get_post('private') == 'true');

		// Updating? Make sure the toolset exists and they aren't trying any
		// funny business...
		if ($toolset_id)
		{
			$orig = ee()->rte_toolset_model->get($toolset_id);

			if ( ! $orig || $is_members && $orig['member_id'] != ee()->session->userdata('member_id'))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('toolset_error'))
					->addToBody(lang('toolset_update_failed'))
					->defer();
				ee()->functions->redirect($error_url);
			}
		}

		// save it
		if (ee()->rte_toolset_model->save_toolset($toolset, $toolset_id) === FALSE)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('toolset_error'))
				->addToBody(lang('toolset_update_failed'))
				->defer();
			ee()->functions->redirect($error_url);
		}

		if ($toolset_id)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('toolset_updated'))
				->defer();
		}
		else
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('toolset_created'))
				->addToBody(sprintf(lang('toolset_created_desc'), $toolset['name']))
				->defer();
		}

		// if it’s new, get the ID
		if ( ! $toolset_id)
		{
			$toolset_id = ee()->db->insert_id();
			ee()->session->set_flashdata('toolset_id', $toolset_id);
		}

		// If the default toolset was deleted
		if (ee()->config->item('rte_default_toolset_id') == 0)
		{
			ee()->config->update_site_prefs(array(
				'rte_default_toolset_id' => $toolset_id
			));
		}

		// update the member profile
		if ($is_members && $toolset_id)
		{
			ee()->db
				->where('member_id', ee()->session->userdata('member_id'))
				->update('members', array('rte_toolset_id' => $toolset_id));
		}

		ee()->functions->redirect($success_url);
	}

	/**
	 * Build RTE JS
	 *
	 * @access	private
	 * @param	int 	The ID of the toolset you want to load
	 * @param	string 	The selector that will match the elements to turn into an RTE
	 * @param	array   Include or skip certain JS libs: array('jquery' => FALSE //skip)
	 * @param	bool	If TRUE, includes tools that are for the control panel only
	 * @return	string 	The JS needed to embed the RTE
	 */
	public function build_js($toolset_id, $selector, $include = [], $cp_only = FALSE)
	{
		ee()->load->model(array('rte_toolset_model','rte_tool_model'));

		if( ! is_array($include) ) {
			$include = [];
		}

		// no toolset specified?
		if ( ! $toolset_id)
		{
			$toolset_id = ee()->rte_toolset_model->get_member_toolset();
		}

		// get the toolset
		$toolset = ee()->rte_toolset_model->get($toolset_id);

		if ( ! $toolset OR ! $toolset['tools'])
		{
			return;
		}

		// get the tools
		if ( ! $tools = ee()->rte_tool_model->get_tools($toolset['tools'], $cp_only))
		{
			return;
		}

		// bare minimum required
		$bits	= array(
			'globals'		=> array(
				'rte' => array()
			),
			'styles' 		=> '',
			'definitions' 	=> '',
			'buttons' 		=> array(),
			'libraries' 	=> array(
				'plugin' => array('wysihat')
			)
    );
    
    $include['jquery_ui'] = isset($include['jquery_ui']) ? $include['jquery_ui'] : false;

		if (array_key_exists('jquery_ui', $include) && $include['jquery_ui'])
		{
			$bits['libraries']['ui'] = array('core', 'widget');
		}

		foreach ($tools as $tool)
		{
			// skip tools that are not available to the front-end
			if ($tool['info']['cp_only'] == 'y' && ! $cp_only)
			{
				continue;
			}

			// load the globals
			if (count($tool['globals']))
			{
				$tool['globals'] = $this->_prep_globals($tool['globals']);
				$bits['globals'] = array_merge_recursive($bits['globals'], $tool['globals']);
			}

			// load any libraries we need
			if ($tool['libraries'] && count($tool['libraries']))
			{
				$bits['libraries'] = array_merge_recursive($bits['libraries'], $tool['libraries']);
			}

			// add any styles we need
			if ( ! empty($tool['styles']))
			{
				$bits['styles'] .= $tool['styles'];
			}

			// load the definition
			if ( ! empty($tool['definition']))
			{
				$bits['definitions'] .= $tool['definition'];
			}

			// add to toolbar
			$bits['buttons'][] = strtolower(str_replace(' ', '_', $tool['info']['name']));
		}

		// Due to some UI constraints headings must come after "normal" buttons
		$key = array_search('headings', $bits['buttons']);
		if ($key !== FALSE)
		{
			$button = array_splice($bits['buttons'], $key, 1);
			$bits['buttons'][] = $button;
		}

		// Due to some UI constraints view_source must come last
		$key = array_search('view_source', $bits['buttons']);
		if ($key !== FALSE)
		{
			$button = array_splice($bits['buttons'], $key, 1);
			$bits['buttons'][] = $button;
		}

		// potentially required assets
		$jquery = URL_THEMES_GLOBAL_ASSET.'javascript/'.PATH_JS.'/jquery/jquery.js';

		ee()->load->library('javascript');

		$js = '(function() {'."\n";
		$js .= 	'// make sure we have jQuery
				var interval = null;'."\n";

		if ($cp_only === FALSE)
		{
			// kick off the JS
			$js .= '
				var EE = ' . json_encode(ee()->javascript->global_vars) . ';';

			if (array_key_exists('jquery', $include) && $include['jquery'])
			{
				$js .= '
					var j = document.createElement("script");
					j.setAttribute("src","' . $jquery . '");
					document.getElementsByTagName("head")[0].appendChild(j);';
			}
		}


		// Even if we don't load jQuery above, we still need to wait for it
		$js .= '
			if (typeof jQuery === "undefined")
			{
				interval = setInterval(loadRTE, 100);
			}
			else
			{
				loadRTE();
			}

			function loadRTE()
			{
				// make sure jQuery is loaded
				if ( typeof jQuery === "undefined" ){ return; }
				clearInterval( interval );

				var $ = jQuery;

				// RTE library
				' . $this->_load_js_files($bits['libraries']) . '

				// RTE styles
				$("head")
					.append( $("<style>' . preg_replace( '/\\s+/', ' ', $bits['styles'] ) . '</style>"));

				// RTE globals
				' . $this->_set_globals($bits['globals']) . '

				// RTE button class definitions
				' . $bits['definitions'] . '

				// RTE editor setup for this page
				$("' . $selector . '")
					.not(".grid-input-form ' . $selector . '")
					.not(".fluid-field-templates ' . $selector . '")
					.addClass("WysiHat-field")
					.wysihat({
						buttons: '.json_encode($bits['buttons']).'
					});

				if (typeof FluidField === "object")
				{
					FluidField.on("rte", "add", function(el) {
						$("' . $selector . '", el).addClass("WysiHat-field")
							.wysihat({
								buttons: '.json_encode($bits['buttons']).'
							});
					});
				}

				if (typeof Grid === "object")
				{
					Grid.bind("rte", "display", function(cell)
					{
						$("' . $selector . '", cell)
							.addClass("WysiHat-field")
							.wysihat({
								buttons: '.json_encode($bits['buttons']).'
							});
					});
				}
			}
		})();';

		return $js;
	}

	/**
	 * Save RTE field
	 *
	 * Use to clean up RTE content prior to DB insertion
	 *
	 * @param string $data the RTE html content
	 *
	 * @return string   the cleaned up RTE html content
	 */
	public function save_field($data)
	{
		if (ee()->session->userdata('rte_enabled') != 'y'
			OR ee()->config->item('rte_enabled') != 'y')
		{
			return $data;
		}

		// If the editor was saved empty, save nothing to database
		// so it behaves as expected with conditional tags
		if ($this->is_empty(trim($data)))
		{
			return '';
		}

		// Swap the real URL with {filedir_x}
		ee()->load->model('file_upload_preferences_model');
		$dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id')
		);

		foreach($dirs as $d)
		{
			$data = str_replace($d['url'], "{filedir_{$d['id']}}", $data);
		}

		return $data;
	}

	/**
	 * Display an RTE field
	 *
	 * @param string $data       the RTE html content
	 * @param string $field_name the field name for the RTE field
	 * @param array $settings   field settings:
	 * 					field_ta_rows - the number of textarea rows
	 * 					field_text_direction - ltr or rtl
	 * 					field_fmt - xhtml, br or none
	 *
	 * @return string
	 */
	public function display_field($data, $field_name, $settings, $container = NULL)
	{
		if ( ! ee()->session->cache('rte', 'loaded'))
		{
			$rte_toolset_id = 0;
			if (isset(ee()->TMPL))
			{
				$rte_toolset_id = (int) ee()->TMPL->fetch_param('rte_toolset_id', 0);
			}

			ee()->javascript->output(
				ee()->rte_lib->build_js($rte_toolset_id, '.WysiHat-field', NULL, (REQ == 'CP'))
			);

			ee()->session->set_cache('rte', 'loaded', TRUE);
		}

		ee()->load->helper('form');

		$field = array(
			'name'	=> $field_name,
			'id'	=> $field_name,
			'rows'	=> $settings['field_ta_rows'],
			'dir'	=> $settings['field_text_direction'],
			'class' => 'has-rte'
		);

		$data = trim($data);
		$data = htmlspecialchars_decode($data, ENT_QUOTES);

		// Check the RTE module and user's preferences
		if ((ee()->session->userdata('rte_enabled') == 'y' OR (ee()->session->userdata('rte_enabled') != 'y'AND ee()->session->userdata('group_id') == 3))
			AND ee()->config->item('rte_enabled') == 'y')
		{
			$field['class']	.= ' WysiHat-field';

			// xhtml vs br
			ee()->load->library('typography');
			$data = ee()->typography->auto_typography($data, TRUE);

			// remove non breaking spaces. typography likes to throw those
			// in when a list is indented.
			$data = str_replace('&nbsp;', ' ', $data);
		}

		// Swap {filedir_x} with the real URL. It will be converted back
		// upon submit by the RTE Image tool.
		ee()->load->model('file_upload_preferences_model');
		$dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id')
		);

		foreach($dirs as $d)
		{
			// tag to replace
			$filedir = "{filedir_{$d['id']}}";
			$data = str_replace($filedir, $d['url'], $data);
		}

		$field['value'] = $data;

		$return_data = form_textarea($field);

		return $return_data;
	}

	/**
	 * Check whether the specified data is empty html
	 *
	 * @param string $data the RTE html content
	 *
	 * @return bool
	 */
	public function is_empty($data)
	{
		return in_array($data, $this->_empty);
	}

	/**
	 * Loads JS library files
	 *
	 * Note: This is partially borrowed from the combo loader
	 *
	 * @access	private
	 * @param	array $load A collection of JS libraries to load
	 * @return	string The libraries
	 */
	private function _load_js_files($load = array())
	{
		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', PATH_THEMES.'asset/javascript/'.PATH_JS.'/jquery/');
		}

		$types	= array(
			'ui'		=> PATH_JQUERY.'ui/jquery.ui.',
			'plugin'	=> PATH_JQUERY.'plugins/',
			'file'		=> PATH_THEMES.'asset/javascript/'.PATH_JS.'/',
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_ADDONS
		);

		$contents = '';

		foreach ($types as $type => $path)
		{
			if (isset($load[$type]))
			{
				// Don't load the same library twice
				$load[$type] = array_unique((array)$load[$type]);

				$files = $load[$type];

				if ( ! is_array($files))
				{
					$files = array( $files );
				}

				foreach ($files as $file)
				{
					if ($type == 'package' OR $type == 'fp_module')
					{
						$file = $file.'/javascript/'.$file;
					}
					elseif ($type == 'file')
					{
						$parts = explode('/', $file);
						$file = array();

						foreach ($parts as $part)
						{
							if ($part != '..')
							{
								$file[] = ee()->security->sanitize_filename($part);
							}
						}

						$file = implode('/', $file);
					}
					else
					{
						$file = ee()->security->sanitize_filename($file);
					}

					$file = $path.$file.'.js';

					if (file_exists($file))
					{
						$contents .= file_get_contents($file)."\n\n";
					}
				}
			}
		}

		return $contents;
	}

	/**
	 * Prep global variables for JS
	 *
	 * @access	private
	 * @param	array $globals The globals to load into JS
	 * @return	array the revised $globals array
	 */
	private function _prep_globals($globals = array())
	{
		$temp = array();

		foreach ($globals as $key => $val)
		{
			if (strpos($key,'.') !== FALSE)
			{
				$parts	= explode('.', $key);
				$parent	= array_shift($parts);
				$key	= implode('.', $parts);
				$temp[$parent] = $this->_prep_globals(array(
					$key	=> $val
				));
			}
			else
			{
				$temp[$key] = $val;
			}
		}

		return $temp;
	}

	/**
	 * Manage the assignment of global JS
	 *
	 * @access	private
	 * @param	array $globals The globals to load into JS
	 * @return	string The JavaScript
	 */
	private function _set_globals($globals = array())
	{
		ee()->load->library('javascript');

		$js = '';

		if (count($globals))
		{
			$js .= 'var EE = ' . json_encode($globals) . ';';
		}

		return $js;
	}

	/**
	 * Tool Set Valid Name handler
	 *
	 * @return bool
	 */
	public function _valid_name($str)
	{
		// check name for XSS
		if ($str != strip_tags($str)
			OR $str != htmlentities($str)
			OR $str != ee('Security/XSS')->clean($str))
		{
			ee()->form_validation->set_message('_valid_name', lang('valid_name_required'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Tool Set Unique Name handler
	 *
	 * @return bool
	 */
	public function _unique_name($str)
	{
		$toolset_id = ee()->input->get_post('toolset_id');
		$is_members = (ee()->input->get_post('private') == 'true');

		// is the name unique?
		if ( ! $is_members && ! ee()->rte_toolset_model->unique_name($str, $toolset_id))
		{
			ee()->form_validation->set_message('_unique_name', lang('unique_name_required'));
			return FALSE;
		}

		return TRUE;
	}
}

// EOF
