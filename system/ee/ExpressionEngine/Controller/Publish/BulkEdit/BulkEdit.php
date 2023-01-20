<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish\BulkEdit;

use ExpressionEngine\Controller\Publish\BulkEdit\AbstractBulkEdit;

/**
 * Bulk Edit Controller
 */
class BulkEdit extends AbstractBulkEdit
{
    /**
     * @var Array Fields we want available to Bulk Edit
     */
    protected $standard_default_fields = [
        'status',
        'expiration_date',
        // Plus comment settings, author, and categories added dynamically below
    ];

    /**
     * Main Bulk Edit form
     *
     * @param Array $data Associative array of field names to field data
     * @param Result $errors Validation result for the given fields, or NULL
     * @return String HTML markup of form
     */
    public function index($data = null, $errors = null)
    {
        // GET for when the entry filter has changed, POST for when coming back
        // from validation error
        $entry_ids = ee()->input->get_post('entry_ids');
        $entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();

        if (! $entry_ids || $entries->count() == 0 || ! $this->hasPermissionToEditEntries($entries)) {
            return ee('CP/Alert')->makeInline()
                ->asIssue()
                ->cannotClose()
                ->withTitle(lang('unauthorized_access'))
                ->addToBody(lang('unauthorized_entry_desc'))
                ->render();
        }

        $entry = $this->getMockEntryForIntersectedChannels($entries->Channel);

        if (ee('Permission')->can('assign_post_authors')) {
            $this->standard_default_fields[] = 'author_id';
        }

        if ($entry->Channel->sticky_enabled) {
            $this->standard_default_fields[] = 'sticky';
        }

        if ($entry->Channel->comment_system_enabled) {
            $this->standard_default_fields[] = 'allow_comments';
            $this->standard_default_fields[] = 'comment_expiration_date';
        }

        $fields = $this->getFieldsForEntry($entry, $this->standard_default_fields);
        $fields += $this->getCategoryFieldsForEntry($entry);

        $data = $data ?: $_GET;
        $entry->set($data);

        // Normalize category field names
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $cat_group => $cat_data) {
                $data['categories[' . $cat_group . ']'] = $cat_data;
            }
        }

        // Display the fields in the same order they were added
        $displayed_fields = [];
        foreach ($data as $field_name => $field) {
            if (isset($fields[$field_name])) {
                $displayed_fields[$field_name] = $fields[$field_name];
            }
        }

        $field_templates = array_diff_key($fields, $displayed_fields);

        $fluid_markup = $this->getFluidMarkupForFields($displayed_fields, $field_templates, $fields, $errors);

        $fieldset_class = '';
        if ($errors) {
            $fieldset_class .= ' fieldset-invalid';
        }

        $vars = [
            'base_url' => ee('CP/URL', 'publish/bulk-edit/save'),
            'cp_page_title' => sprintf(lang('editing_entries'), $entries->count()),
            'save_btn_text' => 'btn_save_all_and_close',
            'save_btn_text_working' => 'btn_saving',
            'sections' => [[
                ee('CP/Alert')->makeInline()
                    ->asWarning()
                    ->cannotClose()
                    ->withTitle(lang('important'))
                    ->addToBody(lang('bulk_edit_notice'))
                    ->addToBody('<b>' . lang('bulk_edit_destructive') . '</b>')
                    ->render(),
                [
                    'title' => 'add_editable_fields',
                    'desc' => 'add_editable_fields_desc',
                    'attrs' => [
                        'class' => $fieldset_class,
                    ],
                    'fields' => [
                        'bulk-edit' => [
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
     * Bulk Edit submit handler
     *
     * @return String HTML markup of form if validation error, array if success
     */
    public function save()
    {
        $entry_ids = ee('Request')->post('entry_ids');
        $entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();

        if (! $entry_ids || $entries->count() == 0 || ! $this->hasPermissionToEditEntries($entries)) {
            show_error(lang('unauthorized_access'), 403);
        }

        // Categories need special handling to overwrite selections in specific
        // groups without affecting other groups
        if ($categories = ee('Request')->post('categories')) {
            $this->assignCategories($entries, $categories);
            unset($_POST['categories']);
        }

        $entries->set($_POST);

        foreach ($entries->validate() as $result) {
            if ($result->isNotValid()) {
                return $this->index($_POST, $result);
            }
        }

        $entries->edit_date = ee()->localize->now;
        $entries->save();

        ee('CP/Alert')->makeInline('entries-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(sprintf(lang('entries_updated'), $entries->count()))
            ->defer();

        return ['success'];
    }

    /**
     * We need a special method for category selection because if we just do
     * ->set($_POST), all category data will be overwritten instead of just the
     * individual groups selected. So we need to merge the existing category
     * selection with the selection made in the selected groups.
     *
     * @param Collection $entries Entries to assign categories to
     * @param Array $categories POST array of category selections
     * @return void
     */
    protected function assignCategories($entries, $categories)
    {
        $category_ids = [];
        $group_ids = [];
        foreach ($categories as $group => $checked) {
            $category_ids = array_merge($category_ids, $checked);
            $group_ids[] = str_replace('cat_group_id_', '', $group);
        }

        if (! empty($group_ids)) {
            foreach ($entries as $entry) {
                // Fiter out the groups marked to override
                $existing_categories = $entry->Categories->filter('group_id', 'NOT IN', $group_ids);
                $new_categories = array_merge($existing_categories->getIds(), $category_ids);

                if (! empty($new_categories)) {
                    $entry->Categories = ee('Model')->get('Category', $new_categories)->all();
                }
            }
        }
    }
}

// EOF
