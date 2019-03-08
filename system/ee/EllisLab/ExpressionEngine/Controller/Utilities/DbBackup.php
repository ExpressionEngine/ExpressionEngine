<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

/**
 * Database Backup Utility Controller
 */
class DbBackup extends Utilities {

	public function index()
	{
		$tables = ee('Database/Backup/Query')->getTables();

		$vars = [
			'cp_page_title' => lang('backup_utility'),
			'save_btn_text' => 'backup_database',
			'save_btn_text_working' => 'backing_up',
			'hide_top_buttons' => TRUE,
			'base_url' => '#',
			'sections' => [
				[
					[
						'title' => 'backup_tables',
						'desc' => sprintf(lang('table_count'), count($tables)),
						'fields' => [
							'progress' => [
								'type' => 'html',
								'content' => ee('View')
									->make('_shared/progress_bar')
									->render(['percent' => 0])
							]
						]
					]
				]
			]
		];

		// Create an error template for us to manipulate and display
		// in the event of AJAX errors
		$backup_ajax_fail_banner = ee('CP/Alert')->makeInline('backup-ajax-fail')
			->asIssue()
			->withTitle(lang('backup_error'))
			->addToBody('%body%');

		$table_counts = [];
		$total_size = 0;
		foreach ($tables as $table => $specs)
		{
			$table_counts[$table] = $specs['rows'];
			$total_size += $specs['size'];
		}

		ee()->cp->add_js_script('file', 'cp/db_backup');
		ee()->javascript->set_global([
			'db_backup' => [
				'endpoint'                => ee('CP/URL')->make('utilities/db-backup/do-backup')->compile(),
				'tables'                  => array_keys($tables),
				'table_counts'            => $table_counts,
				'total_rows'              => array_sum($table_counts),
				'backup_ajax_fail_banner' => $backup_ajax_fail_banner->render(),
				'base_url'                => ee('CP/URL')->make('utilities/db-backup')->compile(),
				'out_of_memory_lang'      => sprintf(lang('backup_out_of_memory'), ee()->cp->masked_url(DOC_URL.'general/system-configuration-overrides.html#db_backup_row_limit'))
			]
		]);

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * AJAX endpoint for backup requests
	 */
	public function doBackup()
	{
		if ( ! ee('Filesystem')->isWritable(PATH_CACHE))
		{
			return $this->sendError(lang('cache_path_not_writable'));
		}

		$table_name = ee('Request')->post('table_name');
		$offset = ee('Request')->post('offset');
		$file_path = ee('Request')->post('file_path');

		// Create a filename with the database name and timestamp
		if (empty($file_path))
		{
			$date = ee()->localize->format_date('%Y-%m-%d_%Hh%im%T');
			$file_path = PATH_CACHE.ee()->db->database.'_'.$date.'.sql';
		}
		else
		{
			// The path we get from POST will be truncated for security,
			// so we need to prepend it back
			$file_path = SYSPATH . $file_path;
		}

		// Some tables might be resource-intensive, do what we can
		@set_time_limit(0);
		@ini_set('memory_limit','512M');

		$backup = ee('Database/Backup', $file_path);

		// Beginning a new backup
		if (empty($table_name))
		{
			try
			{
				$backup->startFile();
				$backup->writeDropAndCreateStatements();
			}
			catch (Exception $e)
			{
				return $this->sendError($e->getMessage());
			}
		}

		try
		{
			$returned = $backup->writeTableInsertsConservatively($table_name, $offset);
		}
		catch (Exception $e)
		{
			return $this->sendError($e->getMessage());
		}

		// Hide the absolute server path to our backup so that it's not exposed
		// in the request and in the front-end success message
		$safe_file_path = str_replace(SYSPATH, '', $file_path);

		// There are more tables to do, let our JavaScript know that we need
		// another request to this method
		if ($returned !== FALSE)
		{
			return [
				'status'     => 'in_progress',
				'table_name' => $returned['table_name'],
				'offset'     => $returned['offset'],
				'file_path'  => $safe_file_path
			];
		}

		$backup->endFile();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->canClose()
			->withTitle(lang('backup_success'))
			->addToBody(sprintf(lang('backup_success_desc'), $safe_file_path))
			->defer();

		// All finished!
		return [
			'status'    => 'finished',
			'file_path' => $safe_file_path
		];
	}

	private function sendError($error)
	{
		return [
			'status'  => 'error',
			'message' => $error
		];
	}

}

// EOF
