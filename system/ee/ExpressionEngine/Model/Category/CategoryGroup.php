<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Category;

use ExpressionEngine\Service\Model\Model\Collection;
use ExpressionEngine\Model\Content\StructureModel;

/**
 * Category Group Model
 */
class CategoryGroup extends StructureModel
{
    protected static $_primary_key = 'group_id';
    protected static $_gateway_names = array('CategoryGroupGateway');

    protected static $_hook_id = 'category_group';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'CategoryGroupSettings' => array(
            'type' => 'hasMany'
        ),
        'CategoryFields' => array(
            'type' => 'hasMany',
            'model' => 'CategoryField'
        ),
        'Categories' => array(
            'type' => 'hasMany',
            'model' => 'Category'
        ),
        'Channels' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channel_category_groups',
                'left' => 'group_id',
                'right' => 'channel_id'
            )
        ),
        'UploadDestinations' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'UploadDestination',
            'pivot' => array(
                'table' => 'upload_prefs_category_groups',
                'left' => 'group_id',
                'right' => 'upload_location_id'
            )
        )
    );

    protected static $_validation_rules = array(
        'group_name' => 'required|unique[site_id]',
        'sort_order' => 'enum[a,c]',
        'field_html_formatting' => 'enum[all,safe,none]',
        'exclude_group' => 'enum[0,1,2]'
    );

    protected static $_events = [
        'afterDelete'
    ];

    // Properties
    protected $group_id;
    protected $site_id;
    protected $group_name;
    protected $sort_order;
    protected $exclude_group;
    protected $field_html_formatting;
    protected $can_edit_categories;
    protected $can_delete_categories;

    public function onAfterDelete()
    {
        // Disassociate this group from channels
        foreach ($this->Channels as $channel) {
            $groups = explode('|', (string) $channel->cat_group);

            if (($key = array_search($this->getId(), $groups)) !== false) {
                unset($groups[$key]);
                $channel->cat_group = implode('|', $groups);
                $channel->save();
            }
        }
    }

    // Clean XSS from group name when saved
    protected function set__group_name($groupName)
    {
        $this->setRawProperty('group_name', ee('Security/XSS')->clean($groupName));
    }

    public function getAllCustomFields()
    {
        return $this->getCategoryFields();
    }

    /**
     * Convenience method to fix inflection
     */
    public function createCategoryField($data)
    {
        return $this->createCategoryFields($data);
    }

    public function getContentType()
    {
        return 'category';
    }

    /**
     * Returns the category tree for this category group
     *
     * @param \EE_Tree $tree An EE_Tree library object
     * @return Object<ImmutableTree> Traversable tree object
     */
    public function getCategoryTree(\EE_Tree $tree)
    {
        $sort_column = ($this->sort_order == 'a') ? 'cat_name' : 'cat_order';

        return $tree->from_list(
            $this->getCategories()->sortBy($sort_column),
            array('id' => 'cat_id')
        );
    }

    /**
     * Generates the metadata needed to hand off to the old channel field API
     * in order to instantiate a field.
     *
     * @return array An associative array.
     */
    public function getFieldMetadata()
    {
        $can_edit = explode('|', rtrim((string) $this->can_edit_categories, '|'));
        $editable = false;

        if (
            ee('Permission')->isSuperAdmin() ||
            (ee('Permission')->can('edit_categories') && ee('Permission')->hasAnyRole($can_edit))
        ) {
            $editable = true;
        }

        $can_delete = explode('|', rtrim((string) $this->can_delete_categories, '|'));
        $deletable = false;

        if (
            ee('Permission')->isSuperAdmin() ||
            (ee('Permission')->can('delete_categories') && ee('Permission')->hasAnyRole($can_delete))
        ) {
            $deletable = true;
        }

        $no_results = [
            'text' => sprintf(lang('no_found'), lang('categories'))
        ];

        if (! INSTALLER && ee('Permission')->can('create_categories')) {
            $no_results['link_text'] = 'add_new';
            $no_results['link_href'] = ee('CP/URL')->make('categories/create/' . $this->getId());
        }

        $metadata = array(
            'field_id' => 'categories',
            'group_id' => $this->getId(),
            'field_label' => $this->group_name,
            'field_required' => 'n',
            'field_show_fmt' => 'n',
            'field_instructions' => lang('categories_desc'),
            'field_text_direction' => 'ltr',
            'field_type' => 'checkboxes',
            'force_react' => true,
            'field_list_items' => '',
            'field_maxl' => 100,
            'editable' => $editable,
            'editing' => false,
            'deletable' => $deletable,
            'nested' => true,
            'nestableReorder' => true,
            'populateCallback' => array($this, 'populateCategories'),
            'manage_toggle_label' => lang('manage_categories'),
            'add_btn_label' => REQ == 'CP' && ee('Permission')->can('create_categories')
                ? lang('add_category')
                : null,
            'content_item_label' => lang('category'),
            'reorder_ajax_url' => ! INSTALLER
                ? ee('CP/URL')->make('categories/reorder/' . $this->getId())->compile()
                : '',
            'auto_select_parents' => ee()->config->item('auto_assign_cat_parents') == 'y',
            'no_results' => $no_results,
            'split_for_two' => true
        );

        return $metadata;
    }

    /**
     * Sets a field's data based on which categories are selected
     */
    public function populateCategories($field)
    {
        $categories = $this->getModelFacade()->get('Category')
            ->with(
                ['Children as C0' =>
                    ['Children as C1' =>
                        ['Children as C2' => 'Children as C3']
                    ]
                ]
            )
            ->with('CategoryGroup')
            ->filter('CategoryGroup.group_id', $field->getItem('group_id'))
            ->filter('Category.parent_id', 0)
            ->all();

        // Sorting alphabetically or custom?
        $sort_column = 'cat_order';
        if ($categories->count() && $categories->first()->CategoryGroup->sort_order == 'a') {
            $sort_column = 'cat_name';
        }

        $category_list = $this->buildCategoryList($categories->sortBy($sort_column), $sort_column);
        $field->setItem('field_list_items', $category_list);

        $object = $field->getItem('categorized_object');

        // isset() and empty() don't work here on $object->Channel because it hasn't been dynamically fetched yet,
        // is_object() apparently works differently and lets it dynamically load it before evaluating
        $has_default = ($object->getName() == 'ee:ChannelEntry' && is_object($object->Channel)) ? true : false;

        // New Channel Entries might have a default category selected, but File
        // entities should not have categories pre-selected for new entries
        if (! $object->isNew() or ($object->getName() == 'ee:ChannelEntry' && $has_default)) {
            $set_categories = $object->Categories->filter('group_id', $field->getItem('group_id'))->pluck('cat_id');
            $field->setData(implode('|', $set_categories));
        }
    }

    /**
     * Builds a tree of categories in the current category group for use in a
     * SelectField form
     *
     * @param array Category tree
     */
    public function buildCategoryOptionsTree()
    {
        $sort_column = 'cat_order';
        if ($this->sort_order == 'a') {
            $sort_column = 'cat_name';
        }

        return $this->buildCategoryList(
            $this->Categories->filter('parent_id', 0),
            $sort_column
        );
    }

    /**
     * Turn the categories collection into a nested array of ids => names
     *
     * @param   Collection  $categories Top level categories to construct tree out of
     * @param   string      $sort_column    Either 'cat_name' or 'cat_order', sorts the
     *                      categories by the given column
     */
    protected function buildCategoryList($categories, $sort_column)
    {
        $list = array();

        foreach ($categories as $category) {
            $children = $category->Children->sortBy($sort_column);

            if (count($children)) {
                $list[$category->cat_id] = array(
                    'name' => $category->cat_name,
                    'children' => $this->buildCategoryList($children, $sort_column)
                );

                continue;
            }

            $list[$category->cat_id] = $category->cat_name;
        }

        return $list;
    }
}

// EOF
