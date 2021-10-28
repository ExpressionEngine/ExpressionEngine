<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP;

/**
 * CP Table
 */
class Table
{
    const COL_TEXT = 1;
    const COL_CHECKBOX = 2;
    const COL_STATUS = 3;
    const COL_TOOLBAR = 4;
    const COL_ID = 5;
    const COL_SMALL = 6;
    const COL_INFO = 7;

    public $config = array();
    protected $columns = array();
    protected $data = array();
    protected $action_buttons = array();
    protected $action_content;
    protected $localize;

    /**
     * Config can have these keys:
     *
     * 'sort_col' - Name of the column currently sorting
     * 'sort_dir' - Direction of the sort, 'asc' or 'desc'
     * 'search' - Search text to search table with
     * 'wrap' - Whether or not to wrap the table in a div that allows overflow scrolling
     * 'autosort' - Handle sorting automatically, this expects the entire dataset to be
     * 		set via setData(); if only a partial dataset is set, handle sorting manually
     * 'autosearch' - Handle searching automatically, this expects the entire dataset to be
     * 		set via setData(); if only a partial dataset is set, handle sorting manually
     * 'lang_cols' - Run column names though lang() on the front end
     * 'limit' - Row limit for the table, automatic pagination is based on this
     * 'page' - Current page
     * 'total_rows' - Total rows in the dataset regardless of limit or page number
     * 'sortable' - Whether or not to allow the columns to sort the table, this can
     * 		also be controlled on a column-by-column basis
     * 'grid_input' - Whether or not this table is being used as a Grid input UI
     * 'reorder' - Whether or not to allow this table to have its rows reordered
     * 'reorder_header' - Whether or not to show a reorder handle in the table's
     * 		header, i.e. if an entire table was reorderable with other tables
     * 'checkbox_header' - For checkbox columns, will also show a checkbox in
     * 		the header when there is no data, i.e. when a table represents an
     * 		entity that can have actions applied to it (edge case setting)
     * 'show_add_button' - whether to show button to add new rows
     *
     * @param	array 	$config	See above for options
     */
    public function __construct($config = array())
    {
        $defaults = array(
            'wrap' => true,
            'sort_col' => null,
            'sort_col_qs_var' => 'sort_col',
            'sort_dir' => 'asc',
            'sort_dir_qs_var' => 'sort_dir',
            'limit' => 25,
            'page' => 1,
            'total_rows' => 0,
            'search' => null,
            'sortable' => true,
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => true,
            'subheadings' => false,
            'grid_input' => false,
            'reorder' => false,
            'reorder_header' => false,
            'checkbox_header' => false,
            'show_add_button' => true,
            'class' => '',
            'attrs' => array(),
            'no_results' => array(
                'text' => 'no_rows_returned',
                'action_text' => '',
                'action_link' => ''
            )
        );

        // By default, tables with subheadings should have no limit,
        // but can be overridden in passed config
        if (isset($config['subheadings']) && $config['subheadings'] === true) {
            $defaults['limit'] = 0;
        }

        $this->config = array_merge($defaults, $config);
    }

    /**
     * Allow read-only access to certain information we have
     */
    public function __get($name)
    {
        switch ($name) {
            case 'sort_col':
                return $this->getSortCol();

                break;
            case 'sort_dir':
                return $this->getSortDir();

                break;
            case 'search':
                return $this->config['search'];

                break;
            default:
                user_error("Invalid property: " . __CLASS__ . "->$name");

                break;
        }
    }

    /**
     * Convenience method for initializing a Table object with current
     * sort parameters set via globals within a CP controller
     *
     * @param	array 	$config	See constructor doc block
     * @return  object	New Table object
     */
    public static function fromGlobals($config = array())
    {
        $sort_col = (isset($config['sort_col_qs_var'])) ? $config['sort_col_qs_var'] : 'sort_col';
        $sort_dir = (isset($config['sort_dir_qs_var'])) ? $config['sort_dir_qs_var'] : 'sort_dir';
        // We'll only place in here what needs overriding
        $defaults = array();

        // Look for search in POST first, then GET
        if (isset($config['search'])) {
            $defaults['search'] = $config['search'];
        } elseif (isset($_POST['search'])) {
            $defaults['search'] = ee('Request')->post('search');
        } elseif (isset($_GET['search'])) {
            $defaults['search'] = ee('Request')->get('search');
        } else {
            $defaults['search'] = false;
        }

        if (isset($_GET[$sort_col])) {
            $defaults['sort_col'] = $_GET[$sort_col];
        }

        if (isset($_GET[$sort_dir]) && in_array(ee('Request')->get($sort_dir), ['asc', 'desc'])) {
            $defaults['sort_dir'] = ee('Request')->get($sort_dir);
        }

        if (isset($_GET['page']) && ee('Request')->get('page') > 0) {
            $defaults['page'] = (int) ee('Request')->get('page');
        }

        if (isset($_GET['perpage']) && ee('Request')->get('perpage') > 0) {
            $defaults['limit'] = (int) ee('Request')->get('perpage');
        }

        $defaults = array_map(function ($value) {
            return htmlentities($value, ENT_NOQUOTES, 'UTF-8');
        }, $defaults);

        return new static(array_merge($config, $defaults));
    }

    /**
     * Set the columns for this table, main argument is an array passed
     * in like this, for example:
     *
     * array(
     *	    'Table Name',
     *	    'Records',
     *	    'Size',
     *	    'Manage' => array(
     *	    	'type'	=> Table::COL_TOOLBAR
     *	    ),
     *	    array(
     *	    	'type'	=> Table::COL_CHECKBOX
     *	    )
     *	);
     *
     * It's an array of column names, with optional settings for each
     * column. Right now, the current options are
     *
     * 	'encode': Whether or not run column contents through htmlentities(),
     *		helps protect against XSS
     *	'sort': Whether or not the column can be sortable
     *	'type': The type of column, derived from the constants above
     *
     * If no column name is needed, just pass an array with your settings
     *
     * @param	array 	$columns	Column names and settings
     * @return  void
     */
    public function setColumns($columns = array())
    {
        $this->columns = array();

        // Default settings for columns
        $defaults = array(
            'label' => null,
            'name' => null,
            'encode' => ! $this->config['grid_input'], // Default to encoding if this isn't a Grid input
            'sort' => true,
            'type' => self::COL_TEXT
        );

        foreach ($columns as $label => $settings) {
            // 'label' key override
            if (is_array($settings) && isset($settings['label'])) {
                $label = $settings['label'];
            }
            // Column has no settings, value is label
            elseif (is_int($label) && is_string($settings)) {
                $label = $settings;
            }

            // Combine desired settings with defaults
            if (is_array($settings)) {
                $settings = array_merge($defaults, $settings);

                // Only these columns are sortable
                if ($settings['type'] !== self::COL_ID &&
                    $settings['type'] !== self::COL_TEXT &&
                    $settings['type'] !== self::COL_STATUS) {
                    $settings['sort'] = false;
                }
            } else {
                $settings = $defaults;
            }

            // If this passes, label was likely set as column's key, set it
            if (! isset($settings['label']) && ! is_int($label)) {
                $settings['label'] = $label;
            }

            if (! isset($settings['name']) && ! is_int($label)) {
                $settings['name'] = $label;
            }

            $this->columns[] = $settings;
        }
    }

    /**
     * Set and normalizes the data for this table, main argument is an
     * array passed in like this, for example:
     *
     * 	$data = array(
     * 		// Row 1
     * 		array(
     * 			'col 1 data',
     * 			'col 2 data',
     * 			'col 3 data',
     * 			// COL_TOOLBAR, array of buttons associated with links
     * 			array('toolbar_items' =>
     * 				array('view' => 'http://test/')
     * 			),
     * 			'status',
     * 			// COL_CHECKBOX, name and value for checkbox
     * 			array('name' => 'table[]', 'value' => 'test')
     * 		),
     * 		// Row 2
     * 		array(
     * 			'col 1 data 2',
     * 			'col 2 data 2',
     * 			NULL, // Can have null values
     * 			array('toolbar_items' => array(
     * 				'view' => array( // Button class name
     * 					// HTML attributes for anchor
     * 					'href' => 'http://test/2',
     * 					'title' => 'view'
     * 				)
     * 			)),
     * 			'status',
     * 			array('name' => 'table[]', 'value' => 'test2')
     * 		)
     * 	 );
     *
     * This goes with the columns example above, where certain types of
     * columns require an array of settings to populate.
     *
     * COL_TOOLBAR: Needs an array of toolbar_items where it's the name of
     * the toolbar icon assocated to the URL it should go to upon click
     *
     * COL_CHECKBOX: Needs the name and value of the checkbox
     *
     * @param	array 	$data	Table data
     * @return  void
     */
    public function setData($data)
    {
        if (! empty($data)) {
            $this->data = array();

            // Default settings for columns
            $defaults = array(
                'type' => self::COL_TEXT,
                'encode' => false
            );

            $this->config['total_rows'] = 0;

            // Normalize the table data for plugging into table view
            foreach ($data as $heading => $rows) {
                if ($this->config['subheadings'] === false) {
                    $rows = array($rows);
                }

                foreach ($rows as $row) {
                    // Make sure we have the same number of columns in the row
                    // as was set using setColumns
                    if (array_key_exists('columns', $row)) {
                        $count = count($row['columns']);
                    } else {
                        $count = count($row);
                    }
                    if ($count != count($this->columns)) {
                        throw new \InvalidArgumentException('Data must have the same number of columns as the set columns.');
                    }

                    $attrs = array();

                    if (count(array_diff(array_keys($row), array('attrs', 'columns'))) == 0) {
                        $attrs = $row['attrs'];
                        $row = $row['columns'];
                    }

                    $i = 0;
                    $data_row = array();

                    foreach ($row as $item) {
                        // Get the settings for this column, we'll set some on
                        // cell for easy access by the view
                        $col_settings = array_values(array_slice($this->columns, $i, 1));

                        // Normal cell content
                        if (! is_array($item)) {
                            $settings = array(
                                'content' => $item,
                                'type' => $col_settings[0]['type'],
                                'encode' => $col_settings[0]['encode']
                            );
                            $data_row[] = array_merge($defaults, $settings);
                        } else {
                            $settings = array_merge($defaults, $item);
                            $settings['type'] = $col_settings[0]['type'];

                            if ($settings['type'] == self::COL_TEXT) {
                                $settings['encode'] = $col_settings[0]['encode'];
                            }

                            $data_row[] = array_merge(array('content' => ''), $settings);
                        }

                        // Validate the some of the types
                        switch ($settings['type']) {
                            case self::COL_CHECKBOX:
                                if (! isset($settings['name']) or ! isset($settings['value'])) {
                                    throw new \InvalidArgumentException('Checkboxes require a name and value.');
                                }

                                break;
                            case self::COL_TOOLBAR:
                                if (! isset($settings['toolbar_items'])) {
                                    throw new \InvalidArgumentException('No toolbar items set for toolbar column type.');
                                }

                                break;
                            default:
                                break;
                        }

                        $i++;
                    }

                    $data_row = array(
                        'attrs' => $attrs,
                        'columns' => $data_row
                    );

                    // Group by subheading only if there is no search criteria,
                    // we drop the headings when showing search results
                    if ($this->config['subheadings'] && empty($this->config['search'])) {
                        $this->data[$heading][] = $data_row;
                    } else {
                        $this->data[] = $data_row;
                    }

                    $this->config['total_rows']++;
                }
            }

            // If this table is not paginated, handle sorting automatically
            if ($this->config['autosort']) {
                $this->sortData();
            }

            // Handle search with a simple strpos()
            if ($this->config['autosearch']) {
                $this->searchData();
            }

            // Apply pagination after search
            if ($this->config['autosort'] && $this->config['limit'] != 0) {
                $offset = ($this->config['page'] - 1) * $this->config['limit'];

                $this->data = array_slice($this->data, $offset, $this->config['limit']);
            }
        }
    }

    /**
     * Some tables need a "tbl-action" row with <a> "buttons". This method will
     * allow for them to be added, and will render the row with the correct
     * colspan.
     *
     * @param string $url The url to use for the href="" attribute
     * @param string $text The text to use for the button
     * @param string $class An additional class string to add to the class
     *   attribute of the <a> tag.
     * @return void
     */
    public function addActionButton($url, $text, $class = "submit")
    {
        $class = 'button button--primary ' . $class;

        $this->action_buttons[] = array(
            'url' => $url,
            'text' => $text,
            'class' => rtrim($class)
        );
    }

    /**
     * Some tables need a "tbl-action" row non-button content. This method will
     * allow for them to be added, and will render the row with the correct
     * colspan.
     *
     * @param string $contetn The content to append
     * @return void
     */
    public function addActionContent($content)
    {
        $this->action_content .= $content;
    }

    /**
     * If the entire data set is passed to the table object, the table
     * object can handle sorting of it automatically without the controller
     * needing to modify its query. But if there is a large amount of data that
     * can be displayed, it's probably best to leave 'autosort' to FALSE and
     * manually do sorting and paging in the controller.
     *
     * @return  void
     */
    private function sortData()
    {
        $subheadings = ($this->config['subheadings'] && empty($this->config['search']));

        // If there's subheadings, sort by subheading in the direction of
        // the sort first, then drill down into the heading's rows
        if ($subheadings) {
            $sort_dir = $this->getSortDir();
            $that = $this;
            uksort($this->data, function ($a, $b) use ($that, $sort_dir) {
                $cmp = $that->compareData($a, $b);

                return ($sort_dir == 'asc') ? $cmp : -$cmp;
            });

            // For each section, sort its rows
            foreach ($this->data as $heading => &$rows) {
                $this->sortRows($rows);
            }
        } else {
            // No subheadings, sort normally
            $this->sortRows($this->data);
        }
    }

    /**
     * Sorts rows based on column and sort direction
     *
     * @return  void
     */
    private function sortRows(&$rows)
    {
        $columns = $this->columns;
        $sort_col = $this->getSortCol();
        $sort_dir = $this->getSortDir();

        // Errors are suppressed due to a PHP bug where PHP incorrectly assumes
        // an array has been changed in a usort function
        $that = $this;
        usort($rows, function ($a, $b) use ($that, $columns, $sort_col, $sort_dir) {
            $search = array_map(function ($column) {
                return $column['label'];
            }, $columns);
            $index = array_search($sort_col, $search);
            $cmp = $that->compareData(
                $a['columns'][$index]['content'],
                $b['columns'][$index]['content']
            );

            return ($sort_dir == 'asc') ? $cmp : -$cmp;
        });
    }

    /**
     * Compare two values automatically
     *
     * @param  Mixed $a Left value
     * @param  Mixed $b Right value
     * @return Integer  Comparison result (-1, 0, 1) based on the two values passed in
     */
    public function compareData($a, $b)
    {   
        /*These 2 ifs are needed to catch columns that do not have a data at that spot.
        For instance blank cookie lifetime setting data for pro.
        This makes it so that Null values are considered to be smallest value.
        To make Null values be considered biggest value have a return 1 and b return -1.*/
        if($a === NULL){
            return -1;
        }
        if($b === NULL){
            return 1;
        }
        // Sort numbers as numbers
        if (is_numeric($a) && is_numeric($b)) {
            $cmp = $a - $b;
        }
        // String sorting
        else {
            // Check for dates
            $date_format = $this->localize->get_date_format();
            $date_a = $this->localize->string_to_timestamp($a, true, $date_format);
            $date_b = $this->localize->string_to_timestamp($b, true, $date_format);

            // Check for disk size
            $sizes = array('KB', 'MB', 'GB', 'TB', 'PB');
            $size_a = strtoupper(substr(strip_tags($a), -2));
            $size_b = strtoupper(substr(strip_tags($b), -2));

            if ($date_a !== false && $date_b !== false) {
                $cmp = $date_a - $date_b;
            } elseif (in_array($size_a, $sizes) && in_array($size_b, $sizes)) {
                $cmp = $this->convertToBytes($a) - $this->convertToBytes($b);
            } else {
                $cmp = strcmp(strtolower(strip_tags($a)), strtolower(strip_tags($b)));
            }
        }

        return $cmp;
    }

    /**
     * Given a disk size string such as "12 GB", "23.0 MB", "42KB", etc,
     * converts to bytes for sort comparison
     *
     * @param	string	$size	Disk size string
     * @return	Integer	Number of bytes the size string represents
     */
    private function convertToBytes($size)
    {
        $number = trim(substr(strip_tags($size), 0, -2));

        switch (trim(strtoupper(substr(strip_tags($size), -2)))) {
            case 'KB':
                return $number * 1024;
            case 'MB':
                return $number * pow(1024, 2);
            case 'GB':
                return $number * pow(1024, 3);
            case 'TB':
                return $number * pow(1024, 4);
            case 'PB':
                return $number * pow(1024, 5);
            default:
                return $size;
        }
    }

    /**
     * If the entire data set is passed to the table object, the table
     * object can handle searching of its contents automatically without the
     * controller needing to modify its query. But if there is a large amount
     * of data that can be displayed, it's probably best to leave 'autosearch'
     * to FALSE and manually do searching in the controller.
     *
     * @return  void
     */
    private function searchData()
    {
        // Bail if there's no search data
        if (empty($this->config['search'])) {
            return;
        }

        foreach ($this->data as $key => $row) {
            $match = false;

            foreach ($row['columns'] as $column) {
                // Only search searchable columns
                if ($column['type'] == self::COL_TEXT or
                    $column['type'] == self::COL_STATUS) {
                    if (strpos(strtolower($column['content']), strtolower($this->config['search'])) !== false) {
                        // Found a match, move on to the next row
                        $match = true;

                        continue 2;
                    }
                }
            }

            // Finally, remove the row if no match was found in any
            // searchable columns
            if (! $match) {
                unset($this->data[$key]);
            }
        }

        $this->config['total_rows'] = count($this->data);
    }

    /**
     * Returns the table configuration and data in a format ready to be
     * processed by the _shared/table view
     *
     * @param	URL	$base_url	URL object of the base URL used for setting
     *                      	the search and sort criteria for sorting and
     *                      	pagination URLs
     * @return	array			Array of view variables, structure is below
     */
    public function viewData($base_url = null)
    {
        if ($base_url != null) {
            $base_url = clone $base_url;

            if ($this->config['search'] === false) {
                $this->config['search'] = '';
            }

            if (!empty($this->config['search'])) {
                $base_url->setQueryStringVariable('search', $this->config['search']);
            }
            $base_url->setQueryStringVariable($this->config['sort_col_qs_var'], $this->getSortCol());
            $base_url->setQueryStringVariable($this->config['sort_dir_qs_var'], $this->getSortDir());
        }

        $view_vars = $this->config;

        return array_merge(
            $view_vars,
            array(
                'base_url' => $base_url,
                'sort_col' => $this->getSortCol(),
                'sort_dir' => $this->getSortDir(),
                'table_attrs' => $this->config['attrs'],
                'columns' => $this->columns,
                'data' => $this->data,
                'action_buttons' => $this->action_buttons,
                'action_content' => $this->action_content,
                'subheadings' => ($this->config['subheadings'] && empty($this->config['search'])),
            )
        );
    }

    /**
     * Returns the current sorting column, or the first column if none is
     * specified in the config
     *
     * @return  string	Name of column to sort by
     */
    private function getSortCol()
    {
        $search = array_map(function ($column) {
            return $column['label'];
        }, $this->columns);

        if ((empty($this->config['sort_col']) && count($this->columns) > 0) or
            ! in_array($this->config['sort_col'], $search)) {
            return isset($this->columns[0]) ? $this->columns[0]['label'] : null;
        }

        return $this->config['sort_col'];
    }

    /**
     * Returns the current sorting direction
     *
     * @return  string	Sort direction, either 'asc' or 'desc'
     */
    private function getSortDir()
    {
        return (! in_array($this->config['sort_dir'], array('asc', 'desc')))
            ? 'asc' : $this->config['sort_dir'];
    }

    /**
     * Set the "no results" text for the table along with an optional action
     * button and link to create a new whatever is supposed to be displayed.
     * Text is typically in the format of "No {item} available" with button
     * text being "Create new {item}"
     *
     * @param	string	$text			Text to be shown in the table when there
     *                       			are no results
     * @param	string	$action_text	Text for action button to create a new item
     * @param	string	$action_link	Link for action button to create a new item
     * @return  void
     */
    public function setNoResultsText($text, $action_text = '', $action_link = '', $external = false)
    {
        $this->config['no_results'] = array(
            'text' => $text,
            'action_text' => $action_text,
            'action_link' => $action_link,
            'external' => $external
        );
    }

    /**
     * Inject the Localize object
     * @param Localize $localize An instance of the Localize class
     */
    public function setLocalize($localize)
    {
        $this->localize = $localize;
    }
}

// EOF
