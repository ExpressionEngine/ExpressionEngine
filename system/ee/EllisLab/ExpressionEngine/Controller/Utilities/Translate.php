<?php

namespace EllisLab\ExpressionEngine\Controller\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ZipArchive;
use EllisLab\ExpressionEngine\Library\CP\Table;


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
 * ExpressionEngine CP Translate Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Translate extends Utilities {

	protected $languages_dir;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_translate'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->languages_dir = SYSPATH.'user/language/';

		if ( ! is_really_writable($this->languages_dir))
		{
			$not_writeable = lang('translation_dir_unwritable');
		}
	}

	/**
	 * Magic method that sets the language and routes the action
	 */
	public function __call($name, $arguments)
	{
		$name = strtolower($name);

		if ( ! array_key_exists($name, ee()->lang->language_pack_names()))
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
		elseif (strtolower($arguments[0]) == 'save' && isset($arguments[1]))
		{
			$this->save($name, $arguments[1]);
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
		$language = ee()->config->item('deft_lang') ?: 'english';
		$this->listFiles($language);
	}

	/**
	 * List the "*_lang.php" files in a $language directory
	 *
	 * @param string $language	The language directory (i.e. 'english')
	 */
	private function listFiles($language)
	{
		if (ee()->input->get_post('bulk_action') == 'export')
		{
			$this->export($language, ee()->input->get_post('selection'));
		}

		ee()->view->cp_page_title = ucfirst($language) . ' ' . lang('language_files');

		$vars = array(
			'language' => $language,
			'pagination' => ''
		);

		$base_url = ee('CP/URL')->make('utilities/translate/' . $language);

		$data = array();

		$this->load->helper('file');

		$path = $this->getLanguageDirectory($language);

		$filename_end = '_lang.php';
		$filename_end_len = strlen($filename_end);

		$languages = array();

		$language_files = get_filenames($path);

		foreach ($language_files as $file)
		{
			if ($file == 'email_data.php' OR $file == 'stopwords.php')
			{
				continue;
			}

			if (substr($file, -$filename_end_len) && substr($file, -4) == '.php')
			{
				$name = str_replace('_lang.php', '', $file);
				$edit_url = ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $name);
				$data[] = array(
					'filename' => array(
							'content' => $file,
							'href' => $edit_url
						),
					array('toolbar_items' => array(
						'edit' => array(
							'href' => $edit_url,
							'title' => strtolower(lang('edit'))
						)
					)),
					array(
						'name' => 'selection[]',
						'value' => $name
					)
				);
			}
		}

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE));
		$table->setColumns(
			array(
				'file_name',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_search_results');
		$table->setData($data);
		$vars['table'] = $table->viewData($base_url);

		$base_url = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
		}

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
			);
		}

		ee()->cp->render('utilities/translate/list', $vars);
	}

	/**
	 * Find the language in the potential language directories
	 *
	 * @param string $language	The language name (i.e. 'english')
	 * @return string The full path to the language directory
	 */
	private function getLanguageDirectory($language)
	{
		foreach (array(SYSPATH.'user/', APPPATH) as $parent_directory)
		{
			if (is_dir($parent_directory.'language/'.$language))
			{
				return $parent_directory.'language/'.$language.'/';
			}
		}

		ee()->view->set_message('issue', lang('cannot_access'));
	}

	/**
	 * Zip and send the selected language files
	 *
	 * @param string $language	The language directory (i.e. 'english')
	 * @param array  $files		The list of files to export
	 */
	private function export($language, $files)
	{
		if (empty($files))
		{
			ee()->view->set_message('issue', lang('no_files_selected'));
			return;
		}

		$path = $this->getLanguageDirectory($language);

		// Confirm the files exist
		foreach($files as $file)
		{
			if ( ! is_readable($path . $file . '_lang.php'))
			{
				$message = $path . $file . '_lang.php ' . lang('cannot_access') . '.';
				ee()->view->set_message('issue', $message);
				return;
			}
		}

		$tmpfilename = tempnam(sys_get_temp_dir(), '');
		$zip = new ZipArchive();
		if ($zip->open($tmpfilename, ZipArchive::CREATE) !== TRUE)
		{
			ee()->view->set_message('issue', lang('cannot_create_zip'));
			return;
		}

		foreach($files as $file)
		{
			$zip->addFile($path . $file . '_lang.php', $file . '_lang.php');
		}
		$zip->close();

		$data = file_get_contents($tmpfilename);
		unlink($tmpfilename);

		ee()->load->helper('download');
		force_download('ExpressionEngine-language-export-' . $language . '.zip', $data);
		exit;
	}

	private function edit($language, $file)
	{
		$file = ee()->security->sanitize_filename($file);

		$path = $this->getLanguageDirectory($language);
		$filename =  $file . '_lang.php';

		if ( ! is_readable($path . $filename))
		{
			$message = $path . $file . '_lang.php ' . lang('cannot_access') . '.';
			ee()->view->set_message('issue', $message, '', TRUE);
			ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language));
		}

		ee()->view->cp_page_title = $filename . ' ' . ucfirst(lang('translation'));

		$vars['language'] = $language;
		$vars['filename'] = $filename;

		$dest_dir = $this->languages_dir . $language . '/';

		require($path . $filename);

		$M = $lang;

		unset($lang);

		if (file_exists($dest_dir . $filename))
		{
			require($dest_dir . $filename);
		}
		else
		{
			$lang = $M;
		}

		$keys = array();

		foreach ($M as $key => $val)
		{
			if ($key != '')
			{
				$trans = ( ! isset($lang[$key])) ? '' : $lang[$key];
				$keys[$key]['original'] = htmlentities($val);
				$keys[$key]['trans'] = str_replace("'", "&#39;", $trans);
				$keys[$key]['type'] = (strlen($val) > 100) ? 'textarea' : 'text';
			}
		}

		$vars = array(
			'language'  => $language,
			'file'		=> $file,
			'keys'		=> $keys
		);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('utilities/translate/' . $language)->compile() => ucfirst($language) . ' ' . lang('language_files')
		);

		ee()->cp->render('utilities/translate/edit', $vars);
	}

	private function save($language, $file)
	{
		$file = ee()->security->sanitize_filename($file);

		$dest_dir = $this->languages_dir . $language . '/';
		$filename =  $file . '_lang.php';
		$dest_loc = $dest_dir . $filename;

		$str = '<?php'."\n".'$lang = array('."\n\n\n";

		ee()->lang->loadfile($file);

		foreach ($_POST as $key => $val)
		{
			$val = str_replace('<script', '', $val);
			$val = str_replace('<iframe', '', $val);
			$val = str_replace(array("\\", "'"), array("\\\\", "\'"), $val);

			$str .= '\''.$key.'\' => '."\n".'\''.$val.'\''.",\n\n";
		}

		$str .= "''=>''\n);\n\n";
		$str .= "// End of File";

		// Make sure any existing file is writeable
		if (file_exists($dest_loc))
		{
			@chmod($dest_loc, FILE_WRITE_MODE);

			if ( ! is_really_writable($dest_loc))
			{
				ee()->view->set_message('issue', lang('trans_file_not_writable'), '', TRUE);
				ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $file));
			}
		}

		$this->load->helper('file');

		if (write_file($dest_loc, $str))
		{
			ee()->view->set_message('success', lang('translations_saved'), str_replace('%s', $dest_loc, lang('file_saved')), TRUE);
		}
		else
		{
			ee()->view->set_message('issue', lang('invalid_path'), $dest_loc, TRUE);
		}
		ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $file));
	}
}
// END CLASS

// EOF
