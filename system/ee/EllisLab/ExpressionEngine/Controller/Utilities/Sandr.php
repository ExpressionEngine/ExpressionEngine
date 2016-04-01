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
 * ExpressionEngine CP Search and Replace Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Sandr extends Utilities {

	/**
	 * Search and Replace utility
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'search_term',
				'label' => 'lang:sandr_search_text',
				'rules' => 'required'
			),
			array(
				'field' => 'replace_term',
				'label' => 'lang:sandr_replace_text',
				'rules' => 'required'
			),
			array(
				'field' => 'replace_where',
				'label' => 'lang:sandr_in',
				'rules' => 'required'
			),
			array(
				'field' => 'password_auth',
				'label' => 'lang:current_password',
				'rules' => 'required|auth_password'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$replaced = $this->_do_search_and_replace(
				ee()->input->post('search_term'),
				ee()->input->post('replace_term'),
				ee()->input->post('replace_where')
			);

			ee()->view->set_message('success', lang('cp_message_success'), sprintf(lang('rows_replaced'), (int)$replaced), TRUE);
			ee()->functions->redirect(ee('CP/URL')->make('utilities/sandr'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('sandr_error'), lang('sandr_error_desc'));
		}

		ee()->load->model('tools_model');
		ee()->view->replace_options = $this->tools_model->get_search_replace_options();

		ee()->view->cp_page_title = lang('sandr');
		ee()->cp->render('utilities/sandr');
	}

	// --------------------------------------------------------------------

	/**
	 * Do Search and Replace
	 *
	 * Used by search_and_replace() to execute replacement
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function _do_search_and_replace($search, $replace, $where)
	{
		// escape search and replace for use in queries
		$search = $this->db->escape_str($search);
		$replace = $this->db->escape_str($replace);
		$where = $this->db->escape_str($where);

		if ($where == 'title')
		{
			$sql = "UPDATE `exp_channel_titles` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		elseif ($where == 'preferences' OR strncmp($where, 'site_preferences_', 17) == 0)
		{
			$rows = 0;

			if ($where == 'preferences')
			{
				$site_id = $this->config->item('site_id');
			}
			else
			{
				$site_id = substr($where, strlen('site_preferences_'));
			}

			/** -------------------------------------------
			/**  Site Preferences in Certain Tables/Fields
			/** -------------------------------------------*/

			$preferences = array(
				'exp_channels' => array(
					'channel_title',
					'channel_url',
					'comment_url',
					'channel_description',
					'comment_notify_emails',
					'channel_notify_emails',
					'search_results_url',
					'rss_url'
				),
				'exp_upload_prefs' => array(
					'server_path',
					'properties',
					'file_properties',
					'url'
				),
				'exp_member_groups' => array(
					'group_title',
					'group_description',
					'mbr_delete_notify_emails'
				),
				'exp_global_variables'	=> array('variable_data'),
				'exp_categories'		=> array('cat_image'),
				'exp_forums'			=> array(
					'forum_name',
					'forum_notify_emails',
					'forum_notify_emails_topics'),
				'exp_forum_boards'		=> array(
					'board_label',
					'board_forum_url',
					'board_upload_path',
					'board_notify_emails',
					'board_notify_emails_topics'
				)
			);

			foreach($preferences as $table => $fields)
			{
				if ( ! $this->db->table_exists($table) OR $table == 'exp_forums')
				{
					continue;
				}

				$site_field = ($table == 'exp_forum_boards') ? 'board_site_id' : 'site_id';

				foreach($fields as $field)
				{
					$this->db->query("UPDATE `{$table}`
								SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
								WHERE `{$site_field}` = '".$this->db->escape_str($site_id)."'");

					$rows += $this->db->affected_rows();
				}
			}

			if ($this->db->table_exists('exp_forum_boards'))
			{
				$this->db->select('board_id');
				$this->db->where('board_site_id', $site_id);
				$query = $this->db->get('forum_boards');

				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{
						foreach($preferences['exp_forums'] as $field)
						{
							$this->db->query("UPDATE `exp_forums`
										SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
										WHERE `board_id` = '".$this->db->escape_str($row['board_id'])."'");

							$rows += $this->db->affected_rows();
						}
					}
				}
			}

			/** -------------------------------------------
			/**  Site Preferences in Database
			/** -------------------------------------------*/

			$this->config->update_site_prefs(array(), $site_id, $search, $replace);

			$rows += 5;
		}
		elseif ($where == 'template_data')
		{
			$sql = "UPDATE `exp_templates` SET `$where` = REPLACE(`{$where}`, '{$search}', '{$replace}'), `edit_date` = '".$this->localize->now."'";
		}
		elseif (strncmp($where, 'template_', 9) == 0)
		{
			$sql = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{$search}', '{$replace}'), edit_date = '".$this->localize->now."'
					WHERE group_id = '".substr($where,9)."'";
		}
		elseif (strncmp($where, 'field_id_', 9) == 0)
		{
			$sql = "UPDATE `exp_channel_data` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		else
		{
			// no valid $where
			return FALSE;
		}

		if (isset($sql))
		{
			$this->db->query($sql);
			$rows = $this->db->affected_rows();
		}

		return $rows;
	}

}
// END CLASS

// EOF
