<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Pagination
 */
class EE_Pagination
{
    public $base_url = ''; // The page we are linking to
    public $prefix = ''; // A custom prefix added to the path.
    public $suffix = ''; // A custom suffix added to the path.

    public $total_rows = ''; // Total number of items (database results)
    public $per_page = 10; // Max number of items you want shown per page
    public $num_links = 2; // Number of "digit" links to show before/after the currently viewed page
    public $cur_page = 0; // The current page being viewed
    public $first_link = '&lsaquo; First';
    public $next_link = '&gt;';
    public $prev_link = '&lt;';
    public $last_link = 'Last &rsaquo;';
    public $uri_segment = 3;
    public $full_tag_open = '';
    public $full_tag_close = '';
    public $first_tag_open = '';
    public $first_tag_close = '&nbsp;';
    public $last_tag_open = '&nbsp;';
    public $last_tag_close = '';
    public $first_url = ''; // Alternative URL for the First Page.
    public $cur_tag_open = '&nbsp;<strong>';
    public $cur_tag_close = '</strong>';
    public $next_tag_open = '&nbsp;';
    public $next_tag_close = '&nbsp;';
    public $prev_tag_open = '&nbsp;';
    public $prev_tag_close = '';
    public $num_tag_open = '&nbsp;';
    public $num_tag_close = '';
    public $page_query_string = false;
    public $query_string_segment = 'per_page';
    public $display_pages = true;
    public $anchor_class = '';

    /**
     * Constructor
     *
     * @access  public
     * @param   array   initialization parameters
     */
    public function __construct($params = array())
    {
        if (count($params) > 0) {
            $this->initialize($params);
        }

        if ($this->anchor_class != '') {
            $this->anchor_class = 'class="' . $this->anchor_class . '" ';
        }

        log_message('debug', "Pagination Class Initialized");
    }

    /**
     * This is the method you want.
     */
    public function create()
    {
        return new Pagination_object();
    }

    /**
     * Initialize Preferences
     *
     * @access  public
     * @param   array   initialization parameters
     * @return  void
     */
    public function initialize($params = array())
    {
        if (count($params) > 0) {
            foreach ($params as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     * Generate the pagination links
     *
     * @access  public
     * @return  string
     */
    public function create_links()
    {
        $link_array = $this->create_link_array();

        // Calculate the total number of pages
        $num_pages = ceil($this->total_rows / $this->per_page);

        // And here we go...
        $output = '';

        // No links to render
        if (empty($link_array)) {
            return $output;
        }

        // Render the "First" link
        if ($this->cur_page > ($this->num_links + 1)) {
            $first_page = $link_array['first_page'][0];

            $output .= $this->first_tag_open . '<a ' . $this->anchor_class . 'href="' . $first_page['pagination_url'] . '">' . $first_page['text'] . '</a>' . $this->first_tag_close;
        }

        // Render the "previous" link
        if (! empty($link_array['previous_page'][0])) {
            $previous_page = $link_array['previous_page'][0];

            $output .= $this->prev_tag_open . '<a ' . $this->anchor_class . 'href="' . $previous_page['pagination_url'] . '">' . $previous_page['text'] . '</a>' . $this->prev_tag_close;
        }

        // Render the pages
        if ($this->display_pages !== false and ! empty($link_array['page'])) {
            // Write the digit links
            foreach ($link_array['page'] as $current_page) {
                if ($current_page['current_page']) {
                    $output .= $this->cur_tag_open . $current_page['pagination_page_number'] . $this->cur_tag_close; // Current page
                } else {
                    $output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $current_page['pagination_url'] . '">' . $current_page['pagination_page_number'] . '</a>' . $this->num_tag_close;
                }
            }
        }

        // Render the "next" link
        if (! empty($link_array['next_page'][0])) {
            $next_page = $link_array['next_page'][0];

            $output .= $this->next_tag_open . '<a ' . $this->anchor_class . 'href="' . $next_page['pagination_url'] . '">' . $next_page['text'] . '</a>' . $this->next_tag_close;
        }

        // Render the "Last" link
        if (($this->cur_page + $this->num_links) < $num_pages) {
            $last_page = $link_array['last_page'][0];

            $output .= $this->last_tag_open . '<a ' . $this->anchor_class . 'href="' . $last_page['pagination_url'] . '">' . $last_page['text'] . '</a>' . $this->last_tag_close;
        }

        // Add the wrapper HTML if exists
        $output = $this->full_tag_open . $output . $this->full_tag_close;

        return $output;
    }

    /**
     * Create's an array of pagination links including the first, previous,
     * next, and last page links
     *
     * @return array Associative array ready to go straight into EE's
     * template parser
     */
    public function create_link_array()
    {
        // If our item count or per-page total is zero there is no need to continue.
        if ($this->total_rows == 0 or $this->per_page == 0) {
            return '';
        }

        // Calculate the total number of pages
        $num_pages = ceil($this->total_rows / $this->per_page);

        // Is there only one page? Hm... nothing more to do here then.
        if ($num_pages == 1) {
            return '';
        }

        $this->_determine_current_page();

        // Figure out the number of links to show
        $this->num_links = (int) $this->num_links;

        if ($this->num_links < 1) {
            show_error('Your number of links must be a positive number.');
        }

        if (! is_numeric($this->cur_page)) {
            $this->cur_page = 0;
        }

        // Is the page number beyond the result range?
        // If so we show the last page
        if ($this->cur_page > $this->total_rows) {
            $this->cur_page = ($num_pages - 1) * $this->per_page;
        }

        $uri_page_number = $this->cur_page;
        $this->cur_page = floor(($this->cur_page / $this->per_page) + 1);

        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

        // Is pagination being used over GET or POST?  If get, add a per_page query
        // string. If post, add a trailing slash to the base URL if needed
        if (ee()->config->item('enable_query_strings') === true or $this->page_query_string === true) {
            $this->base_url = rtrim($this->base_url) . '&amp;' . $this->query_string_segment . '=';
        } else {
            $this->base_url = rtrim($this->base_url, '/') . '/';
        }

        // And here we go...
        $link_array = array();

        $first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;

        // Render the "First" link
        $link_array['first_page'][0] = array(
            'pagination_url' => $first_url,
            'text' => $this->first_link
        );

        // Render the "previous" link
        if ($this->prev_link !== false and $this->cur_page != 1) {
            $i = $uri_page_number - $this->per_page;

            if ($i == 0 && $this->first_url != '') {
                $link_array['previous_page'][0] = array(
                    'pagination_url' => $this->first_url,
                    'text' => $this->prev_link
                );
            } else {
                $i = ($i == 0) ? '' : $this->prefix . $i . $this->suffix;
                $link_array['previous_page'][0] = array(
                    'pagination_url' => $this->base_url . $i,
                    'text' => $this->prev_link
                );
            }
        } else {
            $link_array['previous_page'][0] = array();
        }

        // Render the pages
        if ($this->display_pages !== false) {
            // Write the digit links
            for ($loop = $start - 1; $loop <= $end; $loop++) {
                $offset = ($loop * $this->per_page) - $this->per_page;

                if ($offset >= 0) {
                    $prepped_offset = ($offset == 0) ? '' : $offset;

                    if ($this->cur_page == $loop) {
                        $prepped_offset = ($prepped_offset == '') ? '' : $this->prefix . $prepped_offset . $this->suffix;

                        $link_array['page'][] = array(
                            'pagination_url' => ($prepped_offset == '') ? $first_url : $this->base_url . $prepped_offset,
                            'pagination_page_number' => $loop,
                            'current_page' => true
                        );
                    } elseif ($prepped_offset == '' && $this->first_url != '') {
                        $link_array['page'][] = array(
                            'pagination_url' => $first_url,
                            'pagination_page_number' => $loop,
                            'current_page' => false
                        );
                    } else {
                        $prepped_offset = ($prepped_offset == '') ? '' : $this->prefix . $prepped_offset . $this->suffix;

                        $link_array['page'][] = array(
                            'pagination_url' => $this->base_url . $prepped_offset,
                            'pagination_page_number' => $loop,
                            'current_page' => false
                        );
                    }
                }
            }
        }

        // Render the "next" link
        if ($this->next_link !== false and $this->cur_page < $num_pages) {
            $link_array['next_page'][0] = array(
                'pagination_url' => $this->base_url . $this->prefix . ($this->cur_page * $this->per_page) . $this->suffix,
                'text' => $this->next_link
            );
        } else {
            $link_array['next_page'][0] = array();
        }

        // Render the "Last" link
        $offset = (($num_pages * $this->per_page) - $this->per_page);

        $link_array['last_page'][0] = array(
            'pagination_url' => $this->base_url . $this->prefix . $offset . $this->suffix,
            'text' => $this->last_link
        );

        $this->_remove_double_slashes($link_array);

        return $link_array;
    }

    /**
     * Remove doubles lashes from URLs
     *
     * @param array $array (Passed by reference) Array that will be modified
     *  and all pagination_url array items will have double slashes removed
     *  from the URLs
     */
    private function _remove_double_slashes(&$array)
    {
        ee()->load->helper('string_helper');

        foreach ($array as $key => &$value) {
            if (isset($value[0]) and is_array($value[0])) {
                $this->_remove_double_slashes($value);
            } elseif (! empty($value['pagination_url'])) {
                $value['pagination_url'] = reduce_double_slashes($value['pagination_url']);
            }
        }
    }

    /**
     * Determine's the current page number using either the query string
     * segments or the URI segments
     */
    private function _determine_current_page()
    {
        // Determine the current page number.
        if (ee()->config->item('enable_query_strings') === true or $this->page_query_string === true) {
            if (ee()->input->get($this->query_string_segment) != 0) {
                $this->cur_page = ee()->input->get($this->query_string_segment);

                // Prep the current page - no funny business!
                $this->cur_page = (int) $this->cur_page;
            }
        } else {
            if (ee()->uri->segment($this->uri_segment) != 0) {
                $this->cur_page = ee()->uri->segment($this->uri_segment);

                $this->cur_page = ltrim($this->cur_page, $this->prefix);
                $this->cur_page = rtrim($this->cur_page, $this->suffix);

                // Prep the current page - no funny business!
                $this->cur_page = (int) $this->cur_page;
            }
        }
    }
}

/**
 * Pagination object created for each instance of pagination.
 */
class Pagination_object
{
    public $paginate = false;
    public $total_items = 0;
    public $total_pages = 1;
    public $per_page = 0;
    public $offset = 0;
    public $current_page = 1;
    public $basepath = '';
    public $prefix = "P";

    // Field Pagination specific properties
    public $cfields = array();
    public $field_pagination = false;
    public $field_pagination_query = null;

    private $_template_data = array();
    private $_page_array = array();
    private $_multi_fields = '';
    private $_page_next = '';
    private $_page_previous = '';
    private $_page_links = '';
    private $_page_links_limit = 2;
    private $_type = '';
    private $_position = '';
    private $_pagination_marker = "pagination_marker";
    private $_always_show_first_last = false;

    public function __construct()
    {
        $stack = debug_backtrace(false);
        $this->_type = $stack[2]['class'];

        ee()->load->library('pagination');
        ee()->load->library('template', null, 'TMPL');
    }

    /**
     * Retrieve non-public properties
     * @param  string $name  Name of the property
     * @return mixed         Value of the property
     */
    public function __get($name)
    {
        if (in_array($name, array('type', 'template_data'))) {
            return $this->{'_' . $name};
        }
    }

    /**
     * Sets non-public properties
     * @param string $name  Name of the property to set
     * @param string $value Value of the property
     */
    public function __set($name, $value)
    {
        // Allow for position overrides.
        // position lets the developer override the position of the pagination
        // (e.g. top, bottom, both, hidden)
        if (in_array($name, array('position', 'template_data'))) {
            $this->{'_' . $name} = $value;
        }
    }

    /**
     * Prepare the pagination template
     * Determines if {paginate} is in the tagdata, if so flags that. Also
     * checks to see if paginate_type is field, if it is, then we look for
     * {multi_field="..."} and flag that.
     *
     * The whole goal of this method is to see if we need to paginate and if
     * we do, extract the tags within pagination and put them in another variable
     *
     * @param String $template The template to prepare, typically
     *                         ee()->TMPL->tagdata
     * @return String The template with the pagination removed
     */
    public function prepare($template)
    {
        // Prepare the template
        if (ee()->TMPL->fetch_param('paginate') == 'hidden') {
            $this->paginate = true;
        } elseif (strpos($template, LD . 'paginate' . RD) !== false
            && preg_match_all("/" . LD . "paginate" . RD . "(.+?)" . LD . '\/' . "paginate" . RD . "/s", $template, $paginate_match)) {
            if (ee()->TMPL->fetch_param('paginate_type') == 'field') {
                // If we're supposed to paginate over fields, check to see if
                // {multi_field="..."} exists. If it does capture the conetents
                // and flag this as field_pagination.
                if (preg_match("/" . LD . "multi_field\=[\"'](.+?)[\"']" . RD . "/s", $template, $multi_field_match)) {
                    $this->_multi_fields = ee()->functions->fetch_simple_conditions($multi_field_match[1]);
                    $this->field_pagination = true;
                }
            }

            // Grab the parameters from {pagination_links}
            if (preg_match("/" . LD . "pagination_links(.*)" . RD . "/s", $template, $pagination_links_match)) {
                $parameters = ee('Variables/Parser')->parseTagParameters($pagination_links_match[1]);

                // Check for page_padding
                if (isset($parameters['page_padding'])) {
                    $this->_page_links_limit = $parameters['page_padding'];
                }

                // Check for always_show_first_last
                if (isset($parameters['always_show_first_last'])
                    && substr($parameters['always_show_first_last'], 0, 1) === 'y') {
                    $this->_always_show_first_last = true;
                }
            }

            // -------------------------------------------
            // 'pagination_fetch_data' hook.
            //  - Works with the 'create_pagination' hook
            //  - Developers, if you want to modify the $this object remember
            //  to use a reference on function call.
            //
            if (ee()->extensions->active_hook('pagination_fetch_data') === true) {
                ee()->extensions->call('pagination_fetch_data', $this);
                if (ee()->extensions->end_script === true) {
                    return;
                }
            }
            //
            // -------------------------------------------

            // If {paginate} exists store the pagination template
            $this->paginate = true;
            foreach ($paginate_match[1] as $current_match) {
                $hash = md5($current_match);
                $this->_template_data[$hash] = $current_match;
            }

            // Determine if pagination needs to go at the top and/or bottom, or inline
            $this->_position = ee()->TMPL->fetch_param('paginate', $this->_position);
        }

        foreach ($this->_template_data as $hash => $template_partial) {
            // Create temporary marker for inline position
            $replace_tag = ($this->_position == 'inline') ? LD . $this->_pagination_marker . ':' . $hash . RD : '';

            // Remove pagination tags from template since we'll just
            // append/prepend it later
            $template = str_replace(
                LD . 'paginate' . RD . $template_partial . LD . '/paginate' . RD,
                $replace_tag,
                $template
            );
        }

        return $template;
    }

    /**
     * Build the pagination out, storing it in the Pagination_object
     *
     * @param integer   $total_items    Number of rows we're paginating over
     * @param integer   $per_page   Number of items per page
     * @return Boolean TRUE if successful, FALSE otherwise
     */
    public function build($total_items, $per_page)
    {
        $this->total_items = $total_items;
        $this->per_page = $per_page;

        // -------------------------------------------
        // 'pagination_create' hook.
        //  - Rewrite the pagination function in the Channel module
        //  - Could be used to expand the kind of pagination available
        //  - Paginate via field length, for example
        //
        if (ee()->extensions->active_hook('pagination_create') === true) {
            ee()->extensions->call('pagination_create', $this, $this->total_items);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        // Check again to see if we need to paginate
        if ($this->paginate == true) {
            // If template_group and template are being specified in the
            // index.php and there's no other URI string, specify the basepath
            if ((ee()->uri->uri_string == '' or ee()->uri->uri_string == '/')
                && ee()->config->item('template_group') != ''
                && ee()->config->item('template') != '') {
                $this->basepath = ee()->functions->create_url(
                    ee()->config->slash_item('template_group') . '/' . ee()->config->item('template')
                );
            }

            // If basepath is still nothing, create the url from the uri_string
            if ($this->basepath == '') {
                $this->basepath = ee()->functions->create_url(ee()->uri->uri_string);
            }

            // Determine the offset
            if ($this->offset === 0) {
                $query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;
                if (preg_match("#^{$this->prefix}(\d+)|/{$this->prefix}(\d+)#", $query_string, $match)) {
                    $this->offset = (isset($match[2])) ? (int) $match[2] : (int) $match[1];
                    $this->basepath = reduce_double_slashes(
                        str_replace($match[0], '', $this->basepath)
                    );
                }
            }

            // Standard pagination, not field_pagination
            if ($this->field_pagination == false) {
                // If we're not displaying by something, then we'll need
                // something to paginate, otherwise if we're displaying by
                // something (week, day) it's okay for it to be empty
                if ($this->_type === "Channel"
                    && ee()->TMPL->fetch_param('display_by') == ''
                    && $this->total_items == 0) {
                    return false;
                }

                $this->offset = ($this->offset == '' or ($this->per_page > 1 and $this->offset == 1)) ? 0 : $this->offset;

                // If we're far beyond where we should be, reset us back to
                // the first page
                if ($this->offset > $this->total_items) {
                    return ee()->TMPL->no_results();
                }

                $this->current_page = floor(($this->offset / $this->per_page) + 1);
                $this->total_pages = intval(floor($this->total_items / $this->per_page));
            } else {
                // Field pagination - base values
                // If we're doing field pagination and there's not even one
                // entry, then clear out the sql and get out of here
                if ($this->total_items == 0
                    or ! is_object($this->field_pagination_query)) {
                    return false;
                }

                $m_fields = array();
                $row = $this->field_pagination_query->row_array();

                foreach ($this->_multi_fields as $val) {
                    foreach ($this->cfields as $site_id => $cfields) {
                        if (isset($cfields[$val])) {
                            if (isset($row['field_id_' . $cfields[$val]]) and $row['field_id_' . $cfields[$val]] != '') {
                                // Need unique field shortnames
                                $m_fields[$val] = $val;
                            }
                        }
                    }
                }

                $m_fields = array_values($m_fields);

                $this->per_page = 1;
                $this->total_items = count($m_fields);
                $this->total_pages = $this->total_items;
                if ($this->total_pages == 0) {
                    $this->total_pages = 1;
                }

                $this->offset = ($this->offset == '') ? 0 : $this->offset;
                if ($this->offset > $this->total_items) {
                    $this->offset = 0;
                }

                $this->current_page = floor(($this->offset / $this->per_page) + 1);

                if (isset($m_fields[$this->offset])) {
                    ee()->TMPL->tagdata = preg_replace("/" . LD . "multi_field\=[\"'].+?[\"']" . RD . "/s", LD . $m_fields[$this->offset] . RD, ee()->TMPL->tagdata);
                    ee()->TMPL->var_single[$m_fields[$this->offset]] = $m_fields[$this->offset];
                }
            }

            //  Create the pagination
            if ($this->total_items > 0 && $this->per_page > 0) {
                if ($this->total_items % $this->per_page) {
                    $this->total_pages++;
                }
            }

            // Last check to make sure we actually need to paginate
            if ($this->total_items > $this->per_page) {
                if (strpos($this->basepath, EESELF) === false && ee()->config->item('site_index') != '' && strpos($this->basepath, ee()->config->item('site_index')) === false) {
                    $this->basepath .= EESELF;
                }

                // Check to see if a paginate_base was provided
                if (ee()->TMPL->fetch_param('paginate_base')) {
                    $this->basepath = ee()->functions->create_url(
                        trim_slashes(ee()->TMPL->fetch_param('paginate_base'))
                    );
                }

                $config = array(
                    'first_url' => rtrim($this->basepath, '/'),
                    'base_url' => $this->basepath,
                    'prefix' => $this->prefix,
                    'total_rows' => $this->total_items,
                    'per_page' => $this->per_page,
                    // cur_page uses the offset because P45 (or similar) is a page
                    'cur_page' => $this->offset,
                    'num_links' => $this->_page_links_limit,
                    'first_link' => lang('pag_first_link'),
                    'last_link' => lang('pag_last_link'),
                    'uri_segment' => 0 // Allows $config['cur_page'] to override
                );

                ee()->pagination->initialize($config);
                $this->_page_links = ee()->pagination->create_links();
                ee()->pagination->initialize($config); // Re-initialize to reset config
                $this->_page_array = ee()->pagination->create_link_array();

                // If a page_next should exist, create it
                if ((($this->total_pages * $this->per_page) - $this->per_page) > $this->offset) {
                    $this->_page_next = reduce_double_slashes($this->basepath . '/P' . ($this->offset + $this->per_page));
                }

                // If a page_previous should exist, create it
                if (($this->offset - $this->per_page) >= 0) {
                    $this->_page_previous = reduce_double_slashes($this->basepath . '/P' . ($this->offset - $this->per_page));
                }
            } else {
                $this->offset = 0;
            }
        }

        return true;
    }

    /**
     * Renders all of the pagination data in the current template.
     *
     * Variable Pairs:
     * - page_links
     *
     * Single Variables:
     * - current_page
     * - total_pages
     *
     * Conditionals:
     * - total_pages
     * - previous_page
     * - next_page
     *
     * @param string $return_data The final template data to wrap the
     *      pagination around
     * @return string The $return_data with the pagination data either above,
     *      below or both above and below
     */
    public function render($return_data)
    {
        if ($this->_page_links == '' or $this->paginate === false) {
            // If there's no paginating to do and we're inline, remove the
            // pagination_marker
            if ($this->_position == 'inline') {
                foreach ($this->_template_data as $hash => $template_partial) {
                    $return_data = ee()->TMPL->swap_var_single(
                        $this->_pagination_marker . ':' . $hash,
                        '',
                        $return_data
                    );
                }
            }

            return $return_data;
        }

        foreach ($this->_template_data as $hash => &$template_data) {
            $parse_array = array();

            // Check to see if page_links is being used as a single
            // variable or as a variable pair
            if (strpos($template_data, LD . '/pagination_links' . RD) !== false) {
                $parse_array['pagination_links'] = array($this->_page_array);
            } else {
                $parse_array['pagination_links'] = $this->_page_links;
            }

            // Check to see if we should be showing first/last page or not
            if ($this->_always_show_first_last == false && is_array($parse_array['pagination_links'])) {
                // Don't show the first
                if ($this->current_page <= ($this->_page_links_limit + 1)) {
                    $parse_array['pagination_links'][0]['first_page'] = array();
                }

                // Don't show the last
                if (($this->current_page + $this->_page_links_limit) >= $this->total_pages) {
                    $parse_array['pagination_links'][0]['last_page'] = array();
                }
            }

            // Parse current_page and total_pages by default
            $parse_array['current_page'] = $this->current_page;
            $parse_array['total_pages'] = $this->total_pages;

            // Parse current_page and total_pages
            $template_data = ee()->TMPL->parse_variables(
                $template_data,
                array($parse_array),
                false // Disable backspace parameter so pagination markup is protected
            );

            // Parse {if previous_page} and {if next_page}
            $template_data = $this->_parse_conditional($template_data, 'previous', $this->_page_previous);
            $template_data = $this->_parse_conditional($template_data, 'next', $this->_page_next);

            ee()->TMPL->add_data($parse_array, 'pagination');
            // Parse if total_pages conditionals
            $template_data = ee()->functions->prep_conditionals(
                $template_data,
                array('total_pages' => $this->total_pages)
            );
        }

        // die(var_dump($this->_template_data));

        switch ($this->_position) {
            case "top":
                return implode($this->_template_data) . $return_data;

                break;
            case "both":
                return implode($this->_template_data) . $return_data . implode($this->_template_data);

                break;
            case "inline":
                foreach ($this->_template_data as $hash => $template_partial) {
                    $return_data = ee()->TMPL->swap_var_single(
                        $this->_pagination_marker . ':' . $hash,
                        $template_partial,
                        $return_data
                    );
                }

                return $return_data;

                break;

            return $return_data;

            break;
            case "bottom":
            default:
                return $return_data . implode($this->_template_data);

                break;
        }
    }

    /**
     * Parse {if previous_page} and {if next_page}
     *
     * @param string $template_data The template data to parse for
     * {if previous_page}
     * @param string $type Either 'next' or 'previous' depending on the
     *      conditional you're looking for
     * @param string $replacement What to replace $type_page with
     */
    private function _parse_conditional($template_data, $type, $replacement)
    {
        if (stripos($template_data, "if {$type}_page") !== false) {
            $template_data = preg_replace(
                "/{if {$type}_page}(.*?){(?:auto_)?path.*?}(.*?){\/if}/is",
                "{if {$type}_page}$1{$replacement}$2{/if}",
                $template_data
            );

            $template_data = ee()->functions->prep_conditionals(
                $template_data,
                array(
                    "{$type}_page" => $this->{'_page_' . $type}
                )
            );
        }

        return $template_data;
    }
}

// END Pagination class

// EOF
