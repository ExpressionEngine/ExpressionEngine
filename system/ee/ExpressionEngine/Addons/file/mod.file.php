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
 * File Module
 */
class File
{
    public $reserved_cat_segment = '';
    public $use_category_names = false;
    public $enable = array();
    public $categories = array();
    public $catfields = array();
    public $valid_thumbs = array();
    public $query;
    public $return_data = '';

    /**
      * Constructor
      */
    public function __construct()
    {
        if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '') {
            $this->use_category_names = ee()->config->item("use_category_name");
            $this->reserved_cat_segment = ee()->config->item("reserved_category_word");
        }
    }

    /**
      *  Files tag
      */
    public function entries()
    {
        $this->_fetch_disable_param();

        ee()->load->library('pagination');
        $pagination = ee()->pagination->create();
        ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);

        if ($this->enable['pagination'] == false) {
            $pagination->paginate = false;
        }

        $results = $this->_get_file_data($pagination);

        if (empty($results)) {
            return ee()->TMPL->no_results();
        }

        $this->query = $results;

        if ($this->query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        $this->fetch_categories();
        $this->fetch_valid_thumbs();
        $this->parse_file_entries($pagination);

        if ($this->enable['pagination'] && $pagination->paginate == true) {
            $this->return_data = $pagination->render($this->return_data);
        }

        return $this->return_data;
    }

    /**
      *  Build SQL Query
      */
    private function _get_file_data($pagination = '')
    {
        $file_id = '';
        $category_id = false;
        $category_group = false;
        $category_params = array('category' => 'category_id', 'category_group' => 'category_group');
        $dynamic = (ee()->TMPL->fetch_param('dynamic') !== 'no') ? true : false;

        // Parse the URL query string
        $query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;
        $uristr = ee()->uri->uri_string;
        if ($dynamic && ! empty($query_string)) {
            // If the query string is a number, treat it as a file ID
            if (is_numeric($query_string)) {
                $file_id = $query_string;
            } elseif ($this->enable['categories']) {
                ee()->load->helper('segment');
                $category_id = parse_category($query_string);
            }
        }

        // Check the file_id parameter and override the one fetched from the
        // query string
        if (ee()->TMPL->fetch_param('file_id')) {
            $file_id = ee()->TMPL->fetch_param('file_id');
        }

        // Check for category parameters
        foreach ($category_params as $param => $variable) {
            if ($this->enable['categories']
                && ($temp = ee()->TMPL->fetch_param($param))) {
                $$variable = $temp;
            }
        }

        // Start the cache so we can use for pagination
        ee()->db->start_cache();

        // Join the categories table if we're dealing with categories at all
        if ($category_id or $category_group) {
            ee()->db->distinct();

            // We use 'LEFT' JOIN when there is a 'not' so that we get entries
            // that are not assigned to a category.
            if ((substr($category_group, 0, 3) == 'not' or substr($category_id, 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n') {
                ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'LEFT');
                ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'LEFT');
            } else {
                ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'INNER');
                ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'INNER');
            }
        }

        // Start pulling File IDs to both paginate on then pull data
        ee()->db->select('exp_files.file_id');
        ee()->db->from('files');
        ee()->db->where('model_type', 'File');

        // Specify file ID(s) if supplied
        if ($file_id != '') {
            ee()->functions->ar_andor_string($file_id, 'exp_files.file_id');
        }

        // Specify directory ID(s) if supplied
        if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) !== false) {
            ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
        }
        // If no directory_id is set, restrict files to current site
        else {
            ee()->db->where_in('exp_files.site_id', ee()->TMPL->site_ids);
        }

        // Specify subfolder if supplied
        if (($folder_id = ee()->TMPL->fetch_param('folder_id')) !== false) {
            ee()->functions->ar_andor_string($folder_id, 'directory_id');
        }

        // File type
        if (($file_type = ee()->TMPL->fetch_param('file_type')) !== false) {
            ee()->functions->ar_andor_string($file_type, 'file_type');
        }

        // Specify category and category group ID(s) if supplied
        foreach ($category_params as $param => $variable) {
            if ($$variable) {
                $cat_field_name = ($param == 'category') ? 'exp_categories.cat_id' : 'exp_categories.group_id';

                $include_uncategorized = (substr($$variable, 0, 3) == 'not'
                    && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n') ? true : false;

                ee()->functions->ar_andor_string($$variable, $cat_field_name, '', $include_uncategorized);
            }
        }

        // Set the limit
        $limit = (int) ee()->TMPL->fetch_param('limit', 0);
        $offset = (int) ee()->TMPL->fetch_param('offset', 0);
        if ($limit > 0 && $this->enable['pagination'] && $pagination->paginate == true) {
            $pagination->build(ee()->db->count_all_results(), $limit);
            ee()->db->limit($pagination->per_page, $pagination->offset);
        } elseif ($limit > 0 && $offset >= 0) {
            ee()->db->limit($limit, $offset);
        } else {
            ee()->db->limit(100);
        }

        // Set order and sort
        $allowed_orders = array('title', 'date', 'upload_date', 'random');
        $order_by = strtolower(ee()->TMPL->fetch_param('orderby', 'upload_date'));
        $order_by = ($order_by == 'date' or ! in_array($order_by, $allowed_orders)) ? 'upload_date' : $order_by;
        $random = ($order_by == 'random') ? true : false;
        $sort = strtolower(ee()->TMPL->fetch_param('sort', 'desc'));
        $sort = ($random) ? 'random' : $sort;

        if (! $random) {
            ee()->db->select($order_by);
        }

        ee()->db->order_by($order_by, $sort);

        ee()->db->stop_cache();
        // Run the query and pass it to the final query
        $query = ee()->db->get();
        ee()->db->flush_cache();

        if ($query->num_rows() == 0) {
            return array();
        }

        foreach ($query->result() as $row) {
            $file_ids[] = $row->file_id;
        }

        //  Build the full SQL query
        ee()->db->select('*')
            ->join('upload_prefs', 'upload_prefs.id = files.upload_location_id', 'LEFT')
            ->where_in('file_id', $file_ids)
            ->order_by($order_by, $sort);

        return ee()->db->get('files');
    }

    /**
      *  Fetch categories
      */
    public function fetch_categories()
    {
        ee()->db->select('field_id, field_name')
            ->from('category_fields')
            ->where_in('site_id', ee()->TMPL->site_ids);

        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
            }
        }

        $categories = array();

        foreach ($this->query->result_array() as $row) {
            $categories[] = $row['file_id'];
        }

        $sql = ee()->db->select('c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description,
                                    c.parent_id, p.cat_id, p.file_id, c.group_id, cg.field_html_formatting, fd.*')
            ->from('exp_categories AS c, exp_file_categories AS p')
            ->join('category_field_data AS fd', 'fd.cat_id = c.cat_id', 'LEFT')
            ->join('category_groups AS cg', 'cg.group_id = c.group_id', 'LEFT')
            ->where('c.cat_id = p.cat_id')
            ->where_in('file_id', $categories)
            ->order_by('c.group_id, c.parent_id, c.cat_order');

        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return;
        }

        foreach ($categories as $val) {
            $this->temp_array = array();
            $this->cat_array = array();
            $parents = array();

            foreach ($query->result_array() as $row) {
                if ($val == $row['file_id']) {
                    $this->temp_array[$row['cat_id']] = array('category_id' => $row['cat_id'], 'parent_id' => $row['parent_id'], 'category_name' => $row['cat_name'], 'category_image' => $row['cat_image'], 'category_description' => $row['cat_description'], 'category_group_id' => $row['group_id'], 'category_url_title' => $row['cat_url_title']);

                    // Add in the path variable
                    $this->temp_array[$row['cat_id']]['path'] = ($this->use_category_names == true)
                            ? array($this->reserved_cat_segment . '/' . $row['cat_url_title'], array('path_variable' => true)) :
                                array('/C' . $row['cat_id'], array('path_variable' => true));

                    foreach ($row as $k => $v) {
                        if (strpos($k, 'field') !== false) {
                            $this->temp_array[$row['cat_id']][$k] = $v;
                        }
                    }

                    if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) {
                        $parents[$row['parent_id']] = '';
                    }
                    unset($parents[$row['cat_id']]);
                }
            }

            if (count($this->temp_array) == 0) {
                $temp = false;
            } else {
                foreach ($this->temp_array as $k => $v) {
                    if (isset($parents[$v['parent_id']])) {
                        $v['parent_id'] = 0;
                    }

                    if (0 == $v['parent_id']) {
                        $this->cat_array[] = $v;
                        $this->process_subcategories($k);
                    }
                }
            }

            $this->categories[$val] = $this->cat_array;
        }

        unset($this->temp_array);
        unset($this->cat_array);
    }

    /**
      *  Process Subcategories
      */
    public function process_subcategories($parent_id)
    {
        foreach ($this->temp_array as $key => $val) {
            if ($parent_id == $val['parent_id']) {
                $this->cat_array[] = $val;
                $this->process_subcategories($key);
            }
        }
    }

    /**
      *  Fetch Valid Thumbs
      */
    public function fetch_valid_thumbs()
    {
        ee()->db->select('upload_location_id, short_name');
        ee()->db->from('upload_prefs');

        ee()->db->join('file_dimensions', 'upload_prefs.id = file_dimensions.upload_location_id');

        ee()->db->where_in('upload_prefs.site_id', ee()->TMPL->site_ids);

        if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) != false) {
            ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
        }

        $sql = ee()->db->get();

        if ($sql->num_rows() == 0) {
            return;
        }

        foreach ($sql->result_array() as $row) {
            $this->valid_thumbs[] = array('dir' => $row['upload_location_id'], 'name' => $row['short_name']);
        }
    }

    /**
      *  Parse file entries
      */
    public function parse_file_entries($pagination)
    {
        ee()->load->library('typography');
        ee()->typography->initialize(array(
            'convert_curly' => false
        ));

        // Fetch the "category chunk"
        // We'll grab the category data now to avoid processing cycles in the foreach loop below

        $cat_chunk = array();
        if (strpos(ee()->TMPL->tagdata, LD . '/categories' . RD) !== false) {
            if (preg_match_all("/" . LD . "categories(.*?)" . RD . "(.*?)" . LD . '\/' . 'categories' . RD . "/s", ee()->TMPL->tagdata, $matches)) {
                for ($j = 0; $j < count($matches[0]); $j++) {
                    $cat_chunk[] = array($matches[2][$j], ee('Variables/Parser')->parseTagParameters($matches[1][$j]), $matches[0][$j]);
                }
            }
        }

        ee()->load->model('file_upload_preferences_model');
        $upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(1, null, true);

        $offset = (int) ee()->TMPL->fetch_param('offset', 0);
        if ($this->enable['pagination'] && $pagination->paginate == true) {
            $offset = $pagination->offset;
        }

        $parse_data = array();
        ee()->load->library('file_field');
        foreach ($this->query->result_array() as $count => $row) {
            $row['model_object'] = ee()->file_field->getFileModelForFieldData($row['file_id']);
            $row = ee()->file_field->parse_field($row);
            $row_prefs = $upload_prefs[$row['upload_location_id']];

            //  More Variables, Mostly for Conditionals
            $row['absolute_count'] = (int) $offset + $count + 1;
            $row['entry_id'] = $row['file_id'];

            $row['viewable_image'] = $this->is_viewable_image($row['file_name']);

            // Add in the path variable
            $row['id_path'] = array('/' . $row['file_id'], array('path_variable' => true));

            // typography on title?
            $row['title'] = ee()->typography->format_characters($row['title']);

            // typography on caption
            ee()->typography->parse_type(
                $row['description'],
                array(
                    'text_format' => 'xhtml',
                    'html_format' => 'safe',
                    'auto_links' => 'y',
                    'allow_img_url' => 'y'
                )
            );

            // Backwards compatible support for some old variables
            $row['caption'] = $row['description'];
            $row['entry_date'] = $row['upload_date'];
            $row['edit_date'] = $row['modified_date'];
            $row['filename'] = $row['file_name'];
            $row['file_url'] = $row['url'];

            // Category variables
            $row['categories'] = ($this->enable['categories'] && isset($this->categories[$row['file_id']])) ? $this->categories[$row['file_id']] : array();

            $parse_data[] = $row;
        }

        $this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $parse_data);
    }

    public function is_viewable_image($file)
    {
        $viewable_image = array('bmp','gif','jpeg','jpg','jpe','png');

        $ext = strtolower(substr(strrchr($file, '.'), 1));

        $viewable = (in_array($ext, $viewable_image)) ? true : false;

        return $viewable;
    }

    /**
     * Gets File Metadata- may move to db
     *
     * @param   string  $file_path  The full path to the file to check
     * @return  array
     */
    public function get_file_sizes($file_path)
    {
        ee()->load->helper('file');
        $filedata = array('height' => '', 'width' => '');

        $filedata['is_image'] = $this->is_viewable_image($file_path);

        if ($filedata['is_image'] && function_exists('getimagesize')) {
            $D = @getimagesize($file_path);

            if (is_array($D)) {
                $filedata['height'] = $D['1'];
                $filedata['width'] = $D['0'];
            }
        }

        $s = get_file_info($file_path, array('size'));

        $filedata['size'] = ($s) ? $s['size'] : false;

        return $filedata;
    }

    /**
     * Add-on Icon
     * @return void icon url
     */
    public function addonIcon()
    {
        ee()->load->library('mime_type');

        if (ee()->input->get('prolet')) {
            $prolet = ee('Model')->get('pro:Prolet', (int) ee()->input->get('prolet'))->first();
            if (!empty($prolet)) {
                $path = $prolet->icon;
            }
        } else {
            $addon = ee('Addon')->get(ee()->input->get('addon'));
            $filename = ee()->input->get('file');
            if (!in_array($filename, ['icon.svg', 'icon.png'])) {
                $filename = 'icon.svg';
            }
            $path = $addon->getPath() . '/' . $filename;
        }
        if (empty($path)) {
            $path = 'icon.svg';
        }

        ee()->output->out_type = 'cp_asset';
        ee()->output->enable_profiler(false);
        if (file_exists($path) && is_file($path)) {
            ee()->output->send_cache_headers(filemtime($path), 5184000, $path);
        } else {
            $path = PATH_THEMES . 'asset/img/default-addon-icon.svg';
        }
        $mime = ee('MimeType')->ofFile($path);
        if ($mime == 'image/svg') {
            $mime = 'image/svg+xml';
        }
        @header('Content-type: ' . $mime);

        ee()->output->set_output(file_get_contents($path));

        if (ee()->config->item('send_headers') == 'y') {
            @header('Content-Length: ' . strlen(ee()->output->final_output));
        }
    }

    /**
      * Fetch Disable Parameter
      */
    private function _fetch_disable_param()
    {
        $this->enable = array(
            'categories' => true,
            'category_fields' => true,
            'pagination' => true
        );

        if ($disable = ee()->TMPL->fetch_param('disable')) {
            foreach (explode("|", $disable) as $val) {
                if (isset($this->enable[$val])) {
                    $this->enable[$val] = false;
                }
            }
        }
    }
}
// END CLASS

// EOF
