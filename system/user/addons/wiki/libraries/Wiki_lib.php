<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Wiki Module Library
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Wiki_lib {

var $_base_url = '';

	public function __construct()
	{
		
		$this->_base_url = ee('CP/URL')->make('addons/settings/wiki');
	}

	// -------------------------------------------------------------------------

	/**
	 * Provides Wiki Edit Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_wiki($wiki_id = 0)
	{
		ee()->load->helper('form');

		if ($wiki_id)
		{
			$valid_wiki =  ee('Model')->get('wiki:Wikis')->filter('wiki_id', $wiki_id)->first();
			

			if ( ! $valid_wiki)
			{
				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/wiki'));
			}

			$error_url = ee('CP/URL')->make('addons/settings/wiki/update', array('wiki_id' => $wiki_id));
			$success_url = $error_url;

		}
		else
		{
			$valid_wiki = ee('Model')->make('wiki:Wikis');
			
			$error_url = ee('CP/URL')->make('addons/settings/wiki/create');
			$success_url = ee('CP/URL')->make('addons/settings/wiki');			

			// Only auto-complete short name for new wikis
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=wiki_label_name]").bind("keyup keydown", 
				function() {
					$(this).ee_url_title("input[name=wiki_short_name]");
				});
			');
		}

		if ( ! empty($_POST))
		{
			$valid_wiki->set($_POST);

			$result = $valid_wiki->validate();

			if ($result->isValid())
			{
				$wiki = $wiki->save();

				// If it's new, redirect to main page and highlight
				if ($wiki_id)
				{
					ee()->session->set_flashdata('highlight_id', $new_wiki);
					ee()->functions->redirect($success_url);
			
				// If edit, show success message
					ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('settings_saved'))
					->addToBody(lang('settings_saved_desc'))
					->now();
				}
				else
				{
					ee()->load->library('form_validation');
					ee()->form_validation->_error_array = $result->renderErrors();
				
					ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_error'))
					->addToBody(lang('settings_error_desc'))
					->now();
				}
			}
		
		} // End Validation check on posted data		
		

		$vars['sections'] = $this->make_form($wiki_id, $valid_wiki);
		
		$vars['base_url'] = $this->_base_url.'/update/wiki&wiki_id='.$wiki_id;
		$vars['save_btn_text'] = 'btn_save_settings';
		$vars['save_btn_text_working'] = 'btn_saving';
		$vars['cp_page_title'] = lang('edit_wiki');

 		return ee('View')->make('wiki:update')->render($vars);

	}
	

	function make_form($wiki_id, $wiki = NULL)
	{
		$new = TRUE;
		
		ee()->load->helper('form');

				
		$wiki_users_value = (isset($wiki->wiki_users)) ? $wiki->wiki_users : '';
		$wiki_admins_value = (isset($wiki->wiki_admins)) ?  $wiki->wiki_admins : '';


		$text_formats 	= ee()->addons_model->get_plugin_formatting(TRUE);

		$html_formats 	= array(
						'none'	=> ee()->lang->line('convert_to_entities'),
						'safe'	=> ee()->lang->line('allow_safe_html'),
						'all'	=> ee()->lang->line('allow_all_html')
				);


		$member_group_options = ee()->wiki_model->member_group_options();
		$wiki_users = explode('|', rtrim($wiki_users_value, '|'));
		$wiki_admins = explode('|', rtrim($wiki_admins_value, '|'));
		
		$directories = ee()->wiki_model->fetch_upload_options();

		$form_element = array(
			'title' => 'label_name',
			'desc' => 'label_name_description',
			'fields' => array(
				'wiki_label_name' => array(
					'type' => 'text',
					'required' => TRUE
				)
			)
		);		

		if (isset($wiki->wiki_label_name))
		{
			$form_element['fields']['wiki_label_name']['value'] = $wiki['wiki_label_name'];
		}		
		
		$form[] = $form_element;
		
		$form_element = array(
			'title' => 'short_name',
			'desc' => 'short_name_description',

			'fields' => array(
				'wiki_short_name' => array(
					'type' => 'text',
					'required' => TRUE
				)
			)
		);		

		if (isset($wiki->wiki_short_name))
		{
			$form_element['fields']['wiki_short_name']['value'] = $wiki->wiki_short_name;
		}	

		$form[] = $form_element;	

		$form_element = array(
			'title' => 'text_format',
			'desc' => 'text_format_description',
			'fields' => array(
				'wiki_text_format' => array(
					'type' => 'select',
					'choices' => $text_formats
				)
			)
		);

		if (isset($wiki->wiki_text_format))
		{
			$form_element['fields']['wiki_text_format']['value'] = $wiki->wiki_text_format;
		}
						
		$form[] = $form_element;

		$form_element = array(
			'title' => 'html_format',
			'desc' => 'html_format_description',
			'fields' => array(
				'wiki_html_format' => array(
					'type' => 'select',
					'choices' => $html_formats
				)
			)
		);

		if (isset($wiki->wiki_html_format))
		{
			$form_element['fields']['wiki_html_format']['value'] = $wiki->wiki_html_format;
		}

		$form[] = $form_element;
				
		$form_element = array(
			'title' => 'upload_dir',
			'desc' => 'upload_dir_description',
			'fields' => array(
				'wiki_upload_dir' => array(
					'type' => 'select',
					'choices' => $directories
				)
			)
		);

		if (isset($wiki->wiki_upload_dir))
		{
			$form_element['fields']['wiki_upload_dir']['value'] = $wiki->wiki_upload_dir;
		}	

		$form[] = $form_element;

		$form_element = array(
			'title' => 'admins',
			'desc' => 'admins_description',
			'fields' => array(
				'wiki_admins' => array(
						'type' => 'checkbox',
						'choices' => $member_group_options,
						'value' => $wiki_admins,
						'wrap' => FALSE
				)
			)
		);
		
		$form[] = $form_element;

		
		$form_element = array(
			'title' => 'users',
			'desc' => 'users_description',
			'fields' => array(
				'wiki_users' => array(
						'type' => 'checkbox',
						'choices' => $member_group_options,
						'value' => $wiki_users,
						'wrap' => FALSE
				)
			)
		);

		$form[] = $form_element;
		
		$form_element = array(
			'title' => 'revision_limit',
			'desc' => 'revision_limit_description',
			'fields' => array(
				'wiki_revision_limit' => array(
					'type' => 'text',
				)
			)
		);		

		if (isset($wiki->wiki_revision_limit))
		{
			$form_element['fields']['wiki_revision_limit']['value'] = $wiki->wiki_revision_limit;
		}	
		
		$form[] = $form_element;		
		
			$form_element = array(
			'title' => 'author_limit',
			'desc' => 'author_limit_description',
			'fields' => array(
				'wiki_author_limit' => array(
					'type' => 'text',
				)
			)
		);		

		if (isset($wiki->wiki_author_limit))
		{
			$form_element['fields']['wiki_author_limit']['value'] = $wiki->wiki_author_limit;
		}	

		$form[] = $form_element;
		
		$form_element = array(
			'title' => 'moderation_emails',
			'desc' => 'moderation_emails_description',
			'fields' => array(
				'wiki_moderation_emails' => array(
					'type' => 'text',
				)
			)
		);		

		if (isset($wiki->wiki_moderation_emails))
		{
			$form_element['fields']['wiki_moderation_emails']['value'] = $wiki->wiki_moderation_emails;
		}	
		
		$form[] = $form_element;


		// Namespace Grid
		$grid = $this->getNamespaceGrid($wiki_id);

		$form_element = array(
				'title' => 'namespaces',
				'desc' => 'namespaces_desc',
				'wide' => TRUE,
				'grid' => TRUE,
				'fields' => array(
					'wiki_namespaces_list' => array(
						'type' => 'html',
						'content' => ee()->load->view('_shared/table', $grid->viewData(), TRUE)
				)
			)
		);

		$form[] = $form_element;
		
		return array($form);		
		
	}


	/**
	 * Sets up a GridInput object populated with image manipulation data
	 *
	 * @param	int	$upload_id		ID of upload destination to get image sizes for
	 * @return	GridInput object
	 */
	private function getNamespaceGrid($wiki_id = NULL)
	{

		// Namespace Grid
		$grid = ee('CP/GridInput', array(
			'field_name' => 'wiki_namespaces_list',
			'reorder'    => FALSE, // Order doesn't matter here
		));

		$grid->loadAssets();
		$grid->setColumns(
			array(
				'namespace_label' => array(
					'desc'  => 'namespace_label_desc'
				),
				'namespace_name' => array(
					'desc'  => 'namespace_name_desc'
				),
				'namespace_users' => array(
					'desc'  => 'namespace_users_desc'
				),
				'namespace_admins' => array(
					'desc'  => 'namespace_admins_desc'
				)
			)
		);
		$grid->setNoResultsText('no_namespaces', 'add_namespaces');
		
		$member_choices = array();
		$member_groups = ee()->api->get('MemberGroup');
		$member_groups = $member_groups->all();
		
		foreach ($member_groups as $group)
		{
			$member_choices[$group->group_id] = $group->group_title;
		}		

		$grid->setBlankRow($this->getGridRow($member_choices));

		$validation_data = ee()->input->post('wiki_namespaces_list');
		$namespaces = array();

		// If we're coming back on a validation error, load the Grid from
		// the POST data
		if ( ! empty($validation_data))
		{
			foreach ($validation_data['rows'] as $row_id => $columns)
			{
				// Checkboxes may not be set
				$ns_post_users = (isset($columns['namespace_userss'])) ? $columns['namespace_users'] : array();

				$ns_post_admin = (isset($columns['namespace_admins'])) ? $columns['namespace_admins'] : array();

				$namespaces[$row_id] = array(
					// Fix this, multiple new rows won't namespace right
					'id'           => str_replace('row_id_', '', $row_id),
					'namespace_label'   => $columns['namespace_label'],
					'namespace_name'  => $columns['namespace_name'],
					'namespace_users'  => $ns_post_users,
					'namespace_admins'       => $ns_post_admin
				);
			}

			if (isset($this->edit_errors['namespaces']))
			{
				foreach ($this->edit_errors['namespaces'] as $row_id => $columns)
				{
					$namespaces[$row_id]['errors'] = array_map('strip_tags', $columns);
				}
			}
		}
		// Otherwise, pull from the database if we're editing
		elseif ($namespaces !== NULL)
		{
				// Namespaces
				$namespaces_query = ee()->db->get_where('wiki_namespaces',
												array('wiki_id' => $wiki_id));

			if ($namespaces_query->num_rows() > 0)
			{
					$namespaces = $namespaces_query->result_array();
			}
		}

		// Populate Namespace Grid
		if ( ! empty($namespaces))
		{
			$data = array();

// 467  Undefined index: namespace_id
			foreach($namespaces as $namespace)
			{
				$data[] = array(
					'attrs' => array('row_id' => $namespace['namespace_id']),
					'columns' => $this->getGridRow($member_choices, $namespace),
				);
			}

			$grid->setData($data);
		}

		return $grid;
	}


	/**
	 * Returns an array of HTML representing a single Grid row, populated by data
	 * passed in the $size array: ('short_name', 'resize_type', 'width', 'height')
	 *
	 * @param	array	$size	Array of image size information to populate Grid row
	 * @return	array	Array of HTML representing a single Grid row
	 */
	private function getGridRow($member_choices, $namespace = array())
	{
		$defaults = array(
			'namespace_label' => '',
			'namespace_name' => '',
		);
		
		if ( ! isset($namespace['namespace_users']) )
		{
			$namespace['namespace_users'] = array();
		}
		elseif ( ! is_array($namespace['namespace_users']))
		{
			$namespace['namespace_users'] = explode('|', $namespace['namespace_users']);
		}

		if ( ! isset($namespace['namespace_admins']))
		{
			$namespace['namespace_admins'] = array();
		}	
		elseif ( ! is_array($namespace['namespace_admins']))
		{
			$namespace['namespace_admins'] = explode('|', $namespace['namespace_admins']);
		}

		$namespace = array_merge($defaults, $namespace);
		$namespace = array_map('form_prep', $namespace);
		
		$user_checkboxes = '';
		
		// Not sure about hard coding label?
		foreach ($member_choices as $group_id => $group_name)
		{
			$selected = (in_array($group_id, $namespace['namespace_users'])) ? 'chosen' : '';
			$check = ( ! empty($selected)) ? 'y' : '';
			
			$user_checkboxes .= '<label class="choice block '. $selected.'">'.form_checkbox('namespace_users[]', $group_id, $check).' '.$group_name.'</label>'."\n";
		}
		
		$admin_checkboxes = '';

		foreach ($member_choices as $group_id => $group_name)
		{
			$selected = (in_array($group_id, $namespace['namespace_admins'])) ? 'chosen' : '';
			$check = ( ! empty($selected)) ? 'y' : '';
			
			$admin_checkboxes .= '<label class="choice block '. $selected.'">'.form_checkbox('namespace_admin[]', $group_id).' '.$group_name.'</label>'."\n";
		}		
		
		return array(
			array(
				'html' => form_input('namespace_label', $namespace['namespace_label']),
				'error' => $this->getGridFieldError($namespace, 'namespace_label')
			),
			array(
				'html' => form_input('namespace_name', $namespace['namespace_name']),
				'error' => $this->getGridFieldError($namespace, 'namespace_name')
			),
			array(
				'html' => $user_checkboxes,
				'error' => $this->getGridFieldError($namespace, 'namespace_users')
			),
			array(
				'html' => $admin_checkboxes,
				'error' => $this->getGridFieldError($namespace, 'namespace_admins')
			)				
		);
	}



	/**
	 * Returns the validation error for a specific Grid cell
	 *
	 * @param	array	$size	Array of image size information to populate Grid row
	 * @param	string	$column	Name of column to get an error for
	 * @return	array	Array of HTML representing a single Grid row
	 */
	private function getGridFieldError($size, $column)
	{
		if (isset($size['errors'][$column]))
		{
			return $size['errors'][$column];
		}

		return NULL;
	}


	// --------------------------------------------------------------------

	/**
	 * Saves a wiki
	 *
	 * @access	private
	 * @return	void
	 */
	private function save_wiki($wiki_id = FALSE)
	{
		$new = TRUE;
		ee()->load->model('wiki_model');
	
		$fields = array('wiki_label_name',
							'wiki_short_name',
							'wiki_upload_dir',
							'wiki_users',
							'wiki_admins',
							'wiki_html_format',
							'wiki_text_format',
							'wiki_revision_limit',
							'wiki_author_limit',
							'wiki_moderation_emails');

		$namespaces = ee()->input->post('wiki_namespaces_list');

		$existing_ids = array();
		$new_namespaces = array();
		$db_namespaces = array();

		$namespaces_query = ee()->db->get_where('wiki_namespaces',
												array('wiki_id' => $wiki_id));



		if ($namespaces_query->num_rows() > 0)
		{
			$result = $namespaces_query->result_array();
			
			foreach ($result as $row)
			{
				$db_namespaces[$row['namespace_id']] = $row;
			}
		}


		// collect existing to keep, and new ones to add
		if (isset($namespaces['rows']))
		{
			foreach ($namespaces['rows'] as $row_id => $columns)
			{
				if (strpos($row_id, 'row_id_') !== FALSE)
				{
					$row_id = str_replace('row_id_', '', $row_id);
					$existing_ids[$row_id] = $columns;
				}
				else
				{
					$new_namespaces[$row_id] = $columns;
				}
			}
		}

		$this->namespace_updates($wiki_id, $existing_ids, $new_namespaces, $db_namespaces);

		foreach($fields AS $val)
		{
				if ($val == 'wiki_users' OR $val == 'wiki_admins')
				{
					$data[$val] = implode('|', ee()->input->get_post($val));
				}
				elseif($val != 'wiki_namespaces_list')
				{
					$data[$val] = ee()->input->get_post($val);
				}
		}

		if (count($data) > 0)
		{
			ee()->wiki_model->update_wiki($wiki_id, $data);
		}

			return;
	}




	function namespace_updates($wiki_id, $existing_ids, $new_namespaces, $db_namespace)
	{
		$labels = array();
		$names  = array();

		
		//  Check existing for changes
		//  If Short Name changes update article pages
		$db_ids = array_keys($db_namespace);


		foreach($existing_ids as $id => $data)
		{
			if (in_array($id, $db_ids))
			{
				$ns_admin = (isset($data['namespace_admins']) && is_array($data['namespace_admins'])) ? implode('|', $data['namespace_admins']) : '';
				$ns_users = (isset($data['namespace_users']) && is_array($data['namespace_users'])) ? implode('|', $data['namespace_users'])  : '';
				
				// Let's update existing data regardless
				$namespace_data = array(
						'namespace_name'	=> $data['namespace_name'],
						'namespace_label'	=> $data['namespace_label'],
						'namespace_users'	=> $ns_users,
						'namespace_admins'	=> $ns_admin
						);


				ee()->db->where('wiki_id', $wiki_id);
				ee()->db->update('wiki_namespaces', $namespace_data);	
				
				
				// Check for namespace name change
				if ($data['namespace_name'] != $db_namespace[$id]['namespace_name'])
				{
					ee()->db->set('page_namespace', $data['namespace_name']);
					ee()->db->where('page_namespace', $db_namespace[$id]['namespace_name']);
					ee()->db->update('wiki_page');					
				}

				// Remove from db ids so we can delete any leftovers later
				unset($db_namespace[$id]);	
			}
			
			if (count($db_namespace))
			{
				ee()->db->where_in('namespace_id', array_keys($db_namespace));
				ee()->db->delete('wiki_namespaces');
			}
		}

		// Add any new namespaces
		foreach($new_namespaces as $ns)
		{
				$ns_admin = (isset($ns['namespace_admins']) && is_array($ns['namespace_admins'])) ? implode('|', $ns['namespace_admins']) : '';
				$ns_users = (isset($ns['namespace_users']) && is_array($ns['namespace_users'])) ? implode('|', $ns['namespace_users'])  : '';

			$namespace_data = array(
						'namespace_name'	=> $ns['namespace_name'],
						'namespace_label'	=> $ns['namespace_label'],
						'wiki_id'			=> $wiki_id,
						'namespace_users'	=> $ns_users,
						'namespace_admins'	=> $ns_admin
						);

			ee()->db->insert('wiki_namespaces', $namespace_data);

		}	

	}






	function ns_error($ns_id, $names, $labels)
	{
		if (trim($_POST['namespace_label_'.$ns_id]) == '' OR 
		preg_match("/^\w+$/",$_POST['namespace_name_'.$ns_id]) OR 
		$_POST['namespace_name_'.$ns_id] == 'category' OR 
		in_array($_POST['namespace_name_'.$ns_id], $names) OR 
		in_array($_POST['namespace_label_'.$ns_id], $labels))
		{
			return TRUE;
		}
		
		return FALSE;
	}



	public function _validWikName($str)
	{
		// Check short name characters
		if (preg_match('/[^a-z0-9\-\_]/i', $str))
		{
			ee()->form_validation->set_message('_validWikName', lang('invalid_short_name'));
			return FALSE;
		}
	}


	// --------------------------------------------------------------------

	/**
	  *  Check duplicate short name (callback)
	  */
	function _check_duplicate($str)
	{
		ee()->load->model('wiki_model');

		if (ee()->wiki_model->check_duplicate(ee()->form_validation->old_value('id'), $str) === FALSE)
		{
			ee()->form_validation->set_message('_check_duplicate', ee()->lang->line('duplicate_short_name'));
			return FALSE;
		}

		return TRUE;
	}


}

