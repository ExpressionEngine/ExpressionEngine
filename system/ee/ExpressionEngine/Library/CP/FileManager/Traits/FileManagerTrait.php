<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Traits;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\FileManager\ColumnFactory;

trait FileManagerTrait
{
    
    protected function listingsPage($uploadLocation = null, $view_type = 'list', $filepickerMode = false)
    {
        $vars = array();
        ee()->load->library('file_field');
        ee()->file_field->dragAndDropField('new_file_manager', '', 'all', 'image');

        $upload_location_id = !empty($uploadLocation) ? $uploadLocation->getId() : null;

        $controller = $filepickerMode ? 'addons/settings/filepicker/modal' : (empty($uploadLocation) ? 'files' : 'files/directory/' . $upload_location_id);
        $base_url = ee('CP/URL')->make($controller);

        if (empty($uploadLocation)) {
            $model = 'File';
        } else {
            $model = (!bool_config_item('file_manager_compatibility_mode') && $uploadLocation->allow_subfolders) ? 'FileSystemEntity' : 'File';
        }
        if ($filepickerMode) {
            $field_upload_locations = ee('Request')->get('field_upload_locations') ?: (ee('Request')->get('directory') ?: 'all');
            $requested_directory = ee()->input->get('requested_directory') ?: ee()->input->get('directories');
            if (ee()->input->get('requested_directory') != $requested_directory) {
                //set the old variable name to make the filter work
                $_GET['requested_directory'] = $requested_directory;
            }
            $base_url->addQueryStringVariables([
                'field_upload_locations' => $field_upload_locations,
            ]);
            if (!empty($requested_directory)) {
                $base_url->addQueryStringVariables([
                    'requested_directory' => $requested_directory
                ]);
            }
        }

        $files = ee('Model')->get($model)
            // ->fields($model . '.*', 'UploadDestination.server_path', 'UploadDestination.url');
            ->with('UploadDestination');
        if (empty($upload_location_id)) {
            $files->filter('UploadDestination.module_id', 0)
                ->filter('site_id', ee()->config->item('site_id'));
        } else {
            $files->filter('upload_location_id', $upload_location_id);
        }

        //limit to subfolder, show breadcrumbs
        if (! empty($uploadLocation)) {
            $directory_id = (int) ee('Request')->get('directory_id');
            $files->filter('directory_id', $directory_id);
            if (! empty(ee('Request')->get('directory_id'))) {
                $breadcrumbs = [];
                do {
                    $directory = ee('Model')->get('Directory', $directory_id)->fields('file_id', 'directory_id', 'title')->first();
                    $directory_id = $directory->directory_id;
                    $params = ['directory_id' => $directory->file_id];
                    if ($filepickerMode) {
                        if (!empty($requested_directory)) {
                            $params['requested_directory'] = $requested_directory;
                        }
                        $params['field_upload_locations'] = $field_upload_locations;
                    }
                    $breadcrumbs[ee('CP/URL')->make($controller, $params)->compile()] = $directory->title;
                } while ($directory->directory_id != 0);
                $vars['breadcrumbs'] = array_merge([$base_url->compile() => $uploadLocation->name], array_reverse($breadcrumbs));
                $base_url->setQueryStringVariable('directory_id', (int) ee('Request')->get('directory_id'));
            }
        }

        $filters = ee('CP/Filter');
        $type_filter = $this->createTypeFilter($uploadLocation);
        $category_filter = $this->createCategoryFilter($uploadLocation);
        $author_filter = $this->createAuthorFilter($uploadLocation);
        if ($filepickerMode && $field_upload_locations == 'all') {
            $upload_location_filter = $this->createUploadLocationFilter($uploadLocation);
            $filters->add($upload_location_filter);
        }
        $filters->add($type_filter)
            ->add($category_filter)
            ->add('Date')
            ->add($author_filter)
            ->add('ViewType', ['list', 'thumb'], $view_type)
            ->add('EntryKeyword');
        if ($view_type != 'list') {
            $filters->add(
                'Sort',
                [
                    'column_title|asc' => '<i class="fas fa-sort-amount-up"></i> ' . lang('title'),
                    'column_title|desc' => '<i class="fas fa-sort-amount-down-alt"></i> ' . lang('title'),
                    'date_added|asc' => '<i class="fas fa-sort-amount-up"></i> ' . lang('date_added'),
                    'date_added|desc' => '<i class="fas fa-sort-amount-down-alt"></i> ' . lang('date_added'),
                ],
                'date_added|desc'
            );
        }
        $filters->add('FileManagerColumns', $this->createColumnFilter($uploadLocation), $uploadLocation, $view_type);

        $search_terms = ee()->input->get_post('filter_by_keyword');

        if ($search_terms) {
            $files->search(['title', 'file_name', 'mime_type'], $search_terms);
            $vars['search_terms'] = htmlentities($search_terms, ENT_QUOTES, 'UTF-8');
        }

        if (! empty($type_filter) && $type_filter->value()) {
            $files->filter('file_type', $type_filter->value());
        }

        if ($category_filter->value()) {
            $files->with('Categories')
                ->filter('Categories.cat_id', $category_filter->value());
        }

        if (! empty($author_filter) && $author_filter->value()) {
            $files->filter('uploaded_by_member_id', $author_filter->value());
        }

        $filter_values = $filters->values();
        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $files->filter('upload_date', '>=', $filter_values['filter_by_date'][0]);
                $files->filter('upload_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $files->filter('upload_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        $total_files = $files->count();
        $vars['total_files'] = $total_files;

        $filters->add('Perpage', $total_files, 'show_all_files');

        $filter_values = $filters->values();

        $perpage = $filter_values['perpage'];
        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $perpage;

        $base_url->addQueryStringVariables(
            array_filter(
                $filter_values,
                function ($key) {
                    return (!in_array($key, ['columns', 'sort']));
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        $vars['pagination'] = ee('CP/Pagination', $total_files)
            ->perPage($perpage)
            ->currentPage($page)
            ->render($base_url);

        $table = ee('CP/Table', array(
            'sort_col' => 'date_added',
            'sort_dir' => 'desc',
            'class' => $filepickerMode ? 'file-list tbl-fixed' : 'tbl-fixed'
        ));

        //which columns should we show
        //different view types need different order
        $selected_columns = $filter_values['columns'];
        if ($view_type == 'thumb') {
            if (! $filepickerMode) {
                array_unshift($selected_columns, 'checkbox');
            }
            array_unshift($selected_columns, 'thumbnail');
        } else {
            array_unshift($selected_columns, 'thumbnail');
            if (! $filepickerMode) {
                array_unshift($selected_columns, 'checkbox');
                $selected_columns[] = 'manage';
            }
        }

        $columns = [];
        foreach ($selected_columns as $column) {
            $columns[$column] = ColumnFactory::getColumn($column);
        }
        $columns = array_filter($columns);

        foreach ($columns as $column) {
            if (!empty($column)) {
                if (!empty($column->getEntryManagerColumnModels())) {
                    foreach ($column->getEntryManagerColumnModels() as $with) {
                        if (!empty($with)) {
                            $files->with($with);
                        }
                    }
                }
                if (!empty($column->getEntryManagerColumnFields())) {
                    foreach ($column->getEntryManagerColumnFields() as $field) {
                        if (!empty($field)) {
                            // $files->fields($field);
                        }
                    }
                } else {
                    // $files->fields($column->getTableColumnIdentifier());
                }
            }
        }

        $column_renderer = new EntryManager\ColumnRenderer($columns);
        $table_columns = $column_renderer->getTableColumnsConfig();
        $table->setColumns($table_columns);

        $directory_id = (int) ee('Request')->get('directory_id');
        $folderId = $directory_id ? $directory_id : $upload_location_id;

        $uploaderComponent = [
            'allowedDirectory' => $folderId ?: 'all',
            'contentType' => 'all',
            'file' => null,
            'showActionButtons' => false,
            'createNewDirectory' => false,
            'ignoreChild' => false,
            'addInput' => false,
            'imitationButton' => true
        ];

        $table->setNoResultsHTML(ee('View')->make('ee:_shared/file/upload-widget')->render(['component' => $uploaderComponent]), 'file-upload-widget');

        if (! empty($uploadLocation) && $uploadLocation->subfolders_on_top === true) {
            // $files->fields('model_type');
            $files->order('model_type', 'desc');
        }

        $sort_col = 'file_id';
        foreach ($table_columns as $table_column) {
            if ($table_column['label'] == $table->sort_col) {
                $sort_col = $table_column['name'];

                break;
            }
        }

        $sort_field = $columns[$sort_col]->getEntryManagerColumnSortField();

        $files = $files->order($sort_field, $table->sort_dir)
            ->limit($perpage)
            ->offset($offset)
            ->all();

        $data = array();
        $missing_files = false;

        $file_id = ee()->session->flashdata('file_id');
        $member = ee()->session->getMember();

        foreach ($files as $file) {
            if (! $file->memberHasAccess($member)) {
                continue;
            }

            $attrs = [
                'class' => '',
                'file_id' => $file->file_id,
                'title' => $file->title,
            ];

            if (! $file->exists()) {
                $attrs['class'] = 'missing';
                $missing_files = true;
            }

            if ($file_id && $file->file_id == $file_id) {
                $attrs['class'] .= ' selected';
            }

            if ($view_type != 'list') {
                if ($file->isDirectory()) {
                    $attrs['href'] = ee('CP/URL')->make('files/directory/' . $file->upload_location_id, ['directory_id' => $file->file_id]);
                } elseif (ee('Permission')->can('edit_files')) {
                    $attrs['href'] = ee('CP/URL')->make('files/file/view/' . $file->file_id);
                }
            }

            if ($filepickerMode) {
                if ($file->isFile()) {
                    $attrs['data-id'] = $file->file_id;
                    $attrs['data-url'] = ee('CP/URL')->make($controller, array('file' => $file->file_id));
                }
                if ($file->isDirectory()) {
                    $attrs['data-filter-url'] = ee('CP/URL')->make($controller, ['directory_id' => $file->file_id]);
                    $attrs['data-filter-url']->addQueryStringVariables([
                        'field_upload_locations' => $field_upload_locations,
                    ]);
                    if (!empty($requested_directory)) {
                        $attrs['data-filter-url']->addQueryStringVariables([
                            'requested_directory' => $requested_directory
                        ]);
                    }
                }
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column_renderer->getRenderedTableRowForEntry($file, $view_type, $filepickerMode)
            );
        }

        if ($missing_files) {
            ee('CP/Alert')->makeInline('missing-files')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('files_not_found'))
                ->addToBody(lang('files_not_found_desc'))
                ->now();
        }

        $table->setData($data);


        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $vars['filters'] = $filters->renderEntryFilters($base_url);
        $vars['filters_search'] = $filters->renderSearch($base_url, true);
        $vars['search_value'] = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');

        ee()->javascript->set_global([
            'file_view_url' => ee('CP/URL')->make('files/file/view/###')->compile(),
            'fileManager.fileDirectory.createUrl' => ee('CP/URL')->make('files/uploads/create')->compile(),
            'lang.remove_confirm' => lang('file') . ': <b>### ' . lang('files') . '</b>',
            'viewManager.saveDefaultUrl' => ee('CP/URL')->make('files/views/save-default', ['upload_id' => $upload_location_id, 'viewtype' => $view_type])->compile()
        ]);
        
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
                'cp/files/manager',
                'cp/publish/entry-list',
                'fields/file/file_field_drag_and_drop',
                'cp/files/copy-url'
            ),
        ));
        return $vars;
    }

    /**
     * Creates upload location filter
     */
    private function createUploadLocationFilter($uploadLocation = null)
    {
        $upload_destinations = ee('Model')->get('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('module_id', 0)
            ->order('name', 'asc');

        $options = array();
        foreach ($upload_destinations->all() as $destination) {
            if ($destination->memberHasAccess(ee()->session->getMember()) === false) {
                continue;
            }
            $options[$destination->getId()] = htmlspecialchars($destination->name, ENT_QUOTES, 'UTF-8');
        }

        $filter = ee('CP/Filter')->make('requested_directory', lang('upload_location'), $options);
        $filter->useListFilter();

        return $filter;
    }

    /**
     * Creates type filter
     */
    private function createTypeFilter($uploadLocation = null)
    {
        $typesQuery = ee('db')->select('file_type')->distinct()->from('files');
        if (! empty($uploadLocation)) {
            $typesQuery->where('upload_location_id', $uploadLocation->getId());
        }
        $types = $typesQuery->get();

        $options = array();
        foreach ($types->result() as $type) {
            $options[$type->file_type] = lang('type_' . $type->file_type);
        }

        $filter = ee('CP/Filter')->make('file_type', 'type', $options);
        $filter->useListFilter();

        return $filter;
    }

    /**
     * Creates an author filter
     */
    private function createAuthorFilter($uploadLocation = null)
    {
        $db = ee('db')->distinct()
            ->select('f.uploaded_by_member_id, m.screen_name')
            ->from('files f')
            ->join('members m', 'm.member_id = f.uploaded_by_member_id', 'LEFT')
            ->order_by('screen_name', 'asc');

        if ($uploadLocation) {
            $db->where('upload_location_id', $uploadLocation->id);
        }

        $authors_query = $db->get();

        $author_filter_options = [];
        foreach ($authors_query->result() as $row) {
            if (! empty($row->screen_name)) {
                $author_filter_options[$row->uploaded_by_member_id] = $row->screen_name;
            }
        }

        // Put the current user at the top of the author list
        if (isset($author_filter_options[ee()->session->userdata['member_id']])) {
            $first[ee()->session->userdata['member_id']] = $author_filter_options[ee()->session->userdata['member_id']];
            unset($author_filter_options[ee()->session->userdata['member_id']]);
            $author_filter_options = $first + $author_filter_options;
        }

        $author_filter = ee('CP/Filter')->make('filter_by_author', 'filter_by_author', $author_filter_options);
        $author_filter->setLabel(lang('added_by'));
        $author_filter->useListFilter();

        return $author_filter;
    }

    /**
     * Creates a category filter
     */
    private function createCategoryFilter($uploadLocation = null)
    {
        $cat_id = ($uploadLocation) ? explode('|', (string) $uploadLocation->cat_group) : null;

        $category_groups = ee('Model')->get('CategoryGroup', $cat_id)
            ->with('Categories')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('exclude_group', '!=', 1)
            ->all();

        $category_options = array();
        foreach ($category_groups as $group) {
            $sort_column = ($group->sort_order == 'a') ? 'cat_name' : 'cat_order';
            foreach ($group->Categories->sortBy($sort_column) as $category) {
                $category_options[$category->cat_id] = $category->cat_name;
            }
        }

        $categories = ee('CP/Filter')->make('filter_by_category', 'filter_by_category', $category_options);
        $categories->setPlaceholder(lang('filter_categories'));
        $categories->setLabel(lang('category'));
        $categories->useListFilter(); // disables custom values

        return $categories;
    }

    /**
     * Creates a column filter
     */
    private function createColumnFilter($uploadLocation = null)
    {
        $column_choices = [];

        $columns = ColumnFactory::getAvailableColumns($uploadLocation);

        foreach ($columns as $column) {
            $identifier = $column->getTableColumnIdentifier();

            // This column is mandatory, not optional
            if (in_array($identifier, ['checkbox', 'thumbnail', 'manage'])) {
                continue;
            }

            $column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
        }

        return $column_choices;
    }

    public function getUploadLocationsAndDirectoriesDropdownChoices()
    {
        $upload_destinations = [];
        $uploadLocationsAndDirectoriesDropdownChoices = [];
        if (ee('Permission')->can('upload_new_files')) {
            $upload_destinations = ee('Model')->get('UploadDestination')
                ->fields('id', 'name')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('module_id', 0)
                ->order('name', 'asc')
                ->all();

            if (! ee('Permission')->isSuperAdmin()) {
                $member = ee()->session->getMember();
                $upload_destinations = $upload_destinations->filter(function ($dir) use ($member) {
                    return $dir->memberHasAccess($member);
                });
            }

            foreach ($upload_destinations as $upload_pref) {
                $uploadLocationsAndDirectoriesDropdownChoices[$upload_pref->getId() . '.0'] = [
                    'label' => '<i class="fas fa-hdd"></i>' . $upload_pref->name,
                    'upload_location_id' => $upload_pref->id,
                    'directory_id' => 0,
                    'path' => '',
                    'children' => !bool_config_item('file_manager_compatibility_mode') ? $upload_pref->buildDirectoriesDropdown($upload_pref->getId(), true) : []
                ];
            }
        }
        return $uploadLocationsAndDirectoriesDropdownChoices;
    }
}
