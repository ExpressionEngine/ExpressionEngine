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

/**
 * Quick Bulk Edit Controller
 */
class QuickEdit extends AbstractQuickEdit {

	/**
	 * @var Array Fields we want available to Quick Edit
	 */
	protected $standard_default_fields = [
		'status',
		'expiration_date',
		'comment_expiration_date',
		'sticky',
		'allow_comments',
		'author_id'
	];

	/**
	 * Main Quick Edit form
	 *
	 * @param Array $data Associative array of field names to field data
	 * @param Result $errors Validation result for the given fields, or NULL
	 * @return String HTML markup of form
	 */
	public function index($data = NULL, $errors = NULL)
	{
		// GET for when the entry filter has changed, POST for when coming back
		// from validation error
		$entry_ids = ee()->input->get_post('entry_ids');
		$entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();

		if ( ! $entry_ids OR $entries->count() == 0)
		{
			return show_error(lang('unauthorized_access'), 403);
		}

		$entry = $this->getMockEntryForIntersectedChannels($entries->Channel);

		$fields = $this->getFieldsForEntry($entry, $this->standard_default_fields);
		$fields += $this->getCategoryFieldsForEntry($entry);

		$data = $data ?: $_GET;
		$entry->set($data);

		// Normalize category field names
		if (isset($data['categories']))
		{
			foreach ($data['categories'] as $cat_group => $cat_data)
			{
				$data['categories['.$cat_group.']'] = $cat_data;
			}
		}

		// Display the fields in the same order they were added
		$displayed_fields = [];
		foreach ($data as $field_name => $field)
		{
			if (isset($fields[$field_name]))
			{
				$displayed_fields[$field_name] = $fields[$field_name];
			}
		}

		$field_templates = array_diff_key($fields, $displayed_fields);

		$fluid_markup = $this->getFluidMarkupForFields($displayed_fields, $field_templates, $fields, $errors);

		$fieldset_class = 'fieldset-faux-fluid';
		if ($errors)
		{
			$fieldset_class .= ' fieldset-invalid';
		}

		$vars = [
			'base_url' => ee('CP/URL', 'publish/quick-edit/save'),
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
						'class' => $fieldset_class,
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

	/**
	 * Quick Edit submit handler
	 *
	 * @return String HTML markup of form if validation error, array if success
	 */
	public function save()
	{
		if ( ! ($entry_ids = ee('Request')->post('entry_ids')))
		{
			return show_error(lang('unauthorized_access'), 403);
		}

		$entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();
		$entries->set($_POST);

		foreach ($entries->validate() as $result)
		{
			if ($result->isNotValid())
			{
				return $this->index($_POST, $result);
			}
		}

		$entries->save();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody($entries->count() . ' entries have been updated.')
			->defer();

		return ['success'];
	}
}

// EOF
