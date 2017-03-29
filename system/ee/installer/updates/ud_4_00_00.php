<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'add_field_data_flag',
				'removeMemberHomepageTable',
				'moveMemberFields',
				'warnAboutBirthdayTag'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Adds a column to exp_channel_fields, exp_member_fields, and
	 * exp_category_fields tables that indicates if the
	 * data is in the legacy data tables or their own table.
	 */
	private function add_field_data_flag()
	{
		if ( ! ee()->db->field_exists('legacy_field_data', 'category_fields'))
		{
			ee()->smartforge->add_column(
				'category_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('category_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('legacy_field_data', 'channel_fields'))
		{
			ee()->smartforge->add_column(
				'channel_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('channel_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('m_legacy_field_data', 'member_fields'))
		{
			ee()->smartforge->add_column(
				'member_fields',
				array(
					'm_legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('member_fields', array('m_legacy_field_data' => 'y'));
		}
    }

	private function removeMemberHomepageTable()
	{
		ee()->smartforge->drop_table('member_homepage');
	}

	private function moveMemberFields()
	{
		ee()->lang->load('member');
		ee()->load->model('member_model');

		// Do we need a preflight

		$fields = array(
			'url' => array(
				'field_label' => lang('url'),
				'field_description' => lang('url_desc'),
				'field_type' => 'url'
					),
			'location' => array(
				'field_label' => lang('location'),
				'field_description' => lang('location_desc'),
				'field_type' => 'text'
					),
			'occupation' => array(
				'field_label' => lang('occupation'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'interests' => array(
				'field_label' => lang('interests'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'aol_im' => array(
				'field_label' => lang('mbr_aol_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'yahoo_im' => array(
				'field_label' => lang('yahoo_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'msn_im' => array(
				'field_label' => lang('msn_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'icq' => array(
				'field_label' => lang('icq'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'bio' => array(
				'field_label' => lang('biography'),
				'field_description' => lang('biography_desc'),
				'field_type' => 'textarea'
				),
			'bday_d' => array(),
			'bday_m' => array(),
			'bday_y' => array()
			);

		// Safety check- does field already exist as a custom field
		$existing = ee('Model')->get('MemberField')->fields('m_field_name')->all();
		$map = array();

		if (count($existing) > 0)
		{
			foreach ($existing as $mfield)
			{
				$map[$mfield->m_field_name] = $mfield->field_id;
			}
		}

		$member_columns = ee()->db->list_fields('members');

		$member_table_fields = array();
		$vars = 0;
		foreach ($fields as $field => $data)
		{
			// does field still exist in exp_members
			// if not, there isn't much we can do
			if (in_array($field, $member_columns))
			{
				$member_table_fields[] = $field;
			}
			else
			{
				continue;
			}

			// member field already exists
			if (in_array($field, array_keys($map)))
			{
					continue;
			}

			$vars++;
			ee()->db->select_max($field);
		}

		$make = array();
		if ($vars > 0)
		{
			$query = ee()->db->get('members');
			$make = $query->row_array();

			// Removes all false and null, including 0
			$make = array_filter($make);
		}


		// All fields either exist AND are no longer in exp_members
		// Bail out
		if (empty($member_table_fields) OR empty($make))
		{
			return;
		}

		// If they have any birthday fields, we'll create a birthday variable
		$birthday = FALSE;
		foreach (array('bday_d', 'bday_m', 'bday_y') as $bday)
		{
			if (array_key_exists($bday, $make))
			{
				$fields['birthday'] = array(
					'field_label' => lang('birthday'),
					'field_description' => '',
					'field_type' => 'date'
				);

				$make['birthday'] = TRUE;
				$birthday = TRUE;
				break;
			}
		}

		unset($make['bday_y']);
		unset($make['bday_m']);
		unset($make['bday_d']);

		ee()->load->library('api');

		// Create custom fields
		foreach ($make as $name => $val)
		{
			if (in_array($name, array_keys($map)) OR in_array($name, array('bday_d', 'bday_m', 'bday_y')))
			{
				continue;
			}

			$field = ee('Model')->make('MemberField');

			$field->m_field_type = $fields[$name]['field_type'];

			$field->m_field_label = $fields[$name]['field_label'];
			$field->m_field_name = $name;
			$field->m_field_description = $fields[$name]['field_description'];

			$field->save();

			$map[$field->m_field_name] = $field->field_id;
		}


		// Copy custom field data

		// Should work for everything except birthday
		foreach ($make as $field_name => $vals)
		{
			if ($field_name == 'birthday')
			{
				continue;
			}

			// ARGH- how to handle re-inserting
			// If you rerun it, it just inserts again
			// for all but birthday, do a count, skip if it has any?
			if (ee()->db->count_all_results('member_data_field_'.$map[$field_name]) !== 0)
			{
				continue;
			}


			$sql = 'INSERT INTO exp_member_data_field_'.$map[$field_name].' (member_id, m_field_id_'.$map[$field_name].')
                SELECT m.member_id, m.'.$field_name.' FROM exp_members m';

			ee()->db->query($sql);
		}

		if ($birthday AND ee()->db->count_all_results('member_data_field_'.$map['birthday']) == 0)
		{
			ee()->db->select('member_id, bday_d, bday_m, bday_y');
			$query = ee()->db->get('members');

			foreach ($query->result() as $row)
			{
				if (empty($row->bday_y) AND empty($row->bday_m) AND empty($row->bday_d))
				{
					$r['member_id'] = $row->member_id;
					$r['m_field_id_'.$map['birthday']] = 0;
				}
				else
				{
					$year = ( ! empty($row->bday_y) AND strlen($row->bday_y) == 4) ? $row->bday_y : '1900';
					$month = ( ! empty($row->bday_m)) ? str_pad($row->bday_m, 2,"0", STR_PAD_LEFT) : '01';
					$day = ( ! empty($row->bday_d)) ? str_pad($row->bday_d, 2,"0", STR_PAD_LEFT) : '01';

					$r['member_id'] = $row->member_id;
					$r['m_field_id_'.$map['birthday']] = ee()->localize->string_to_timestamp($year.'-'.$month.'-'.$day.' 01:00 AM');

				}
				$data[] = $r;
			}

			ee()->db->insert_batch(
				'member_data_field_'.$map['birthday'], $data
				);
		}


		// Drop columns from exp_members
		foreach ($fields as $field => $data)
		{
			ee()->smartforge->drop_column('members', $field);
		}
	}
	
	private function warnAboutBirthdayTag()
	{
		ee()->update_notices->setVersion('4.0');
		ee()->update_notices->header('{birthday} member field variable is now a date type variable');
		ee()->update_notices->item(' Checking for templates to review ...');

		ee()->remove('template');
		require_once(APPPATH . 'libraries/Template.php');
		ee()->set('template', new Installer_Template());

		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new MSM_Config());

		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		$temp_warnings = array();
		$snip_warnings = array();
		$warnings = FALSE;
		$tag = LD.'birthday'.RD;
		foreach ($templates as $template)
		{
			if (strpos($template->template_data, $tag) !== FALSE)
			{
        		$temp_warnings[] = $template->get_group()->group_name.'/'.$template->template_name;
				$warnings = TRUE;
			}
		}
		
		// Check snippets
		ee()->load->model('snippet_model');
		$snippets = ee()->snippet_model->fetch();

		foreach($snippets as $snippet)
		{
			if (strpos($template->template_data, $tag) !== FALSE)
			{
        		$snip_warnings[] = $template->get_group()->group_name.'/'.$template->template_name;
				$warnings = TRUE;
			}
		}

		// Output the templates/snippets that have {birthday} in them
		if ($warnings)
		{
			$notice = 'The member profile variable {birthday} has been removed from the default member variables and replaced by
			a date type member custom field variable.  If you are currently using this variable in templates or snippets, you will want to edit it to include
			date formatting parameters.<br><br>';
			
			if (count($temp_warnings))
			{
				$notice .= 'The following templates contain a {birthday} variable:<br><br>'.implode('<br>', $temp_warnings).'<br><br>';
			}
			
			if (count($snip_warnings))
			{
				$notice .= 'The following snippets contain a {birthday} variable:<br><br>'.implode('<br>', $snip_warnings).'<br><br>';
			}
			
			ee()->update_notices->item($note);
		}

		ee()->update_notices->item('Done.');

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}

}

// EOF
