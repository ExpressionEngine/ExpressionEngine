<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Translate Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Translate extends Utilities {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! is_really_writable(APPPATH.'translations/'))
		{
			$not_writeable = lang('translation_dir_unwritable');
		}

		// Add in any submitted search phrase
		$this->view->filter_by_phrase_value = ee()->input->get_post('filter_by_phrase');
	}

	/**
	 * Magic method that sets the language and routes the action
	 */
	public function __call($name, $arguments)
	{
		$name = strtolower($name);

		ee()->load->model('language_model');
		$languages =ee()->language_model->language_pack_names();
		if ( ! array_key_exists($name, $languages))
		{
			show_404();
		}

		if (empty($arguments))
		{
			$this->listFiles($name);
		}
		elseif (strtolower($arguments[0]) == 'edit' && isset($arguments[1]))
		{
			$this->edit($name, $arguments[1]);
		}
		else
		{
			show_404();
		}
	}

	/**
	 * Determine's the default language and lists those files.
	 */
	public function index()
	{
		$language = (ee()->config->item('deft_lang') && ee()->config->item('deft_lang') != '') ? ee()->config->item('deft_lang') : 'english';
		$this->listFiles($language);
	}

	private function listFiles($language)
	{
		ee()->view->cp_page_title = ucfirst($language) . ' ' . lang('language_files');
		ee()->view->language = $language;

		$base_url = new URL('utilities/translate/' . $language, ee()->session->session_id());
		if ( ! empty($this->view->filter_by_phrase_value))
		{
			$base_url->setQueryStringVariable('filter_by_phrase', $this->view->filter_by_phrase_value);
		}

		$files = array();

		$this->load->helper('file');

		$path = APPPATH.'language/'.$language;
		$ext_len = strlen('.php');

		$filename_end = '_lang.php';
		$filename_end_len = strlen($filename_end);

		$languages = array();

		$language_files = get_filenames($path);

		foreach ($language_files as $file)
		{
			if ($file == 'email_data.php')
			{
				continue;
			}

			if (substr($file, -$filename_end_len) && substr($file, -$ext_len) == '.php')
			{
				if ( ! empty($this->view->filter_by_phrase_value))
				{
					if (strpos($file, $this->view->filter_by_phrase_value) === FALSE)
					{
						continue;
					}
				}

				$files[] = array(
					'filename'	=> $file,
					'name'		=> str_replace('_lang.php', '', $file)
				);
			}
		}

		if (ee()->input->get('file_name_direction') == 'desc')
		{
			rsort($files);

			// Set the new sort URL
			$base_url->setQueryStringVariable('file_name_direction', 'asc');
			ee()->view->file_name_sort_url = $base_url->compile();

			// Reset the base to reflect our actual direction
			$base_url->setQueryStringVariable('file_name_direction', 'desc');
			ee()->view->file_name_direction = 'desc';
		}
		else
		{
			sort($files);

			// Set the new sort URL
			$base_url->setQueryStringVariable('file_name_direction', 'desc');
			ee()->view->file_name_sort_url = $base_url->compile();

			// Reset the base to reflect our actual direction
			$base_url->setQueryStringVariable('file_name_direction', 'asc');
			ee()->view->file_name_direction = 'asc';
		}

		$chunks = array_chunk($files, 50);

		// Paginate!
		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;
		$pagination = new Pagination(50, count($files), $page);
		ee()->view->pagination = $pagination->cp_links($base_url);

		ee()->view->files = $chunks[$page - 1];

		ee()->cp->render('utilities/translate/list');
	}

	private function edit($language, $file)
	{
		ee()->cp->render('utilities/translate/edit');
	}
}
// END CLASS

/* End of file Translate.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Translate.php */
