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
				'removeMemberHomepageTable',
				'moveMemberFields'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
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

		foreach ($fields as $field => $data)
		{
			ee()->db->select_max($field);
		}

		$query = ee()->db->get('members');
		$move = $query->row_array();

		// Removes all false and null, including 0
		$move = array_filter($move);

		if (empty($move))
		{
			return;
		}

		// If they have any birthday fields, we'll create a birthday variable
		$birthday = FALSE;
		foreach (array('bday_d', 'bday_m', 'bday_y') as $bday)
		{
			if (array_key_exists($bday, $move))
			{
				$fields['birthday'] = array(
					'field_label' => lang('birthday'),
					'field_description' => '',
					'field_type' => 'date'
				);

				$move['birthday'] = TRUE;

				$birthday = TRUE;
			}

			unset($move[$bday]);
		}


		// Safety check- does field already exist in exp_member_fields
		$existing = ee('Model')->get('MemberField')->fields('m_field_name')->all()->pluck('m_field_name');


		ee()->load->library('api');

		// Create custom fields
		foreach ($move as $name => $val)
		{
			if (in_array($name, $existing) OR in_array($name, array('bday_d', 'bday_m', 'bday_y')))
			{
				continue;
			}

			$new_fields[] = $name;

			$field = ee('Model')->make('MemberField');

			$field->m_field_type = $fields[$name]['field_type'];


			$field->m_field_label = $fields[$name]['field_label'];
			$field->m_field_name = $name;
			$field->m_field_description = $fields[$name]['field_description'];

			$field->save();
		}


		// Copy custom field data
		// Seth can do this via select into query- need to merge.

		// Drop columns from exp_members
		foreach ($fields as $field => $data)
		{
			ee()->smartforge->drop_column('members', $field);
		}

	}
	
	
	private function _update_relationship_tags()
	{
		ee()->remove('template');
		require_once(APPPATH . 'libraries/Template.php');
		ee()->set('template', new Installer_Template());

		// Since we don't have consistent destructors,
		// we'll keep this here.
		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new MSM_Config());

		// We need to figure out which template to load.
		// Need to check the edit date.
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		// related_entries
		// Foreach template
		foreach($templates as $template)
		{
			// If there aren't any related entries tags, then we don't need to continue.
			if (strpos($template->template_data, 'related_entries') === FALSE
				&& strpos($template->template_data, 'reverse_related_entries') === FALSE)
			{
				continue;
			}

			// Find the {related_entries} and {reverse_related_entries} tags
			// (match pairs and wrapped tags)
			$template->template_data = ee()->template->replace_related_entries_tags($template->template_data);

			// save the template
			// if saving to file, save the file
			if ($template->loaded_from_file)
			{
				ee()->template_model->save_to_file($template);
			}
			else
			{
				ee()->template_model->save_to_database($template);
			}
		}

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}





	private function _warn_about_layout_contents()
	{
		ee()->update_notices->setVersion('2.9');
		ee()->update_notices->header('{layout:contents} reserved variable is strictly enforced.');
		ee()->update_notices->item(' Checking for templates to review ...');

		ee()->remove('template');
		require_once(APPPATH . 'libraries/Template.php');
		ee()->set('template', new Installer_Template());

		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new MSM_Config());

		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		$warnings = array();
		foreach ($templates as $template)
		{
			// This catches any {layout=} and {layout:set} tags
			if (preg_match_all('/('.LD.'layout\s*)(.*?)'.RD.'/s', $template->template_data, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $match)
				{
					$params = ee()->functions->assign_parameters($match[2]);

					// If any of the parameters indicate it's trying to
					// set the contents variable, log the template name
					if (isset($params['contents']) OR
						(isset($params['name']) && $params['name'] == 'contents'))
					{
						$warnings[] = $template->get_group()->group_name.'/'.$template->template_name;
					}
				}
			}
		}

		// Output a list of templates that are setting layout:contents
		if ( ! empty($warnings))
		{
			ee()->update_notices->item('The following templates are manually setting the {layout:contents} variable, please use a different variable name.<br>'.implode('<br>', $warnings));
		}

		ee()->update_notices->item('Done.');

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}


	// -------------------------------------------------------------------

	/**
	 * Update Relationship Tags in Snippets, Missed in Previous Update
	 *
	 * 	Pulls snippets from the database, examines them for any relationship tags,
	 * updates them and then saves them back to the database.
	 *
	 * @return void
	 */
	protected function _update_relationship_tags_in_snippets()
	{
		if ( ! class_exists('Installer_Template'))
		{
			require_once(APPPATH . 'libraries/Template.php');
		}
		ee()->remove('template');
		ee()->set('template', new Installer_Template());

		ee()->load->model('snippet_model');
		$snippets = ee()->snippet_model->fetch();

		foreach($snippets as $snippet)
		{
			// If there aren't any related entries tags, then we don't need to continue.
			if (strpos($snippet->snippet_contents, 'related_entries') === FALSE
				&& strpos($snippet->snippet_contents, 'reverse_related_entries') === FALSE)
			{
				continue;
			}

			$snippet->snippet_contents = ee()->template->replace_related_entries_tags($snippet->snippet_contents);
			ee()->snippet_model->save($snippet);
		}
	}


	
	
}

// EOF
