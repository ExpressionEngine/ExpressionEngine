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

		$vars = $this->getDefaultForm();

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
					$vars = $this->getAliasForm($set, $result);

					ee('CP/Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('channel_set_duplicates_error'))
						->addToBody(lang('channel_set_duplicates_error_desc'))
						->now();
				}
				else
				{
					$fatal = $result->getErrors();
					$model_errors = $result->getModelErrors();

					// todo find a way to display those errors
				}
			}
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('channels')->compile() => lang('channels')
		);

		ee()->view->cp_page_title = lang('import_channel');
		ee()->cp->render('channels/sets/index', $vars);
	}

	public function importWithAliases()
	{
		if (empty($_POST))
		{
			ee()->functions->redirect(ee('CP/URL', 'channels/sets'));
		}

		$set = ee('ChannelSet')->importDir(PATH_CACHE.ee('Request')->post('set_path'));
		$set->setAliases($_POST);

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

		var_dump($result->getRecoverableErrors());
		die('failed again?!');
	}

	private function getDefaultForm()
	{
		$base_url = ee('CP/URL', 'channels/sets');

		return array(
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
	}

	private function getAliasForm($set, $result)
	{
		$vars = array();
		$vars['sections'] = array();

		$human_fields = array(
			'ee:Channel' => 'channel_title',
			'ee:ChannelFieldGroup' => 'group_name'
		);

		$short_names = array(
			'ee:Channel' => array('channel_name' => 'channel_title')
		);

		foreach ($result->getRecoverableErrors() as $section => $errors)
		{
			foreach ($errors as $error)
			{
				$fields = array();

				list($model, $field, $value, $rule) = $error;

				$model_name = $model->getName();

				$model_ident = $human_fields[$model_name];
				$model_id = $model->$model_ident;

				if (isset($short_names[$model_name]))
				{
					$human_field = $short_names[$model_name][$field];

					$fields[] = array(
						'title' => $human_field,
						'fields' => array(
							$model_name.'['.$value.']['.$human_field.']' => array(
								'type' => 'text',
								'value' => $model->$human_field,
								'required' => TRUE
							)
						)
					);
				}

				$fields[] = array(
					'title' => $field,
					'fields' => array(
						$model_name.'['.$value.']['.$field.']' => array(
							'type' => 'text',
							'value' => $value,
							'required' => TRUE
						)
					)
				);

				$vars['sections'][$section.' '.$model_id] = $fields;
			}
		}

		// Final view variables we need to render the form
		$vars += array(
			'base_url' => ee('CP/URL', 'channels/sets/importWithAliases'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'form_hidden' => array(
				'set_path' => str_replace(PATH_CACHE, '', $set->getPath())
			)
		);

		return $vars;
	}
}

// EOF
