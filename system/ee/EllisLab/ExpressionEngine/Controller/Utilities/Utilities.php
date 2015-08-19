<?php

namespace EllisLab\ExpressionEngine\Controller\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Utilities Class
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

		ee()->lang->loadfile('utilities');

		$this->generateSidebar();

		ee()->view->header = array(
			'title' => lang('system_utilities')
		);
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('Sidebar')->make();

		$sidebar->addHeader(lang('communicate'), ee('CP/URL', 'utilities/communicate'))
			->addBasicList()
				->addItem(lang('sent'), ee('CP/URL', 'utilities/communicate/sent'));

		$langauge_list = $sidebar->addHeader(lang('cp_translation'))
			->addBasicList();

		$default_language = ee()->config->item('deft_lang') ?: 'english';
		$languages = array();

		foreach (ee()->lang->language_pack_names() as $key => $value)
		{
			$menu_title = $value;
			$url = ee('CP/URL', 'utilities/translate/' . $key);

			if ($key == $default_language)
			{
				$menu_title .= ' (' . lang('default') . ')';

				// Make the default language first
				$languages = array_merge(array($menu_title => $url), $languages);
				continue;
			}

			$languages[$menu_title] = $url;
		}

		foreach ($languages as $menu_title => $url)
		{
			$langauge_list->addItem($menu_title, $url);
		}

		$sidebar->addHeader(lang('php_info'), ee('CP/URL', 'utilities/php'))
			->urlIsExternal();

		$sidebar->addHeader(lang('debug_extensions'), ee('CP/URL', 'utilities/extensions'));

		$import_list = $sidebar->addHeader(lang('import_tools'))
			->addBasicList();

		$import_list->addItem(lang('file_converter'), ee('CP/URL', 'utilities/import-converter'));
		$import_list->addItem(lang('member_import'), ee('CP/URL', 'utilities/member-import'));

		$sidebar->addHeader(lang('sql_manager_abbr'), ee('CP/URL', 'utilities/sql'))
			->addBasicList()
				->addItem(lang('query_form'), ee('CP/URL', 'utilities/query'));

		$data_list = $sidebar->addHeader(lang('data_operations'))
			->addBasicList();

		$data_list->addItem(lang('cache_manager'), ee('CP/URL', 'utilities/cache'));
		$data_list->addItem(lang('statistics'), ee('CP/URL', 'utilities/stats'));
		$data_list->addItem(lang('search_and_replace'), ee('CP/URL', 'utilities/sandr'));
	}

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		// Will redirect based on permissions later
		ee()->functions->redirect(ee('CP/URL', 'utilities/communicate'));
	}
}
// END CLASS

/* End of file Utilities.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controller/Utilities/Utilities.php */
