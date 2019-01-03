<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;


/**
 * Design\Forums Controller
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

	public function index($theme = NULL)
	{
		$base_path = FALSE;
		$this->load->helper('directory');
		$files = array();
		$theme_dirs = ee('Theme')->listUserThemes('forum');

		if ($theme_dirs && empty($theme))
		{
			$theme = array_keys($theme_dirs)[0];
		}

		if ($theme)
		{
			$base_path = ee('Theme')->getUserPath('forum/' . ee()->security->sanitize_filename($theme));

			// Check if custom templates are in themes folder instead of system folder
			if (strpos($base_path, PATH_THIRD_THEMES) !== FALSE)
			{
				ee()->load->library('logger');
				$version_url = ee()->cp->masked_url(DOC_URL.'installation/version_notes_4.2.2.html');

				ee()->logger->developer('As of 4.2.2, forum templates should be in folder: system/user/templates/_themes/forum/.  <a href="'.$version_url.'">Please see 4.2.2 version notes.</a>', TRUE);
			}
		}

		$base_url = ee('CP/URL')->make('design/forums/index/' . $theme);

		$table = ee('CP/Table', array('autosort' => TRUE, 'subheadings' => TRUE));
		$table->setNoResultsText(sprintf(lang('no_user_templates_found'), DOC_URL.'add-ons/forum/forum_themes.html'));
		$table->setColumns(
			array(
				'template',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
			)
		);

		$vars['themes'] = '';
		$data = array();

		if ($base_path && is_dir($base_path))
		{
			$dir_map = directory_map($base_path) ?: array();

			foreach ($dir_map as $dir => $files)
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

			if ( ! empty($files))
			{
				foreach ($theme_dirs as $dir => $name)
				{
					$themes[ee('CP/URL')->make('design/forums/index/' . $dir)->compile()] = $name;
				}

				$vars['themes'] = form_dropdown('theme', $themes, ee('CP/URL')->make('design/forums/index/' . $theme));
			}
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$this->generateSidebar('forums');
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('forum_templates');

		ee()->javascript->change("select[name=\'theme\']", 'window.location.href = $(this).val()');

		ee()->cp->render('design/forums/index', $vars);
	}

	public function edit($theme, $dir, $file)
	{
		$path = ee('Theme')->getUserPath('forum/'
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
					ee()->functions->redirect(ee('CP/URL')->make('design/forumsindex/' . $theme));
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
