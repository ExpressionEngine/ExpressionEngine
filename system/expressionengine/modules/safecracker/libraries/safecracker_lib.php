<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team, 
 * 		- Original Development by Barrett Newton -- http://barrettnewton.com
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine SafeCracker Module Library 
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Safecracker_lib
{
	public $initialized = FALSE;
	public $form_error = FALSE;
	public $site_id;
	
	public $categories;
	public $channel;
	public $checkboxes;
	public $custom_field_conditional_names;
	public $custom_fields;
	public $custom_option_fields;
	public $date_fields;
	public $datepicker;
	public $default_fields;
	public $edit;
	public $entry;
	public $error_handling;
	public $errors;
	public $field_errors;
	public $file;
	public $file_fields;
	public $form_validation_methods;
	public $head;
	public $json;
	public $logged_out_member_id;
	public $logged_out_group_id;
	public $native_option_fields;
	public $native_variables;
	public $option_fields;
	public $parse_variables;
	public $pre_save;
	public $preserve_checkboxes;
	public $post_error_callbacks;
	public $require_save_call;
	public $settings;
	public $skip_xss_fieldtypes;
	public $skip_xss_field_ids;
	public $statuses;
	public $show_fields;
	public $title_fields;
	public $valid_callbacks;
	
	public $lang, $api_channel_fields, $form_validation;
	
	/**
	 * constructor
	 * 
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		//set a global object
		$this->EE->safecracker = $this;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Creates the entry form
	 * 
	 * @return	string
	 */
	public function entry_form()
	{
		//can't be used in a form action
		if ( ! isset($this->EE->TMPL))
		{
			return '';
		}
		
		$this->EE->lang->loadfile('safecracker');
		
		if ( ! isset($this->EE->extensions->extensions['form_declaration_modify_data'][10]['Safecracker_ext']))
		{
			return $this->EE->output->show_user_error(FALSE, $this->EE->lang->line('safecracker_extension_not_installed'));
		}
		
		// -------------------------------------------
		// 'safecracker_entry_form_tagdata_start' hook.
		//  - Developers, if you want to modify the $this object remember
		//	to use a reference on func call.
		//

		if ($this->EE->extensions->active_hook('safecracker_entry_form_absolute_start') === TRUE)
		{
			$this->EE->extensions->call('safecracker_entry_form_absolute_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		}
		
		$this->fetch_site($this->EE->TMPL->fetch_param('site'));
		
		$this->initialize(empty($this->form_error));
		
		$this->EE->load->helper('form');
		$this->EE->router->set_class('cp');
		$this->EE->load->library('cp');
		$this->EE->router->set_class('ee');
		$this->EE->load->library('javascript');
		$this->EE->load->library('api');
		$this->EE->load->library('form_validation');
		$this->EE->api->instantiate('channel_fields');
		$this->load_channel_standalone();
		
		$this->EE->lang->loadfile('content');
		$this->EE->lang->loadfile('upload');
		
		$this->EE->javascript->output('if (typeof SafeCracker == "undefined" || ! SafeCracker) { var SafeCracker = {markItUpFields:{}};}');
		
		// Figure out what channel we're working with
		$this->fetch_channel($this->EE->TMPL->fetch_param('channel_id'), $this->EE->TMPL->fetch_param('channel'));
		
		if ( ! $this->channel)
		{
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('safecracker_no_channel'));
		}
		
		//get the entry data, if an entry was specified
		$this->fetch_entry($this->EE->TMPL->fetch_param('entry_id'), $this->EE->TMPL->fetch_param('url_title'));
		
		// Whoa there big conditional, what's going on here?
		// We want to make sure no one's being tricky here and supplying
		// an invalid entry_id or url_title via a segment, so we need to
		// check to see if either exists and if it does make sure that the
		// passed in version is the same as what we find in the database.
		// If they are different (most likely it wasn't found in the 
		// database) then don't show them the form
		
		if (
			($this->EE->TMPL->fetch_param('entry_id') != '' AND
			$this->entry('entry_id') != $this->EE->TMPL->fetch_param('entry_id')) OR
			($this->EE->TMPL->fetch_param('url_title') != '' AND
			$this->entry('url_title') != $this->EE->TMPL->fetch_param('url_title'))
		)
		{
			if ($this->EE->TMPL->no_results())
			{
				return $this->EE->TMPL->no_results();
			}
			
			return $this->EE->output->show_user_error(FALSE, $this->EE->lang->line('safecracker_require_entry'));
		}
		
		// @added rev 57
		if ( ! $this->entry('entry_id') && $this->bool_string($this->EE->TMPL->fetch_param('require_entry')))
		{
			if ($this->EE->TMPL->no_results())
			{
				return $this->EE->TMPL->no_results();
			}
			
			return $this->EE->output->show_user_error(FALSE, $this->EE->lang->line('safecracker_require_entry'));
		}
		
		if ($this->entry('entry_id') && ! $this->form_error)
		{
			$this->edit = TRUE;
		}
		
		// @added rev 57
		if ($this->edit && $this->bool_string($this->EE->TMPL->fetch_param('author_only')) && $this->entry('author_id') != $this->EE->session->userdata('member_id'))
		{
			return $this->EE->output->show_user_error(FALSE, $this->EE->lang->line('safecracker_author_only'));
		}
		
		if (is_array($this->entry('category')))
		{
			$this->entry['categories'] = $this->entry('category');
		}
		
		//add hidden field data
		$this->form_hidden(
			array(
				'ACT' => $this->EE->functions->fetch_action_id('Safecracker', 'submit_entry'),
				'site_id' => $this->site_id,
				'return' => ($this->EE->TMPL->fetch_param('return_'.$this->EE->session->userdata('group_id'))) ? $this->EE->TMPL->fetch_param('return_'.$this->EE->session->userdata('group_id')) : $this->EE->TMPL->fetch_param('return'),
				'json' => $this->bool_string($this->EE->TMPL->fetch_param('json')) ? 1 : FALSE,
				'dynamic_title' => ($this->EE->TMPL->fetch_param('dynamic_title')) ? base64_encode($this->EE->TMPL->fetch_param('dynamic_title')) : FALSE,
				'error_handling' => ($this->EE->TMPL->fetch_param('error_handling')) ? $this->EE->TMPL->fetch_param('error_handling') : FALSE,
				'preserve_checkboxes' => ($this->EE->TMPL->fetch_param('preserve_checkboxes')) ? $this->EE->TMPL->fetch_param('preserve_checkboxes') : FALSE,
				'secure_return' => $this->bool_string($this->EE->TMPL->fetch_param('secure_return')) ? 1 : FALSE,
				'allow_comments' => $this->bool_string($this->EE->TMPL->fetch_param('allow_comments')) ? 'y' : 'n',
			)
		);
		
		unset($this->EE->TMPL->tagparams['allow_comments']);
		
		//add form attributes
		$this->form_attribute(
			array(
				'onsubmit' => $this->EE->TMPL->fetch_param('onsubmit'),
				'name' => $this->EE->TMPL->fetch_param('name'),
				'class' => $this->EE->TMPL->fetch_param('class'),
				'id' => $this->EE->TMPL->fetch_param('id')
			)
		);
		
		if ($this->EE->TMPL->fetch_param('datepicker'))
		{
			$this->datepicker = $this->bool_string($this->EE->TMPL->fetch_param('datepicker'), $this->datepicker);
		}
		
		if ($this->datepicker)
		{
			$this->EE->javascript->output('$.datepicker.setDefaults({dateFormat:$.datepicker.W3C+EE.date_obj_time});');
		}
		
		foreach ($this->EE->TMPL->tagparams as $key => $value)
		{
			if (preg_match('/^rules:(.+)/', $key, $match))
			{
				$this->form_hidden('rules['.$match[1].']', $this->encrypt_input($value));
			}
		}
		
		//decide which fields to show, based on pipe delimited list of field id's and/or field short names
		if ($this->EE->TMPL->fetch_param('show_fields'))
		{
			if (preg_match('/not (.*)/', $this->EE->TMPL->fetch_param('show_fields'), $match))
			{
				foreach ($this->custom_fields as $field_name => $field)
				{
					$this->show_fields[] = $field_name;
				}
				
				foreach (explode('|', $match[1]) as $field_name)
				{
					if (is_numeric($field_name))
					{
						$field_name = $this->get_field_name($field_name);
					}
					
					$index = ($field_name !== FALSE) ? array_search($field_name, $this->show_fields) : FALSE;
					
					if ($index !== FALSE)
					{
						unset($this->show_fields[$index]);
					}
				}
			}
			else
			{
				foreach (explode('|', $this->EE->TMPL->fetch_param('show_fields')) as $field_name)
				{
					if (is_numeric($field_name))
					{
						$field_name = $this->get_field_name($field_name);
					}
					
					if ($field_name)
					{	
						$this->show_fields[] = $field_name;
					}
				}
			}
		}
		
		// -------------------------------------------
		// 'safecracker_entry_form_tagdata_start' hook.
		//  - Developers, if you want to modify the $this object remember
		//	to use a reference on func call.
		//

		if ($this->EE->extensions->active_hook('safecracker_entry_form_tagdata_start') === TRUE)
		{
			$this->EE->TMPL->tagdata = $this->EE->extensions->call('safecracker_entry_form_tagdata_start', $this->EE->TMPL->tagdata, $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		}
		
		// build custom field variables
		$custom_field_variables = array();
		
		foreach ($this->custom_fields as $field_name => $field)
		{
			/*
			if ($this->EE->TMPL->fetch_param($field_name))
			{
				$this->form_hidden($field_name, $this->EE->TMPL->fetch_param($field_name));
			}
			*/
			
			// standard vars/conditionals
			$custom_field_variables_row = array(
				'required' => ($field['field_required'] == 'n') ? 0 : 1,
				'text_direction' => $field['field_text_direction'],
				'field_data' => $this->entry($field_name),
				'rows' => $field['field_ta_rows'],
				'maxlength' => $field['field_maxl'],
				'formatting_buttons' => '',
				'field_show_formatting_btns' => (isset($field['field_show_formatting_btns']) && $field['field_show_formatting_btns'] == 'y') ? 1 : 0,
				'textinput' => 0,
				'pulldown' => 0,
				'checkbox' => 0,
				'relationship' => 0,
				'multiselect' => 0,
				'date' => 0,
				'radio' => 0,
				'display_field' => '',
				'options' => $this->get_field_options($field_name, ($field['field_required'] == 'n' && ! preg_match('/multi_?select|radio|checkbox/', $field['field_type']))),
				'error' => ( ! empty($this->field_errors[$field['field_name']])) ? $this->EE->lang->line($this->field_errors[$field['field_name']]) : ''
			);
			
			$custom_field_variables_row = array_merge($field, $custom_field_variables_row);
			
			$fieldtypes = $this->EE->api_channel_fields->fetch_installed_fieldtypes();
			
			//add a negative conditional based on fieldtype
			foreach ($fieldtypes as $type => $fieldtype)
			{
				$custom_field_variables_row[$type] = 0;
			}
			
			// fieldtype conditionals
			foreach ($this->custom_fields as $f_name => $f)
			{
				$custom_field_variables_row[$f['field_type']] = $custom_field_variables_row[$f_name] = ($field['field_type'] == $f['field_type']) ? 1 : 0;
			}
			
			if (array_key_exists($field['field_type'], $this->custom_field_conditional_names))
			{
				$custom_field_variables_row[$this->custom_field_conditional_names[$field['field_type']]] = 1;
			}
			
			if ($field['field_type'] == 'date')
			{
				if ($this->datepicker)
				{
					$default_date = (($this->entry($field_name)) ? $this->entry($field_name) : $this->EE->localize->now) * 1000;
					$this->EE->javascript->output('$(\'input[name="'.$field_name.'"]\').datepicker({defaultDate: new Date('.$default_date.')});');
				}
				
				//$custom_field_variables_row['field_data'] = $this->EE->localize->set_human_time($this->EE->localize->offset_entry_dst($this->entry($field_name), $this->entry('dst_enabled'), FALSE));
				$custom_field_variables_row['field_data'] = $this->EE->localize->set_human_time($this->entry($field_name));
			}
			
			$custom_field_variables[$field_name] = $custom_field_variables_row;
		}
		
		// parse custom fields loop
		if (preg_match('/'.LD.'custom_fields'.RD.'(.*)'.LD.'\/custom_fields'.RD.'/s', $this->EE->TMPL->tagdata, $match))
		{
			$custom_field_output = '';
			
			$tagdata = $match[1];
			
			$formatting_buttons = (strpos($tagdata, LD.'formatting_buttons'.RD) !== FALSE);
			
			foreach ($custom_field_variables as $field_name => $custom_field_variables_row)
			{
				if ($this->show_fields && ! in_array($field_name, $this->show_fields))
				{
					continue;
				}
				
				if ($formatting_buttons && $custom_field_variables_row['field_show_formatting_btns'])
				{
					$this->markitup = TRUE;
					$this->EE->javascript->output('SafeCracker.markItUpFields["'.$field_name.'"] = '.$custom_field_variables_row['field_id'].';');
				}
				
				$temp = $tagdata;
				
				//parse conditionals
				//$temp = $this->swap_conditionals($temp, $custom_field_variables_row);
				$embed_vars = $this->EE->TMPL->embed_vars;
				
				$this->EE->TMPL->embed_vars = array_merge($this->EE->TMPL->embed_vars, $custom_field_variables_row);
				
				$temp = $this->EE->TMPL->advanced_conditionals($temp);
				
				$this->EE->TMPL->embed_vars = $embed_vars;
				
				if (strpos($temp, LD.'display_field'.RD) !== FALSE)
				{
					$custom_field_variables_row['display_field'] = $this->display_field($field_name);
					
					if ($custom_field_variables_row['field_type'] == 'file')
					{
						$custom_field_variables_row['display_field'] = '<div class="publish_field">'.$custom_field_variables_row['display_field'].'</div>';
					}
				}
				
				foreach ($custom_field_variables_row as $key => $value)
				{
					if (is_array($value))
					{
						$temp = $this->swap_var_pair($key, $value, $temp);
					}
					// don't use our conditionals as vars
					else if ( ! is_int($value))
					{
						$temp = $this->EE->TMPL->swap_var_single($key, $value, $temp);
					}
				}
				
				if ($custom_field_variables_row['field_type'] === 'catchall')
				{
					$temp = $this->replace_tag($field_name, $this->entry($field_name), array(), $temp);
				}
				
				$custom_field_output .= $temp;
			}
			
			$this->EE->TMPL->tagdata = str_replace($match[0], $custom_field_output, $this->EE->TMPL->tagdata);
		}
		
		if ( ! empty($this->markitup))
		{
			$this->EE->javascript->output('$.each(SafeCracker.markItUpFields,function(a){$("#"+a).markItUp(mySettings);});');
		}
		
		foreach ($this->EE->TMPL->var_pair as $tag_pair_open => $tagparams)
		{
			$tag_name = current(preg_split('/\s/', $tag_pair_open));
			
			if ($tag_name == 'categories')
			{
				$this->EE->TMPL->tagdata = $this->swap_var_pair($tag_pair_open, $this->categories($tagparams), $this->EE->TMPL->tagdata, $tag_name, ! empty($tagparams['backspace']) ? $tagparams['backspace'] : FALSE);
				//$this->parse_variables['categories'] = $this->categories($tagparams);
			}
			
			else if ($tag_name == 'statuses')
			{
				$this->fetch_statuses();
				
				$this->parse_variables['statuses'] = $this->statuses;
			}
			
			//custom field pair parsing with replace_tag
			else if (isset($this->custom_fields[$tag_name]))
			{
				if (preg_match_all('/'.LD.preg_quote($tag_pair_open).RD.'(.*)'.LD.'\/'.$tag_name.RD.'/s', $this->EE->TMPL->tagdata, $matches))
				{	
					foreach ($matches[1] as $match_index => $var_pair_tagdata)
					{
						$this->EE->TMPL->tagdata = str_replace($matches[0][$match_index], $this->replace_tag($tag_name, $this->entry($tag_name), $tagparams, $var_pair_tagdata), $this->EE->TMPL->tagdata);
					}
				}
			}
			
			//options:field_name tag pair parsing
			else if (preg_match('/^options:(.*)/', $tag_name, $match) && in_array($this->get_field_type($match[1]), $this->option_fields))
			{
				$this->parse_variables[$match[0]] = (isset($custom_field_variables[$match[1]]['options'])) ? $custom_field_variables[$match[1]]['options'] : '';
			}
			
			//parse category menu
			else if ($tag_name == 'category_menu')
			{
				$this->channel_standalone->_category_tree_form($this->channel('cat_group'), 'edit', '', $this->entry('categories'));
				
				$this->parse_variables['category_menu'] = array(array('select_options' => implode("\n", $this->channel_standalone->categories)));
			}
			
			//parse status menu
			else if ($tag_name = 'status_menu')
			{
				$this->fetch_statuses();
				
				$select_options = '';
				
				foreach ($this->statuses as $status)
				{
					$status['selected'] = ($this->entry('status') == $status['status']) ? ' selected="selected"' : '';
					
					$status['checked'] = ($this->entry('status') == $status['status']) ? ' checked="checked"' : '';
					
					$status['name'] = (in_array($status['status'], array('open', 'closed'))) ? $this->EE->lang->line($status['status']) : $status['status'];
					
					$select_options .= '<option value="'.$status['status'].'"'.$status['selected'].'>'.$status['name'].'</option>'."\n";
				}
				
				$this->parse_variables['status_menu'] = array(array('select_options' => $select_options));
			}
		}
		
		//edit form
		if ($this->entry)
		{
			//not necessary for edit forms
			$this->EE->TMPL->tagparams['use_live_url'] = 'no';
			
			//$expiration_date = ($this->entry('expiration_date')) ? $this->entry('expiration_date')*1000 : $this->EE->localize->offset_entry_dst($this->EE->localize->now, $this->entry('dst_enabled'), FALSE)*1000;
			//$comment_expiration_date = ($this->entry('comment_expiration_date')) ? $this->entry('comment_expiration_date')*1000 : $this->EE->localize->offset_entry_dst($this->EE->localize->now, $this->entry('dst_enabled'), FALSE)*1000;
			$expiration_date = ($this->entry('expiration_date')) ? $this->entry('expiration_date')*1000 : $this->EE->localize->now*1000;
			$comment_expiration_date = ($this->entry('comment_expiration_date')) ? $this->entry('comment_expiration_date')*1000 : $this->EE->localize->now*1000;
			
			if ($this->datepicker)
			{
				if (strpos($this->EE->TMPL->tagdata, 'entry_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=entry_date]").datepicker({defaultDate: new Date('.($this->entry('entry_date')*1000).')});');
				}
				
				if (strpos($this->EE->TMPL->tagdata, 'expiration_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=expiration_date]").datepicker({defaultDate: new Date('.$expiration_date.')});');
				}
				
				if (strpos($this->EE->TMPL->tagdata, 'comment_expiration_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=comment_expiration_date]").datepicker({defaultDate: new Date('.$comment_expiration_date.')});');
				}
			}

			foreach ($this->EE->TMPL->var_single as $key)
			{
				if ($this->entry($key) !== FALSE)
				{
					if (in_array($key, $this->date_fields) || $this->get_field_type($key) == 'date')
					{
						//$this->parse_variables[$key] = ($this->entry($key)) ? $this->EE->localize->set_human_time($this->EE->localize->offset_entry_dst($this->entry($key), $this->entry('dst_enabled'), FALSE)) : '';
						$this->parse_variables[$key] = ($this->entry($key)) ? $this->EE->localize->set_human_time($this->entry($key)) : '';
					}
					elseif (in_array($key, $this->checkboxes))
					{
						$this->parse_variables[$key] = ($this->entry($key) == 'y') ? 'checked="checked"' : '';
					}
					else
					{
						$this->parse_variables[$key] = $this->entry($key);
					}
				}
				
				else if (preg_match('/entry_id_path=([\042\047])?([^\042\047]*)[\042\047]?/', $key, $match))
				{
					$this->parse_variables[$match[0]] = $this->EE->functions->create_url($match[2].'/'.$this->entry('entry_id'));
				}
				
				else if (preg_match('/(url_title_path|title_permalink)=[\042\047]?([^\042\047]*)[\042\047]?/', $key, $match))
				{
					$this->parse_variables[$match[0]] = $this->EE->functions->create_url($match[2].'/'.$this->entry('url_title'));
				}
				
				// use fieldtype display_field method
				else if (preg_match('/^field:(.*)$/', $key, $match))
				{
					$this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields)) ? $this->display_field($match[1]) : '';
				}
				
				else if (preg_match('/^label:(.*)$/', $key, $match))
				{
					$this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields)) ? $this->custom_fields[$match[1]]['field_label'] : '';
				}
				
				else if (preg_match('/^selected_option:(.*?)(:label)?$/', $key, $match) && in_array($this->get_field_type($match[1]), $this->option_fields))
				{
					$options = (isset($custom_field_variables[$match[1]]['options'])) ? $custom_field_variables[$match[1]]['options'] : array();
					
					$selected_option = '';
					
					foreach ($options as $option)
					{
						if ($option['option_value'] == $this->entry($match[1]))
						{
							$selected_option = ( ! empty($match[2])) ? $option['option_name'] : $option['option_value'];
						}
					}
					
					$this->parse_variables[$match[0]] = $selected_option;
				}
				
				else if (preg_match('/^instructions:(.*)$/', $key, $match))
				{
					$this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields)) ? $this->custom_fields[$match[1]]['field_instructions'] : '';
				}
				
				else if (preg_match('/^error:(.*)$/', $key, $match))
				{
					$this->parse_variables[$match[0]] = ( ! empty($this->field_errors[$match[1]])) ? $this->field_errors[$match[1]] : '';
				}
			}
			
			$this->form_hidden(
				array(
				      'entry_id' => $this->entry('entry_id'),
				      'unique_url_title' => ($this->bool_string($this->EE->TMPL->fetch_param('unique_url_title'))) ? '1' : '',
				      'author_id'=> $this->entry('author_id')
				)	
			);
			
		}
		elseif ($this->channel('channel_id'))
		{
			$this->parse_variables['entry_date'] = $this->EE->localize->set_human_time();
			
			if ($this->datepicker)
			{
				//$this->EE->javascript->output('$.datepicker.setDefaults({defaultDate: new Date('.($this->EE->localize->offset_entry_dst($this->EE->localize->now, FALSE, FALSE)*1000).')});');
				$this->EE->javascript->output('$.datepicker.setDefaults({defaultDate: new Date('.($this->EE->localize->now*1000).')});');

				if (strpos($this->EE->TMPL->tagdata, 'entry_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=entry_date]").datepicker();');
				}
				
				if (strpos($this->EE->TMPL->tagdata, 'expiration_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=expiration_date]").datepicker();');
				}
				
				if (strpos($this->EE->TMPL->tagdata, 'comment_expiration_date') !== FALSE)
				{
					$this->EE->javascript->output('$("input[name=comment_expiration_date]").datepicker();');
				}
			}
			
			foreach ($this->custom_fields as $field)
			{
				foreach ($this->EE->TMPL->var_pair as $tag_pair_open => $tagparams)
				{
					$tag_name = current(preg_split('/\s/', $tag_pair_open));
					
					if ($tag_name == $field['field_name'])
					{
						//special parsing here for catchall fieldtype, pls keep this in
						if ($field['field_type'] === 'catchall')
						{
							if (preg_match_all('/'.LD.$tag_pair_open.RD.'(.*)'.LD.'\/'.$field['field_name'].RD.'/s', $this->EE->TMPL->tagdata, $matches))
							{
								foreach ($matches[1] as $match_index => $var_pair_tagdata)
								{
									if (preg_match_all('/'.LD.'([^\s]*)'.RD.'(.*)'.LD.'\/'.'\1'.RD.'/s', $var_pair_tagdata, $submatches))
									{
										foreach ($submatches[2] as $submatch_index => $sub_var_pair_tagdata)
										{
											$var_pair_tagdata = str_replace($submatches[0][$submatch_index], $sub_var_pair_tagdata, $var_pair_tagdata);
										}
									}
							
									$var_pair_tagdata = preg_replace('/'.LD.'([^\s]*)'.RD.'/s', '', $var_pair_tagdata);
									
									$this->EE->TMPL->tagdata = str_replace($matches[0][$match_index], $var_pair_tagdata, $this->EE->TMPL->tagdata);
								}
							}
						}
						else
						{
							$this->parse_variables[$field['field_name']] = '';
						}
					}
					
					else if ($tag_name == 'options:'.$field['field_name'] && in_array($this->get_field_type($field['field_name']), $this->option_fields))
					{
						$this->parse_variables['options:'.$field['field_name']] = (isset($custom_field_variables[$field['field_name']]['options'])) ? $custom_field_variables[$field['field_name']]['options'] : '';
					}
				}
				
				$this->parse_variables[$field['field_name']] = '';
				$this->parse_variables['label:'.$field['field_name']] = $field['field_label'];
				$this->parse_variables['selected_option:'.$field['field_name'].':label'] = '';
				$this->parse_variables['selected_option:'.$field['field_name']] = '';
				$this->parse_variables['label:'.$field['field_name']] = $field['field_label'];
				$this->parse_variables['instructions:'.$field['field_name']] = $field['field_instructions'];
				$this->parse_variables['error:'.$field['field_name']] = ( ! empty($this->field_errors[$field['field_name']])) ? $this->field_errors[$field['field_name']] : '';
				
				//let's not needlessly call this, otherwise we could get duplicate fields rendering
				if (strpos($this->EE->TMPL->tagdata, LD.'field:'.$field['field_name'].RD) !== FALSE)
				{
					$this->parse_variables['field:'.$field['field_name']] = (array_key_exists($field['field_name'], $this->custom_fields)) ? $this->display_field($field['field_name']) : '';
				}
			}
		}

		foreach ($this->title_fields as $field)
		{
			if (isset($this->EE->TMPL->var_single['error:'.$field]))
			{
				$this->parse_variables['error:'.$field] = ( ! empty($this->field_errors[$field])) ? $this->field_errors[$field] : '';
			}
		}
		
		// Add global errors
		if (count($this->errors) === 0)
		{
			$this->parse_variables['global_errors'] = array(array());
		}
		else
		{
			$this->parse_variables['global_errors'] = array();
			
			foreach ($this->errors as $error)
			{
				$this->parse_variables['global_errors'][] = array('error' => $error);
			}
		}
		
		$this->parse_variables['global_errors:count'] = count($this->errors);
		
		// Add field errors
		if (count($this->field_errors) === 0)
		{
			$this->parse_variables['field_errors'] = array(array());
		}
		else
		{
			$this->parse_variables['field_errors'] = array();
			
			foreach ($this->field_errors as $field => $error)
			{
				$this->parse_variables['field_errors'][] = array('field' => $field, 'error' => $error);
			}
		}
		
		$this->parse_variables['field_errors:count'] = count($this->field_errors);
		
		// Add field errors to conditional parsing
		$conditional_errors = $this->parse_variables;
		if ( ! empty($conditional_errors['field_errors'][0]))
		{
			foreach ($conditional_errors['field_errors'] as $error)
			{
				$conditional_errors['error:' . $error['field']] = $error['error'];
			}
			
			unset($conditional_errors['field_errors']);
		}
		
		// Parse captcha conditional
		$captcha_conditional = array(
			'captcha' => ($this->channel('channel_id') && $this->logged_out_member_id && ! empty($this->settings['require_captcha'][$this->EE->config->item('site_id')][$this->channel('channel_id')]))
		);

		// Parse conditionals
		// $this->parse_variables['error:title'] = TRUE;
		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals(
			$this->EE->TMPL->tagdata, 
			array_merge($conditional_errors, $captcha_conditional)
		);
		
		// Make sure {captcha_word} is blank
		$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha_word', '', $this->EE->TMPL->tagdata);
		
		// Replace {captcha} with actual captcha
		$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('captcha', $this->EE->functions->create_captcha(), $this->EE->TMPL->tagdata);
		
		// Parse the variables
		if ($this->parse_variables)
		{
			$this->EE->TMPL->tagdata = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($this->parse_variables));
		}
			
		if ($this->file)
		{
			$this->EE->session->cache['safecracker']['enctype'] = 'enctype="multipart/form-data"';
		}
		
		//load member data for logged out member
		$this->fetch_logged_out_member($this->EE->TMPL->fetch_param('logged_out_member_id'));
		
		//add encrypted member_id to form
		if ($this->EE->TMPL->fetch_param('logged_out_member_id') && $this->logged_out_member_id)
		{
			$this->form_hidden('logged_out_member_id', $this->encrypt_input($this->logged_out_member_id));
		}
		
		//add class to form
		if ($this->EE->TMPL->fetch_param('class'))
		{
			$this->EE->TMPL->tagparams['form_class'] = $this->EE->TMPL->fetch_param('class');
		}
		
		$this->load_session_override();
		
		//set group-based return url
		$this->form_hidden('return', ($this->EE->TMPL->fetch_param('return_'.$this->EE->session->userdata['group_id'])) ? $this->EE->TMPL->fetch_param('return_'.$this->EE->session->userdata['group_id']) : $this->EE->TMPL->fetch_param('return'));
		
		//get rid of the saef_javascript variable, we don't want that parsing in channel_standalone
		if (($array_search = array_search('saef_javascript', $this->EE->TMPL->var_single)) !== FALSE)
		{
			unset($this->EE->TMPL->var_single[$array_search]);
		}
		
		$this->EE->session->cache['safecracker']['form_declaration'] = TRUE;
		
		//temporarily set the site_id for cross-site saef
		$current_site_id = $this->EE->config->item('site_id');
		
		$this->EE->config->set_item('site_id', $this->site_id);
		
		$include_jquery = $this->EE->TMPL->fetch_param('include_jquery');
		
		//force include to no, for channel_standalone parsing
		$this->EE->TMPL->tagparams['include_jquery'] = 'no';
		
		$return = $this->channel_standalone->entry_form(TRUE, $this->EE->functions->cached_captcha);
		
		$this->EE->config->set_item('site_id', $current_site_id);
		
		if (isset($this->EE->session->cache['safecracker']['channel_standalone_output_js']))
		{
			$this->head .= '<script type="text/javascript" charset="utf-8">// <![CDATA[ '."\n";
			
			foreach ($this->EE->session->cache['safecracker']['channel_standalone_output_js']['json'] as $key => $value)
			{
				if ($key == 'EE')
				{
					$value['XID'] = '{XID_HASH}';
					
					$this->head .= 'if (typeof EE == "undefined" || ! EE) { '."\n".'var EE = '.$this->EE->javascript->generate_json($value, TRUE).';}'."\n";
				}
				else 
				{
					$this->head .= $key.' = '.$this->EE->javascript->generate_json($value, TRUE).';'."\n";
				}
				
				$first = FALSE;
			}

			$this->head .= "\n".' // ]]>'."\n".'</script>';

			//this is no longer necessary since adding the combo loader
			/*
			if ($this->bool_string($this->EE->TMPL->fetch_param('saef_javascript'), TRUE))
			{
				$this->head .= $this->EE->session->cache['safecracker']['channel_standalone_output_js']['str'];
			}
			*/
		}
		
		$js_defaults = array(
			'ui' => array('core', 'widget', 'button', 'dialog'),
			'plugin' => array('scrollable', 'scrollable.navigator', 'ee_filebrowser', 'ee_fileuploader', 'markitup', 'thickbox'),
		);
		
		if (version_compare(APP_VER, '2.1.3', '>'))
		{
			$js_defaults['plugin'][] = 'toolbox.expose';
			$js_defaults['plugin'][] = 'overlay';
			$js_defaults['plugin'][] = 'tmpl';
		}
		
		if ($this->datepicker)
		{
			$js_defaults['ui'][] = 'datepicker';
		}
		
		foreach ($js_defaults as $type => $files)
		{
			foreach ($files as $file)
			{
				if ( ! isset($this->EE->cp->js_files[$type]))
				{
					$this->EE->cp->js_files[$type] = array();
				}
				else if (is_string($this->EE->cp->js_files[$type]))
				{
					$this->EE->cp->js_files[$type] = explode(',', $this->EE->cp->js_files[$type]);
				}
				
				if ( ! in_array($file, $this->EE->cp->js_files[$type]))
				{
					$this->EE->cp->js_files[$type][] = $file;
				}
			}
		}
		
		$ui = array(
			'core' => FALSE,
			'widget' => array('core'),
			'mouse' => array('core', 'widget'),
			'position' => array('core'),
			'draggable' => array('core', 'widget', 'mouse'),
			'droppable' => array('core', 'widget', 'mouse', 'draggable'),
			'resizable' => array('core', 'widget', 'mouse'),
			'selectable' => array('core', 'widget', 'mouse'),
			'sortable' => array('core', 'widget', 'mouse'),
			'accordion' => array('core', 'widget'),
			'autocomplete' => array('core'),
			'button' => array('core', 'widget', 'position'),
			'dialog' => array('core', 'widget', 'mouse', 'position', 'draggable', 'resizable', 'button'),
			'slider' => array('core', 'widget', 'mouse'),
			'tabs' => array('core', 'widget'),
			'datepicker' => array('core'),
			'progressbar' => array('core', 'widget'),
			'effects' => array('core'),
		);
		
		foreach ($this->EE->cp->js_files as $type => $files)
		{
			//let's get the order right
			if ($type == 'ui')
			{
				$temp = array();
				
				foreach ($files as $file)
				{
					$temp[] = $file;
					if (is_array($ui[$file]))
					{
						$temp = array_merge($ui[$file], $temp);
					}
				}
				
				$files = array();
				
				foreach (array_keys($ui) as $file)
				{
					if (in_array($file, $temp))
					{
						$files[] = $file;
					}
				}
			}
			
			if (empty($files))
			{
				unset($this->EE->cp->js_files[$type]);
			}
			else
			{
				$mtime[] = $this->EE->cp->_get_js_mtime($type, $files);
				$this->EE->cp->js_files[$type] = implode(',', $files);
			}
		}
		
		if (empty($mtime))
		{
			$mtime = array($this->EE->localize->now);
		}
		
		$use_live_url = ($this->bool_string($this->EE->TMPL->fetch_param('use_live_url'), TRUE)) ? '&use_live_url=y' : '';
		
		$include_jquery = ($this->bool_string($include_jquery, TRUE)) ? '&include_jquery=y' : '';
	
		$this->head .= '<script type="text/javascript" charset="utf-8" src="'.$this->EE->functions->fetch_site_index().QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Safecracker', 'combo_loader').'&'.str_replace('%2C', ',', http_build_query($this->EE->cp->js_files)).'&v='.max($mtime).$use_live_url.$include_jquery.'"></script>'."\n";
		
		//add fieldtype styles
		foreach ($this->EE->cp->its_all_in_your_head as $item)
		{
			$this->head .= $item."\n";
		}
		
		//add fieldtype scripts
		foreach ($this->EE->cp->footer_item as $item)
		{
			$this->head .= $item."\n";
		}
		
		$this->unload_session_override();
		
		//add loaded JS
		$this->EE->javascript->compile();

		if ( ! empty($this->EE->jquery->jquery_code_for_compile))
		{
			$script = '$(document).ready(function() {' . "\n";
			$script .= implode('', $this->EE->jquery->jquery_code_for_compile);
			$script .= '});';
			$script = preg_replace('/\s*eeSpell\.init\(\);\s*/', '', $script);

			$this->head .= $this->EE->javascript->inline($script);
			
			$this->EE->jquery->jquery_code_for_compile = array();
		}
		//if (isset($this->EE->load->_ci_cached_vars['script_foot']))
		//{
			//$script = $this->EE->load->_ci_cached_vars['script_foot'];
			
			//$script = preg_replace('/\s*eeSpell\.init\(\);\s*/', '', $script);
			
			//$this->head .= $script;
		//}

		//add datepicker class
		if ($this->datepicker)
		{
			$date_fmt = $this->EE->session->userdata('time_format');
			$date_fmt = $date_fmt ? $date_fmt : $this->EE->config->item('time_format');

			$this->head .= '<style type="text/css">.hasDatepicker{background:#fff url('.$this->EE->config->item('theme_folder_url').'cp_themes/default/images/calendar_bg.gif) no-repeat 98% 2px;background-repeat:no-repeat;background-position:99%;}</style>';
			$this->head .= trim('
				<script type="text/javascript">
					$.createDatepickerTime=function(){
						date = new Date();
						hours = date.getHours();
						minutes = date.getMinutes();
						suffix = "";
						format = "'.$date_fmt.'";
					
						if (minutes < 10) {
							minutes = "0" + minutes;
						}
					
						if (format == "us") {
							if (hours > 12) {
								hours -= 12;
								suffix = " PM";
							} else if (hours == 12) {
								suffix = " PM";
							} else {
								suffix = " AM";
							}
						}
					
						return " \'" + hours + ":" + minutes + suffix + "\'";
					}
				
					EE.date_obj_time = $.createDatepickerTime();
				</script>');
		}
		
		//make head appear by default
		if (preg_match('/'.LD.'safecracker_head'.RD.'/', $return))
		{
			$return = $this->EE->TMPL->swap_var_single('safecracker_head', $this->head, $return);
		}
		// Head should only be there if the param is there and there is a valid member_id
		else if (
			$this->bool_string($this->EE->TMPL->fetch_param('safecracker_head'), TRUE) AND
			($this->logged_out_member_id OR $this->EE->session->userdata('member_id'))
		)
		{
			$return .= $this->head;
		}
		
		//added in 1.0.3
		if ($this->bool_string($this->EE->TMPL->fetch_param('secure_action')))
		{
			$return = preg_replace('/(<form.*?action=")http:/', '\\1https:', $return);
		}
		
		$return = $this->EE->functions->insert_action_ids($return);
		
		
		// -------------------------------------------
		// 'safecracker_entry_form_tagdata_end' hook.
		//  - Developers, if you want to modify the $this object remember
		//	to use a reference on func call.
		//

		if ($this->EE->extensions->active_hook('safecracker_entry_form_tagdata_end') === TRUE)
		{
			$return = $this->EE->extensions->call('safecracker_entry_form_tagdata_end', $return, $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		}
		
		return $return;
	}

	// --------------------------------------------------------------------
    
	/**
	 * Creates or edits an entry
	 * 
	 * @return	void
	 */
	public function submit_entry()
	{
		$this->initialize();
		
		$this->fetch_site(FALSE, $this->EE->input->post('site_id', TRUE));
		
		$this->fetch_channel($this->EE->input->post('channel_id', TRUE));
		
		$this->EE->load->helper(array('url', 'form'));
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_fields');
		$this->EE->load->library('filemanager');
		$this->EE->load->library('form_validation');
		$this->EE->load->library('localize');
		$this->EE->load->model(array('field_model', 'tools_model'));
		
		$this->EE->filemanager->_initialize(array());
				
		$this->EE->lang->loadfile('content');
		$this->EE->lang->loadfile('form_validation');
		$this->EE->lang->loadfile('safecracker');
		
		$this->EE->router->set_class('cp');
		$this->EE->load->library('cp');
		$this->EE->router->set_class('ee');
		
		$rules = $this->EE->input->post('rules');
		
		//just to prevent any errors
		if ( ! defined('BASE'))
		{
			$s = ($this->EE->config->item('admin_session_type') != 'c') ? $this->EE->session->userdata('session_id') : 0;
			define('BASE', SELF.'?S='.$s.'&amp;D=cp');
		}
		
		$this->json = $this->EE->input->post('json');
		$this->error_handling = $this->EE->input->post('error_handling');
		
		// -------------------------------------------
		// 'safecracker_submit_entry_start' hook.
		//  - Developers, if you want to modify the $this object remember
		//	to use a reference on func call.
		//

		if ($this->EE->extensions->active_hook('safecracker_submit_entry_start') === TRUE)
		{
			$this->EE->extensions->call('safecracker_submit_entry_start', $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		}
		
		$logged_out_member_id = FALSE;
		
		if ( ! $this->EE->session->userdata('member_id') && $this->EE->input->post('logged_out_member_id'))
		{
			if ($logged_out_member_id = $this->decrypt_input($this->EE->input->post('logged_out_member_id')))
			{
				$this->fetch_logged_out_member($logged_out_member_id);
			}
		}
		else if ($this->channel('channel_id') && ! $this->EE->session->userdata('member_id') &&  ! empty($this->settings['logged_out_member_id'][$this->EE->config->item('site_id')][$this->channel('channel_id')]))
		{
			$this->fetch_logged_out_member($this->settings['logged_out_member_id'][$this->EE->config->item('site_id')][$this->channel('channel_id')]);
		}
		
		//captcha check
		if ($this->channel('channel_id') && ! empty($this->logged_out_member_id) && ! empty($this->settings['require_captcha'][$this->EE->config->item('site_id')][$this->EE->input->post('channel_id', TRUE)]))
		{
			if ( ! $this->EE->input->post('captcha'))
			{
				$this->errors[] = $this->EE->lang->line('captcha_required');
			}
			
			$this->EE->db->where('word', $this->EE->input->post('captcha', TRUE));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('date > ', '(UNIX_TIMESTAMP()-7200)', FALSE);
		    
			if ( ! $this->EE->db->count_all_results('captcha'))
			{
				$this->errors[] = $this->EE->lang->line('captcha_incorrect');
			}
			
			$this->EE->db->where('word', $this->EE->input->post('captcha', TRUE));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('date < ', '(UNIX_TIMESTAMP()-7200)', FALSE);
			
			$this->EE->db->delete('captcha');
		}
		
		//is an edit form?
		if ($this->EE->input->post('entry_id'))
		{
			$this->edit = TRUE;
			
			$this->fetch_entry($this->EE->input->post('entry_id', TRUE));
			
			if ($this->EE->input->post('category') === FALSE && $this->entry('categories'))
			{
				$_POST['category'] = $this->entry('categories');
			}
		}
		else
		{
			if ($this->EE->input->post('unique_url_title', TRUE))
			{
				$_POST['url_title'] = uniqid($this->EE->input->post('url_title', TRUE) ? $this->EE->input->post('url_title', TRUE) : url_title($this->EE->input->post('title', TRUE)), TRUE);
			}
			
			$this->entry['dst_enabled'] = $this->EE->input->post('dst_enabled');
		}
		
		$this->preserve_checkboxes = $this->bool_string($this->EE->input->post('preserve_checkboxes'), FALSE);
		
		foreach ($this->custom_fields as $i => $field)
		{
			$isset = (isset($_POST['field_id_'.$field['field_id']]) || isset($_POST[$field['field_name']]) || (((isset($_FILES['field_id_'.$field['field_id']]) && $_FILES['field_id_'.$field['field_id']]['error'] != 4) || (isset($_FILES[$field['field_name']]) && $_FILES[$field['field_name']]['error'] != 4)) && in_array($field['field_type'], $this->file_fields)));
			
			$this->custom_fields[$i]['isset'] = $isset;
			
			if ( ! $this->edit || $isset)
			{
				$field_rules = array();
				
				if ( ! empty($rules[$field['field_name']]))
				{
					$field_rules = explode('|', $this->decrypt_input($rules[$field['field_name']]));
				}
				
				if ( ! in_array('call_field_validation['.$field['field_id'].']', $field_rules))
				{
					array_unshift($field_rules, 'call_field_validation['.$field['field_id'].']');
				}
				
				if ($field['field_required'] == 'y' && ! in_array('required', $field_rules))
				{
					array_unshift($field_rules, 'required');
				}
				
				$this->EE->form_validation->set_rules($field['field_name'], $field['field_label'], implode('|', $field_rules));
			}
			else
			{
				if ($field['field_type'] == 'date')
				{
					//$_POST['field_id_'.$field['field_id']] = $_POST[$field['field_name']] = $this->EE->localize->set_human_time($this->EE->localize->offset_entry_dst($this->entry($field['field_name']), $this->entry('dst_enabled'), FALSE));
					$_POST['field_id_'.$field['field_id']] = $_POST[$field['field_name']] = $this->EE->localize->set_human_time($this->entry($field['field_name']));
				}
				else if ($field['field_required'] == 'y')
				{
					//add a dummy value to be removed later
					//to get around _check_data_for_errors, a redundant check
					$_POST['field_id_'.$field['field_id']] = '1';
				}
			}
			
			//$this->EE->form_validation->set_rules($field['field_name'], $field['field_label'], implode('|', $field_rules));
			
			foreach ($_POST as $key => $value)
			{
				//change field_name'd POSTed keys to field_id's
				if ($key == $field['field_name'])
				{
					//@TODO what to do about xss_clean and "naughty" html
					//for now you can crack open this file and manually add fields_ids and/or field types to the respective arrays
					//to prevent xss_clean
					//i had some people complain about not being able to submit <object>'s
					$xss_clean = ( ! in_array($field['field_id'], $this->skip_xss_field_ids) && ! in_array($field['field_type'], $this->skip_xss_fieldtypes));
					
					$_POST['field_id_'.$field['field_id']] = $this->EE->input->post($key, $xss_clean);
					
					//auto set format if not POSTed
					$fmt = $field['field_fmt'];
					
					if ($this->EE->input->post('field_ft_'.$field['field_id']) !== FALSE)
					{
						$fmt = $this->EE->input->post('field_ft_'.$field['field_id'], TRUE);
					}
					elseif ($this->EE->input->post($field['field_name'].'_ft') !== FALSE)
					{
						$fmt = $this->EE->input->post($field['field_name'].'_ft', TRUE);
					}
					
					$_POST['field_ft_'.$field['field_id']] = $fmt;
				}
				else if (preg_match('/^'.$field['field_name'].'_(.+)/', $key, $match))
				{
					//also change utility POST fields, ie my_field_field_directory to field_id_X_directory
					$_POST['field_id_'.$field['field_id'].'_'.$match[1]] = $this->EE->input->post($key, TRUE);
				}
			}
			
			if (in_array($field['field_type'], $this->file_fields) || $field['field_type'] == 'matrix')
			{
				//change field_name'd POSTed files to field_id's
				foreach ($_FILES as $key => $value)
				{
					if ($key == $field['field_name'])
					{
						$_FILES['field_id_'.$field['field_id']] = $value;
						unset($_FILES[$key]);
					}
					else if (preg_match('/^'.$field['field_name'].'_(.+)/', $key, $match))
					{
						$_FILES['field_id_'.$field['field_id'].'_'.$match[1]] = $value;
						unset($_FILES[$key]);
					}
				}
			}
		}
		
		foreach ($this->title_fields as $field)
		{
			if (isset($this->default_fields[$field]))
			{
				$this->EE->api_channel_fields->set_settings($field, $this->default_fields[$field]);
				
				$this->EE->form_validation->set_rules($field, $this->default_fields[$field]['field_label'], $this->default_fields[$field]['rules']);
			}
			
			if ($this->EE->input->post($field) !== FALSE)
			{
				$_POST[$field] = $this->EE->input->post($field, TRUE);
			}
			else
			{
				if ($field == 'entry_date')
				{
					if ($this->entry($field))
					{
						//$_POST[$field] = $this->EE->localize->set_human_time($this->EE->localize->offset_entry_dst($this->entry($field), $this->entry('dst_enabled'), FALSE));
						$_POST[$field] = $this->EE->localize->set_human_time($this->entry($field));
					}
					else
					{
						//$_POST[$field] = $this->EE->localize->set_human_time($this->EE->localize->offset_entry_dst($this->EE->localize->now, $this->EE->input->post('dst_enabled'), FALSE));
						$_POST[$field] = $this->EE->localize->set_human_time($this->EE->localize->now);
					}
				}
				else
				{
					if ($this->entry($field) !== FALSE)
					{
						if ( ! in_array($field, $this->checkboxes) || $this->preserve_checkboxes)
						{
							$_POST[$field] = $this->entry($field);
						}
					}
				}
			}
		}
		
		//don't override status on edit, only on publish
		if ( ! $this->edit && ! empty($this->settings['override_status'][$this->EE->config->item('site_id')][$this->EE->input->post('channel_id')]))
		{
			$_POST['status'] = $this->settings['override_status'][$this->EE->config->item('site_id')][$this->EE->input->post('channel_id')];
		}
		
		$_POST['ping_servers'] = (is_array($this->EE->input->post('ping'))) ? $this->EE->input->post('ping', TRUE) : array();
		
		$_POST['ping_errors'] = FALSE;
		
		$_POST['revision_post'] = $_POST;
		
		$this->load_session_override();
		
		//added for EE2.1.2
		$this->EE->api->instantiate(array('channel_categories'));
		$this->EE->load->library('api/api_sc_channel_entries');
		
		//trick the form_validation lib
		//show 'em who's the boss
		$this->EE->form_validation->CI =& $this;
		$this->lang =& $this->EE->lang;
		$this->api_channel_fields =& $this->EE->api_channel_fields;
		
		foreach ($this->form_validation_methods as $method)
		{
			$this->EE->form_validation->set_message($method, $this->EE->lang->line('safecracker_'.$method));
		}
		
		if ($this->EE->input->post('dynamic_title'))
		{
			$dynamic_title = base64_decode($this->EE->input->post('dynamic_title'));
			
			foreach ($_POST as $key => $value)
			{
				if (is_string($value) && strstr($dynamic_title, '['.$key.']') !== FALSE)
				{
					$dynamic_title = str_replace('['.$key.']', $value, $dynamic_title);
				}
			}
			
			$_POST['title'] = $dynamic_title;
		}
		
		foreach ($this->EE->api_channel_fields->settings as $field_id => $settings)
		{
			$settings['field_name'] = 'field_id_'.$field_id;
			
			if (isset($settings['field_settings']))
			{
				$settings = array_merge($settings, $this->unserialize($settings['field_settings'], TRUE));
			}
			
			$this->EE->api_channel_fields->settings[$field_id] = $this->EE->session->cache['safecracker']['field_settings'][$field_id] = $settings;
		}
		
		//moved to before custom field processing,
		//since we are now using the call_field_validation rule
		if ( ! $this->EE->form_validation->run())
		{
			$this->field_errors = $this->EE->form_validation->_error_array;
		}
		
		if ( ! $this->EE->security->check_xid($this->EE->input->post('XID')))
		{
			$this->EE->functions->redirect(stripslashes($this->EE->input->post('RET')));		
		}
		
		if (empty($this->field_errors) && empty($this->errors))
		{
			//temporarily change site_id for cross-site forms
			//channel_entries api doesn't allow you to specifically set site_id
			$current_site_id = $this->EE->config->item('site_id');
			
			$this->EE->config->set_item('site_id', $this->site_id);
			
			if (in_array($this->channel('channel_id'), $this->EE->functions->fetch_assigned_channels()))
			{
				if ($this->entry('entry_id'))
				{
					$submit = $this->EE->api_sc_channel_entries->update_entry($this->entry('entry_id'), $_POST);
				}
				else
				{
					$submit = $this->EE->api_sc_channel_entries->submit_new_entry($this->channel('channel_id'), $_POST);
				}
				
				if ( ! $submit)
				{
					$this->errors = $this->EE->api_sc_channel_entries->errors;
				}
			}
			else
			{
				
				$this->errors[] = $this->EE->lang->line('unauthorized_for_this_channel');
			}
			
			$this->EE->config->set_item('site_id', $current_site_id);
			
			$this->clear_entry();
			
			//load the just created entry into memory
			$this->fetch_entry($this->EE->api_sc_channel_entries->entry_id);
		}
		
		$this->unload_session_override();
		
		// -------------------------------------------
		// 'safecracker_submit_entry_end' hook.
		//  - Developers, if you want to modify the $this object remember
		//	to use a reference on func call.
		//

		if ($this->EE->extensions->active_hook('safecracker_submit_entry_end') === TRUE)
		{
			$edata = $this->EE->extensions->call('safecracker_submit_entry_end', $this);
			if ($this->EE->extensions->end_script === TRUE) return;
		}
		
		if (is_array($this->errors))
		{
			//add the field name to custom_field_empty errors
			foreach ($this->errors as $field_name => $error)
			{
				if ($error == $this->EE->lang->line('custom_field_empty'))
				{
					$this->errors[$field_name] = $error.' '.$field_name;
				}
			}
		}
		
		if ( ! $this->json && ($this->errors || $this->field_errors) && $this->error_handling == 'inline')
		{
			$this->entry = $_POST;
			
			$this->form_error = TRUE;
			
			foreach($this->post_error_callbacks as $field_type => $callbacks)
			{
				$callbacks = explode('|', $callbacks);
				
				foreach ($this->custom_fields as $field)
				{
					if ($field['field_type'] == $field_type)
					{
						foreach ($callbacks as $callback)
						{
							if (in_array($callback, $this->valid_callbacks))
							{
								$this->entry[$field['field_name']] = $this->entry['field_id_'.$field['field_id']] = call_user_func($callback, $this->entry($field['field_name']));
							}
						}
					}
				}
			}
			
			foreach ($this->date_fields as $field)
			{
				if ($this->entry($field) && ! is_numeric($this->entry($field)))
				{
					//$this->entry[$field] = $this->EE->localize->offset_entry_dst($this->EE->localize->convert_human_date_to_gmt($this->entry($field)), $this->entry('dst_enabled'), FALSE);
					$this->entry[$field] = $this->EE->localize->convert_human_date_to_gmt($this->entry($field));
				}
			}
			
			if (version_compare(APP_VER, '2.1.3', '>'))
			{
				$this->EE->core->generate_page();
			}
			else
			{
				$this->EE->core->_generate_page();
			}
			
			return;
		}
		
		if ($this->json)
		{
			return $this->send_ajax_response(
				//$this->EE->javascript->generate_json(
					array(
						'success' => (empty($this->errors) && empty($this->field_errors)) ? 1 : 0,
						'errors' => (empty($this->errors)) ? array() : $this->errors,
						'field_errors' => (empty($this->field_errors)) ? array() : $this->field_errors,
						'entry_id' => $this->entry('entry_id'),
						'url_title' => $this->entry('url_title'),
						'channel_id' => $this->entry('channel_id'),
					)
				//)
			);
		}
		
		if ($this->errors OR $this->field_errors)
		{
			return $this->EE->output->show_user_error(FALSE, array_merge($this->errors, $this->field_errors));
		}
		
		if ( ! AJAX_REQUEST)
		{
			$this->EE->security->delete_xid($this->EE->input->post('XID'));
		}
		
		$return = ($this->EE->input->post('return')) ? $this->EE->functions->create_url($this->EE->input->post('return', TRUE)) : $this->EE->functions->fetch_site_index();
		    
		if (strpos($return, 'ENTRY_ID') !== FALSE)
		{
			$return = str_replace('ENTRY_ID', $this->entry('entry_id'), $return);
		}
		
		if (strpos($return, 'URL_TITLE') !== FALSE)
		{
			$return = str_replace('URL_TITLE', $this->entry('url_title'), $return);
		}
		
		if ($hook_return = $this->EE->api_sc_channel_entries->trigger_hook('entry_submission_redirect', $return))
		{
			$return = $hook_return;
		}
		
		if ($this->EE->input->post('secure_return'))
		{
			$return = preg_replace('/^http:/', 'https:', $return);
		}
		
		$this->EE->functions->redirect($return);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Converts text-based template parameter to boolean
	 * 
	 * @param	string $string
	 * @param	bool $default = FALSE
	 * @return	bool
	 */
	public function bool_string($string, $default = FALSE)
	{
		if (preg_match('/true|t|yes|y|on|1/i', $string))
		{
			return TRUE;
		}
		
		if (preg_match('/false|f|no|n|off|0/i', $string))
		{
			return FALSE;
		}
		
		return $default;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Filters and sorts the categories
	 * 
	 * @param	array $params
	 * @return	array
	 */
	public function categories($params)
	{
		$this->fetch_categories();
		
		$this->EE->load->library('data_sorter');
		
		if ( ! $categories = $this->categories)
		{
			return array();
		}
		
		if ( ! $params)
		{
			return $categories;
		}
		
		if ( ! empty($params['group_id']))
		{
			$this->EE->data_sorter->filter($categories, 'group_id', $params['group_id'], 'in_array');
		}
		
		if ( ! empty($params['order_by']))
		{
			$this->EE->data_sorter->sort($categories, $params['order_by'], @$params['sort']);
		}
		
		//reset array indices
		return array_merge($categories);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Retrieves current channel data
	 * 
	 * @param	mixed $key
	 * @return	mixed
	 */
	public function channel($key)
	{
		return (isset($this->channel[$key])) ? $this->channel[$key] : FALSE;
	}

	// --------------------------------------------------------------------
		
	/**
	 * Clears the library's entry
	 * 
	 * @return	void
	 */
	public function clear_entry()
	{
		$this->entry = FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Decrypts a form input
	 * 
	 * @param	mixed $input
	 * @return	void
	 */
	public function decrypt_input($input, $xss_clean = TRUE)
	{
		//$this->EE->load->library('encrypt');
		//return $this->EE->encrypt->decode($input, $this->EE->session->sess_crypt_key);
		
		if (function_exists('mcrypt_encrypt'))
		{
			$decoded = rtrim(
				mcrypt_decrypt(
					MCRYPT_RIJNDAEL_256,
					md5($this->EE->session->sess_crypt_key),
					base64_decode($input),
					MCRYPT_MODE_ECB,
					mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)
				),
				"\0"
			);
		}
		else
		{
			$raw = base64_decode($input);
			
			$decoded = substr($raw, 0, -32);
	
			if (substr($raw, -32) !== md5($this->EE->session->sess_crypt_key.$decoded))
			{
				return '';
			}
		}
		
		return ($xss_clean) ? $this->EE->security->xss_clean($decoded) : $decoded;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Display a custom field
	 * 
	 * @param	mixed $field_name
	 * @return	void
	 */
	public function display_field($field_name)
	{
		$this->EE->load->library('api');
		
		$this->EE->load->helper('custom_field');
		
		$this->EE->load->model('tools_model');
		
		$this->EE->load->library('javascript');
		
		if (isset($this->extra_js[$this->get_field_type($field_name)]))
		{
			$this->EE->javascript->output($this->extra_js[$this->get_field_type($field_name)]);
		}
		
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->api_channel_fields->field_type = $this->get_field_type($field_name);
		
		$this->EE->api_channel_fields->field_types[$this->EE->api_channel_fields->field_type]->field_name = $field_name;
		
		$this->EE->api_channel_fields->field_types[$this->EE->api_channel_fields->field_type]->field_id = $this->get_field_id($field_name);
		
		$this->EE->api_channel_fields->field_types[$this->EE->api_channel_fields->field_type]->settings = array_merge($this->get_field_settings($field_name), $this->get_field_data($field_name), $this->EE->api_channel_fields->get_global_settings($this->EE->api_channel_fields->field_type));
		
		if ($this->EE->api_channel_fields->field_type == 'date')
		{
			$this->EE->api_channel_fields->field_types[$this->EE->api_channel_fields->field_type]->settings['dst_enabled'] = $this->entry($field_name);
		}
		
		$_GET['entry_id'] = $this->entry('entry_id');
		$_GET['channel_id'] = $this->entry('channel_id');
		
		return $this->EE->api_channel_fields->apply('display_field', array('data' => $this->entry($field_name)));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Encrypts a form input
	 * 
	 * @param	mixed $input
	 * @return	void
	 */
	public function encrypt_input($input)
	{
		//$this->EE->load->library('encrypt');
		//return $this->EE->encrypt->encode($input, $this->EE->session->sess_crypt_key);
		
		if ( ! function_exists('mcrypt_encrypt'))
		{
			return base64_encode($input.md5($this->EE->session->sess_crypt_key.$input));
		}
		
		return base64_encode(mcrypt_encrypt(
			MCRYPT_RIJNDAEL_256,
			md5($this->EE->session->sess_crypt_key),
			$input,
			MCRYPT_MODE_ECB,
			mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)
		));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Retrieves current entry data
	 * 
	 * @param	mixed $key
	 * @return	void
	 */
	public function entry($key, $force_string = FALSE)
	{
		if (isset($this->entry[$key]))
		{
			return $this->entry[$key];
		}
		
		return ($force_string) ? '' : FALSE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load categories
	 * 
	 * @return	void
	 */
	public function fetch_categories()
	{
		//exit if already loaded, or if there is no category group
		if ($this->categories || ! $this->channel('cat_group'))
		{
			return;
		}

		// Load up the library and figure out what belongs and what's selected
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_categories');
		$category_list = $this->EE->api_channel_categories->category_tree(
			$this->channel('cat_group'),
			$this->entry('categories')
		);

		$categories = array();

		foreach ($category_list as $category_id => $category_info)
		{
			// Indent category names
			if ($category_info[5] > 1) {
				$category_info[1] = str_repeat(NBS.NBS.NBS.NBS, $category_info[5] - 1) . $category_info[1];
			}

			$selected = ($category_info[4] === TRUE) ? ' selected="selected"' : '';
			$checked = ($category_info[4] === TRUE) ? ' checked="checked"' : '';

			// Translate response from API to something parse variables can understand
			$categories[$category_id] = array(
				'category_id' => $category_info[0],
				'category_name' => $category_info[1],
				'category_group_id' => $category_info[2],
				'category_group' => $category_info[3],
				'category_parent' => $category_info[6],
				'category_depth' => $category_info[5],

				'selected' => $selected,
				'checked' => $checked
			);
		}
		
		$this->categories = $categories;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load channel
	 * 
	 * @param	int $channel_id
	 * @param	mixed $channel_name
	 * @param	mixed $entry_id
	 * @param	mixed $url_title = FALSE
	 * @return	void
	 */
	public function fetch_channel($channel_id, $channel_name = FALSE, $entry_id = FALSE, $url_title = FALSE)
	{
		//exit if already loaded
		if ($this->channel('channel_id'))
		{
			return;
		}
		
		//get field group and 
		$this->EE->db->where('channels.site_id', $this->site_id);
		$this->EE->db->limit(1);
		
		if ($channel_id)
		{
			$this->EE->db->where('exp_channels.channel_id', $this->EE->security->xss_clean($channel_id));
		}
		elseif ($channel_name)
		{
			$this->EE->db->where('exp_channels.channel_name', $this->EE->security->xss_clean($channel_name));
		}
		elseif ($entry_id)
		{
			$this->EE->db->join('exp_channel_titles', 'exp_channel_titles.channel_id = exp_channels.channel_id');
			$this->EE->db->where('exp_channel_titles.entry_id', $this->EE->security->xss_clean($entry_id));
		}
		elseif ($url_title)
		{
			$this->EE->db->join('exp_channel_titles', 'exp_channel_titles.channel_id = exp_channels.channel_id');
			$this->EE->db->where('exp_channel_titles.url_title', $this->EE->security->xss_clean($url_title));
		}
		else
		{
			return;
		}
		
		$query = $this->EE->db->get('channels');
		
		if ( ! $query->num_rows())
		{
			return;
		}
		
		$this->channel = $query->row_array();
		
		if ( ! empty($this->EE->TMPL))
		{
			$this->EE->TMPL->tagparams['channel'] = $this->channel('channel_name');
		}
		
		$this->fetch_custom_fields();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load custom fields
	 * 
	 * @return	void
	 */
	public function fetch_custom_fields()
	{
		//exit if already loaded, or if there is no field group
		if ($this->custom_fields || ! $this->channel('field_group'))
		{
			return;
		}
		
		$this->EE->load->model('channel_model');
	
		$query = $this->EE->channel_model->get_channel_fields($this->channel('field_group'));
		
		foreach ($query->result_array() as $row)
		{
			$this->custom_fields[$row['field_name']] = $row;
			
			foreach ($this->unserialize($row['field_settings'], TRUE) as $key => $value)
			{
				$this->custom_fields[$row['field_name']][$key] = $value;
			}
			
			$this->custom_field_names[$row['field_id']] = $row['field_name'];
			
			if (in_array($row['field_type'], $this->file_fields))
			{
				$this->file = TRUE;
			}
		}
		
		//prepare the channel fields api
		//which is use to trigger fieldtype methods,
		//namely save and display_field
		$this->EE->load->library('api');
		
		$this->EE->api->instantiate(array('channel_fields'));
		
		foreach ($this->custom_fields as $field)
		{
			if ( ! array_key_exists($field['field_type'], $this->EE->api_channel_fields->field_types))
			{
				$this->EE->api_channel_fields->field_types[$field['field_type']] = $this->EE->api_channel_fields->include_handler($field['field_type']);
			}

			$this->EE->api_channel_fields->custom_fields[$field['field_id']] = $field['field_type'];
			
			$this->EE->api_channel_fields->set_settings($field['field_id'], $field);
			
			$this->EE->api_channel_fields->setup_handler($field['field_id']);
		}
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Load entry
	 * 
	 * @param	mixed $entry_id
	 * @param	mixed $url_title
	 * @return	void
	 */
	public function fetch_entry($entry_id, $url_title = FALSE)
	{
		//exit if already loaded, or no entry_id/url_title
		if ($this->entry || ( ! $entry_id && ! $url_title))
		{
			return;
		}
		
		//fetch channel data, including custom fields
		if ( ! $this->channel('channel_id'))
		{
			$this->fetch_channel(NULL, NULL, $entry_id, $url_title);
		}
		
		//get an array with entry title data, custom field data (with field_id_X AND short name keys)
		$select = 'exp_channel_titles.*, exp_channel_data.*';
		
		foreach ($this->custom_fields as $field)
		{
			$select .= ', exp_channel_data.`field_id_'.$field['field_id'].'` as `'.$field['field_name'].'`';
		}
		
		$this->EE->db->select($select, FALSE);
		$this->EE->db->from('exp_channel_titles');
		$this->EE->db->join('exp_channel_data', 'exp_channel_titles.entry_id = exp_channel_data.entry_id');
		$this->EE->db->where('exp_channel_titles.site_id', $this->site_id);
		$this->EE->db->where('exp_channel_titles.'.(($entry_id) ? 'entry_id' : 'url_title'), $this->EE->security->xss_clean(($entry_id) ? $entry_id : $url_title));
		$this->EE->db->where('exp_channel_data.channel_id', $this->channel('channel_id'));
		$this->EE->db->limit(1);
		
		$query = $this->EE->db->get();
		
		if ($query->num_rows())
		{
			$row = $query->row_array();
			
			$row['categories'] = array();
			
			$this->EE->db->select('cat_id');
			
			$this->EE->db->where('entry_id', $row['entry_id']);
			
			$cat_query = $this->EE->db->get('exp_category_posts');
			
			foreach ($cat_query->result_array() as $cat_row)
			{
				$row['categories'][] = $cat_row['cat_id'];
			}
			
			$this->entry = $row;
		} else {
			
		}
		
		unset($query);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load logged out member data
	 * 
	 * @param	mixed $logged_out_member_id
	 * @return	void
	 */
	public function fetch_logged_out_member($logged_out_member_id = FALSE)
	{
		if ($this->EE->session->userdata('member_id') || $this->logged_out_member_id)
		{
			return;
		}
		
		if ( ! $logged_out_member_id && $this->channel('channel_id') && ! empty($this->settings['allow_guests'][$this->EE->config->item('site_id')][$this->channel('channel_id')]) && ! empty($this->settings['logged_out_member_id'][$this->EE->config->item('site_id')][$this->channel('channel_id')]))
		{
			$logged_out_member_id = $this->settings['logged_out_member_id'][$this->EE->config->item('site_id')][$this->channel('channel_id')];
		}
		
		$logged_out_member_id = $this->sanitize_int($logged_out_member_id);
		
		if ($logged_out_member_id)
		{
			$this->EE->db->select('member_id, group_id');
			$this->EE->db->where('member_id', $logged_out_member_id);
			
			$query = $this->EE->db->get('members');
			
			if ($query->num_rows() == 0)
			{
				// Invalid guest member id was specified
				return $this->EE->output->show_user_error('general', $this->EE->lang->line('safecracker_invalid_guest_member_id'));
			}

			$this->logged_out_member_id = $query->row('member_id');
			$this->logged_out_group_id = $query->row('group_id');
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load settings
	 * 
	 * @return	void
	 */
	public function fetch_settings()
	{
		if (empty($this->settings))
		{
			$this->EE->db->select('settings');
			$this->EE->db->where('class', 'Safecracker_ext');
			$this->EE->db->limit(1);
			
			$query = $this->EE->db->get('extensions');
			
			if ($query->row('settings'))
			{
				$this->settings = $this->unserialize($query->row('settings'));
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load site
	 * 
	 * @return	void
	 */
	public function fetch_site($site_name = FALSE, $site_id = FALSE)
	{
		if ($site_name)
		{
			$query = $this->EE->db->select('site_id')->from('sites')->where('site_name', $site_name)->limit(1)->get();
		
			$this->site_id = ($query->num_rows()) ? $query->row('site_id') : $this->EE->config->item('site_id');
		}
		else
		{
			$this->site_id = ($site_id) ? $site_id : $this->EE->config->item('site_id');
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load statuses
	 * 
	 * @return	void
	 */
	public function fetch_statuses()
	{
		//exit if already loaded, or if there is no status group
		if ($this->statuses || ! $this->channel('status_group'))
		{
			return;
		}
		
		$this->EE->load->model('channel_model');
	
		$query = $this->EE->channel_model->get_channel_statuses($this->channel('status_group'));
		
		$this->statuses = $query->result_array();
		
		$this->EE->lang->loadfile('content');
		
		foreach ($this->statuses as $index => $status)
		{
			$this->statuses[$index]['name'] = $this->EE->lang->line($status['status']);
			$this->statuses[$index]['selected'] = ($status['status'] == $this->entry('status')) ? ' selected="selected"' : '';
			$this->statuses[$index]['checked'] = ($status['status'] == $this->entry('status')) ? ' checked="checked"' : '';
		}

		// Remove statuses the member does not have access to.
		// hat tip to @litzinger for the fix.
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$member_group_id = $this->EE->session->userdata('group_id');
		}
		// In the event the person isn't logged in, figure out what group_id 
		// we're supposed to be using
		else
		{
			$this->fetch_logged_out_member();
			$member_group_id = $this->logged_out_group_id;
		}
		$no_access = $this->EE->db->where('member_group', $member_group_id)
								  ->get('status_no_access')
								  ->result_array();
		$remove = array();

		foreach ($no_access as $no)
		{
			$remove[] = $no['status_id'];
		}

		foreach ($this->statuses as $idx => $status)
		{
			if (in_array($status['status_id'], $remove))
			{
				unset($this->statuses[$idx]);
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Add a form attribute to entry form
	 * 
	 * @param	mixed $name
	 * @param	mixed $value
	 * @return	void
	 */
	public function form_attribute($name, $value = '')
	{
		if (is_array($name))
		{
			foreach ($name as $key => $value)
			{
				$this->form_attribute($key, $value);
			}
			
			return;
		}
		
		if ($value === FALSE || $value === '')
		{
			return;
		}
		
		$this->EE->session->cache['safecracker']['form_declaration_data'][$name] = $value;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Add a hidden field to entry form
	 * 
	 * @param	mixed $name
	 * @param	mixed $value
	 * @return	void
	 */
	public function form_hidden($name, $value = '')
	{
		if (is_array($name))
		{
			foreach ($name as $key => $value)
			{
				$this->form_hidden($key, $value);
			}
			
			return;
		}
		
		if ($value === FALSE || $value === '')
		{
			return;
		}
		
		$this->EE->session->cache['safecracker']['form_declaration_hidden_fields'][$name] = $value;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Retrieve field data
	 * Returns array of all field data if no key specified
	 * 
	 * @param	mixed $field_name
	 * @param	mixed $key
	 * @return	void
	 */
	public function get_field_data($field_name, $key = FALSE)
	{
		if (in_array($field_name, $this->title_fields))
		{
			return array();
		}
		
		if (isset($this->custom_fields[$field_name]))
		{
			if ($key)
			{
				return (isset($this->custom_fields[$field_name][$key])) ? $this->custom_fields[$field_name][$key] : array();
			}
			else
			{
				return $this->custom_fields[$field_name];
			}
		}
		
		return array();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Gets the field id of a field
	 * 
	 * @param	mixed $field_name
	 * @return	void
	 */
	public function get_field_id($field_name)
	{
		return $this->get_field_data($field_name, 'field_id');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Gets the field name of a field
	 * 
	 * @param	mixed $field_id
	 * @return	void
	 */
	public function get_field_name($field_id)
	{
		return (isset($this->custom_field_names[$field_id])) ? $this->custom_field_names[$field_id] : FALSE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Gets a field's options
	 * 
	 * @param	mixed $field_name
	 * @param	mixed $add_blank = FALSE
	 * @return	void
	 */
	public function get_field_options($field_name, $add_blank = FALSE)
	{
		$field = $this->get_field_data($field_name);
		
		$options = array();
		
		if ($add_blank)
		{
			$options[] = array(
				'option_value' => '',
				'option_name' => '--',
				'selected' => '',
				'checked' => ''
			);
		}
		
		if (in_array($field['field_type'], $this->option_fields))
		{
			if ($field['field_pre_populate'] == 'y')
			{
				$query = $this->EE->db->select('field_id_'.$field['field_pre_field_id'])
						->distinct()
						->from('channel_data')
						->where('channel_id', $field['field_pre_channel_id'])
						->where('field_id_'.$field['field_pre_field_id'].' !=', '')
						->get();
				
				$current = explode('|', $this->entry($field['field_name']));
				
				foreach ($query->result_array() as $row)
				{
					$options[] = array(
						'option_value' => $row['field_id_'.$field['field_pre_field_id']],
						'option_name' => str_replace(array("\r\n", "\r", "\n", "\t"), ' ' , substr($row['field_id_'.$field['field_pre_field_id']], 0, 110)),
						'selected' => (in_array($row['field_id_'.$field['field_pre_field_id']], $current)) ? ' selected="selected"' : '',
						'checked' => (in_array($row['field_id_'.$field['field_pre_field_id']], $current)) ? ' checked="checked"' : '',
					);
				}
			}
			
			else if ($field['field_list_items'])
			{
				foreach (preg_split('/[\r\n]+/', $field['field_list_items']) as $row)
				{
					$row = trim($row);
					
					if ( ! $row)
					{
						continue;
					}
					
					$field_data = (is_array($this->entry($field_name))) ? $this->entry($field_name) : explode('|', $this->entry($field_name));
					
					$options[] = array(
						'option_value' => $row,
						'option_name' => $row,
						'selected' => (in_array($row, $field_data)) ? ' selected="selected"' : '',
						'checked' => (in_array($row, $field_data)) ? ' checked="checked"' : '',
					);
				}
			}

			else if ( ! in_array($field['field_type'], $this->native_option_fields))
			{
				$field_settings = $this->unserialize($field['field_settings'], TRUE);
				
				if ( ! empty($field_settings['options']))
				{
					foreach ($field_settings['options'] as $option_value => $option_name)
					{
						$field_data = (is_array($this->entry($field_name))) ? $this->entry($field_name) : preg_split('/[\r\n]+/', $this->entry($field_name));
						
						$options[] = array(
							'option_value' => $option_value,
							'option_name' => $option_name,
							'selected' => (in_array($option_value, $field_data)) ? ' selected="selected"' : '',
							'checked' => (in_array($option_value, $field_data)) ? ' checked="checked"' : ''
						);
					}
				}
			}
		}
		
		else if ($field['field_type'] == 'rel')
		{
			$rel_child_id = '';
			
			if ($this->entry($field_name))
			{
				if ($this->form_error)
				{
					$rel_child_id = $this->entry($field_name);
				}
				else
				{
					$this->EE->db->select('rel_child_id');
					$this->EE->db->where('rel_id', $this->entry($field_name));
					$this->EE->db->where('rel_parent_id', $this->entry('entry_id'));
					
					$query = $this->EE->db->get('relationships');
					
					$rel_child_id = $query->row('rel_child_id');
				}
			}
			
			$orderby = $this->get_field_data($field_name, 'field_related_orderby');
			
			if ($orderby == 'date')
			{
				$orderby = 'entry_'.$orderby;						
			}

			$this->EE->db->select('entry_id, title');
			$this->EE->db->where('channel_id', $this->get_field_data($field_name, 'field_related_id'));
			$this->EE->db->order_by($orderby, $this->get_field_data($field_name, 'field_related_sort'));

			if ($this->get_field_data($field_name, 'field_related_max'))
			{
				$this->EE->db->limit($this->get_field_data($field_name, 'field_related_max'));
			}

			$query = $this->EE->db->get('channel_titles');
			
			foreach ($query->result() as $row)
			{
				$options[] = array(
					'option_value' => $row->entry_id,
					'option_name' => $row->title,
					'selected' => ($row->entry_id == $rel_child_id) ? ' selected="selected"' : '',
					'checked' => ($row->entry_id == $rel_child_id) ? ' checked="checked"' : '',
				);
			}
		}
		
		return $options;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Gets a field's settings
	 * 
	 * @param	mixed $field_name
	 * @param	mixed $unserialize = TRUE
	 * @return	void
	 */
	public function get_field_settings($field_name, $unserialize = TRUE)
	{
		if ( ! $field_settings = $this->get_field_data($field_name, 'field_settings'))
		{
			return array();
		}
		
		return ($unserialize) ? $this->unserialize($field_settings, TRUE) : $field_settings;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Gets the type of a field
	 * 
	 * @param	mixed $field_name
	 * @return	void
	 */
	public function get_field_type($field_name)
	{
		return $this->get_field_data($field_name, 'field_type');
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Initialize the library properties
	 * 
	 * @return	void
	 */
	public function initialize($reinitialize = FALSE)
	{
		if ($this->initialized && ! $reinitialize)
		{
			return;
		}
		
		$this->initialized = TRUE;
		
		$this->categories = array();
		$this->channel = array();
		$this->checkboxes = array(
			'sticky',
			'dst_enabled',
			'allow_comments'
		);
		$this->custom_field_conditional_names = array(
			'rel' => 'relationship',
			'text' => 'textinput',
			'select' => 'pulldown',
			'checkboxes' => 'checkbox',
			'multi_select' => 'multiselect'
		);
		$this->custom_fields = array();
		$this->custom_option_fields = array();
		$this->date_fields = array(
			'comment_expiration_date',
			'expiration_date',
			'entry_date',
			'edit_date',
			'recent_comment_date',
			'recent_trackback_date'
		);
		$this->datepicker = TRUE;
		$this->default_fields = array(
			'title' => array(
				'field_name' => 'title',
				'field_label' => 'lang:title',
				'field_type' => 'text',
				'rules' => 'required|call_field_validation[title]'
			),
			'url_title' => array(
				'field_name' => 'url_title',
				'field_label' => 'lang:url_title',
				'field_type' => 'text',
				'rules' => 'call_field_validation[url_title]'
			),
			'entry_date' => array(
				'field_name' => 'entry_date',
				'field_label' => 'lang:entry_date',
				'field_type' => 'date',
				'rules' => 'required|call_field_validation[entry_date]|callback_valid_ee_date'
			),
			'expiration_date' => array(
				'field_name' => 'expiration_date',
				'field_label' => 'lang:expiration_date',
				'field_type' => 'date',
				'rules' => 'call_field_validation[expiration_date]'
			),
			'comment_expiration_date' => array(
				'field_name' => 'comment_expiration_date',
				'field_label' => 'lang:comment_expiration_date',
				'field_type' => 'date',
				'rules' => 'call_field_validation[comment_expiration_date]'
			)
		);
		$this->edit = FALSE;
		$this->entry = array();
		$this->error_handling = 'message';
		$this->errors = array();
		$this->field_errors = array();
		$this->file = FALSE;
		$this->file_fields = array(
			// @todo: As of EE 2.2 (or earlier), SafeCracker doesn't fully work with standard file fields, only SafeCracker File
			//'file',
			'safecracker_file'
		);
		$this->form_validation_methods = array(
			'valid_ee_date'
		);
		$this->head = '';
		$this->json = FALSE;	
		$this->logged_out_member_id = FALSE;	
		$this->logged_out_group_id = FALSE;	
		$this->native_option_fields = array(
			'multi_select',
			'select',
			'radio',
			'checkboxes'
		);
		$this->native_variables = array(
			'comment_expiration_date' => 'date',
			'expiration_date' => 'date',
			'entry_date' => 'date',
			'url_title' => 'text',
			'sticky' => FALSE,
			'dst_enabled' => FALSE,
			'allow_comments' => FALSE,
			'title' => 'text'
		);
		$this->option_fields = array();	
		$this->parse_variables = array();
		$this->pre_save = array(
			'matrix'
		);
		$this->preserve_checkboxes = FALSE;
		$this->post_error_callbacks = array();
		$this->require_save_call = array();
		$this->settings = array();
		$this->skip_xss_fieldtypes = array();
		$this->skip_xss_field_ids = array();
		$this->statuses = array();
		$this->show_fields = array();
		$this->title_fields = array(
			'entry_id',
			'site_id',
			'channel_id',
			'author_id',
			'pentry_id',
			'forum_topic_id',
			'ip_address',
			'title',
			'url_title',
			'status',
			'versioning_enabled',
			'view_count_one',
			'view_count_two',
			'view_count_three',
			'view_count_four',
			'allow_comments',
			'sticky',
			'entry_date',
			'dst_enabled',
			'year',
			'month',
			'day',
			'expiration_date',
			'comment_expiration_date',
			'edit_date',
			'recent_comment_date',
			'comment_total',
		);
		$this->valid_callbacks = array(
			'html_entity_decode',
			'htmlentities'
		);
		
		$this->fetch_settings();
		
		$this->option_fields = $this->native_option_fields;
		
		$this->EE->config->load('config');
		
		if (is_array($this->EE->config->item('safecracker_option_fields')))
		{
			$this->custom_option_fields = $this->EE->config->item('safecracker_option_fields');
			
			$this->option_fields = array_merge($this->option_fields, $this->custom_option_fields);
		}
		
		if (is_array($this->EE->config->item('safecracker_post_error_callbacks')))
		{
			$this->post_error_callbacks = array_merge($this->post_error_callbacks, $this->EE->config->item('safecracker_post_error_callbacks'));
		}
		
		if (is_array($this->EE->config->item('safecracker_file_fields')))
		{
			$this->file_fields = array_merge($this->file_fields, $this->EE->config->item('safecracker_file_fields'));
		}
		
		if (is_array($this->EE->config->item('safecracker_require_save_call')))
		{
			$this->require_save_call = $this->EE->config->item('safecracker_require_save_call');
		}
		
		if (is_array($this->EE->config->item('safecracker_field_extra_js')))
		{
			$this->extra_js = $this->EE->config->item('safecracker_field_extra_js');
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Loads the channel standalone module
	 * 
	 * @return	void
	 */
	public function load_channel_standalone()
	{
		require_once(PATH_MOD.'channel/mod.channel.php');
		
		require_once(PATH_MOD.'channel/mod.channel_standalone.php');
		
		$this->channel_standalone = new Channel_standalone();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Loads the session override library
	 * 
	 * @return	void
	 */
	public function load_session_override()
	{
		if (empty($this->logged_out_member_id))
		{
			return;
		}
		
		$this->temp_session = $this->EE->session;

		//$this->temp_session = $this->EE->functions->clone_object($this->EE->session);
		
		if ( ! class_exists('SC_Session'))
		{
			require_once PATH_MOD.'safecracker/libraries/SC_Session.php';
		}
		
		$this->EE->session = new SC_Session(array(
			'session_object' => $this->temp_session,
			'logged_out_member_id' => $this->logged_out_member_id,
			'logged_out_group_id' => $this->logged_out_group_id
		));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Replaces a tag
	 * 
	 * @param	mixed $field_name
	 * @param	mixed $data
	 * @param	mixed $params = array()
	 * @param	mixed $tagdata = FALSE
	 * @return	void
	 */
	public function replace_tag($field_name, $data, $params = array(), $tagdata = FALSE)
	{
		if ( ! $params)
		{
			$params = array();
		}
		
		if ( ! isset($this->custom_fields[$field_name]))
		{
			return $tagdata;
		}
	
		$this->EE->load->library('api');
		
		$this->EE->load->helper('custom_field');
		
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->api_channel_fields->field_type = $this->get_field_type($field_name);
		
		$this->EE->api_channel_fields->field_types[$this->EE->api_channel_fields->field_type]->settings = array_merge($this->get_field_settings($field_name), $this->get_field_data($field_name), $this->EE->api_channel_fields->get_global_settings($this->EE->api_channel_fields->field_type));
		
		$_GET['entry_id'] = $this->entry('entry_id');
		
		$this->EE->api_channel_fields->apply('_init', array(array('row' => $this->entry)));

		$data = $this->EE->api_channel_fields->apply('pre_process', array($data));
		
		return $this->EE->api_channel_fields->apply('replace_tag', array('data' => $data, 'params' => $params, 'tagdata' => $tagdata));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Clean an ID
	 * 
	 * @param	mixed $id
	 * @return	mixed
	 */
	public function sanitize_int($data)
	{
		if (is_int($data + 0))
		{
			return $data;
		}
		
		$data = preg_replace('/[^\d]/', '', $data);
		
		return ($data) ? $data : FALSE;
	}

	// --------------------------------------------------------------------	
	
	public function send_ajax_response($msg, $error = FALSE)
	{
		if ($this->EE->config->item('send_headers') == 'y')
		{
			//so the output class doesn't try to send any headers
			//we are taking over
			$this->EE->config->config['send_headers'] = NULL;
			
			$this->EE->load->library('user_agent', array(), 'user_agent');
			
			//many browsers do not consistently like this content type
			//array('Firefox', 'Mozilla', 'Netscape', 'Camino', 'Firebird')
			if (is_array($msg) && in_array($this->EE->user_agent->browser(), array('Safari', 'Chrome')))
			{
				@header('Content-Type: application/json');
			}
			else
			{
				@header('Content-Type: text/html; charset=UTF-8');	
			}
		}
		
		$this->EE->output->send_ajax_response($msg, $error);
	}

	// --------------------------------------------------------------------
	
	/**
	 * swap_conditionals
	 * 
	 * @param	mixed $tagdata
	 * @param	mixed $conditionals
	 * @return	void
	 */
	public function swap_conditionals($tagdata, $conditionals)
	{
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $conditionals);
		
		$tagdata = preg_replace('/\{if\s+[\042\047]*0[\042\047]*\}(.+?)\{\/if\}/si', '', $tagdata);
		
		$tagdata = preg_replace('/\{if\s+[\042\047]*1[\042\047]*\}(.+?)\{\/if\}/si', '\\1', $tagdata);
			
		return $tagdata;
	}

	// --------------------------------------------------------------------
	
	/**
	 * swap_var_pair
	 * 
	 * @param	mixed $key
	 * @param	mixed $rows
	 * @param	mixed $tagdata
	 * @param	mixed $close_key = ''
	 * @param	mixed $backspace = FALSE
	 * @return	void
	 */
	public function swap_var_pair($key, $rows, $tagdata, $close_key = '', $backspace = FALSE)
	{
		$close_key = ($close_key) ? $close_key : $key;
		
		if (preg_match_all('/'.LD.$key.RD.'(.*?)'.LD.'\/'.$close_key.RD.'/s', $tagdata, $matches))
		{
			foreach ($matches[1] as $match_index => $var_pair_tagdata)
			{
				$output = '';
				
				foreach ($rows as $row)
				{
					$row_output = $var_pair_tagdata;
					
					foreach ($row as $k => $v)
					{
						$row_output = $this->EE->TMPL->swap_var_single($k, $v, $row_output);
					}
					
					$output .= $row_output."\n";
				}
				
				if ($backspace && is_numeric($backspace))
				{
					$output = substr($output, 0, -1*($backspace+1));
				}
				
				$tagdata = str_replace($matches[0][$match_index], $output, $tagdata);
			}
		}
		
		return $tagdata;
	}

	// --------------------------------------------------------------------
	
	/**
	 * unload_session_override
	 * 
	 * @return	void
	 */
	public function unload_session_override()
	{
		if (empty($this->logged_out_member_id))
		{
			return;
		}
		
		$this->EE->session = $this->temp_session;
		
		unset($this->temp_session);
	}

	// --------------------------------------------------------------------
	
	/**
	 * unserialize
	 * 
	 * @param	mixed $data
	 * @param	mixed $base64_decode = FALSE
	 * @return	void
	 */
	public function unserialize($data, $base64_decode = FALSE)
	{
		if ($base64_decode)
		{
			$data = base64_decode($data);
		}
		
		$data = @unserialize($data);
		
		return (is_array($data)) ? $data : array();
	}

	// --------------------------------------------------------------------
	
	/* form validation methods */
	
	public function valid_ee_date($data)
	{
		return (is_numeric($this->EE->localize->convert_human_date_to_gmt($data)));
	}
}

/* End of file safecracker_lib.php */
/* Location: ./system/expressionengine/modules/safecracker/libraries/safecracker_lib.php */