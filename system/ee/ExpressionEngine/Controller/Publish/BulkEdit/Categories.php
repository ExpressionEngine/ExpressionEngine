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
 * Add Categories Bulk Edit Controller
 */
class Categories extends AbstractBulkEdit
{
    /**
     * Add Categories form
     *
     * @return String HTML markup of form
     */
    public function add()
    {
        return $this->getForm('add');
    }

    /**
     * Remove Categories form
     *
     * @return String HTML markup of form
     */
    public function remove()
    {
        return $this->getForm('remove');
    }

    /**
     * Add/Remove Categories form
     *
     * @return String HTML markup of form
     */
    protected function getForm($intent)
    {
        $entry_ids = ee('Request')->get('entry_ids');
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
        $fields = $this->getCategoryFieldsForEntry($entry);

        if (empty($fields)) {
            return ee('View')->make('ee:_shared/form/no_results')->render(['text' => lang('no_cat_groups_in_common')]);
        }

        $entry->set($_GET);

        $field_definitions = [
            ee('CP/Alert')->makeInline()
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('important'))
                ->addToBody(lang('bulk_edit_' . $intent . '_categories_notice'))
                ->render()
        ];

        foreach ($fields as $field) {
            $field_definitions[] = [
                'title' => $field->getItem('field_label'),
                'desc' => $field->getItem('field_instructions'),
                'fields' => [
                    $field->getName() => [
                        'type' => 'html',
                        'content' => $field->getForm()
                    ]
                ]
            ];
        }

        $vars = [
            'base_url' => ee('CP/URL', 'publish/bulk-edit/categories/save-' . $intent),
            'cp_page_title' => sprintf(lang($intent . '_categories_entries'), $entries->count()),
            'save_btn_text' => 'btn_save_all_and_close',
            'save_btn_text_working' => 'btn_saving',
            'sections' => [$field_definitions]
        ];

        return ee('View')->make('ee:_shared/form')->render($vars);
    }

    /**
     * Add Categories submit handler
     *
     * @return Array
     */
    public function saveAdd()
    {
        return $this->save('add');
    }

    /**
     * Remove Categories submit handler
     *
     * @return Array
     */
    public function saveRemove()
    {
        return $this->save('remove');
    }

    /**
     * Add/Remove Categories submit handler
     *
     * @param String $intent Either 'add' or 'remove'
     * @return Array
     */
    protected function save($intent)
    {
        if (! ($entry_ids = ee('Request')->post('entry_ids'))) {
            show_error(lang('unauthorized_access'), 403);
        }

        $category_ids = [];
        foreach (ee('Request')->post('categories') as $group => $checked) {
            $category_ids = array_merge($category_ids, $checked);
        }

        if (! empty($category_ids)) {
            $entries = ee('Model')->get('ChannelEntry', $entry_ids)->all();

            if (! $this->hasPermissionToEditEntries($entries)) {
                show_error(lang('unauthorized_access'), 403);
            }

            $entries->edit_date = ee()->localize->now;

            $channel = $this->getIntersectedChannel($entries->Channel);

            // Double-check passed categories belong to groups assigned to the channel
            $categories = ee('Model')->get('Category', $category_ids)
                ->filter('group_id', 'IN', $channel->CategoryGroups->getIds())
                ->all();

            if ($categories->count()) {
                switch ($intent) {
                    case 'add':
                        $this->addCategories($entries, $categories);

                        break;
                    case 'remove':
                        $this->removeCategories($entries, $categories);

                        break;
                }

                ee('CP/Alert')->makeInline('entries-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(sprintf(lang('entries_updated'), $entries->count()))
                    ->defer();
            }
        }

        return ['success'];
    }

    /**
     * Because we can't just do $entries->Categories->add($categories) :(
     *
     * @param Collection $entries Entries to assign categories to
     * @param Collection $categories Categories to assign to entries
     * @return void
     */
    protected function addCategories($entries, $categories)
    {
        foreach ($entries as $entry) {
            // Don't attempt to assign the already-assigned
            $categories_to_add = $categories->filter(function ($category) use ($entry) {
                return ! in_array($category->getId(), $entry->Categories->getIds());
            });
            $entry->Categories->getAssociation()->add($categories_to_add);
            $entry->save();
        }
    }

    /**
     * Because we can't just do $entries->Categories->remove($categories) :(
     *
     * @param Collection $entries Entries to remove categories from
     * @param Collection $categories Categories to remove from entries
     * @return void
     */
    protected function removeCategories($entries, $categories)
    {
        foreach ($entries as $entry) {
            $entry->Categories->remove($categories);
            $entry->save();
        }
    }
}

// EOF
