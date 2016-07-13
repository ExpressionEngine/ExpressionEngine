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

			if ($cat_groups->count() == 1)
			{
				$metadata['field_label'] = lang('categories');
			}

			$field_id = 'categories[cat_group_id_'.$cat_group->getId().']';
			$facade = new FieldFacade($field_id, $metadata);
			$facade->setName($field_id);

			$field = new FieldDisplay($facade);

			$field = array(
				'title' => $field->getLabel(),
				'desc' => $field->getInstructions(),
				'fields' => array(
					$facade->getId() => array(
						'type' => 'html',
						'content' => $field->getForm()
					)
				)
			);
			
			if (ee()->cp->allowed_group('can_create_categories'))
			{
				$field['example'] = '<a class="btn action submit m-link" rel="modal-checkboxes-edit" data-group-id="'.$cat_group->getId().'" href="#">'.lang('btn_add_category').'</a>';
			}
			
			$sections[0][] = $field;
		}

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		ee('Category')->addCategoryJS();
		ee('Category')->addCategoryModals();

		return $html;
	}
}

// EOF
