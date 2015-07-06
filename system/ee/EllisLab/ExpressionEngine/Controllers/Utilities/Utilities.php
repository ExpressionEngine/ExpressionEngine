<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

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

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => ee('CP/URL', 'utilities/communicate'),
			array(
				'sent' => ee('CP/URL', 'utilities/communicate/sent')
			),
			'cp_translation',
			$languages,
			'php_info' => array('href' => ee('CP/URL', 'utilities/php'), 'rel' => 'external'),
			'debug_extensions' => array('href' => ee('CP/URL', 'utilities/extensions')),
			'import_tools',
			array(
				'file_converter' => ee('CP/URL', 'utilities/import-converter'),
				'member_import' => ee('CP/URL', 'utilities/member-import')
			),
			'sql_manager_abbr' => ee('CP/URL', 'utilities/sql'),
			array(
				'query_form' => ee('CP/URL', 'utilities/query')
			),
			'data_operations',
			array(
				'cache_manager' => ee('CP/URL', 'utilities/cache'),
				'statistics' => ee('CP/URL', 'utilities/stats'),
				'search_and_replace' => ee('CP/URL', 'utilities/sandr')
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
		ee()->functions->redirect(ee('CP/URL', 'utilities/communicate'));
	}
}
// END CLASS

/* End of file Utilities.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Utilities/Utilities.php */