<?php

namespace EllisLab\ExpressionEngine\Controller\Channels;

use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;

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
 * ExpressionEngine CP Channel Set Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Sets extends AbstractChannelsController {

	/**
	 * General Settings
	 */
	public function index()
	{
		$this->generateSidebar('channel');
		$base_url = ee('CP/URL', 'channels/sets');

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => $base_url,
			'errors' => NULL,
			'has_file_input' => TRUE,
			'save_btn_text' => 'btn_import',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'file_upload',
						'fields' => array(
							'set_file' => array('type' => 'file'),
							'required' => TRUE
						)
					),
				)
			)
		);

		if ( ! empty($_FILES))
		{
			$set_file = ee('Request')->file('set_file');

			$validator = ee('Validation')->make(array(
				'set_file' => 'required',
			));

			$result = $validator->validate(array('set_file' => $set_file['name']));

			if ($result->isNotValid())
			{
				$errors = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('channel_set_upload_error'))
					->addToBody(lang('channel_set_upload_error_desc'))
					->now();

				$vars['errors'] = $errors;
			}
			else
			{
				$set = ee('ChannelSet')->importUpload($set_file);
				ee()->functions->redirect(
					ee('CP/URL')->make(
						'channels/sets/doImport',
						array('set_path' => str_replace(PATH_CACHE, '', $set->getPath())))
				);
			}
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels')
		);

		ee()->view->cp_page_title = lang('import_channel');
		ee()->cp->render('channels/sets/index', $vars);
	}

	/**
	 * Export a channel as a channel set
	 */
	public function export($channel_id = NULL)
	{
		$channel = NULL;

		if (isset($channel_id))
		{
			$channel = ee('Model')
				->get('Channel', $channel_id)
				->filter('site_id', ee()->config->item('site_id'))->first();
		}

		if ( ! isset($channel))
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('channel_set_not_exported'))
				->addToBody(lang('channel_set_not_exported_desc'))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels'));
		}

		$file = ee('ChannelSet')->export(array($channel));

		$data = file_get_contents($file);

		ee()->load->helper('download');
		force_download('ChannelSet.zip', $data);
		exit;
	}

	/**
	 * Import a channel set
	 */
	public function doImport()
	{
		$set_path = ee('Request')->get('set_path');

		// no path or unacceptable path? abort!
		if ( ! $set_path || strpos($set_path, '..') !== FALSE)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('channel_set_upload_error'))
				->addToBody(lang('channel_set_upload_error_desc'))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/sets'));
		}

		// load up the set
		$set = ee('ChannelSet')->importDir(PATH_CACHE.ltrim($set_path, '/'));

		// posted values? grab 'em
		if (isset($_POST))
		{
			$set->setAliases($_POST);
		}

		$result = $set->validate();

		if ($result->isValid())
		{
			$set->save();

			$alert = ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('channel_set_imported'))
				->addToBody(lang('channel_set_imported_desc'))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels'));
		}

		if ($result->isRecoverable())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('channel_set_duplicates_error'))
				->addToBody(lang('channel_set_duplicates_error_desc'))
				->now();
		}
		else
		{
			$errors = $result->getErrors();
			$model_errors = $result->getModelErrors();
			foreach (array('Channel Field', 'Category') as $type)
			{
				if (isset($model_errors[$type]))
				{
					foreach ($model_errors[$type][0][2] as $error)
					{
						$errors[] = $error->getLanguageKey();
					}
				}
			}

			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('channel_set_upload_error'))
				->addToBody($errors)
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'channels/sets'));
		}

		$this->generateSidebar('channel');

		$vars = $this->createAliasForm($set, $result);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels')
		);

		ee()->view->cp_page_title = lang('import_channel');
		ee()->cp->render('channels/sets/index', $vars);
	}

	private function createAliasForm($set, $result)
	{
		ee()->lang->loadfile('filemanager');
		$vars = array();
		$vars['sections'] = array();
		$vars['errors'] = new \EllisLab\ExpressionEngine\Service\Validation\Result;

		$hidden = array();
		foreach ($_POST as $model => $ident)
		{
			foreach ($ident as $field => $properties)
			{
				foreach ($properties as $property => $value)
				{
					// Not sure what was submitted here.
					if (is_array($value))
					{
						continue;
					}

					$key = "{$model}[{$field}][{$property}]";
					$hidden[$key] = $value;
				}
			}
		}

		foreach ($result->getRecoverableErrors() as $section => $errors)
		{
			foreach ($errors as $error)
			{
				$fields = array();

				list($model, $field, $ident, $rule) = $error;

				$model_name = $model->getName();
				$long_field = $result->getLongFieldIfShortened($model, $field);

				// Show the current model title in the section header
				$title_field = $result->getTitleFieldFor($model);

				// Frequently the error is on the short_name, but in those cases
				// you really want to edit the long name as well, so we'll show it.
				if (isset($long_field))
				{
					$key = $model_name.'['.$ident.']['.$long_field.']';
					$vars['sections'][$section.': '.$model->$title_field][] = array(
						'title' => $long_field,
						'fields' => array(
							$key => array(
								'type' => 'text',
								'value' => $model->$long_field,
								// 'required' => TRUE
							)
						)
					);
					unset($hidden[$key]);
				}

				$key = $model_name.'['.$ident.']['.$field.']';
				$vars['sections'][$section.': '.$model->$title_field][] = array(
					'title' => $field,
					'fields' => array(
						$model_name.'['.$ident.']['.$field.']' => array(
							'type' => 'text',
							'value' => $model->$field,
							'required' => TRUE
						)
					)
				);

				unset($hidden[$key]);

				foreach ($rule as $r)
				{
					$vars['errors']->addFailed($model_name.'['.$ident.']['.$field.']', $r);
				}
			}
		}

		if ( ! empty($hidden))
		{
			$vars['form_hidden'] = $hidden;
		}

		// Final view variables we need to render the form
		$vars += array(
			'base_url' => ee('CP/URL')->make('channels/sets/doImport', array('set_path' => str_replace(PATH_CACHE, '', $set->getPath()))),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
		);

		return $vars;
	}
}

// EOF
