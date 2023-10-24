<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\EntryListing;

use Serializable;
use BadMethodCallException;
use InvalidArgumentException;
use ExpressionEngine\Service\View\View;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * CP Entry Listing Service
 */
class EntryListing
{
    /**
     * @var Filter $author_filter Author Filter object
     */
    public $author_filter;

    /**
     * @var Filter $channel_filter Channel Filter object
     */
    public $channel_filter;

    /**
     * @var Filter $category_filter Category Filter object
     */
    public $category_filter;

    /**
     * @var Filter $status_filter Status Filter object
     */
    public $status_filter;

    /**
     * @var int $site_id Current site ID
     */
    protected $site_id;

    /**
     * @var boolean $is_admin Whether or not a Super Admin is making this
     * request, skips $allowed_channels check
     */
    protected $is_admin;

    /**
     * @var array $allowed_channels IDs of channels this user is allowed to access
     */
    protected $allowed_channels;

    /**
     * @var array $extra_filters More filters to include
     */
    protected $extra_filters;

    /**
     * @var boolean $include_views_filter Whether to include the views selector on the entry manager
     */
    protected $include_views_filter;

    /**
     * @var boolean $include_column_filter Whether to include the column selector on the entry manager
     */
    protected $include_column_filter;

    /**
     * @var int $view ID of the Entry Listing view to use
     */
    protected $view_id;

    /**
     * @var int $now Timestamp of current time, used to filter entries by date
     */
    protected $now;

    /**
     * @var string $search_in What fields to include in the keyword search
     */
    protected $search_in;

    /**
     * @var string $search_value Search criteria to filter entries by
     */
    protected $search_value;

    /**
     * @var Query\Builder $entries Builder object for the channel entries
     */
    protected $entries;

    /**
     * @var FilterFactory $filters FilterFactory object
     */
    protected $filters;

    /**
     * @var Collection of channel models
     */
    protected $channels;

    /**
     * Constructor
     * @param int $site_id Current site ID
     * @param boolean $is_admin Whether or not a Super Admin is making this
     * request, skips $allowed_channels check
     * @param array $allowed_channels IDs of channels this user is allowed to access
     * @param int $now Timestamp of current time, used to filter entries by date
     * @param string $search_value Search criteria to filter entries by
     * @param string $search_in What fields to include in the keyword search
     */
    public function __construct($site_id, $is_admin, $allowed_channels = array(), $now = null, $search_value = null, $search_in = null, $include_author_filter = false, $view_id = null, $extra_filters = [])
    {
        $this->site_id = $site_id;
        $this->is_admin = $is_admin;
        $this->allowed_channels = $allowed_channels;
        $this->now = $now;
        $this->search_value = $search_value;
        $this->search_in = $search_in;
        $this->extra_filters = $extra_filters;
        $this->view_id = $view_id;
        if ($include_author_filter && !in_array('Author', $this->extra_filters)) {
            $this->extra_filters[] = 'Author';
        }

        $this->setupFilters();
        $this->setupEntries();
    }

    /**
     * Getter for channel entries Query\Builder object
     *
     * @return Query\Builder
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Getter for channel entries Query\Builder object
     *
     * @return FilterFactory
     */
    public function getFilters()
    {
        $count = $this->getEntryCount();

        // Add this last to get the right $count
        $this->filters->add('Perpage', $count, 'all_entries');

        return $this->filters;
    }

    public function getEntryCount()
    {
        static $count;

        if (is_null($count)) {
            $count = $this->getEntries()->count();
        }

        return $count;
    }

    public function getChannelModelFromFilter()
    {
        static $channel = null;

        if (
            is_null($channel)
            && $this->channel_filter
            && $this->channel_filter->value()
        ) {
            $channel = ee('Model')->get('Channel', $this->channel_filter->value())
                ->with('CategoryGroups')
                ->first();
        }

        return $channel;
    }

    /**
     * Sets up our various filters for showing an entry listing and
     * creates the FilterFactory object
     */
    private function setupFilters()
    {
        $this->channel_filter = $this->createChannelFilter();

        $channel = $this->getChannelModelFromFilter();

        $this->category_filter = $this->createCategoryFilter($channel);
        $this->status_filter = $this->createStatusFilter($channel);

        $this->filters = ee('CP/Filter')
            ->add($this->channel_filter)
            ->add($this->category_filter)
            ->add($this->status_filter)
            ->add('Date')
            ->add('EntryKeyword')
            ->add(
                'SearchIn',
                [
                    'titles' => 'titles',
                    'titles_and_content' => 'titles_and_content',
                ],
                $this->search_in
            );

        if (in_array('Author', $this->extra_filters)) {
            $this->author_filter = $this->createAuthorFilter($channel);
            $this->filters->add($this->author_filter);
        }

        if (in_array('Columns', $this->extra_filters)) {
            $this->filters->add('Columns', $this->createColumnFilter($channel), $channel, $this->view_id);
        }
    }

    /**
     * Add extra filters
     */
    public function addFilter(string $filter, $args = null)
    {
        $this->filters->add($filter, $args);
    }

    /**
     * Given various filters and permissions, sets up an entries Query\Builder
     * to be passed on to the caller to optionally add more filtering to
     */
    protected function setupEntries()
    {
        $entries = ee('Model')->get('ChannelEntry')
            ->with('Autosaves', 'Channel')
            ->fields('entry_id', 'title', 'Channel.channel_title', 'Channel.preview_url', 'Channel.status_group', 'comment_total', 'entry_date', 'status', 'sticky')
            ->filter('site_id', $this->site_id);

        if (in_array('Columns', $this->extra_filters)) {
            $columns = array_map(function ($identifier) {
                return EntryManager\ColumnFactory::getColumn($identifier);
            }, $this->filters->values()['columns']);
            foreach ($columns as $column) {
                if (!empty($column)) {
                    if (!empty($column->getEntryManagerColumnModels())) {
                        foreach ($column->getEntryManagerColumnModels() as $with) {
                            if (!empty($with)) {
                                $entries->with($with);
                            }
                        }
                    }
                    if (!empty($column->getEntryManagerColumnFields())) {
                        foreach ($column->getEntryManagerColumnFields() as $field) {
                            if (!empty($field)) {
                                $entries->fields($field);
                            }
                        }
                    } else {
                        $entries->fields($column->getTableColumnIdentifier());
                    }
                }
            }
        }

        // We need to filter by Channel first (if necessary) as that will
        // impact the entry count for the perpage filter
        $channel_id = $this->channel_filter->value();

        // If we have a selected channel filter, and we are not an admin, we
        // first need to ensure it is in the list of assigned channels. If it
        // is we will filter by that id. If not we throw an error.
        $channel = null;
        if ($channel_id) {
            if ($this->is_admin || in_array($channel_id, $this->allowed_channels)) {
                $entries->filter('channel_id', $channel_id);
                $channel = $this->getChannelModelFromFilter();

                $channel_name = $channel->channel_title;
            } else {
                show_error(lang('unauthorized_access'), 403);
            }
        } else {
            // If we have no selected channel filter, and we are not an admin, we
            // need to filter via WHERE IN
            if (! $this->is_admin) {
                if (empty($this->allowed_channels)) {
                    show_error(lang('no_channels'));
                }

                $entries->filter('channel_id', 'IN', $this->allowed_channels);
            }
        }

        if ($this->category_filter->value()) {
            $entries->with('Categories')
                ->filter('Categories.cat_id', $this->category_filter->value());
        }

        if ($this->status_filter->value()) {
            $entries->filter('status', $this->status_filter->value());
        }

        // if the user has no 'other entries' permissions at all
        // or if they are viewing a channel where they have "own" permissions only
        // then restrict to their own entries
        if (
            (!empty($channel_id) && !ee('Permission')->has('can_edit_other_entries_channel_id_' . $channel_id)) ||
            !ee('Permission')->hasAny('can_edit_other_entries')
        ) {
            $entries->filter('author_id', ee()->session->userdata('member_id'));
        } elseif (! empty($this->author_filter) && $this->author_filter->value()) {
            $entries->filter('author_id', $this->author_filter->value());
        }

        if (! empty($this->search_value)) {
            if (is_numeric($this->search_value) && strlen($this->search_value) < 3) {
                $entries->filter('entry_id', $this->search_value);
            } else {
                // setup content fields to use in search
                $content_fields = [];
                if ($this->search_in == 'titles_and_content' || $this->search_in == 'content') {
                    if (!empty($channel)) {
                        $custom_fields = $channel->getAllCustomFields();
                    } else {
                        $custom_fields = array();

                        foreach ($this->getChannels() as $channel) {
                            $custom_fields = array_merge($custom_fields, $channel->getAllCustomFields()->asArray());
                        }
                    }

                    foreach ($custom_fields as $cf) {
                        $content_fields[] = 'field_id_' . $cf->getId();
                    }
                }

                $search_fields = [];

                switch ($this->search_in) {
                    case 'titles_and_content':
                        $search_fields = array_merge(['title', 'url_title', 'entry_id'], $content_fields);

                        break;
                    case 'content':
                        $search_fields = $content_fields;

                        break;
                    case 'titles':
                        $search_fields = ['title', 'url_title', 'entry_id'];

                        break;
                }

                $entries->search(array_unique($search_fields), $this->search_value);
            }
        }

        $filter_values = $this->filters->values();

        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $entries->filter('entry_date', '>=', $filter_values['filter_by_date'][0]);
                $entries->filter('entry_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $entries->filter('entry_date', '>=', $this->now - $filter_values['filter_by_date']);
            }
        }

        $this->entries = $entries;
    }

    /**
     * Creates an author filter
     */
    private function createAuthorFilter($channel_id = null)
    {
        $db = ee('db')->distinct()
            ->select('t.author_id, m.screen_name, m.username')
            ->from('channel_titles t')
            ->join('members m', 'm.member_id = t.author_id', 'LEFT')
            ->order_by('screen_name', 'asc');

        if ($channel_id) {
            $db->where('channel_id', $channel_id->channel_id);
        }

        $authors_query = $db->get();

        $author_filter_options = [];
        foreach ($authors_query->result() as $row) {
            $author_filter_options[$row->author_id] = (!empty($row->screen_name)) ? $row->screen_name : $row->username;
        }

        // Put the current user at the top of the author list
        if (isset($author_filter_options[ee()->session->userdata['member_id']])) {
            $first[ee()->session->userdata['member_id']] = $author_filter_options[ee()->session->userdata['member_id']];
            unset($author_filter_options[ee()->session->userdata['member_id']]);
            $author_filter_options = $first + $author_filter_options;
        }

        $author_filter = ee('CP/Filter')->make('filter_by_author', 'filter_by_author', $author_filter_options);
        $author_filter->setPlaceholder(lang('filter_authors'));
        $author_filter->setLabel(lang('author'));
        $author_filter->useListFilter();

        return $author_filter;
    }

    /**
     * Creates a column filter
     */
    private function createColumnFilter($channel = null)
    {
        // Gather data for column selection tool
        return $this->getColumnChoices(EntryManager\ColumnFactory::getAvailableColumns($channel));
    }

    /**
     * Creates a channel filter
     */
    public function createChannelFilter()
    {
        $channels = $this->getChannels();
        $channel_filter_options = $channels->getDictionary('channel_id', 'channel_title');

        $channel_filter = ee('CP/Filter')->make('filter_by_channel', 'filter_by_channel', $channel_filter_options);
        $channel_filter->setPlaceholder(lang('filter_channels'));
        $channel_filter->setLabel(lang('channel'));
        $channel_filter->useListFilter(); // disables custom values

        return $channel_filter;
    }

    /**
     * Get the allowed channels
     */
    protected function getChannels()
    {
        if (empty($this->channels)) {
            $allowed_channel_ids = ($this->is_admin) ? null : $this->allowed_channels;
            $this->channels = ee('Model')->get('Channel', $allowed_channel_ids)
                ->fields('channel_id', 'channel_title')
                ->filter('site_id', ee()->config->item('site_id'))
                ->order('channel_title', 'asc')
                ->all();
        }

        return $this->channels;
    }

    /**
     * Creates a category filter
     */
    private function createCategoryFilter($channel = null)
    {
        $category_groups = ($channel)  ? $channel->CategoryGroups : [];

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
     * Creates a status filter
     */
    private function createStatusFilter($channel = null)
    {
        if ($channel) {
            $statuses = $channel->Statuses;
        } else {
            $statuses = ee('Model')->get('Status')->all(true);
        }

        $status_options = array();

        foreach ($statuses as $status) {
            $status_name = ($status->status == 'closed' or $status->status == 'open') ? lang($status->status) : $status->status;
            $status_options[$status->status] = $status_name;
        }

        $status = ee('CP/Filter')->make('filter_by_status', 'filter_by_status', $status_options);
        $status->setLabel(lang('status'));
        $status->disableCustomValue();

        return $status;
    }

    /**
     * Formats column data for use in selection UI
     *
     * @param array[Column]
     * @return array Identifier => Label
     */
    private function getColumnChoices($columns)
    {
        $column_choices = [];

        foreach ($columns as $column) {
            $identifier = $column->getTableColumnIdentifier();

            // This column is mandatory, not optional
            if ($identifier == 'checkbox') {
                continue;
            }

            $column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
        }

        return $column_choices;
    }
}
// EOF
