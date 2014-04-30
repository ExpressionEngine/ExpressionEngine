<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Utilities extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => cp_url('utilities/communicate'),
			'cp_translation',
			array(
				// Show installed languages?
				'English (Default)' => cp_url('utilities/communicate')
			),
			'php_info' => cp_url('utilities/php'),
			'import_tools',
			array(
				'file_converter' => cp_url('utilities/import_converter'),
				'member_import' => cp_url('utilities/member_import')
			),
			'sql_manager' => cp_url('utilities/sql'),
			array(
				'query_form' => cp_url('utilities/query')
			),
			'data_operations',
			array(
				'cache_manager' => cp_url('utilities/cache'),
				'statistics' => cp_url('utilities/stats'),
				'search_and_replace' => cp_url('utilities/sandr')
			)
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		// Will redirect based on permissions later
		$this->communicate();
	}

	// --------------------------------------------------------------------

	/**
	 * Communicate
	 *
	 * @access	public
	 * @return	void
	 */
	public function communicate()
	{
		ee()->view->cp_page_title = lang('communicate');
		ee()->cp->render('utilities/communicate');
	}

	// --------------------------------------------------------------------

	/**
	 * PHP Info
	 *
	 * @access	public
	 * @return	void
	 */
	public function php()
	{
		// Here, we'll capture the output of PHP Info and modify its markup
		// to appear in our control panel
		ob_start();

		phpinfo();

		$buffer = ob_get_contents();

		ob_end_clean();

		$output = (preg_match("/<body.*?".">(.*)<\/body>/is", $buffer, $match)) ? $match['1'] : $buffer;
		$output = preg_replace("/width\=\".*?\"/", "width=\"100%\"", $output);
		$output = preg_replace("/<hr.*?>/", "<br />", $output); // <?
		$output = preg_replace("/<a href=\"http:\/\/www.php.net\/\">.*?<\/a>/", "", $output);
		$output = preg_replace("/<a href=\"http:\/\/www.zend.com\/\">.*?<\/a>/", "", $output);
		$output = preg_replace("/<a.*?<\/a>/", "", $output);// <?
		$output = preg_replace("/<th(.*?)>/", "<th \\1 >", $output);
		$output = preg_replace("/<tr(.*?).*?".">/", "<tr \\1>\n", $output);
		$output = preg_replace("/<td.*?".">/", "<td valign=\"top\">", $output);
		$output = preg_replace("/<h2 align=\"center\">PHP License<\/h2>.*?<\/table>/si", "", $output);
		$output = preg_replace("/ align=\"center\"/", "", $output);
		$output = preg_replace("/<table(.*?)bgcolor=\".*?\">/", "\n\n<table\\1>", $output);
		$output = preg_replace("/<table(.*?)>/", "\n\n<table\\1 class=\"mainTable\" cellspacing=\"0\">", $output);
		$output = preg_replace("/<h2>PHP License.*?<\/table>/is", "", $output);
		$output = preg_replace("/<br \/>\n*<br \/>/is", "", $output);
		$output = preg_replace('/<h(1|2)\s*(class="p")?/i', '<h\\1', $output);
		$output = str_replace("<h1></h1>", "", $output);
		$output = str_replace("<h2></h2>", "", $output);

		ee()->view->phpinfo = $output;
		ee()->view->cp_page_title = lang('php_info');

		// Need a separate page title because this lang key has an abbr tag,
		// can't also use it in <title/>
		ee()->view->page_title = sprintf(lang('php_info_title'), phpversion());

		ee()->cp->add_to_head($this->view->head_link('css/v3/phpinfo.css'));
		ee()->cp->render('utilities/php-info');
	}

	// --------------------------------------------------------------------

	/**
	 * Cache Manager
	 *
	 * @access	public
	 * @return	void
	 */
	public function cache()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('cache_type[]', 'lang:caches_to_clear', 'required');

		if (ee()->form_validation->run() !== FALSE)
		{
			// Clear each cache type checked
			foreach (ee()->input->post('cache_type') as $type)
			{
				ee()->functions->clear_caching($type);
			}

			ee()->session->set_flashdata('success', lang('caches_cleared'));
			ee()->functions->redirect(cp_url('utilities/cache'));
		}

		ee()->view->cp_page_title = lang('cache_manager');
		ee()->cp->render('utilities/cache');
	}

	// --------------------------------------------------------------------

	/**
	 * Search and Replace utility
	 *
	 * @access	public
	 * @return	void
	 */
	public function sandr()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
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
				'label' => 'lang:sandr_password',
				'rules' => 'required|auth_password'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			$replaced = $this->_do_search_and_replace(
				ee()->input->post('search_term'),
				ee()->input->post('replace_term'),
				ee()->input->post('replace_where')
			);

			ee()->session->set_flashdata('success', sprintf(lang('rows_replaced'), (int)$replaced));

			ee()->functions->redirect(cp_url('utilities/sandr'));
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

	// --------------------------------------------------------------------

	/**
	 * Future home of the member import file converter
	 */
	public function import_converter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->view->cp_page_title = lang('import_converter');
		ee()->cp->render('utilities/import-converter');
	}

	// --------------------------------------------------------------------

	/**
	 * Future home of the member import file converter
	 */
	public function member_import()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'xml_file',
				 'label'   => 'lang:mbr_xml_file',
				 'rules'   => 'required|file_exists'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			// Do something...
		}

		$groups = ee()->api->get('MemberGroup')->order('group_id', 'asc')->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		ee()->lang->load('admin');
		ee()->load->model('admin_model');
		$config_fields = ee()->config->prep_view_vars('localization_cfg');

		$vars = array(
			'language_options' => array('None' => 'None', 'English' => 'English'),
			'member_groups' => $member_groups,
			'date_format' => $config_fields['fields']['date_format'],
			'time_format' => $config_fields['fields']['time_format'],
			'timezone_menu' => ee()->localize->timezone_menu(set_value('timezones'), 'timezones')
		);

		ee()->view->cp_page_title = lang('member_import');
		ee()->cp->render('utilities/member-import', $vars);
	}
}

/* End of file ee.php */
/* Location: ./system/expressionengine/controllers/ee.php */