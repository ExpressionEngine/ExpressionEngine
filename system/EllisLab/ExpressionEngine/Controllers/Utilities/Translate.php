<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
