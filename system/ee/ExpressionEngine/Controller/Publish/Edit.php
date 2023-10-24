<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish;

use ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntry;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use Mexitek\PHPColors\Color;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * Publish/Edit Controller
 */
class Edit extends AbstractPublishController
{
    protected $permissions;

    public function __construct()
    {
        parent::__construct();

        $this->permissions = [
            'all' => [],
            'others' => [],
            'self' => [],
        ];

        foreach ($this->assigned_channel_ids as $channel_id) {
            $this->permissions['others'][] = 'can_edit_other_entries_channel_id_' . $channel_id;
            $this->permissions['self'][] = 'can_edit_self_entries_channel_id_' . $channel_id;
        }

        $this->permissions['all'] = array_merge($this->permissions['others'], $this->permissions['self']);

        if (! ee('Permission')->hasAny($this->permissions['all'])) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Displays all available entries
     *
     * @return void
     */
    public function index()
    {
        if (ee()->input->post('bulk_action') == 'remove') {
            $this->remove(ee()->input->post('selection'));
        }

        $vars = array();
        $vars['channels_exist'] = true;

        $base_url = ee('CP/URL')->make('publish/edit');

        // Create the entry listing object which handles getting the entries
        $extra_filters = [];
        if (ee('Permission')->hasAny($this->permissions['others'])) {
            $extra_filters[] = 'Author';
        }
        $extra_filters[] = 'Columns';

        $entry_listing = ee(
            'CP/EntryListing',
            ee()->input->get_post('filter_by_keyword'),
            ee()->input->get_post('search_in') ?: 'titles_and_content',
            false,
            null, //ee()->input->get_post('view') ?: '',//view is not used atm
            $extra_filters
        );

        $entries = $entry_listing->getEntries();
        $filters = $entry_listing->getFilters();
        $filter_values = $filters->values();
        $channel_id = $entry_listing->channel_filter->value();

        //which columns should we show
        $selected_columns = $filter_values['columns'];
        $selected_columns[] = 'checkbox';
        // array_unshift($selected_columns, 'checkbox');

        $columns = [];
        foreach ($selected_columns as $column) {
            $columns[$column] = EntryManager\ColumnFactory::getColumn($column);
        }
        $columns = array_filter($columns);

        $count = $entry_listing->getEntryCount();

        // if no entries check to see if we have any channels
        if (empty($count)) {
            // cast to bool
            $vars['channels_exist'] = (bool) ee('Model')->get('Channel')->filter('site_id', ee()->config->item('site_id'))->count();
        }

        $vars['filters'] = $filters->renderEntryFilters($base_url);
        $vars['filters_search'] = $filters->renderSearch($base_url);
        $vars['search_value'] = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');

        $base_url->addQueryStringVariables(
            array_filter(
                $filter_values,
                function ($key) {
                    return ($key != 'columns');
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        // Create the table that displays the entries
        $table = ee('CP/Table', array(
            'sort_dir' => 'desc',
            'sort_col' => 'column_entry_date',
        ));

        $column_renderer = new EntryManager\ColumnRenderer($columns);
        $table_columns = $column_renderer->getTableColumnsConfig();
        $table->setColumns($table_columns);

        if ($vars['channels_exist']) {
            $table->setNoResultsText(lang('no_entries_exist'));
        } else {
            $table->setNoResultsText(
                sprintf(lang('no_found'), lang('channels'))
            . ' <a href="' . ee('CP/URL', 'channels/create') . '">' . lang('add_new') . '</a>'
            );
        }

        $show_new_button = $vars['channels_exist'];
        if ($channel_id) {
            $channel = $entry_listing->getChannelModelFromFilter();

            // Have we reached the max entries limit for this channel?
            if ($channel->maxEntriesLimitReached()) {
                // Don't show New button
                $show_new_button = false;

                $desc_key = ($channel->max_entries == 1)
                    ? 'entry_limit_reached_one_desc' : 'entry_limit_reached_desc';
                ee('CP/Alert')->makeInline()
                    ->asWarning()
                    ->withTitle(lang('entry_limit_reached'))
                    ->addToBody(sprintf(lang($desc_key), $channel->max_entries))
                    ->now();
            }
        }

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $sort_col = 'entry_id';
        foreach ($table_columns as $table_column) {
            if ($table_column['label'] == $table->sort_col) {
                $sort_col = $table_column['name'];

                break;
            }
        }
        $sort_field = $columns[$sort_col]->getEntryManagerColumnSortField();
        $entries->order($sort_field, $table->sort_dir);
        if ($sort_col != 'entry_id') {
            $entries->order('entry_id', $table->sort_dir);
        }
        $entries->limit($filter_values['perpage'])
            ->offset($offset);
        $entries = $entries->all();

        $data = array();

        $entry_id = ee()->session->flashdata('entry_id');

        $statuses = ee('Model')->get('Status')->all(true)->indexBy('status');

        $addQueryString = ee('CP/URL')->getCurrentUrl()->qs;
        if ($page != 1) {
            $addQueryString['page'] = $page;
        }
        if (isset($addQueryString['page']) && isset($addQueryString['sort_col']) && $addQueryString['sort_col'] == 'edit_date') {
            unset($addQueryString['page']);
        }

        foreach ($entries as $entry) {
            // wW had a delete cascade issue that could leave entries orphaned and
            // resulted in errors, so we'll sneakily use this controller to clean up
            // for now.
            if (is_null($entry->Channel)) {
                $entry->delete();

                continue;
            }

            $attrs = array();

            if ($entry_id && $entry->entry_id == $entry_id) {
                $attrs = array('class' => 'selected');
            }

            if ($entry->Autosaves->count()) {
                $attrs = array('class' => 'auto-saved');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column_renderer->getRenderedTableRowForEntry($entry, 'list', false, $addQueryString)
            );
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $menu = ee()->menu->generate_menu();
        $choices = [];
        foreach ($menu['channels']['create'] as $text => $link) {
            $choices[$link->compile()] = $text;
        }

        $vars['head'] = array(
            'title' => lang('entry_manager'),
            'action_button' => (count($choices) || ee('Permission')->can('create_entries_channel_id_' . $channel_id)) && $show_new_button ? [
                'text' => $channel_id ? sprintf(lang('btn_create_new_entry_in_channel'), $channel->channel_title) : lang('new'),
                'href' => ee('CP/URL', 'publish/create/' . $channel_id)->compile(),
                'filter_placeholder' => lang('filter_channels'),
                'choices' => $channel_id ? null : $choices
            ] : null,
            'class' => 'entries'
        );

        if (! ($table->sort_dir == 'desc' && $table->sort_col == 'column_entry_date')) {
            $base_url->addQueryStringVariables(
                array(
                    'sort_dir' => $table->sort_dir,
                    'sort_col' => $table->sort_col
                )
            );
        }

        $vars['pagination'] = ee('CP/Pagination', $count)
            ->perPage($filter_values['perpage'])
            ->currentPage($page)
            ->render($base_url);

        ee()->javascript->set_global([
            'lang.remove_confirm' => lang('entry') . ': <b>### ' . lang('entries') . '</b>',

            'publishEdit.sequenceEditFormUrl' => ee('CP/URL')->make('publish/edit/entry/###')->compile(),
            'publishEdit.bulkEditFormUrl' => ee('CP/URL')->make('publish/bulk-edit')->compile(),
            'publishEdit.addCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/add')->compile(),
            'publishEdit.removeCategoriesFormUrl' => ee('CP/URL')->make('publish/bulk-edit/categories/remove')->compile(),
            'bulkEdit.lang' => [
                'selectedEntries' => lang('selected_entries'),
                'filterSelectedEntries' => lang('filter_selected_entries'),
                'noEntriesFound' => sprintf(lang('no_found'), lang('entries')),
                'showing' => lang('showing'),
                'of' => lang('of'),
                'clearAll' => lang('clear_all'),
                'removeFromSelection' => lang('remove_from_selection'),
            ],
            'viewManager.saveDefaultUrl' => ee('CP/URL')->make('publish/views/save-default', ['channel_id' => $channel_id])->compile()
        ]);

        ee()->cp->add_js_script(array(
            'file' => array(
                'common',
                'cp/confirm_remove',
                'cp/publish/entry-list',
                'components/bulk_edit_entries',
                'cp/publish/bulk-edit'
            ),
        ));

        ee()->view->cp_page_title = lang('edit_channel_entries');
        if (! empty($filter_values['filter_by_keyword'])) {
            $vars['cp_heading'] = sprintf(lang('search_results_heading'), $count, $filter_values['filter_by_keyword']);
        } else {
            $vars['cp_heading'] = sprintf(
                lang('all_channel_entries'),
                (isset($channel->channel_title)) ? $channel->channel_title : ''
            );
        }

        if ($channel_id) {
            $vars['can_edit'] = ee('Permission')->hasAny(
                'can_edit_self_entries_channel_id_' . $channel_id,
                'can_edit_other_entries_channel_id_' . $channel_id
            );

            $vars['can_delete'] = ee('Permission')->hasAny(
                'can_delete_all_entries_channel_id_' . $channel_id,
                'can_delete_self_entries_channel_id_' . $channel_id
            );
        } else {
            $edit_perms = [];
            $del_perms = [];

            foreach ($entries->pluck('channel_id') as $entry_channel_id) {
                $edit_perms[] = 'can_edit_self_entries_channel_id_' . $entry_channel_id;
                $edit_perms[] = 'can_edit_other_entries_channel_id_' . $entry_channel_id;

                $del_perms[] = 'can_delete_all_entries_channel_id_' . $entry_channel_id;
                $del_perms[] = 'can_delete_self_entries_channel_id_' . $entry_channel_id;
            }

            $vars['can_edit'] = (empty($edit_perms)) ? false : ee('Permission')->hasAny($edit_perms);
            $vars['can_delete'] = (empty($del_perms)) ? false : ee('Permission')->hasAny($del_perms);
        }

        ee()->cp->add_js_script([
            'plugin' => ['ui.touch.punch', 'ee_interact.event'],
            'file' => ['fields/relationship/mutable_relationship', 'fields/relationship/relationship'],
            'ui' => 'sortable'
        ]);

        ee()->view->cp_breadcrumbs = array(
            '' => lang('entries')
        );

        if (AJAX_REQUEST) {
            return array(
                'html' => ee('View')->make('publish/partials/edit_list_table')->render($vars),
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('publish/views/save-default', ['channel_id' => $channel_id])->compile()
            );
        }

        ee()->cp->render('publish/edit/index', $vars);
    }

    public function entry($id = null, $autosave_id = null)
    {
        if (! $id) {
            show_404();
        }

        $base_url = ee('CP/URL')->getCurrentUrl();

        // Sequence editing?
        $sequence_editing = false;
        if ($entry_ids = ee('Request')->get('entry_ids')) {
            $sequence_editing = true;

            $index = array_search($id, $entry_ids) + 1;
            $next_entry_id = isset($entry_ids[$index]) ? $entry_ids[$index] : null;
            $base_url->setQueryStringVariable('next_entry_id', $next_entry_id);
        }

        // If an entry or channel on a different site is requested, try
        // to switch sites and reload the publish form
        $site_id = (int) ee()->input->get_post('site_id');
        if ($site_id != 0 && $site_id != ee()->config->item('site_id') && empty($_POST)) {
            ee()->cp->switch_site($site_id, $base_url);
        }

        $entry = ee('Model')->get('ChannelEntry', $id)
            ->with('Channel', 'Status', 'Autosaves')
            ->all()
            ->first();

        if (! $entry) {
            show_error(lang('no_entries_matching_that_criteria'));
        }

        //if the entry-to-be-saved belongs to different site, switch to that site
        if ($entry->site_id != ee()->config->item('site_id')) {
            if (ee('Request')->isPost()) {
                $orig_site_id = ee()->config->item('site_id');
                ee()->cp->switch_site($entry->site_id, $base_url);
            } else {
                //but we only auto-switch if we're saving
                show_error(lang('no_entries_on_this_site'));
            }
        }

        if (
            ($entry->author_id != ee()->session->userdata('member_id') && ! ee('Permission')->can('edit_other_entries_channel_id_' . $entry->channel_id)) ||
            ($entry->author_id == ee()->session->userdata('member_id') && ! ee('Permission')->can('edit_self_entries_channel_id_' . $entry->channel_id))
        ) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! in_array($entry->channel_id, $this->assigned_channel_ids)) {
            show_error(lang('unauthorized_access'), 403);
        }

        // -------------------------------------------
        // 'publish_form_entry_data' hook.
        //  - Modify entry's data
        //  - Added: 1.4.1
        if (ee()->extensions->active_hook('publish_form_entry_data') === true) {
            $result = ee()->extensions->call('publish_form_entry_data', $entry->getValues());
            $entry->set($result);
        }
        // -------------------------------------------

        $entry_title = htmlentities($entry->title, ENT_QUOTES, 'UTF-8');
        ee()->view->cp_page_title = sprintf(lang('edit_entry_with_title'), $entry_title);

        $form_attributes = array(
            'class' => 'ajax-validate',
        );

        $livePreviewReady = $this->createLivePreviewModal($entry);

        $vars = array(
            'head' => [
                'title' => lang('edit_entry'),
                'class' => 'entries'
            ],
            'form_url' => $base_url,
            'form_attributes' => $form_attributes,
            'form_title' => lang('edit_entry'),
            'errors' => new \ExpressionEngine\Service\Validation\Result(),
            'autosaves' => $this->getAutosavesTable($entry, $autosave_id),
            'buttons' => $this->getPublishFormButtons($entry, $livePreviewReady),
            'in_modal_context' => $sequence_editing
        );

        if (ee()->input->get('hide_closer') === 'y' && ee()->input->get('modal_form') === 'y') {
            if (ee()->input->get('return') != '') {
                $vars['form_hidden'] = [
                    'return' => urldecode(ee()->input->get('return', true))
                ];
            }
            $vars['hide_sidebar'] = true;
            $vars['hide_topbar'] = true;
            $vars['pro_class'] = 'pro-frontend-modal';
        }

        if ($sequence_editing) {
            $vars['modal_title'] = sprintf('(%d of %d) %s', $index, count($entry_ids), $entry_title);
            $vars['buttons'] = [[
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_next',
                'text' => $index == count($entry_ids) ? 'save_and_close' : 'save_and_next',
                'working' => 'btn_saving'
            ]];
        }

        $version_id = ee()->input->get('version');

        if ($entry->Channel->enable_versioning) {
            $vars['revisions'] = $this->getRevisionsTable($entry, $version_id);
        }

        if ($version_id) {
            $version = $entry->Versions->filter('version_id', $version_id)->first();
            if (!is_null($version)) {
                $version_data = $version->version_data;
                $vars['version'] = $version->toArray();
                $vars['version']['number'] = $entry->Versions->filter('version_date', '<=', $version->version_date)->count();
                $entry->set($version_data);

                ee('CP/Alert')->makeInline('viewing-revision')
                    ->asWarning()
                    ->withTitle(lang('viewing_revision'))
                    ->addToBody(lang('viewing_revision_desc'))
                    ->now();
            }
        }

        if (ee('Request')->get('load_autosave') == 'y') {
            $autosaveExists = ee('Model')->get('ChannelEntryAutosave')
                ->fields('entry_id')
                ->filter('original_entry_id', $entry->entry_id)
                ->first();
            if ($autosaveExists) {
                $autosave_id = $autosaveExists->entry_id;
            }
        }

        if ($autosave_id) {
            $autosaved = ee('Model')->get('ChannelEntryAutosave', $autosave_id)
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();

            if ($autosaved) {
                $entry->set($autosaved->entry_data);
            }
        }

        $channel_layout = ee('Model')->get('ChannelLayout')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('channel_id', $entry->channel_id)
            ->with('PrimaryRoles')
            ->filter('PrimaryRoles.role_id', ee()->session->userdata('role_id'))
            ->first();

        if (empty($channel_layout)) {
            $channel_layout = ee('Model')->get('ChannelLayout')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('channel_id', $entry->channel_id)
                ->with('PrimaryRoles')
                ->filter('PrimaryRoles.role_id', 'IN', ee()->session->getMember()->getAllRoles()->pluck('role_id'))
                ->all()
                ->first();
        }

        $vars['layout'] = $entry->getDisplay($channel_layout);

        $result = $this->validateEntry($entry, $vars['layout']);

        if ($result instanceof ValidationResult) {
            $vars['errors'] = $result;

            if ($result->isValid()) {
                return $this->saveEntryAndRedirect($entry);
            }
        }

        $vars['entry'] = $entry;

        $this->setGlobalJs($entry, true);

        ee()->cp->add_js_script(array(
            'plugin' => array(
                'ee_url_title',
                'ee_filebrowser',
                'ee_fileuploader',
            ),
            'file' => array(
                'cp/publish/publish',
                'cp/publish/entry-list',
            )
        ));

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('publish/edit')->compile() => lang('entries'),
            ee('CP/URL')->make('publish/edit', ['filter_by_channel' => $entry->channel_id])->compile() => $entry->Channel->channel_title,
            '' => lang('edit_entry')
        );

        //switch the site back if needed
        if (ee('Request')->isPost() && isset($orig_site_id)) {
            ee()->cp->switch_site($orig_site_id, $base_url);
        }

        if (ee('Request')->get('modal_form') == 'y') {
            $vars['layout']->setIsInModalContext(true);
            ee()->output->enable_profiler(false);

            if (ee('Request')->get('hide_closer') == 'y') {
                ee()->cp->add_js_script(array(
                    'pro_file' => array(
                        'iframe-listener'
                    )
                ));
            }

            return ee()->view->render('publish/modal-entry', $vars);
        }

        ee()->cp->render('publish/entry', $vars);
    }

    private function remove($entry_ids)
    {
        $perms = [];

        foreach ($this->assigned_channel_ids as $channel_id) {
            $perms[] = 'can_delete_all_entries_channel_id_' . $channel_id;
            $perms[] = 'can_delete_self_entries_channel_id_' . $channel_id;
        }

        if (! ee('Permission')->hasAny($perms)) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($entry_ids)) {
            $entry_ids = array($entry_ids);
        }

        $entry_names = array_merge($this->removeAllEntries($entry_ids), $this->removeSelfEntries($entry_ids));

        if (!empty($entry_names)) {
            ee('CP/Alert')->makeInline('entries-form')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody(lang('entries_deleted_desc'))
                ->addToBody($entry_names)
                ->defer();
        }

        if (count($entry_names) != count($entry_ids)) {
            $entries_not_deleted = ee('Model')->get('ChannelEntry', $entry_ids)->all()->pluck('title');
            ee('CP/Alert')->makeInline('entries-form-error')
                ->asWarning()
                ->withTitle(lang('warning'))
                ->addToBody(lang('entries_not_deleted_desc'))
                ->addToBody($entries_not_deleted)
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('publish/edit', ee()->cp->get_url_state()));
    }

    private function removeEntries($entry_ids, $self_only = false)
    {
        $entries = ee('Model')->get('ChannelEntry', $entry_ids)
            ->filter('site_id', ee()->config->item('site_id'));

        if (!$this->is_admin) {
            if (empty($this->assigned_channel_ids)) {
                show_error(lang('no_channels'));
            }

            if ($self_only == true) {
                $entries->filter('author_id', ee()->session->userdata('member_id'));
            }

            $channel_ids = [];
            $permission = ($self_only == true) ? 'delete_self_entries' : 'delete_all_entries';

            foreach ($this->assigned_channel_ids as $channel_id) {
                if (ee('Permission')->can($permission . '_channel_id_' . $channel_id)) {
                    $channel_ids[] = $channel_id;
                }
            }

            // No permission to delete self entries
            if (empty($channel_ids)) {
                return [];
            }

            $entries->filter('channel_id', 'IN', $channel_ids);
        }

        $all_entries = $entries->all();
        $entry_names = [];
        if (!empty($all_entries)) {
            $entry_names = $all_entries->pluck('title');
            $entry_ids = $all_entries->pluck('entry_id');

            // Remove pages URIs
            $site_id = ee()->config->item('site_id');
            $site_pages = ee()->config->item('site_pages');

            if ($site_pages !== false && count($site_pages[$site_id]) > 0) {
                foreach ($all_entries as $entry) {
                    unset($site_pages[$site_id]['uris'][$entry->entry_id]);
                    unset($site_pages[$site_id]['templates'][$entry->entry_id]);

                    ee()->config->set_item('site_pages', $site_pages);

                    $entry->Site->site_pages = $site_pages;
                    $entry->Site->save();
                }
            }

            $entries->delete();
        }

        return $entry_names;
    }

    private function removeAllEntries($entry_ids)
    {
        return $this->removeEntries($entry_ids);
    }

    private function removeSelfEntries($entry_ids)
    {
        return $this->removeEntries($entry_ids, true);
    }
}

// EOF
