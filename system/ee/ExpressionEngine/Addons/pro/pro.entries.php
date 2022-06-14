<?php

use ExpressionEngine\Addons\Pro\Service\Prolet;

use ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntry;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use Mexitek\PHPColors\Color;
use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Addons\Pro\Library\CP\EntryManager\ColumnFactory;

class Entries_pro extends Prolet\AbstractProlet
{
    protected $name = 'Entries';

    protected $size = 'large';

    protected $icon = 'fa-newspaper.svg';

    protected $buttons = [];

    private $permissions;

    public function checkPermissions()
    {
        $this->permissions = [
            'all' => [],
            'others' => [],
            'self' => [],
        ];

        $assigned_channel_ids = ee()->session->getMember()->getAssignedChannels()->pluck('channel_id');
        foreach ($assigned_channel_ids as $channel_id) {
            $this->permissions['others'][] = 'can_edit_other_entries_channel_id_' . $channel_id;
            $this->permissions['self'][] = 'can_edit_self_entries_channel_id_' . $channel_id;
        }

        $this->permissions['all'] = array_merge($this->permissions['others'], $this->permissions['self']);

        if (! ee('Permission')->hasAny($this->permissions['all'])) {
            return false;
        }

        return true;
    }

    public function index()
    {
        ee()->lang->loadfile('content');
        
        $vars = array();
        $vars['channels_exist'] = true;

        $base_url = ee('CP/URL')->make('pro/prolet/' . ee()->uri->segment(4), ['current_uri' => ee('Request')->get('current_uri')]);

        // Create the entry listing object which handles getting the entries
        $extra_filters = [];

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
        $selected_columns = ['entry_id', 'title', 'entry_date', 'author', 'status'];//$filter_values['columns'];
        $columns = [];
        foreach ($selected_columns as $column) {
            $columns[$column] = ColumnFactory::getColumn($column);
        }
        $columns = array_filter($columns);

        if (! ee('Permission')->hasAny($this->permissions['others'])) {
            $entries->filter('author_id', ee()->session->userdata('member_id'));
        }

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

        /*$show_new_button = $vars['channels_exist'];
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
        }*/
        $show_new_button = false;

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
        $entries->order($sort_field, $table->sort_dir)
            ->limit($filter_values['perpage'])
            ->offset($offset);

        $data = array();

        $entry_id = ee()->session->flashdata('entry_id');

        $statuses = ee('Model')->get('Status')->all()->indexBy('status');

        foreach ($entries->all() as $entry) {
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
                'columns' => $column_renderer->getRenderedTableRowForEntry($entry)
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

        if ($table->sort_dir != 'desc' && $table->sort_col != 'column_entry_date') {
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

            foreach ($entries->all()->pluck('channel_id') as $entry_channel_id) {
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

        $rawOutput = ee()->cp->render('publish/partials/edit_list_table', $vars, true);

        return $rawOutput;

        
    }

}
