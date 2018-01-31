<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Publish\QuickEdit;

use EllisLab\ExpressionEngine\Controller\Publish\QuickEdit\AbstractQuickEdit;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Quick Bulk Edit Controller
 */
class QuickEdit extends AbstractQuickEdit {

	protected $standard_default_fields = [
		'status',
		'expiration_date',
		'comment_expiration_date',
		'sticky',
		'allow_comments',
		'author_id'
		// Plus common category groups added dynamically below
	];

	public function index()
	{
		if ( ! ($entry_ids = ee('Request')->get('entryIds')))
		{
			return 'nope';
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();

		// TODO: Filter entries based on permissions, just in case
		$fields = $this->getFieldsForEntries($entries);

		$filters = ee('View')->make('fluid_field:filters')->render(['fields' => $fields]);

		$displayed_fields = '';
		foreach ($_GET as $field_name => $value)
		{
			if (isset($fields[$field_name]))
			{
				$field = $fields[$field_name];
				$field->setData($value);
				$displayed_fields .= ee('View')->make('fluid_field:field')->render([
					'field' => $field,
					'field_name' => $field_name,
					'filters' => '',
					'errors' => NULL,
				]);
			}
		}

		$field_templates = '';
		foreach ($fields as $field_name => $field)
		{
			$field_templates .= ee('View')->make('fluid_field:field')->render([
				'field' => $field,
				'field_name' => $field_name,
				'filters' => '',
				'errors' => NULL,
			]);
		}

		$fluid_markup = ee('View')->make('fluid_field:publish')->render([
			'fields'          => $displayed_fields,
			'field_templates' => $field_templates,
			'filters'         => $filters,
		]);

		$fluid_markup .= form_hidden('entries_to_edit', $entry_ids);

		$vars = [
			'base_url' => '',
			'cp_page_title' => 'Editing ' . $entries->count() . ' entries',
			'save_btn_text' => 'Save All & Close',
			'save_btn_text_working' => 'btn_saving',
			'sections' => [[
				ee('CP/Alert')->makeInline()
					->asWarning()
					->cannotClose()
					->withTitle('Important!')
					->addToBody('Any fields submitted will overwrite that field\'s stored content for all selected entries.')
					->addToBody('<b>This is a destructive and irreversible action.</b>')
					->render(),
				[
					'title' => 'Add editable fields',
					'desc' => 'Chosen fields will be added below, and will be editable for <b>all</b> selected entries.',
					'attrs' => [
						'class' => 'fieldset-faux-fluid',
					],
					'fields' => [
						'quick-edit' => [
							'type' => 'html',
							'content' => $fluid_markup
						]
					]
				]
			]]
		];

		return ee('View')->make('ee:_shared/form')->render($vars);
	}
}

// EOF
