<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Category;

/**
 * Category Factory Service
 */
class Factory {

	/**
	 * Adds the JS scripts and variables the category UX needs.
	 */
	public function addCategoryJS()
	{
		ee()->cp->add_js_script(array(
			'plugin' => array(
				'ee_url_title'
			),
			'file' => array(
				'cp/categories'
			)
		));

		ee()->javascript->set_global([
			'categories.createUrl' => ee('CP/URL')->make('categories/create/###')->compile(),
			'categories.editUrl'   => ee('CP/URL')->make('categories/edit/###')->compile(),
			'categories.removeUrl' => ee('CP/URL')->make('categories/remove-single/###')->compile(),
			'categories.fieldUrl' => ee('CP/URL')->make('categories/category-group-publish-field/###')->compile()
		]);
	}

	/**
	 * Adds modals for the category add/edit form and category removal confirmation
	 */
	public function addCategoryModals()
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
			'form_url'	=> ee('CP/URL')->make('categories/remove'),
			'hidden'	=> array(
				'bulk_action'	=> 'remove',
				'categories[]'	=> ''
			)
		));
		ee('CP/Modal')->addModal('modal-checkboxes-confirm-remove', $cat_remove_modal);
	}

}

// EOF
