<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
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
 * ExpressionEngine CP Design\Forums Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Forums extends AbstractDesignController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (ee()->config->item('forum_is_installed') != "y")
		{
			show_404();
		}

		if ( ! ee()->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->stdHeader();

		ee()->lang->loadfile('specialty_tmp');
	}

	public function index($theme = '')
	{
		if (empty($theme))
		{
			$theme = $this->getDefaultTheme();
		}

		$base_path = ee('Theme')->getPath('forum/' . ee()->security->sanitize_filename($theme));

		if ( ! is_dir($base_path))
		{
			show_error(lang('unable_to_find_templates'));
		}

		ee()->load->helper('directory');

		$vars = array();

		$base_url = ee('CP/URL')->make('design/forums/index/' . $theme);

		$table = ee('CP/Table', array('autosort' => TRUE, 'subheadings' => TRUE));
		$table->setNoResultsText('no_templates_found');
		$table->setColumns(
			array(
				'template',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
			)
		);

		$data = array();
		foreach (directory_map($base_path) as $dir => $files)
		{
			$path = $base_path . '/' . $dir;

			if ( ! is_array($files) OR $dir == 'images')
			{
				continue;
			}

			foreach ($files as $file)
			{
				if (strpos($file, '.') !== FALSE)
				{
					$human = str_replace('_', ' ', substr($file, 0, -strlen(strrchr($file, '.'))));
					$edit_url = ee('CP/URL')->make('design/forums/edit/' . $theme . '/' . $dir . '/' . $human);
					$human = ucfirst($human);
					$data[$dir][] = array(
						array(
							'content' => (lang($human) == FALSE) ? $human : lang($human),
							'href' => $edit_url
							),
						array('toolbar_items' => array(
							'edit' => array(
								'href' => $edit_url,
								'title' => lang('edit')
							),
						))
					);
				}
			}

		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		ee()->load->model('member_model');

		$themes = array();
		foreach (ee('Theme')->listThemes('forum') as $dir => $name)
		{
			$themes[ee('CP/URL')->make('design/forums/index/' . $dir)->compile()] = $name;
		}

		$vars['themes'] = form_dropdown('theme', $themes, ee('CP/URL')->make('design/forums/index/' . $theme));

		$this->generateSidebar('forums');
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('forum_templates');

		ee()->javascript->change("select[name=\'theme\']", 'window.location.href = $(this).val()');

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('addons/settings/forum')->compile() => lang('forum_manager'),
		);

		ee()->cp->render('design/forums/index', $vars);
	}

	private function getDefaultTheme()
	{
		$files = ee('Theme')->listThemes('forum');

		if (empty($files))
		{
			show_error(lang('unable_to_find_templates'));
		}

		if (isset($files['default']))
		{
			return 'default';
		}
		else
		{
			$theme_path = ee('Theme')->getPath('forum/');

			foreach (array_keys($files) as $dir)
			{
				if (is_dir($theme_path . $dir))
				{
					return $dir;
				}
			}
		}

		show_error(lang('unable_to_find_templates'));
	}

	public function edit($theme, $dir, $file)
	{
		$path = ee('Theme')->getPath('forum/'
			.ee()->security->sanitize_filename($theme)
			.'/'
			.ee()->security->sanitize_filename($dir)
			.'/'
			.ee()->security->sanitize_filename($file . '.html'));

		if ( ! file_exists($path))
		{
			show_error(lang('unable_to_find_template_file'));
		}

		$template_name = ucwords(str_replace('_', ' ', $file));

		if ( ! empty($_POST))
		{
			if ( ! write_file($path, ee()->input->post('template_data')))
			{
				show_error(lang('error_opening_template'));
			}
			else
			{
				ee()->functions->clear_caching('all');

				$alert = ee('CP/Alert')->makeInline('template-form')
					->asSuccess()
					->withTitle(lang('update_template_success'))
					->addToBody(sprintf(lang('update_template_success_desc'), $template_name));

				if (ee()->input->post('submit') == 'finish')
				{
					$alert->defer();
					ee()->functions->redirect(ee('CP/URL')->make('design/forums'));
				}

				$alert->now();
			}
		}

		if ( ! is_really_writable($path))
		{
			ee('CP/Alert')->makeInline('message-warning')
				->asWarning()
				->cannotClose()
				->withTitle(lang('file_not_writable'))
				->addToBody(lang('file_writing_instructions'))
				->now();
		}

		$fp = fopen($path, 'r');
		$fstat = fstat($fp);
		fclose($fp);

		$vars = array(
			'form_url'      => ee('CP/URL')->make('design/forums/edit/' . $theme . '/' . $dir . '/' . $file),
			'edit_date'     => ee()->localize->human_time($fstat['mtime']),
			'template_data' => file_get_contents($path),
		);

		$this->loadCodeMirrorAssets();

		ee()->view->cp_page_title = sprintf(lang('edit_template'), $template_name);
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design')->compile() => lang('template_manager'),
			ee('CP/URL')->make('design/forums/')->compile() => sprintf(lang('breadcrumb_group'), lang('forums'))
		);

		ee()->cp->render('design/forums/edit', $vars);
	}
}

// EOF
