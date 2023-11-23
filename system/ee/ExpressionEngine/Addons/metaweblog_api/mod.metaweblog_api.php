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
 * Metaweblog API Module
 */
class Metaweblog_api
{
    public $return_data = ''; 						// Bah!
    public $LB = "\r\n";					// Line Break for Entry Output

    public $status = '';						// Retrieving
    public $channel = '';
    public $fields = array();
    public $userdata = array();

    public $entry = null;
    public $title = 'MetaWeblog API Entry';	// Default Title
    public $channel_id = '1';						// Default Channel ID
    public $site_id = '1';						// Default Site ID
    public $channel_url = '';						// Channel URL for Permalink
    public $comment_url = '';						// Comment URL for Permalink
    public $deft_category = '';						// Default Category for Channel

    public $excerpt_field = '1';						// Default Except Field ID
    public $content_field = '2';						// Default Content Field ID
    public $more_field = '3';						// Default More Field ID
    public $keywords_field = '0';						// Default Keywords Field ID
    public $upload_dir = '';						// Upload Directory for Media Files

    public $field_name = 'body';					// Default Field Name
    public $entry_status = 'null';					// Entry Status from Configuration
    public $field_data = array();					// Array of Field Data
    public $field_format = array();					// Array of Field Formats
    public $categories = array();					// Categories (new/edit/get entry)
    public $assign_parents = true;						// Assign cat parents to post
    public $cat_parents = array();					// Parent categories of new/edited entry

    public $parse_type = false;					// Use Typography class when sending entry?
    public $html_format = 'none';					// Channel's HTML Formatting Preferences

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        ee()->lang->loadfile('metaweblog_api');

        $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : '1';

        $this->assign_parents = (ee()->config->item('auto_assign_cat_parents') == 'n') ? false : true;

        /** ----------------------------------------
        /**  Configuration Options
        /** ----------------------------------------*/
        $query = ee()->db->get_where('metaweblog_api', array('metaweblog_id' => $id));

        if ($query->num_rows() > 0) {
            foreach ($query->row_array() as $name => $pref) {
                $name = str_replace('metaweblog_', '', $name);
                $name = str_replace('_id', '', $name);

                if ($pref == 'y' or $pref == 'n') {
                    $this->{$name} = ($pref == 'y') ? true : false;
                } else {
                    $this->{$name} = $pref;
                }
            }
        }
    }

    /**
     * Incoming MetaWeblog API Requests
     *
     * @access	public
     * @return	void
     */
    public function incoming()
    {
        /** ---------------------------------
        /**  Load the XML-RPC Files
        /** ---------------------------------*/
        ee()->load->library('xmlrpc');
        ee()->load->library('xmlrpcs');

        /* ---------------------------------
        /*  Specify Functions
        /*	Normally, we would add a signature and docstring to the array for
        /*	each function, but since these are widespread and well known
        /*	functions I just skipped it.
        /* ---------------------------------*/

        $functions = array('metaWeblog.newPost' => array('function' => 'Metaweblog_api.newPost'),
            'metaWeblog.editPost' => array('function' => 'Metaweblog_api.editPost'),
            'metaWeblog.getPost' => array('function' => 'Metaweblog_api.getPost'),
            'metaWeblog.getCategories' => array('function' => 'Metaweblog_api.getCategories'),
            'metaWeblog.getRecentPosts' => array('function' => 'Metaweblog_api.getRecentPosts'),
            'metaWeblog.deletePost' => array('function' => 'Metaweblog_api.deletePost'),
            'metaWeblog.getUsersBlogs' => array('function' => 'Metaweblog_api.getUsersBlogs'),
            'metaWeblog.newMediaObject' => array('function' => 'Metaweblog_api.newMediaObject'),

            'blogger.getUserInfo' => array('function' => 'Metaweblog_api.getUserInfo'),
            'blogger.getUsersBlogs' => array('function' => 'Metaweblog_api.getUsersBlogs'),
            'blogger.deletePost' => array('function' => 'Metaweblog_api.deletePost'),

            'mt.getCategoryList' => array('function' => 'Metaweblog_api.getCategoryList'),
            'mt.get_postCategories' => array('function' => 'Metaweblog_api.get_postCategories'),
            'mt.getPostCategories' => array('function' => 'Metaweblog_api.get_postCategories'),
            'mt.publishPost' => array('function' => 'Metaweblog_api.publishPost'),
            'mt.getRecentPostTitles' => array('function' => 'Metaweblog_api.getRecentPostTitles'),
            'mt.setPostCategories' => array('function' => 'Metaweblog_api.setPostCategories'),
            'mt.supportedMethods' => array('function' => 'this.listMethods'),
            'mt.supportedTextFilters' => array('function' => 'Metaweblog_api.supportedTextFilters')
        );

        /** ---------------------------------
        /**  Instantiate the Server Class
        /** ---------------------------------*/
        ee()->xmlrpcs->initialize(array('functions' => $functions, 'object' => $this, 'xss_clean' => false));
        ee()->xmlrpcs->serve();
    }

    /**
     * Submit New Post
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function newPost($plist)
    {
        $parameters = $plist->output_parameters();
        $this->channel_id = $parameters['0'];

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        $this->parse_channel($this->channel_id);

        $this->fetch_entry(0);

        if ($this->entry_status != '' && $this->entry_status != 'null') {
            $this->status = $this->entry_status;
        } else {
            $this->status = ($parameters['4'] == '0') ? 'closed' : 'open';
        }

        $this->title = $parameters['3']['title'];

        $this->field_data['excerpt'] = (! isset($parameters['3']['mt_excerpt'])) ? '' : $parameters['3']['mt_excerpt'];
        $this->field_data['content'] = (! isset($parameters['3']['description'])) ? '' : $parameters['3']['description'];
        $this->field_data['more'] = (! isset($parameters['3']['mt_text_more'])) ? '' : $parameters['3']['mt_text_more'];
        $this->field_data['keywords'] = (! isset($parameters['3']['mt_keywords'])) ? '' : $parameters['3']['mt_keywords'];

        if (isset($parameters['3']['mt_allow_comments'])) {
            $deft_comments = ($parameters['3']['mt_allow_comments'] == 1) ? 'y' : 'n';
        }

        // Default category was set in fetch_entry()
        if (isset($parameters['3']['categories'])) {
            if (count($parameters['3']['categories']) > 0) {
                $cats = array();

                foreach ($parameters['3']['categories'] as $cat) {
                    if (trim($cat) != '') {
                        $cats[] = $cat;
                    }
                }

                if (count($cats) > 0) {
                    $this->check_categories(array_unique($cats));
                }
            }
        }

        // Default formatting for all of the channel's fields...

        foreach ($this->fields as $field_id => $field_data) {
            $entry_data['field_ft_' . $field_id] = $field_data['1'];
        }

        // Set custom field data
        $entry_data = $this->get_field_entry_data($parameters);

        $entry_data['site_id'] = $this->site_id;
        $entry_data['versioning_enabled'] = 'n';

        ee()->session->userdata = array_merge(
            ee()->session->userdata,
            array(
                'group_id' => $this->userdata['group_id'],
                'member_id' => $this->userdata['member_id'],
                'assigned_channels' => $this->userdata['assigned_channels']
            )
        );

        $entry_data['title'] = $this->title;
        $entry_data['channel_id'] = $this->channel_id;
        $entry_data['url_title'] = ee('Format')->make('Text', $this->title)->urlSlug()->compile();
        $entry_data['status'] = $this->status;
        $entry_data['entry_date'] = ee()->localize->now;

        if (! empty($this->categories)) {
            $entry_data['categories'] = $this->categories;
        }

        $this->entry->set($entry_data);

        $result = $this->entry->validate();

        if ($result->isValid()) {
            $this->entry->save();

            //Return Entry ID of new entry - defaults to string, so nothing fancy
            $response = $this->entry->entry_id;

            return ee()->xmlrpc->send_response($response);
        }

        $errors = $result->renderErrors();

        foreach ($errors as $field => $val) {
            $mssg .= $field . ': ' . $val . "\n";
        }

        return ee()->xmlrpc->send_error_message('804', lang('new_entry_errors') . $mssg);
    }

    /**
     * Edit Post
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function editPost($plist)
    {
        $parameters = $plist->output_parameters();
        //$this->channel_id = $parameters['0'];

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
            // If there aren't any channels assigned to the user, bail out

            if (count($this->userdata['assigned_channels']) == 0) {
                return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_access'));
            }
        }

        $entry_id = $parameters['0'];
        $this->fetch_entry($entry_id);

        $this->parse_channel($this->channel_id);

        if ($this->entry_status != '' && $this->entry_status != 'null') {
            $this->status = $this->entry_status;
        } else {
            $this->status = ($parameters['4'] == '0') ? 'closed' : 'open';
        }

        /** ---------------------------------------
        /**  Parse Channel Meta-Information
        /** ---------------------------------------*/
        $this->title = $parameters['3']['title'];

        $this->field_data['excerpt'] = (! isset($parameters['3']['mt_excerpt'])) ? '' : $parameters['3']['mt_excerpt'];
        $this->field_data['content'] = (! isset($parameters['3']['description'])) ? '' : $parameters['3']['description'];
        $this->field_data['more'] = (! isset($parameters['3']['mt_text_more'])) ? '' : $parameters['3']['mt_text_more'];
        $this->field_data['keywords'] = (! isset($parameters['3']['mt_keywords'])) ? '' : $parameters['3']['mt_keywords'];

        if (isset($parameters['3']['mt_allow_comments'])) {
            $metadata['allow_comments'] = ($parameters['3']['mt_allow_comments'] == 1) ? 'y' : 'n';
        }

        if (! empty($parameters['3']['dateCreated'])) {
            $metadata['entry_date'] = $this->iso8601_decode($parameters['3']['dateCreated']);
        }

        $metadata['edit_date'] = date("YmdHis");

        /** ---------------------------------------
        /**  Parse Channel Field Data
        /** ---------------------------------------*/

        // Set custom field data
        $entry_data = $this->get_field_entry_data($parameters);

        $entry_data['site_id'] = $this->site_id;
        $entry_data['versioning_enabled'] = 'n';

        $entry_data['channel_id'] = $this->channel_id;

        $entry_data['title'] = $this->title;
        $entry_data['channel_id'] = $this->channel_id;
        $entry_data['url_title'] = ee('Format')->make('Text', $this->title)->urlSlug()->compile();
        $entry_data['status'] = $this->status;
        //$entry_data['entry_date'] = ee()->localize->now;

        $data = array_merge($metadata, $entry_data);

        // Add Categories, if any

        if (isset($parameters['3']['categories'])) {
            if (count($parameters['3']['categories']) > 0) {
                $cats = array();

                foreach ($parameters['3']['categories'] as $cat) {
                    if (trim($cat) != '') {
                        $cats[] = $cat;
                    }
                }

                if (count($cats) > 0) {
                    $this->check_categories(array_unique($cats));
                }
            }

            $data['categories'] = $this->categories;
        }

        $this->entry->set($data);

        $result = $this->entry->validate();

        if ($result->isValid()) {
            $this->entry->save();

            //Return Entry ID of new entry - defaults to string, so nothing fancy
            $response = $this->entry->entry_id;
        } else {
            $errors = $result->renderErrors();

            foreach ($errors as $field => $val) {
                $mssg .= $field . ': ' . $val . "\n";
            }

            return ee()->xmlrpc->send_error_message('804', lang('new_entry_errors') . $mssg);
        }

        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        if (ee()->config->item('new_posts_clear_caches') == 'y') {
            ee()->functions->clear_caching('all');
        } else {
            ee()->functions->clear_caching('sql');
        }

        /** ---------------------------------
        /**  Return Boolean TRUE
        /** ---------------------------------*/

        return ee()->xmlrpc->send_response(array(1,'boolean'));
    }

    public function get_field_entry_data($parameters)
    {
        $entry_data = array();

        $convert_breaks = (! isset($parameters['3']['mt_convert_breaks'])) ? '' : $parameters['3']['mt_convert_breaks'];

        if ($convert_breaks === '0') {
            // MarsEdit sends '0' as synonymous with 'none'
            $convert_breaks = 'none';
        } elseif ($convert_breaks != '') {
            $plugins = $this->fetch_plugins();

            if (! in_array($convert_breaks, $plugins)) {
                $convert_breaks = '';
            }
        }

        if (isset($this->fields[$this->excerpt_field])) {
            if (isset($entry_data['field_id_' . $this->excerpt_field])) {
                $entry_data['field_id_' . $this->excerpt_field] .= $this->field_data['excerpt'];
            } else {
                $entry_data['field_id_' . $this->excerpt_field] = $this->field_data['excerpt'];
            }

            $entry_data['field_ft_' . $this->excerpt_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->excerpt_field]['1'];
        }

        if (isset($this->fields[$this->content_field])) {
            if (isset($entry_data['field_id_' . $this->content_field])) {
                $entry_data['field_id_' . $this->content_field] .= $this->field_data['content'];
            } else {
                $entry_data['field_id_' . $this->content_field] = $this->field_data['content'];
            }

            $entry_data['field_ft_' . $this->content_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->content_field]['1'];
        }

        if (isset($this->fields[$this->more_field])) {
            if (isset($entry_data['field_id_' . $this->more_field])) {
                $entry_data['field_id_' . $this->more_field] .= $this->field_data['more'];
            } else {
                $entry_data['field_id_' . $this->more_field] = $this->field_data['more'];
            }

            $entry_data['field_ft_' . $this->more_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->more_field]['1'];
        }

        if (isset($this->fields[$this->keywords_field])) {
            if (isset($entry_data['field_id_' . $this->keywords_field])) {
                $entry_data['field_id_' . $this->keywords_field] .= $this->field_data['keywords'];
            } else {
                $entry_data['field_id_' . $this->keywords_field] = $this->field_data['keywords'];
            }

            $entry_data['field_ft_' . $this->keywords_field] = ($convert_breaks != '') ? $convert_breaks : $this->fields[$this->keywords_field]['1'];
        }

        return $entry_data;
    }

    public function fetch_entry($entry_id)
    {
        if (! $entry_id) {
            if (! $this->channel_id) {
                return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
            }

            $channel = ee('Model')->get('Channel')
                ->with('ChannelFormSettings')
                ->filter('channel_id', $this->channel_id)
                ->first();

            if (empty($channel)) {
                return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel') . $this->channel_id);
            }

            $this->entry = ee('Model')->make('ChannelEntry');
            $this->entry->Channel = $channel;
            $this->entry->ip_address = ee()->input->ip_address();
            // Assign defaults based on the channel
            $this->entry->title = $channel->default_entry_title;
            $this->entry->versioning_enabled = $channel->enable_versioning;
            $this->entry->status = $channel->deft_status;
            $this->entry->author_id = $this->userdata['member_id'];

            if (! empty($this->channel->deft_category)) {
                $cat = ee('Model')->get('Category', $this->channel->deft_category)->first();

                if ($cat) {
                    $this->entry->Categories = $cat;
                }
            }

            return;
        }

        $query = ee('Model')->get('ChannelEntry')->with('Channel');
        $query->filter('entry_id', $entry_id);
        $query->filter('ChannelEntry.site_id', $this->site_id);

        $entry = $query->first();

        if (isset($entry)) {
            $this->entry = $entry;
            $this->title = $this->entry->title;
            $this->channel_id = $this->entry->channel_id;

            if (! in_array($this->channel_id, array_keys($this->userdata['assigned_channels'])) && ! ee('Permission')->isSuperAdmin()) {
                return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
            }

            if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
                if ($this->entry->author_id != $this->userdata['member_id']) {
                    return ee()->xmlrpc->send_error_message('806', ee()->lang->line('entry_uneditable'));
                }
            }

            return;
        }

        return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
    }

    /**
     * MT API: Publish Post
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function publishPost($plist)
    {
        /** ---------------------------------
        /**  Clear caches
        /** ---------------------------------*/
        if (ee()->config->item('new_posts_clear_caches') == 'y') {
            ee()->functions->clear_caching('all');
        } else {
            ee()->functions->clear_caching('sql');
        }

        /** ---------------------------------
        /**  Return Boolean TRUE
        /** ---------------------------------*/

        return ee()->xmlrpc->send_response(array(1,'boolean'));
    }

    /**
     * Get a single post
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getPost($plist)
    {
        $parameters = $plist->output_parameters();

        return $this->getRecentPosts($plist, $parameters['0']);
    }

    /**
     * Get recent posts
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getRecentPosts($plist, $entry_id = '')
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        $this->channel_id = $parameters['0'];

        /** ---------------------------------------
        /**  Parse Out Channel Information
        /** ---------------------------------------*/
        if ($entry_id == '') {
            $this->parse_channel($this->channel_id);
            $limit = (! empty($parameters['3']) && is_numeric($parameters['3'])) ? $parameters['3'] : '10';
        }

        /** ---------------------------------------
        /**  Perform Query
        /** ---------------------------------------*/
        $query = ee('Model')->get('ChannelEntry');

        if ($entry_id == '') {
            $query->filter('channel_id', $this->channel_id);
        }

        if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
            //$sql .= "AND wt.author_id = '".$this->userdata['member_id']."' ";
            $query->filter('author_id', $this->userdata['member_id']);
        }

        if ($entry_id != '') {
            //$sql .= "AND wt.entry_id = '{$entry_id}' ";
            $query->filter('entry_id', $entry_id);
        } else {
            //$sql .= str_replace('exp_channels.channel_id','wt.channel_id', $this->channel_sql)." ";
        }

        if ($entry_id == '') {
            //$sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";
            $query->order('entry_date', 'desc')->limit($limit);
        }

        $query = $query->all();

        if (! $query) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entries_found'));
        }

        if (! in_array($this->channel_id, array_keys($this->userdata['assigned_channels'])) && ! ee('Permission')->isSuperAdmin()) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        if ($entry_id != '') {
            $this->parse_channel($this->channel_id);
        }

        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
        if ($this->parse_type === true) {
            ee()->load->library('typography');
            ee()->typography->initialize(
                array(
                    'encode_email' => false)
            );
            ee()->config->set_item('enable_emoticons', 'n');
        }

        /** ---------------------------------------
        /**  Process Output
        /** ---------------------------------------*/
        $settings = array();
        $settings['html_format'] = $this->html_format;
        $settings['auto_links'] = 'n';
        $settings['allow_img_url'] = 'y';

        $response = array();

        foreach ($query as $row) {
            $convert_breaks = 'none';
            $link = reduce_double_slashes(parse_config_variables($this->comment_url) . '/' . $row->url_title . '/');

            // Fields:  Textarea and Text Input Only

            $this->field_data = array('excerpt' => '', 'content' => '', 'more' => '', 'keywords' => '');

            if (isset($this->fields[$this->excerpt_field])) {
                $field_ft = 'field_ft_' . $this->excerpt_field;
                $field_id = 'field_id_' . $this->excerpt_field;

                if ($this->parse_type === true) {
                    $settings['text_format'] = $row->$field_ft;

                    $this->field_data['excerpt'] = ee()->typography->parse_type($row->$field_id, $settings);
                } else {
                    $this->field_data['excerpt'] .= $row->$field_id;
                }
            }

            if (isset($this->fields[$this->content_field])) {
                $field_ft = 'field_ft_' . $this->content_field;
                $field_id = 'field_id_' . $this->content_field;
                $convert_breaks = $row->$field_ft;

                if ($this->parse_type === true) {
                    $settings['text_format'] = $row->$field_ft;

                    $this->field_data['content'] = ee()->typography->parse_type($row->$field_id, $settings);
                } else {
                    $this->field_data['content'] .= $row->$field_id;
                }
            }

            if (isset($this->fields[$this->more_field])) {
                $field_ft = 'field_ft_' . $this->more_field;
                $field_id = 'field_id_' . $this->more_field;

                if ($this->parse_type === true) {
                    $settings['text_format'] = $row->$field_ft;

                    $this->field_data['more'] = ee()->typography->parse_type($row->$field_id, $settings);
                } else {
                    $this->field_data['more'] .= $row->$field_id;
                }
            }

            if (isset($this->fields[$this->keywords_field])) {
                $field_ft = 'field_ft_' . $this->keywords_field;
                $field_id = 'field_id_' . $this->keywords_field;
                if ($this->parse_type === true) {
                    $settings['text_format'] = $row->$field_ft;

                    $this->field_data['keywords'] = ee()->typography->parse_type($row->$field_id, $settings);
                } else {
                    $this->field_data['keywords'] .= $row->$field_id;
                }
            }

            // Categories

            $cat_array = array();

            $sql = "SELECT	exp_categories.cat_id, exp_categories.cat_name
					FROM	exp_category_posts, exp_categories
					WHERE	exp_category_posts.cat_id = exp_categories.cat_id
					AND		exp_category_posts.entry_id = '" . ee()->db->escape_str($row->entry_id) . "'
					ORDER BY cat_id";

            $results = ee()->db->query($sql);

            if ($results->num_rows() > 0) {
                foreach ($results->result_array() as $rrow) {
                    $cat_array[] = array($rrow['cat_name'], 'string');
                    //$cat_array[] = array($rrow['cat_id'], 'string');
                }
            }

            // Entry Data to XML-RPC form
            $entry_data = array(array(
                'userid' =>
                array($row->author_id,'string'),
                'dateCreated' =>
                array(date('Ymd\TH:i:s', $row->entry_date) . 'Z','dateTime.iso8601'),
                'blogid' =>
                array($row->channel_id,'string'),
                'title' =>
                array($row->title, 'string'),
                'mt_excerpt' =>
                array($this->field_data['excerpt'],'string'),
                'description' =>
                array($this->field_data['content'],'string'),
                'mt_text_more' =>
                array($this->field_data['more'],'string'),
                'mt_keywords' =>
                array($this->field_data['keywords'],'string'),
                'mt_convert_breaks' =>
                array($convert_breaks,'string'),
                'postid' =>
                array($row->entry_id,'string'),
                'link' =>
                array($link,'string'),
                'permaLink' =>
                array($link,'string'),
                'categories' =>
                array($cat_array,'array'),
                'mt_allow_comments' =>
                array(($row->allow_comments == 'y') ? 1 : 0,'int')
            ),
                'struct');

            array_push($response, $entry_data);
        }

        if ($entry_id != '') {
            return ee()->xmlrpc->send_response($entry_data);
        } else {
            return ee()->xmlrpc->send_response(array($response, 'array'));
        }
    }

    /**
     * MT API: get recent post title
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getRecentPostTitles($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        /** ---------------------------------------
        /**  Parse Out Channel Information
        /** ---------------------------------------*/
        $this->parse_channel($parameters['0']);
        $limit = (! empty($parameters['3']) && is_numeric($parameters['3'])) ? $parameters['3'] : '10';

        /** ---------------------------------------
        /**  Perform Query
        /** ---------------------------------------*/
        $sql = "SELECT DISTINCT(wt.entry_id), wt.title, wt.channel_id,
				wt.author_id, wt.entry_date
				FROM	exp_channel_titles wt, exp_channel_data
				WHERE wt.entry_id = exp_channel_data.entry_id ";

        if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
            $sql .= "AND wt.author_id = '" . $this->userdata['member_id'] . "' ";
        }

        $sql .= str_replace('exp_channels.channel_id', 'wt.channel_id', $this->channel_sql) . " ";

        $sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entries_found'));
        }

        if (! in_array($query->row('channel_id'), array_keys($this->userdata['assigned_channels'])) && ! ee('Permission')->isSuperAdmin()) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        /** ---------------------------------------
        /**  Process Output
        /** ---------------------------------------*/
        $response = array();

        foreach ($query->result_array() as $row) {
            // Entry Data to XML-RPC form

            $entry_data = array(array(
                'userid' =>
                array($row['author_id'],'string'),
                'dateCreated' =>
                array(date('Ymd\TH:i:s', $row['entry_date']) . 'Z','dateTime.iso8601'),
                'title' =>
                array($row['title'], 'string'),
                'postid' =>
                array($row['entry_id'],'string'),
            ),
                'struct');

            array_push($response, $entry_data);
        }

        return ee()->xmlrpc->send_response(array($response, 'array'));
    }

    /**
     * MT API: get post categories
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function get_postCategories($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        $query = ee()->db->query("SELECT channel_id FROM exp_channel_titles
							 WHERE entry_id = '" . ee()->db->escape_str($parameters['0']) . "'");

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
        }

        if (! ee('Permission')->isSuperAdmin() && ! array_key_exists($query->row('channel_id'), $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        $cats = array();

        $sql = "SELECT	exp_categories.cat_id, exp_categories.cat_name
				FROM	exp_category_posts, exp_categories
				WHERE	exp_category_posts.cat_id = exp_categories.cat_id
				AND		exp_category_posts.entry_id = '" . ee()->db->escape_str($parameters['0']) . "'
				ORDER BY cat_id";

        $query = ee()->db->query($sql);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $cat = array();

                $cat['categoryId'] = array($row['cat_id'],'string');
                $cat['categoryName'] = array($row['cat_name'],'string');

                array_push($cats, array($cat, 'struct'));
            }
        }

        return ee()->xmlrpc->send_response(array($cats, 'array'));
    }

    /**
     * MT API: set post categories
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function setPostCategories($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
            // If there aren't any channels assigned to the user, bail out

            if (count($this->userdata['assigned_channels']) == 0) {
                return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_access'));
            }
        }

        /** ---------------------------------------
        /**  Details from Parameters
        /** ---------------------------------------*/
        $entry_id = ee()->db->escape_str($parameters['0']);

        /** ---------------------------------------
        /**  Retrieve Entry Information
        /** ---------------------------------------*/
        $sql = "SELECT channel_id, author_id
				FROM exp_channel_titles
				WHERE entry_id = '" . $entry_id . "' ";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
        }

        if (! in_array($query->row('channel_id'), array_keys($this->userdata['assigned_channels'])) && ! ee('Permission')->isSuperAdmin()) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->can('edit_other_entries') && ! ee('Permission')->isSuperAdmin()) {
            if ($query->row('author_id') != $this->userdata['member_id']) {
                return ee()->xmlrpc->send_error_message('806', ee()->lang->line('entry_uneditable'));
            }
        }

        $this->channel_id = $query->row('channel_id') ;

        $this->parse_channel($this->channel_id);

        /** ---------------------------------------
        /**  Parse Categories
        /** ---------------------------------------*/
        if (! empty($parameters['3']) && count($parameters['3']) > 0) {
            $cats = array();

            foreach ($parameters['3'] as $cat_data) {
                $cats[] = $cat_data['categoryId'];
            }

            if (count($cats) == 0 && ! empty($this->deft_category)) {
                $cats = array($this->deft_category);
            }

            if (count($cats) > 0) {
                $this->check_categories($cats);
            }
        } else {
            return ee()->xmlrpc->send_response(array(1,'boolean'));
            //return ee()->xmlrpc->send_error_message('802', ee()->lang->line('entry_uneditable'));
        }

        /** ---------------------------------
        /**  Insert Categories, if any
        /** ---------------------------------*/
        ee()->db->query("DELETE FROM exp_category_posts WHERE entry_id = '$entry_id'");

        if (count($this->categories) > 0) {
            foreach ($this->categories as $cat_id => $cat_name) {
                ee()->db->query("INSERT INTO exp_category_posts
							(entry_id, cat_id)
							VALUES
							('" . $entry_id . "', '$cat_id')");
            }
        }

        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        if (ee()->config->item('new_posts_clear_caches') == 'y') {
            ee()->functions->clear_caching('all');
        } else {
            ee()->functions->clear_caching('sql');
        }

        /** ---------------------------------
        /**  Return Boolean TRUE
        /** ---------------------------------*/

        return ee()->xmlrpc->send_response(array(1,'boolean'));
    }

    /**
     * Fetch member data
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function fetch_member_data($username, $password)
    {
        ee()->load->library('auth');

        if (false == ($auth = ee()->auth->authenticate_username($username, $password))) {
            return false;
        }

        // load userdata from Auth object, a few fields from the members table, but most from the group
        $auth->start_session();

        /** -------------------------------------------------
        /**  Find Assigned Channels
        /** -------------------------------------------------*/
        $assigned_channels = ee()->session->getMember()->getAssignedChannels()->getDictionary('channel_id', 'channel_title'); 

        if (empty($assigned_channels)) {
            return false; // Nowhere to Post!!
        }

        $this->userdata['assigned_channels'] = $assigned_channels;

        ee()->session->userdata = array_merge(
            ee()->session->userdata,
            $this->userdata
        );
        $this->userdata = ee()->session->userdata;

        return true;
    }

    /**
     * METAWEBLOG API: get categories
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getCategories($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->isSuperAdmin() && ! array_key_exists($parameters['0'], $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
        }

        $this->parse_channel($parameters['0']);

        $cats = array();

        $sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.cat_description
				FROM exp_categories
				LEFT JOIN exp_channel_category_groups ON exp_categories.group_id = exp_channel_category_groups.group_id
				LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id = exp_channels.channel_id
				WHERE exp_channels.channel_id = '" . ee()->db->escape_str($this->channel_id) . "'";

        $query = ee()->db->query($sql);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $cat = array();

                $link = reduce_double_slashes(parse_config_variables($this->channel_url) . '/C' . $row['cat_id'] . '/');

                $cat['categoryId'] = array($row['cat_id'],'string');
                $cat['description'] = array(($row['cat_description'] == '') ? $row['cat_name'] : $row['cat_description'],'string');
                $cat['categoryName'] = array($row['cat_name'],'string');
                $cat['htmlUrl'] = array($link,'string');
                $cat['rssUrl'] = array($link,'string'); // No RSS URL for Categories

                array_push($cats, array($cat, 'struct'));
            }
        }

        return ee()->xmlrpc->send_response(array($cats, 'array'));
    }

    /**
     * MT API: get category list
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getCategoryList($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->isSuperAdmin() && ! array_key_exists($parameters['0'], $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
        }

        $this->parse_channel($parameters['0']);

        $cats = array();

        $sql = "SELECT exp_categories.cat_id, exp_categories.cat_name
				FROM exp_categories
				LEFT JOIN exp_channel_category_groups ON exp_categories.group_id = exp_channel_category_groups.group_id
				LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id = exp_channels.channel_id
				WHERE exp_channels.channel_id = '" . ee()->db->escape_str($this->channel_id) . "'";

        $query = ee()->db->query($sql);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $cat = array();

                $cat['categoryId'] = array($row['cat_id'],'string');
                $cat['categoryName'] = array($row['cat_name'],'string');

                array_push($cats, array($cat, 'struct'));
            }
        }

        return ee()->xmlrpc->send_response(array($cats, 'array'));
    }

    /**
     * Parses out received channel parameters
     *
     * @access	public
     * @param	int
     * @return	void
     */
    public function parse_channel($channel_id)
    {
        $channel_id = trim($channel_id);
        $this->status = 'open';

        $channel = ee('Model')->get('Channel', $channel_id)->first();

        if (! $channel) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
        }

        $this->channel_id = $channel->channel_id;
        $this->channel_url = parse_config_variables($channel->channel_url);
        $this->comment_url = parse_config_variables($channel->comment_url);
        $this->deft_category = $channel->deft_category;
        $this->html_format = $channel->channel_html_formatting;
        $this->site_id = $channel->site_id;

        if ($this->site_id != ee()->config->item('site_id')) {
            ee()->config->site_prefs('', $this->site_id);

            $this->assign_parents = (ee()->config->item('auto_assign_cat_parents') == 'n') ? false : true;
        }

        if (! array_key_exists($channel->channel_id, $this->userdata['assigned_channels']) && ! ee('Permission')->isSuperAdmin()) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
        }

        /** ---------------------------------------
        /**  Find Fields
        /** ---------------------------------------*/
        $allowed_fieldtypes = array('text', 'textarea', 'rte');
        foreach ($channel->getAllCustomFields() as $field) {
            if (! in_array($field->field_type, $allowed_fieldtypes)) {
                continue;
            }

            $this->fields[$field->field_id] = array($field->field_name, $field->field_fmt);
        }
    }

    /**
     * Check validity of categories
     *
     * @access	public
     * @param	array
     * @return	void
     */
    public function check_categories($array, $debug = '0')
    {
        $categories = array_unique($array);

        $sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.parent_id, exp_categories.group_id
				FROM exp_categories
				LEFT JOIN exp_channel_category_groups ON exp_categories.group_id = exp_channel_category_groups.group_id
				LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id = exp_channels.channel_id
				WHERE exp_channels.channel_id = '" . ee()->db->escape_str($this->channel_id) . "'";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
        }

        $good = 0;
        $all_cats = array();

        // //$this->categories[cat_group_id_1] = array(2,4);

        foreach ($query->result_array() as $row) {
            $all_cats[$row['cat_id']] = $row['cat_name'];

            if (in_array($row['cat_id'], $categories) or in_array($row['cat_name'], $categories)) {
                $good++;

                $this->categories[$row['cat_id']] = $row['cat_id'];
            }
        }

        if ($good < count($categories)) {
            return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
        }
    }

    /**
     * Delete Post
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function deletePost($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['2'], $parameters['3'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->isSuperAdmin() &&
             ! ee('Permission')->can('delete_self_entries') &&
             ! ee('Permission')->can('delete_all_entries')) {
            return ee()->xmlrpc->send_error_message('808', ee()->lang->line('invalid_access'));
        }

        ee()->session->userdata = array_merge(
            ee()->session->userdata,
            array(
                'group_id' => $this->userdata['group_id'],
                'member_id' => $this->userdata['member_id'],
                'assigned_channels' => $this->userdata['assigned_channels']
            )
        );

        // Delete the entry
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_entries');

        $r = ee()->api_channel_entries->delete_entry($parameters['1']);

        if (! $r) {
            $errors = implode(', ', ee()->api_channel_entries->get_errors());

            return ee()->xmlrpc->send_error_message('809', $errors);
        } else {
            return ee()->xmlrpc->send_response(array(1,'boolean'));
        }
    }

    /**
     * METAWEBLOG API: new media object
     *
     * XSS Cleaning is bypassed when uploading a file through MetaWeblog API
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function newMediaObject($plist)
    {
        $parameters = $plist->output_parameters();

        if ($this->upload_dir == '') {
            return ee()->xmlrpc->send_error_message('801', ee()->lang->line('invalid_access'));
        }

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (! ee('Permission')->isSuperAdmin() && ! array_key_exists($parameters['0'], $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
        }

        if (! ee('Permission')->isSuperAdmin()) {
            $assigned_upload_dest = ee()->session->getMember()->getAssignedUploadDestinations()->indexBy('id');

            if (! isset($assigned_upload_dest[$this->upload_dir])) {
                return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
            }
        }

        ee()->load->model('file_upload_preferences_model');

        $upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(null, $this->upload_dir);

        if (empty($upload_prefs)) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        /** -------------------------------------
        /**  upload the image
        /** -------------------------------------*/
        ee()->load->library('filemanager');

        // Disable XSS Filtering
        ee()->filemanager->xss_clean_off();

        // Figure out the FULL file path
        $file_path = ee()->filemanager->clean_filename(
            $parameters['3']['name'],
            $this->upload_dir,
            array('ignore_dupes' => false)
        );

        $filename = basename($file_path);

        // Check to see if we're dealing with relative paths
        if (strncmp($file_path, '..', 2) == 0) {
            $directory = dirname($file_path);
            $file_path = realpath(substr($directory, 1)) . '/' . $filename;
        }

        // Upload the file
        $config = array('upload_path' => dirname($file_path));
        ee()->load->library('upload', $config);

        if (ee()->upload->raw_upload($filename, $parameters['3']['bits']) === false) {
            return ee()->xmlrpc->send_error_message(
                '810',
                ee()->lang->line('unable_to_upload')
            );
        }

        // Send the file
        $result = ee()->filemanager->save_file(
            $file_path,
            $this->upload_dir,
            array(
                'title' => $filename,
                'path' => dirname($file_path),
                'file_name' => $filename
            )
        );

        // Check to see the result
        if ($result['status'] === false) {
            ee()->xmlrpc->send_error_message(
                '810',
                $result['message']
            );
        }

        // Build XMLRPC response
        $response = array(
            array(
                'url' => array(
                    $upload_prefs['url'] . $filename,
                    'string'
                ),
            ),
            'struct'
        );

        return ee()->xmlrpc->send_response($response);
    }

    /**
     * BLOGGER API: send user information
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getUserInfo($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        $response = array(array(
            'nickname' =>
            array($this->userdata['screen_name'],'string'),
            'userid' =>
            array($this->userdata['member_id'],'string'),
            'url' =>
            array($this->userdata['url'],'string'),
            'email' =>
            array($this->userdata['email'],'string'),
            'lastname' =>
            array('','string'),
            'firstname' =>
            array('','string')
        ),
            'struct');

        return ee()->xmlrpc->send_response($response);
    }

    /**
     * METAWEBLOG API: get user's blogs
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function getUsersBlogs($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        ee()->db->select('channel_id, channel_title, channel_url');
        ee()->db->where_in('channel_id', array_keys($this->userdata['assigned_channels']));

        $query = ee()->db->get('channels');

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('no_channels_found'));
        }

        $response = array();

        foreach ($query->result_array() as $row) {
            $channel = array(array(
                "url" =>
                array(parse_config_variables($row['channel_url']),"string"),
                "blogid" =>
                array($row['channel_id'], "string"),
                "blogName" =>
                array($row['channel_title'], "string")),'struct');

            array_push($response, $channel);
        }

        return ee()->xmlrpc->send_response(array($response, 'array'));
    }

    /**
     * ISO-8601 time to server or UTC time
     *
     * @access	public
     * @param	time
     * @return	void
     */
    public function iso8601_decode($time, $utc = true)
    {
        // return a time in the localtime, or UTC
        $t = 0;

        if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $time, $regs)) {
            /*
            if ($utc === TRUE)
            {
                $t = gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);

                $time_difference = (ee()->config->item('server_offset') == '') ? 0 : ee()->config->item('server_offset');

                $server_time = time()+date('Z');
                $offset_time = $server_time + $time_difference*60;
                $gmt_time = time();

                $diff_gmt_server = ($gmt_time - $server_time) / 3600;
                $diff_blogger_server = ($offset_time - $server_time) / 3600;
                $diff_gmt_blogger = $diff_gmt_server - $diff_blogger_server;
                $gmt_offset = -$diff_gmt_blogger;

                $t -= $gmt_offset;
            }
            */

            $t = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
        }

        return $t;
    }

    /**
     * MT API:  supportedTextFilters
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function supportedTextFilters($plist)
    {
        $plugin_list = $this->fetch_plugins();

        $plugins = array();

        foreach ($plugin_list as $val) {
            $name = ucwords(str_replace('_', ' ', $val));

            if ($name == 'Br') {
                $name = ee()->lang->line('auto_br');
            } elseif ($name == 'Xhtml') {
                $name = ee()->lang->line('xhtml');
            }

            $plugin = array(array('key' => array($val,'string'),
                'label' => array($name,'string')
            ),
                'struct');

            array_push($plugins, $plugin);
        }

        return ee()->xmlrpc->send_response(array($plugins, 'array'));
    }

    /**
     * Fetch installed plugins
     *
     * @access	public
     * @return	void
     */
    public function fetch_plugins()
    {
        // Always available
        $plugins = array('br', 'xhtml');

        // Additional first or third-party plugins
        ee()->load->library('addons');

        foreach (ee()->addons->get_files('plugins') as $plugin) {
            $plugins[] = strtolower($plugin['class']);
        }

        sort($plugins);

        // Add None as the first option
        $plugins = array_merge(array('none'), $plugins);

        return $plugins;
    }

    /**
     * Get Settings for the channel
     *
     *
     *
     */
    public function get_settingsZZ($channel_id, $which = 'new')
    {
        ee()->load->model('channel_model');
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        ee()->db->select('field_group');
        ee()->db->where('channel_id', $channel_id);
        $field_group = ee()->db->get('channels');

        $field_query = ee()->channel_model->get_channel_fields($field_group->row('field_group'));

        foreach ($field_query->result_array() as $row) {
            $field_data = '';
            $field_fmt = '';

            if ($which == 'edit') {
                $field_data = (! isset($resrow['field_id_' . $row['field_id']])) ? '' : $resrow['field_id_' . $row['field_id']];
                $field_fmt = (! isset($resrow['field_ft_' . $row['field_id']])) ? $row['field_fmt'] : $resrow['field_ft_' . $row['field_id']];
            } else { // New entry- use the default setting
                $field_fmt = $row['field_fmt'];
            }

            // Settings that need to be prepped
            $settings = array(
                'field_instructions' => trim($row['field_instructions']),
                'field_text_direction' => ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
                'field_fmt' => $field_fmt,
                'field_data' => $field_data,
                'field_name' => 'field_id_' . $row['field_id'],
            );

            $ft_settings = array();

            if (isset($row['field_settings']) && strlen($row['field_settings'])) {
                $ft_settings = unserialize(base64_decode($row['field_settings']));
            }

            $settings = array_merge($row, $settings, $ft_settings);

            ee()->api_channel_fields->set_settings($row['field_id'], $settings);
        }
    }
}

// EOF
