<?php

namespace EllisLab\ExpressionEngine\Service\File;

use EllisLab\ExpressionEngine\Model\File\File as FileModel;
use EllisLab\ExpressionEngine\Model\Content\FieldFacade;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;

class Upload {

	/**
	 * Creates and returns the HTML to add or edit a file.
	 *
	 * @param obj $file A File Model object
	 * @param array $errors An array of errors
	 * @return string HTML
	 */
	public function getFileDataForm(FileModel $file, $errors)
	{
		$html = '';

		$sections = array(
			array(
				array(
					'title' => 'file',
					'desc' => 'file_desc',
					'fields' => array(
						'file' => array(
							'type' => 'file',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'title',
					'fields' => array(
						'title' => array(
							'type' => 'text',
							'value' => $file->title
						)
					)
				),
				array(
					'title' => 'description',
					'fields' => array(
						'description' => array(
							'type' => 'textarea',
							'value' => $file->description
						)
					)
				),
				array(
					'title' => 'credit',
					'fields' => array(
						'credit' => array(
							'type' => 'text',
							'value' => $file->credit
						)
					)
				),
				array(
					'title' => 'location',
					'fields' => array(
						'location' => array(
							'type' => 'text',
							'value' => $file->location
						)
					)
				),
			)
		);

		// Remove the file field when we are editing
		if ( ! $file->isNew())
		{
			unset($sections[0][0]);
		}

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	/**
	 * Creates and returns the HTML to add or edit a file's categories.
	 *
	 * @param obj $file A File Model object
	 * @param array $errors An array of errors
	 * @return string HTML
	 */
	public function getCategoryForm(FileModel $file, $errors)
	{
		ee()->lang->loadfile('content');
		$html = '';

		$sections = array(
			array(
			)
		);

		$cat_groups = ee('Model')->get('CategoryGroup')
			->filter('group_id', 'IN', explode('|', $file->UploadDestination->cat_group))
			->all();

		if (count($cat_groups) == 0)
		{
			$url = ee('CP/URL', 'files/uploads/edit/' . $file->UploadDestination->getId())->compile();
			return ee('CP/Alert')->makeInline('empty-category-tab')
				->asWarning()
				->cannotClose()
				->withTitle(lang('no_categories_assigned'))
				->addToBody(sprintf(lang('no_categories_assigned_file_desc'), $url))
				->render();
		}

		foreach ($cat_groups as $cat_group)
		{
			$metadata = $cat_group->getFieldMetadata();
			$metadata['categorized_object'] = $file;
			$metadata['field_instructions'] = lang('file_categories_desc');
			$metadata['editable'] = FALSE;

			if ($cat_groups->count() == 1)
			{
				$metadata['field_label'] = lang('categories');
			}

			$field_id = 'categories[cat_group_id_'.$cat_group->getId().']';
			$facade = new FieldFacade($field_id, $metadata);
			$facade->setName($field_id);

			$field = new FieldDisplay($facade);

			$sections[0][] = array(
				'title' => $field->getLabel(),
				'desc' => $field->getInstructions(),
				'fields' => array(
					$facade->getId() => array(
						'type' => 'html',
						'content' => $field->getForm()
					)
				)
			);
		}

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		$this->addCategoryJS();
		$this->addCategoryModals();

		return $html;
	}

	/**
	 * Adds the JS scripts and variables the category UX needs.
	 */
	protected function addCategoryJS()
	{
		ee()->cp->add_js_script('plugin', 'nestable');
		ee()->cp->add_js_script('file', 'cp/categories');

		ee()->javascript->set_global(array(
			'category.add.URL'             => ee('CP/URL')->make('channels/cat/createCat/###')->compile(),
			'category.edit.URL'            => ee('CP/URL')->make('channels/cat/editCat/###')->compile(),
			'category.reorder.URL'         => ee('CP/URL')->make('channels/cat/cat-reorder/###')->compile(),
			'category.auto_assign_parents' => ee()->config->item('auto_assign_cat_parents'),
		));
	}

	/**
	 * Adds modals for the category add/edit form and category removal confirmation
	 */
	protected function addCategoryModals()
	{
		// Don't bother adding modals to DOM if they don't have permission
		if ( ! ee()->cp->allowed_group_any(
			'can_create_categories',
			'can_edit_categories',
			'can_delete_categories'
		))
		{
			return;
		}

		$cat_form_modal = ee('View')->make('ee:_shared/modal')->render(array(
			'name'		=> 'modal-checkboxes-edit',
			'contents'	=> '')
		);
		ee('CP/Modal')->addModal('modal-checkboxes-edit', $cat_form_modal);

		$cat_remove_modal = ee('View')->make('ee:_shared/modal_confirm_remove')->render(array(
			'name'		=> 'modal-checkboxes-confirm-remove',
			'form_url'	=> ee('CP/URL')->make('channels/cat/removeCat'),
			'hidden'	=> array(
				'bulk_action'	=> 'remove',
				'categories[]'	=> ''
			)
		));
		ee('CP/Modal')->addModal('modal-checkboxes-confirm-remove', $cat_remove_modal);
	}
}

// EOF
