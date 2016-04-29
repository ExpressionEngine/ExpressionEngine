<?php

namespace EllisLab\ExpressionEngine\Controller\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Import Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MemberImport extends Utilities {

	protected $taken = array();
	protected $members = array();
	protected $members_custom = array();
	protected $default_fields = array();
	protected $default_custom_fields = array();

	/**
	 * Member import
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$groups = ee('Model')->get('MemberGroup')->order('group_title', 'asc')->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		ee()->lang->loadfile('settings');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'mbr_xml_file',
					'desc' => 'mbr_xml_file_location',
					'fields' => array(
						'xml_file' => array('type' => 'text', 'required' => TRUE)
					)
				),
			),
			'mbr_import_default_options' => array(
				array(
					'title' => 'member_group',
					'fields' => array(
						'group_id' => array(
							'type' => 'select',
							'choices' => $member_groups
						)
					)
				),
				array(
					'title' => 'mbr_language',
					'fields' => array(
						'language' => array(
							'type' => 'select',
							'choices' => ee()->lang->language_pack_names(),
							'value' => ee()->config->item('deft_lang') ?: 'english'
						)
					)
				),
				array(
					'title' => 'timezone',
					'fields' => array(
						'timezones' => array(
							'type' => 'html',
							'content' => ee()->localize->timezone_menu(set_value('default_site_timezone') ?: ee()->config->item('default_site_timezone'), 'timezones')
						)
					)
				),
				array(
					'title' => 'mbr_datetime_fmt',
					'desc' => 'used_in_cp_only',
					'fields' => array(
						'date_format' => array(
							'type' => 'select',
							'choices' => array(
								'%n/%j/%Y' => 'mm/dd/yyyy',
								'%j/%n/%Y' => 'dd/mm/yyyy',
								'%j-%n-%Y' => 'dd-mm-yyyy',
								'%Y-%m-%d' => 'yyyy-mm-dd'
							)
						),
						'time_format' => array(
							'type' => 'select',
							'choices' => array(
								'24' => lang('24_hour'),
								'12' => lang('12_hour')
							)
						)
					)
				),
				array(
					'title' => 'include_seconds',
					'desc' => 'include_seconds_desc',
					'fields' => array(
						'include_seconds' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'mbr_create_custom_fields',
					'desc' => 'mbr_create_custom_fields_desc',
					'fields' => array(
						'auto_custom_field' => array(
							'type' => 'yes_no',
							'value' => set_value('auto_custom_field') ?: 'y'
						)
					)
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'xml_file',
				 'label'   => 'lang:mbr_xml_file',
				 'rules'   => 'required|file_exists'
			),
			array(
				 'field'   => 'auto_custom_field',
				 'label'   => 'lang:auto_custom_field',
				 'rules'   => ''
			)
		));

		$base_url = ee('CP/URL')->make('utilities/member-import');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			return $this->memberImportConfirm();
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('member_import_error'), lang('member_import_error_desc'));
		}

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('member_import');
		ee()->view->save_btn_text = 'mbr_import_btn';
		ee()->view->save_btn_text_working = 'mbr_import_btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Confirm Import Member Data from XML
	 *
	 * Confirmation page for Member Data import
	 *
	 * @return	mixed
	 */
	public function memberImportConfirm()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->model('member_model');
		ee()->lang->loadfile('settings');

		$member_group = ee()->api
			->get('MemberGroup')
			->filter('group_id', ee()->input->post('group_id'))
			->first();

		$group_title = '';
		$group_name = ' -- ';

		if ( ! empty($member_group))
		{
			$group_name = $member_group->group_title;
		}

		$data = array(
			'xml_file'   		=> parse_config_variables(ee()->input->post('xml_file')),
			'group_id' 			=> ee()->input->post('group_id'),
			'language' 			=> (ee()->input->post('language') == lang('none')) ? '' : ee()->input->post('language'),
			'timezones' 		=> ee()->input->post('timezones'),
			'date_format' 		=> ee()->input->post('date_format'),
			'time_format' 		=> ee()->input->post('time_format'),
			'include_seconds' 	=> ee()->input->post('include_seconds'),
			'auto_custom_field' => (ee()->input->post('auto_custom_field') == 'y') ? 'y' : 'n'
		);

		ee()->lang->load('admin');
		$localization_cfg = ee()->config->get_config_fields('localization_cfg');
		$added_fields = ee()->input->post('added_fields');

		$vars = array(
			'added_fields'		=> $added_fields,
			'xml_file'   		=> $data['xml_file'],
			'default_group_id'	=> $group_name,
			'language' 			=> ($data['language'] == '') ? lang('none') : ucfirst($data['language']),
			'timezones' 		=> $data['timezones'],
			'date_format' 		=> lang($localization_cfg['date_format'][1][$data['date_format']]),
			'time_format' 		=> lang($localization_cfg['time_format'][1][$data['time_format']]),
			'include_seconds' 	=> ($data['include_seconds'] == 'y') ? lang('yes') : lang('no'),
			'auto_custom_field' => ($data['auto_custom_field'] == 'y' || ($added_fields && count($added_fields) > 0)) ? lang('yes') : lang('no')
		);

		$map = FALSE;

		if (isset($_POST['field_map']))
		{
			$map = TRUE;
		}

		$vars['form_hidden'] = ($map) ? array_merge($data, $_POST['field_map']) : $data;

		// Branch off here if we need to create a new custom field
		if ($data['auto_custom_field'] == 'y' && ee()->input->post('added_fields') === FALSE)
		{
			$new_custom_fields = $this->_custom_field_check($data['xml_file']);

			if ($new_custom_fields !== FALSE && count($new_custom_fields) > 0)
			{
				return $this->_new_custom_fields_form($vars, $new_custom_fields);
			}

			$vars['message'] = lang('unable_to_parse_custom_fields');
		}

		ee()->view->cp_page_title = lang('confirm_import');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/member_import'), lang('member_import'));

		ee()->cp->render('utilities/member-import/confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Process XML
	 *
	 * Imports the members from XML and redirects to the index page on successful completion
	 *
	 * @return	void
	 */
	public function processXml()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('member_import');

		$xml_file   = ( ! $this->input->post('xml_file'))  ? '' : parse_config_variables($this->input->post('xml_file'));

		//  Read XML file contents
		$this->load->helper('file');
		$contents = read_file($xml_file);

		if ($contents === FALSE)
		{
			ee()->view->set_message('issue', lang('file_read_error'), lang('file_read_error_desc'));
			return $this->memberImportConfirm();
		}

		$this->load->library('xmlparser');

		// parse XML data
		$xml = $this->xmlparser->parse_xml($contents);

		if ($xml === FALSE)
		{
			ee()->view->set_message('issue', lang('xml_parse_error'), lang('xml_parse_error_desc'));
			return $this->memberImportConfirm();
		}

		// Any custom fields exist
		// TODO: MemberField model isn't ready, using query builder
		$this->db->select('m_field_name, m_field_id');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			$custom_fields = TRUE;

			foreach ($m_custom_fields->result() as $row)
			{
				if (isset($_POST['map'][$row->m_field_name]))
				{
					$this->default_custom_fields[$_POST['map'][$row->m_field_name]] = $row->m_field_id;
				}
				else
				{
					$this->default_custom_fields[$row->m_field_name] = $row->m_field_id;
				}
			}
		}

		$errors = $this->validateXml($xml);

		//  Show Errors
		if (count($errors) > 0)
		{
			$out = array();

			foreach($errors as $error)
			{
				foreach($error as $val)
				{
					$out[] = $val;
				}
			}

			ee()->view->set_message('issue', lang('cp_message_issue'), $out);
			return $this->memberImportConfirm();
		}

		/** -------------------------------------
		/**  Ok! Cross Fingers and do it!
		/** -------------------------------------*/

		$imports = $this->doImport();

		$msg = lang('import_success_blurb').'<br>'.str_replace('%x', $imports, lang('total_members_imported'));

		ee()->view->set_message('success', lang('import_success'), $msg, TRUE);

		$this->functions->redirect(ee('CP/URL')->make('utilities/member_import'));
	}

	// --------------------------------------------------------------------

	/**
	 * Validate XML for Member Import
	 *
	 * Validates both the format and content of Member Import XML
	 *
	 * @return	mixed
	 */
	public function validateXml($xml)
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('member_import');
		$this->load->library('validate');

		$this->validate->member_id			= '';
		$this->validate->val_type			= 'new';
		$this->validate->fetch_lang			= TRUE;
		$this->validate->require_cpw		= FALSE;
		$this->validate->enable_log			= FALSE;
		$this->validate->cur_username		= '';
		$this->validate->cur_screen_name	= '';
		$this->validate->cur_password		= '';
		$this->validate->cur_email			= '';

		$i = 0;

		$fields = ee('Model')->make('Member')->getFields();

		foreach ($fields as $field)
		{
			$this->default_fields[$field] = '';
		}

		$this->db->select('m_field_name, m_field_id');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
	 		foreach ($m_custom_fields->result() as $row)
	 		{
				$this->default_custom_fields[$row->m_field_name] = $row->m_field_id;
			}
		}

		// we don't allow <unique_id>
		unset($this->default_fields['unique_id']);

		$u = array(); // username garbage array
		$s = array(); // screen_name garbage array
		$e = array(); // email garbage array
		$m = array(); // member_id garbage array
		$errors = array(); // Collect errors in here

		if (is_array($xml->children[0]->children))
		{
			foreach($xml->children as $member)
			{
				if ($member->tag == "member")
				{
					foreach($member->children as $tag)
					{
						// Is the XML tag an allowed database field
						if (isset($this->default_fields[$tag->tag]))
						{
							$this->members[$i][$tag->tag] = $tag->value;
						}
						elseif ($tag->tag == 'birthday')
						{
							// We have a special XML format for birthdays that doesn't match the database fields
							foreach($tag->children as $birthday)
							{
									switch ($birthday->tag)
									{
										case 'day':
											$this->members[$i]['bday_d'] = $birthday->value;
											break;
										case 'month':
											$this->members[$i]['bday_m'] = $birthday->value;
											break;
										case 'year':
											$this->members[$i]['bday_y'] = $birthday->value;
											break;
										default:
											$errors[] = array(lang('invalid_tag')." '&lt;".$birthday->tag."&gt;'");
											break;
									}
							}


							if ( ! isset($this->members[$i]['bday_d']) || ! isset($this->members[$i]['bday_m']) || ! isset($this->members[$i]['bday_y']))
							{
								$errors[] = array(lang('missing_birthday_child'));
							}

							$this->members[$i][$tag->tag] = $tag->value;
						}
						elseif (isset($this->default_custom_fields[$tag->tag]))
						{
							$this->members_custom[$i][$tag->tag] = $tag->value;
						}
						else
						{
							// not a database field and not a <birthday> so club it like a baby seal!
							//$errors[] = array(lang('invalid_tag')." '&lt;".$tag->tag."&gt;'");
						}

						/* -------------------------------------
						/*  username and email
						/*  must be validated and unique
						/* -------------------------------------*/

						switch ($tag->tag)
						{
							case 'username':
								$this->validate->username = $tag->value;
								if ( ! in_array($tag->value, $u))
								{
									$u[] = $tag->value;
								}
								else
								{
									$errors[] = array(lang('duplicate_username').$tag->value);
								}
								break;
							case 'screen_name':
								$this->validate->screen_name = $tag->value;
								$s[] = $tag->value;
								break;
							case 'email':
								if ( ! in_array($tag->value, $e))
								{
									$e[] = $tag->value;
								}
								else
								{
									$errors[] = array(lang('duplicate_email').$tag->value);
								}
								$this->validate->email = $tag->value;
								break;
							case 'member_id':
								if ( ! in_array($tag->value, $m))
								{
									$m[] = $tag->value;
								}
								else
								{
									$errors[] = array(str_replace("%x", $tag->value, lang('duplicate_member_id')));
								}
								break;
							case 'password':
								// We require a type attribute here, as outlined in the docs.
								// This is a quick error check to ensure its present.
								if ( ! @$tag->attributes['type'])
								{
									show_error(str_replace('%x', $this->validate->username, lang('missing_password_type')));
								}

								// encode password if it is type="text"
								$this->members[$i][$tag->tag] = ($tag->attributes['type'] == 'text') ? sha1($tag->value) : $tag->value;
								break;
						}
					}

					$username 		= (isset($this->members[$i]['username'])) ? $this->members[$i]['username'] : '';
					$screen_name 	= (isset($this->members[$i]['screen_name'])) ? $this->members[$i]['screen_name'] : '';
					$email 			= (isset($this->members[$i]['email'])) ? $this->members[$i]['email'] : '';

					/* -------------------------------------
					/*  Validate separately to display
					/*  exact problem
					/* -------------------------------------*/

					$this->validate->validate_username();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Username: '".$username."' - ".lang('within_user_record')." '".$username."')";
						}
						$errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					$this->validate->validate_screen_name();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Screen Name: '".$screen_name."' - ".lang('within_user_record')." '".$username."')";
						}
						$errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					$this->validate->validate_email();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Email: '".$email."' - ".lang('within_user_record')." '".$username."')";
						}
						$errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					/** -------------------------------------
					/**  Add a random hash if no password is defined
					/** -------------------------------------*/

					if ( ! isset($this->members[$i]['password']))
					{
						$this->members[$i]['password'] = sha1(mt_rand());
					}
					$i++;
				}
				else
				{
					/** -------------------------------------
					/**  Element isn't <member>
					/** -------------------------------------*/

					$errors[] = array(lang('invalid_element'));
				}
			}
		}
		else
		{
			/** -------------------------------------
			/**  No children of the root element
			/** -------------------------------------*/

			$errors[] = array(lang('invalid_xml'));
		}

		return $errors;
	}

	// --------------------------------------------------------------------

	/**
	 * Do Import
	 *
	 * Inserts new members into the database
	 *
	 * @return	number
	 */
	public function doImport()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		//  Set our optional default values
		$this->default_fields['group_id']			= $this->input->post('group_id');
		$this->default_fields['language']			= ($this->input->post('language') == lang('none') OR $this->input->post('language') == '') ? 'english' : strtolower($this->input->post('language'));
		$this->default_fields['timezone']			= $this->input->post('timezones') ?: NULL;
		$this->default_fields['date_format']		= $this->input->post('date_format') ?: NULL;
		$this->default_fields['time_format']		= $this->input->post('time_format') ?: NULL;
		$this->default_fields['include_seconds']	= $this->input->post('include_seconds') ?: NULL;
		$this->default_fields['ip_address']			= '0.0.0.0';
		$this->default_fields['join_date']			= $this->localize->now;

		//  Rev it up, no turning back!
		$new_ids = array();
		$counter = 0;
		$custom_fields = (count($this->default_custom_fields) > 0) ? TRUE : FALSE;

		foreach ($this->members as $count => $member)
		{
			$data = array();
			$dupe = FALSE;

			foreach ($this->default_fields as $key => $val)
			{
				if (isset($member[$key]))
				{
					$data[$key] = $member[$key];
				}
				elseif ($val != '')
				{
					$data[$key] = $val;
				}
			}

			if ($custom_fields)
			{
				foreach ($this->default_custom_fields as $name => $id)
				{
					if (isset($this->members_custom[$count][$name]))
					{
						$cdata['m_field_id_'.$id] = $this->members_custom[$count][$name];
					}
				}
			}

			//  Add a unique_id for each member
			$data['unique_id'] = random_string('encrypt');

			/* -------------------------------------
			/*  See if we've already imported a member with this member_id -
			/*  could possibly occur if an auto_increment value is used
			/*  before a specified member_id.
			/* -------------------------------------*/

			if (isset($data['member_id']))
			{
				if (isset($new_ids[$data['member_id']]))
				{
					/* -------------------------------------
					/*  Grab the member so we can re-insert it after we
					/*  take care of this nonsense
					/* -------------------------------------*/
					$dupe = TRUE;

					if ($custom_fields == TRUE)
					{
						$tempcdata = $this->db->get_where('member_data', array('member_id' => $data['member_id']));
					}
				}
			}
			/* -------------------------------------
			/*  Shove it in!
			/*  We are using REPLACE as we want to overwrite existing members if a member id is specified
			/* -------------------------------------*/

			$this->db->replace('members', $data);
			$mid = $this->db->insert_id();

			//  Add the member id to the array of imported member id's
			$new_ids[$mid] = $mid;

			if ($custom_fields == TRUE)
			{
				$cdata['member_id'] = $mid;
			}

			//  Insert the old auto_incremented member, if necessary
			if ($dupe === TRUE)
			{
				unset($tempdata->row['member_id']); // dump the member_id so it can auto_increment a new one
				$this->db->insert('members', $tempdata->row);
				$replace_mid = $this->db->insert_id();

				$new_ids[$replace_mid] = '';

				if ($custom_fields == TRUE)
				{
					$tempcdata->row['member_id'] = $replace_mid;
					$this->db->insert('member_data', $tempcdata->row);
				}
			}

			if ($custom_fields == TRUE)
			{
				$this->db->replace('member_data', $cdata);
			}

			$counter++;
		}

		/** -------------------------------------
		/**  Add records to exp_member_data and exp_member_homepage tables for all imported members
		/** -------------------------------------*/

		$values = '';

		foreach ($new_ids as $key => $val)
		{
			$values .= "('$key'),";
		}

		$values = substr($values, 0, -1);

		if ($custom_fields == FALSE)
		{
			$this->db->query("INSERT INTO exp_member_data (member_id) VALUES ".$values);
		}

		$this->db->query("INSERT INTO exp_member_homepage (member_id) VALUES ".$values);

		//  Update Statistics
		$this->stats->update_member_stats();

		return $counter;
	}

	// --------------------------------------------------------------------

	/**
	 * Custom Field Check
	 *
	 * Finds the fields in the first XML record that do not already exist
	 *
	 * @return	array
	 */
	private function _custom_field_check($xml_file)
	{
		//  Read XML file contents
		$this->load->helper('file');
		$contents = read_file($xml_file);
		$new_custom_fields = array();

		if ($contents === FALSE)
		{
			return;
		}

		$this->load->library('xmlparser');

		// parse XML data
		$xml = $this->xmlparser->parse_xml($contents);

		if ($xml == FALSE)
		{
			return FALSE;
		}

		//  Retreive Valid fields from database
		$query = $this->db->query("SHOW COLUMNS FROM exp_members");
		$existing_fields['birthday'] = '';

		foreach ($query->result_array() as $row)
		{
			$existing_fields[$row['Field']] = '';
		}

		$this->db->select('m_field_name');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			foreach ($m_custom_fields->result() as $row)
			{
				$existing_c_fields[$row->m_field_name] = '';
			}
		}

		// We go through a single iteration to find the fields
		if (is_array($xml->children[0]->children))
		{
			$member = $xml->children['0'];

			if ($member->tag == "member")
			{
				foreach($member->children as $tag)
				{
					$i = 0;

					// Is the XML tag an allowed database field
					if ( ! isset($existing_fields[$tag->tag]) && ! isset($existing_c_fields[$tag->tag]))
					{
						$new_custom_fields['new'][] = $tag->tag;
						$new_custom_fields['xml_fields'][] = $tag->tag;
					}
					elseif (isset($existing_c_fields[$tag->tag]))
					{
						while($i < 100)
						{
							$i++;

							if ( ! isset($existing_c_fields[$tag->tag.'_'.$i]))
							{
								$new_custom_fields['new'][] = $tag->tag.'_'.$i;
								$new_custom_fields['xml_fields'][] = $tag->tag;
								break;
							}
						}
					}
				}
			}
		}

		return $new_custom_fields;
	}

	// --------------------------------------------------------------------

	/**
	 * New Custom Fields Form
	 *
	 * Generates the form for new custom field settings
	 *
	 * @return	void
	 */
	private function _new_custom_fields_form($vars, $new_custom_fields)
	{
		$vars['form_hidden']['new'] = $new_custom_fields['new'];
		$vars['new_fields'] = $new_custom_fields['new'];

		$query = $this->member_model->count_records('member_fields');

		$vars['order_start'] = $query + 1;

		ee()->view->cp_page_title = lang('custom_fields');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/member_import'), lang('member_import'));
		ee()->cp->render('utilities/member-import/custom', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Create Custom Fields
	 *
	 * Creates the custom field form
	 *
	 * @return	mixed
	 */
	public function createCustomFields()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->_create_custom_validation();

		if ($this->form_validation->run() === FALSE)
		{
			return $this->member_import_confirm();
		}

		$error = array();
		$taken = array();

		$total_fields = count($this->input->post('create_ids'));

		foreach ($_POST['create_ids'] as $k => $v)
		{
			$data['m_field_name'] 		= $_POST['m_field_name'][$k];
			$data['m_field_label'] 		= $_POST['m_field_label'][$k];
			$data['m_field_description']= (isset($_POST['m_field_description'][$k])) ? $_POST['m_field_description'][$k]	: '';
			$data['m_field_type'] 		= (isset($_POST['m_field_type'][$k])) ? $_POST['m_field_type'][$k] : 'text';
			$data['m_field_list_items'] = (isset($_POST['m_field_list_items'][$k])) ? $_POST['m_field_list_items'][$k] : '';
			$data['m_field_ta_rows'] 	= (isset($_POST['m_field_ta_rows'][$k])) ? $_POST['m_field_ta_rows'][$k] : '100';
			$data['m_field_maxl'] 		= (isset($_POST['m_field_maxl'][$k])) ? $_POST['m_field_maxl'][$k] : '100';
			$data['m_field_width'] 		= (isset($_POST['m_field_width'][$k])) ? $_POST['m_field_width'][$k] : '100%';
			$data['m_field_search'] 	= 'y';
			$data['m_field_required'] 	= (isset($_POST['required'][$k])) ? 'y' : 'n';
			$data['m_field_public'] 	= (isset($_POST['public'][$k])) ? 'y' : 'n';
			$data['m_field_reg'] 		= (isset($_POST['reg_form'][$k])) ? 'y' : 'n';
			$data['m_field_fmt'] 		= (isset($_POST['m_field_fmt'][$k])) ? $_POST['m_field_fmt'][$k] : 'xhtml';
			$data['m_field_order'] 		= (isset($_POST['m_field_order'][$k])) ? $_POST['m_field_order'][$k] : '';

			$this->db->insert('member_fields', $data);
			$field_id = $this->db->insert_id();
			$this->db->query('ALTER table exp_member_data add column m_field_id_'.$field_id.' text NULL DEFAULT NULL');

			$_POST['added_fields'][$_POST['m_field_name'][$k]] = $_POST['m_field_label'][$k];
			//$_POST['xml_custom_fields'][$_POST['xml_field_name'][$k]] = $field_id;

			if ($_POST['new'][$k] != $_POST['m_field_name'][$k])
			{
				$_POST['field_map']['map'][$_POST['m_field_name'][$k]] = $_POST['new'][$k];
			}
			//$this->default_custom_fields[$_POST['m_field_name'][$k]] = 'm_field_id_'.$this->db->insert_id();

		}

		$_POST['auto_custom_field'] = 'n';
		unset($_POST['new']);
		unset($_POST['m_field_name']);
		unset($_POST['m_field_label']);
		unset($_POST['create_ids']);

		return $this->memberImportConfirm();
	}

	// --------------------------------------------------------------------

	/**
	 * Create Custom Field Validation
	 *
	 * Validates new custom field submission
	 *
	 * @return	mixed
	 */
	private function _create_custom_validation()
	{
		ee()->load->library('form_validation');

		// Gather existing field names
		ee()->db->select('m_field_name');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			foreach ($m_custom_fields->result() as $row)
			{
				$this->taken[] = $row->m_field_name;
			}
		}

		if (isset($_POST['create_ids']))
		{
			foreach($_POST['create_ids'] as $key => $val)
			{
				ee()->form_validation->set_rules("m_field_name[".$key."]", '', 'required|callback__valid_name');
				ee()->form_validation->set_rules("m_field_label[".$key."]", '', 'required');
				ee()->form_validation->set_rules("required[".$key."]", '', '');
				ee()->form_validation->set_rules("public[".$key."]", '', '');
				ee()->form_validation->set_rules("reg_form[".$key."]", '', '');
				ee()->form_validation->set_rules("xml_field_name[".$key."]", '', '');
			}
		}

		ee()->form_validation->set_message('required', lang('s_required'));
	}
}
// END CLASS

// EOF
