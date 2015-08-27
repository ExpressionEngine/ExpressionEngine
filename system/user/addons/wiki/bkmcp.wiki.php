<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;
//use EllisLab\ExpressionEngine\Model\File\UploadDestination;
//use EllisLab\ExpressionEngine\Module\Member\Model;


/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.htmlup
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Wiki Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Control Panel Page
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Wiki_mcp {

	var $_base_url = '';

	/**
	  *  Constructor
	  */
	public function __construct()
	{

		// set some properties
		$this->_base_url = ee('CP/URL', 'addons/settings/wiki');
		ee()->load->library('form_validation');
		ee()->load->library('wiki_lib');
		ee()->load->model('addons_model');
		
	}

	// --------------------------------------------------------------------

	/**
	  *  A Wiki Config
	  */
	public function index()
	{
		ee()->load->library('javascript');
		ee()->load->helper('form');

		ee()->cp->add_js_script('fp_module', 'wiki');

		$vars['wikis'] = array();
		$vars['pagination'] = '';

		$base_url = $this->_base_url;

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));

		$table->setColumns(
			array(
				'label_name',
				'short_name',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$select = array('wiki_id', 'wiki_label_name', 'wiki_short_name');
		$sort_order = array('wiki_label_name', 'asc');


		$wikis = ee('Model')->get('wiki:Wiki')->all();
		
	
		$data = array();

		foreach ($wikis as $row)
		{
				$checkbox = array(
					'name' => 'selection[]',
					'value' => $row->wiki_id,
					'data'	=> array(
						'confirm' => lang('wiki') . ': <b>' . htmlentities($row->wiki_label_name, ENT_QUOTES) . '</b>'
					)
				);

				$columns = array(
					'label_name' => $row->wiki_label_name,
					'short_name' => $row->wiki_short_name,
					array(
						'toolbar_items' => array(
							'edit' => array(
								'href' => ee('CP/URL', '/cp/addons/settings/wiki/update'.AMP.'wiki_id='. $row->wiki_id),
								'title' => lang('edit')
							)
						)
					),
					$checkbox
				);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $row->wiki_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);
		
		$table->setNoResultsText('no_wikis', 'create_wiki', ee('CP/URL', 'addons/settings/wiki/create'));
		

		$vars['table'] = $table->viewData($this->_base_url);
		$vars['base_url'] = clone $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);
	}

		ee()->javascript->set_global('lang.remove_confirm', lang('wiki') . ': <b>### ' . lang('wikis') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		return ee('View')->make('wiki:index')->render($vars);


		
/*
		return array(
  'body'       => ee('View')->make('wiki:index')->render($vars),
  'heading'    => lang('wiki_manager')
		);
*/
	}


	// --------------------------------------------------------------------

	/**
	 * Provides New Wiki Screen HTML
	 *
	 * @access	public
	 * @return	string The page
	 */
	public function create()
	{
		return array(
			'body'			=> $this->edit_wiki(0),
			'heading'		=> lang('create_wiki'),
			'breadcrumb' 	=> array(
				ee('CP/URL', 'addons/settings/wiki')->compile() => lang('wiki_manager')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Provides Edit 
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function update()
	{
		$wiki_id = ee()->input->get_post('wiki_id');

		return array(
			'body'			=> $this->edit_wiki($wiki_id),
			'heading'		=> lang('edit_wiki'),
			'breadcrumb' 	=> array(
				ee('CP/URL', 'addons/settings/wiki')->compile() => lang('wiki_manager')
			)
		);
	}


	// --------------------------------------------------------------------

	/**
	  *  Delete Wikis
	  */

	function delete()
	{
		$selection = ee()->input->post('selection');

		foreach ($selection as $key => $val)
		{
			$damned[] = $val;
		}

		if ( ! empty($damned) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$wiki_ids = array_filter($damned, 'is_numeric');

			if ( ! empty($wiki_ids))
			{
				ee('Model')->get('wiki:Wikis', $wiki_ids)->delete();

				ee('CP/Alert')->makeInline('wikis-table')
					->asSuccess()
					->withTitle(lang('wikis_removed'))
					->addToBody(sprintf(lang('wikis_removed_desc'), count($wiki_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect($this->_base_url);

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
			$valid_wiki =  ee('Model')->get('wiki:Wiki')->filter('wiki_id', $wiki_id)->first();
			

			if ( ! $valid_wiki)
			{
				ee()->functions->redirect(ee('CP/URL', 'addons/settings/wiki'));
			}

			$error_url = ee('CP/URL', 'addons/settings/wiki/update', array('wiki_id' => $wiki_id));
			$success_url = $error_url;

		}
		else
		{
			$valid_wiki = ee('Model')->make('wiki:Wiki');
			
			$error_url = ee('CP/URL', 'addons/settings/wiki/create');
			$success_url = ee('CP/URL', 'addons/settings/wiki');			

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
			
			// Need to convert this field from its presentation serialization
			$valid_wiki->wiki_moderation_emails = explode(',', trim(preg_replace("/[\s,|]+/", ',', $_POST['wiki_moderation_emails']), ','));

			$result = $valid_wiki->validate();

			if ($result->isValid())
			{
				$wiki = $valid_wiki->save();

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

		$text_formats 	= ee()->addons_model->get_plugin_formatting(TRUE);

		$html_formats 	= array(
						'none'	=> ee()->lang->line('convert_to_entities'),
						'safe'	=> ee()->lang->line('allow_safe_html'),
						'all'	=> ee()->lang->line('allow_all_html')
				);

		
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(2,3,4))
			->order('group_title')
			->all();

		$member_group_options = array();
		foreach ($member_groups as $group)
		{
			$member_group_options[$group->group_id] = $group->group_title;
		}			
			
		
		$form_element = array(
			'title' => 'label_name',
			'fields' => array(
				'wiki_label_name' => array(
					'type' => 'text',
					'value' => $wiki->wiki_label_name,
					'required' => TRUE
				)
			)
		);		

		$form[] = $form_element;
		
		$form_element = array(
			'title' => 'short_name',
			'fields' => array(
				'wiki_short_name' => array(
					'type' => 'text',
					'value' => $wiki->wiki_short_name,					
					'required' => TRUE
				)
			)
		);		

		$form[] = $form_element;	

		$form_element = array(
			'title' => 'text_format',
			'fields' => array(
				'wiki_text_format' => array(
					'type' => 'select',
					'value' => $wiki->wiki_text_format,
					'choices' => $text_formats
				)
			)
		);

						
		$form[] = $form_element;

		$form_element = array(
			'title' => 'html_format',
			'fields' => array(
				'wiki_html_format' => array(
					'type' => 'select',
					'value' => $wiki->wiki_html_format,
					'choices' => $html_formats
				)
			)
		);


		$form[] = $form_element;
				
		$form_element = array(
			'title' => 'upload_dir',
			'fields' => array(
				'wiki_upload_dir' => array(
					'type' => 'select',
					'value' => $wiki->wiki_upload_dir,
					'choices' => ee('Model')->get('UploadDestination')
						->filter('site_id', ee()->config->item('site_id'))
						->all()
						->getDictionary('id', 'name')
				)
			)
		);
	

		$form[] = $form_element;

		$form_element = array(
			'title' => 'admins',
			'desc' => 'admins_description',
			'fields' => array(
				'wiki_admins' => array(
						'type' => 'checkbox',
						'choices' => $member_group_options,
						'value' => $wiki->wiki_admins,
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
						'value' => $wiki->wiki_users,
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
					'value' => $wiki->wiki_revision_limit
				)
			)
		);		


		$form[] = $form_element;		
		
			$form_element = array(
			'title' => 'author_limit',
			'desc' => 'author_limit_description',
			'fields' => array(
				'wiki_author_limit' => array(
					'type' => 'text',
					'value' => $wiki->wiki_author_limit,
				)
			)
		);		

		$form[] = $form_element;
		
	
		$form_element = array(
			'title' => 'moderation_emails',
			'desc' => 'moderation_emails_description',
			'fields' => array(
				'wiki_moderation_emails' => array(
					'type' => 'text',
					'value' =>  implode("\n", $wiki->wiki_moderation_emails)
				)
			)
		);		
		
		$form[] = $form_element;


		// Namespace Grid
		$grid = $this->getNamespaceGrid($wiki_id);

		$form_element = array(
				'title' => 'namespaces',
				'desc' => 'namespaces_list',
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
				$ns_post_users = (isset($columns['namespace_users'])) ? $columns['namespace_users'] : array();

				$ns_post_admins = (isset($columns['namespace_admins'])) ? $columns['namespace_admins'] : array();

				$namespaces[$row_id] = array(
					// Fix this, multiple new rows won't namespace right
					'id'           => str_replace('row_id_', '', $row_id),
					'namespace_label'   => $columns['namespace_label'],
					'namespace_name'  => $columns['namespace_name'],
					'namespace_users'  => $ns_post_users,
					'namespace_admins'    => $ns_post_admins
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

			foreach($namespaces as $namespace)
			{
				$data[] = array(
					'attrs' => array('row_id' => $namespace['id']),
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
			
			$admin_checkboxes .= '<label class="choice block '. $selected.'">'.form_checkbox('namespace_admins[]', $group_id).' '.$group_name.'</label>'."\n";
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
	 * Delete Namespace
	 */
	function delete_namespace()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(ee()->lang->line('unauthorized_access'));
		}
		
		if (ee()->wiki_model->delete_namespace(ee()->input->get_post('namespace_id')) === TRUE)
		{
			ee()->output->send_ajax_response(array('response' => 'success'));
		}

		ee()->output->send_ajax_response(array('response' => 'failure'));
	}


}
/* END Class */

/* End of file mcp.wiki.php */
/* Location: ./system/expressionengine/modules/wiki/mcp.wiki.php */
