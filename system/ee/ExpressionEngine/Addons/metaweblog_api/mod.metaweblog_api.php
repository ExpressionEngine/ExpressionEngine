<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Member\Member;

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

    public $id = '';						// Configuration ID
    public $pref_name = '';						// Configuration Name

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

    public $max_request_size = 4194304;				// Largest request body accepted, in bytes (override: metaweblog_max_request_size)

    protected $member = null;					// Member model of the authenticated member
    protected $channel_sites = array();				// Site ID of each channel assigned to the member

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
        $max_request_size = (int) ee()->config->item('metaweblog_max_request_size');

        ee()->xmlrpcs->initialize(array(
            'functions' => $functions,
            'object' => $this,
            'xss_clean' => false,
            'max_request_size' => ($max_request_size > 0) ? $max_request_size : $this->max_request_size
        ));
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

        if (($error = $this->parse_channel($this->channel_id)) !== null) {
            return $error;
        }

        if (! $this->member_can('create_entries', $this->channel_id)) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        if (($parameters['3'] = $this->post_struct($parameters)) === false) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('new_entry_errors'));
        }

        if (($error = $this->new_entry()) !== null) {
            return $error;
        }

        if ($this->has_entry_status()) {
            $this->status = $this->entry_status;
        } else {
            $this->status = $this->wants_publish($parameters) ? 'open' : 'closed';
        }

        $this->title = $parameters['3']['title'];

        $this->field_data['excerpt'] = (! isset($parameters['3']['mt_excerpt'])) ? '' : $parameters['3']['mt_excerpt'];
        $this->field_data['content'] = (! isset($parameters['3']['description'])) ? '' : $parameters['3']['description'];
        $this->field_data['more'] = (! isset($parameters['3']['mt_text_more'])) ? '' : $parameters['3']['mt_text_more'];
        $this->field_data['keywords'] = (! isset($parameters['3']['mt_keywords'])) ? '' : $parameters['3']['mt_keywords'];

        if (isset($parameters['3']['mt_allow_comments'])) {
            $deft_comments = ($parameters['3']['mt_allow_comments'] == 1) ? 'y' : 'n';
        }

        // Default category was set in new_entry()
        if (isset($parameters['3']['categories'])) {
            if (count($parameters['3']['categories']) > 0) {
                $cats = array();

                foreach ($parameters['3']['categories'] as $cat) {
                    if (trim($cat) != '') {
                        $cats[] = $cat;
                    }
                }

                if (count($cats) > 0 && ($error = $this->check_categories(array_unique($cats))) !== null) {
                    return $error;
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
        $mssg = '';

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

        $entry_id = $parameters['0'];

        if (($error = $this->fetch_entry($entry_id)) !== null) {
            return $error;
        }

        if (($error = $this->parse_channel($this->channel_id)) !== null) {
            return $error;
        }

        if (($parameters['3'] = $this->post_struct($parameters)) === false) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('new_entry_errors'));
        }

        if ($this->has_entry_status()) {
            $this->status = $this->entry_status;
        } else {
            $this->status = $this->wants_publish($parameters) ? 'open' : 'closed';
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

                if (count($cats) > 0 && ($error = $this->check_categories(array_unique($cats))) !== null) {
                    return $error;
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
            $mssg = '';

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

    /**
     * A record ID sent by the client: a whole number above zero
     *
     * @access	protected
     * @param	mixed
     * @return	int|bool	The ID, or FALSE for anything else
     */
    protected function valid_id($id)
    {
        if (is_string($id)) {
            $id = trim($id);
            $id = (ctype_digit($id) && strlen($id) <= 10) ? (int) $id : false;
        }

        return (is_int($id) && $id > 0) ? $id : false;
    }

    /**
     * The post struct of newPost and editPost, with members of the expected types
     *
     * @access	protected
     * @param	array	Request parameters
     * @return	array|bool	FALSE when the struct or one of its members has the wrong type
     */
    protected function post_struct($parameters)
    {
        if (! isset($parameters['3']) || ! is_array($parameters['3'])) {
            return false;
        }

        $post = $parameters['3'];

        foreach (array('title', 'description', 'mt_excerpt', 'mt_text_more', 'mt_keywords', 'mt_convert_breaks', 'mt_allow_comments', 'dateCreated') as $member) {
            if (isset($post[$member]) && ! is_scalar($post[$member])) {
                return false;
            }
        }

        $post['title'] = isset($post['title']) ? (string) $post['title'] : '';

        if (isset($post['categories'])) {
            if (! is_array($post['categories'])) {
                return false;
            }

            $categories = array();

            foreach ($post['categories'] as $category) {
                // An empty list is decoded as a single empty element
                if ($category === null) {
                    continue;
                }

                if (! is_string($category) && ! is_int($category)) {
                    return false;
                }

                $categories[] = (string) $category;
            }

            $post['categories'] = $categories;
        }

        return $post;
    }

    /**
     * Whether the publish flag of newPost and editPost asks for an open entry
     *
     * @access	protected
     * @param	array	Request parameters
     * @return	bool
     */
    protected function wants_publish($parameters)
    {
        return ! (isset($parameters['4']) && is_scalar($parameters['4']) && $parameters['4'] == '0');
    }

    /**
     * Start a new entry in the current channel, for newPost() only
     *
     * @access	protected
     * @return	object|null	Error response, or NULL when the entry is ready
     */
    protected function new_entry()
    {
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

        return null;
    }

    /**
     * Load an existing entry that the authenticated member may edit
     *
     * @access	public
     * @param	mixed	Entry ID as sent by the client
     * @return	object|null	Error response, or NULL when the entry is loaded
     */
    public function fetch_entry($entry_id)
    {
        if (($entry_id = $this->valid_id($entry_id)) === false) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
        }

        // The entry may belong to any site: access follows its channel
        $query = ee('Model')->get('ChannelEntry')->with('Channel');
        $query->filter('entry_id', $entry_id);

        $entry = $query->first();

        if (! isset($entry)) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
        }

        if (($error = $this->check_entry_access($entry->channel_id, $entry->author_id)) !== null) {
            return $error;
        }

        $this->entry = $entry;
        $this->title = $this->entry->title;
        $this->channel_id = $this->entry->channel_id;
    }

    /**
     * Whether the authenticated member holds an entry permission in a channel
     *
     * @access	protected
     * @param	string	create_entries, edit_self_entries, edit_other_entries, delete_self_entries or delete_all_entries
     * @param	int
     * @return	bool
     */
    protected function member_can($action, $channel_id)
    {
        if (! is_scalar($channel_id) || ! array_key_exists($channel_id, $this->userdata['assigned_channels']) || ! isset($this->channel_sites[$channel_id])) {
            return false;
        }

        if ($this->member->isSuperAdmin()) {
            return true;
        }

        // Roles hold their permissions per site, and a channel may belong to
        // another site than the one serving the request
        $permissions = $this->member->getPermissionsForSite($this->channel_sites[$channel_id]);

        return array_key_exists('can_' . $action . '_channel_id_' . (int) $channel_id, $permissions);
    }

    /**
     * Which entries of a channel the authenticated member may open, by
     * the same per-channel permissions the Control Panel uses
     *
     * @access	protected
     * @param	int
     * @return	string	all, self, others or none
     */
    protected function editable_authors($channel_id)
    {
        $self = $this->member_can('edit_self_entries', $channel_id);
        $others = $this->member_can('edit_other_entries', $channel_id);

        if ($self && $others) {
            return 'all';
        }

        return $self ? 'self' : ($others ? 'others' : 'none');
    }

    /**
     * Check that the authenticated member may open an entry
     *
     * @access	protected
     * @param	int		Channel of the entry
     * @param	int		Author of the entry
     * @return	object|null	Error response, or NULL when allowed
     */
    protected function check_entry_access($channel_id, $author_id)
    {
        if (! array_key_exists($channel_id, $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        $action = ($author_id == $this->member->member_id) ? 'edit_self_entries' : 'edit_other_entries';

        if (! $this->member_can($action, $channel_id)) {
            return ee()->xmlrpc->send_error_message('806', ee()->lang->line('entry_uneditable'));
        }

        return null;
    }

    /**
     * Whether the configuration sets a status for every entry
     *
     * @access	protected
     * @return	bool
     */
    protected function has_entry_status()
    {
        return $this->entry_status != '' && strtolower($this->entry_status) != 'null';
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
        $parameters = $plist->output_parameters();

        if (! isset($parameters['2']) || ! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        // Only for an entry the member may edit
        if (($error = $this->fetch_entry($parameters['0'])) !== null) {
            return $error;
        }

        // The caches cleared below are those of the entry's site
        if (($error = $this->parse_channel($this->channel_id)) !== null) {
            return $error;
        }

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

        return $this->getRecentPosts($plist, isset($parameters['0']) ? $parameters['0'] : false);
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
        $single_entry = (func_num_args() > 1);

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if ($single_entry) {
            /** ---------------------------------------
            /**  A single entry: access follows the entry's own channel
            /** ---------------------------------------*/
            $entry_id = $this->valid_id($entry_id);

            $entry = ($entry_id !== false)
                ? ee('Model')->get('ChannelEntry')->filter('entry_id', $entry_id)->first()
                : null;

            if (! $entry) {
                return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
            }

            if (($error = $this->check_entry_access($entry->channel_id, $entry->author_id)) !== null) {
                return $error;
            }

            if (($error = $this->parse_channel($entry->channel_id)) !== null) {
                return $error;
            }

            $query = array($entry);
        } else {
            /** ---------------------------------------
            /**  Parse Out Channel Information
            /** ---------------------------------------*/
            if (($error = $this->parse_channel($parameters['0'])) !== null) {
                return $error;
            }

            $limit = (! empty($parameters['3']) && is_numeric($parameters['3'])) ? max(1, (int) $parameters['3']) : 10;

            /** ---------------------------------------
            /**  Perform Query
            /** ---------------------------------------*/
            $authors = $this->editable_authors($this->channel_id);

            if ($authors == 'none') {
                return ee()->xmlrpc->send_response(array(array(), 'array'));
            }

            $query = ee('Model')->get('ChannelEntry')->filter('channel_id', $this->channel_id);

            if ($authors == 'self') {
                $query->filter('author_id', $this->member->member_id);
            } elseif ($authors == 'others') {
                $query->filter('author_id', '!=', $this->member->member_id);
            }

            $query = $query->order('entry_date', 'desc')->limit($limit)->all();
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

        if ($single_entry) {
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
        if (($error = $this->parse_channel($parameters['0'])) !== null) {
            return $error;
        }

        $limit = (! empty($parameters['3']) && is_numeric($parameters['3'])) ? max(1, (int) $parameters['3']) : 10;

        /** ---------------------------------------
        /**  Perform Query
        /** ---------------------------------------*/
        $authors = $this->editable_authors($this->channel_id);

        if ($authors == 'none') {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entries_found'));
        }

        $sql = "SELECT DISTINCT(wt.entry_id), wt.title, wt.channel_id,
				wt.author_id, wt.entry_date
				FROM	exp_channel_titles wt, exp_channel_data
				WHERE wt.entry_id = exp_channel_data.entry_id
				AND wt.channel_id = '" . ee()->db->escape_str($this->channel_id) . "' ";

        if ($authors == 'self') {
            $sql .= "AND wt.author_id = '" . ee()->db->escape_str($this->member->member_id) . "' ";
        } elseif ($authors == 'others') {
            $sql .= "AND wt.author_id != '" . ee()->db->escape_str($this->member->member_id) . "' ";
        }

        $sql .= "ORDER BY entry_date desc LIMIT 0, {$limit}";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entries_found'));
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

        if (($entry_id = $this->valid_id($parameters['0'])) === false) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
        }

        $query = ee()->db->query("SELECT channel_id, author_id FROM exp_channel_titles
							 WHERE entry_id = '" . ee()->db->escape_str($entry_id) . "'");

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
        }

        if (($error = $this->check_entry_access($query->row('channel_id'), $query->row('author_id'))) !== null) {
            return $error;
        }

        $cats = array();

        $sql = "SELECT	exp_categories.cat_id, exp_categories.cat_name
				FROM	exp_category_posts, exp_categories
				WHERE	exp_category_posts.cat_id = exp_categories.cat_id
				AND		exp_category_posts.entry_id = '" . ee()->db->escape_str($entry_id) . "'
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

        /** ---------------------------------------
        /**  Details from Parameters
        /** ---------------------------------------*/
        if (($entry_id = $this->valid_id($parameters['0'])) === false) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
        }

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

        if (($error = $this->check_entry_access($query->row('channel_id'), $query->row('author_id'))) !== null) {
            return $error;
        }

        $this->channel_id = $query->row('channel_id') ;

        if (($error = $this->parse_channel($this->channel_id)) !== null) {
            return $error;
        }

        /** ---------------------------------------
        /**  Parse Categories
        /** ---------------------------------------*/
        if (isset($parameters['3']) && is_array($parameters['3'])) {
            $cats = array();

            foreach ($parameters['3'] as $cat_data) {
                // An empty list, which removes the categories, is decoded as a single empty element
                if ($cat_data === null) {
                    continue;
                }

                if (! is_array($cat_data) || ! isset($cat_data['categoryId'])) {
                    return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
                }

                $cats[] = $cat_data['categoryId'];
            }

            // An empty list leaves the entry without categories: the
            // channel's default category applies to new entries only
            if (count($cats) > 0 && ($error = $this->check_categories($cats)) !== null) {
                return $error;
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
        $authenticated = $this->authenticate($username, $password);

        // The response carries no cookies, whatever the outcome
        header_remove('Set-Cookie');

        return $authenticated;
    }

    /**
     * Whether a request value can be stored in any installation's tables:
     * valid UTF-8 with no character longer than three bytes
     *
     * @access	protected
     * @param	string
     * @return	bool
     */
    protected function storable($value)
    {
        return preg_match('/[\x{10000}-\x{10FFFF}]/u', $value) === 0;
    }

    /**
     * Whether the content of the authenticated member's uploads is
     * inspected: the same rules as xss_check() applies to uploads in the
     * Control Panel, for the member of this request rather than the
     * session the request arrived with
     *
     * @access	protected
     * @return	bool
     */
    protected function inspects_uploads()
    {
        if ($this->member->isSuperAdmin() || ee()->config->item('xss_clean_uploads') == 'n') {
            return false;
        }

        $member_exceptions = preg_split('/[\s|,]/', (string) ee()->config->item('xss_clean_member_exception'), -1, PREG_SPLIT_NO_EMPTY);

        if (in_array((string) $this->member->member_id, $member_exceptions, true)) {
            return false;
        }

        $role_exceptions = preg_split('/[\s|,]/', (string) ee()->config->item('xss_clean_member_group_exception'), -1, PREG_SPLIT_NO_EMPTY);
        $roles = $this->member->getAllRoles();

        foreach (array_merge($roles->pluck('role_id'), $roles->pluck('name')) as $role) {
            if (in_array((string) $role, $role_exceptions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check the credentials of a request and describe the member to the
     * rest of the request
     *
     * @access	protected
     * @param	mixed	Username as sent by the client
     * @param	mixed	Password as sent by the client
     * @return	bool
     */
    protected function authenticate($username, $password)
    {
        ee()->load->library('auth');

        if (! is_string($username) || ! is_string($password) || $username === '' || $password === '') {
            return false;
        }

        // Nothing longer than an account can have, and nothing that could not
        // be recorded with the attempt: the username, and the user agent as
        // the session library stores it (its first 120 bytes)
        $username_length = preg_match_all('/./us', $username);
        $stored_user_agent = substr((string) ee()->input->user_agent(), 0, 120);

        if (
            $username_length === false ||
            $username_length > USERNAME_MAX_LENGTH ||
            strlen($password) > PASSWORD_MAX_LENGTH ||
            ! $this->storable($username) ||
            ! $this->storable($stored_user_agent)
        ) {
            return false;
        }

        // Same password lockout accounting as the login forms
        if (ee()->session->check_password_lockout($username) === true) {
            return false;
        }

        // Banned, guest and pending accounts never sign in here. They are
        // answered like any other failed attempt.
        $account = ee()->db->select('role_id')->where('username', $username)->get('members');
        $excluded_roles = array(Member::BANNED, Member::GUESTS, Member::PENDING);

        if (
            ($account->num_rows() === 1 && in_array($account->row('role_id'), $excluded_roles)) ||
            false == ($auth = ee()->auth->authenticate_username($username, $password))
        ) {
            // The answer is the same whether or not the attempt could be recorded
            try {
                ee()->session->save_password_lockout($username);
            } catch (\Exception $e) {
                log_message('error', 'MetaWeblog API: a failed sign-in attempt could not be recorded.');
            }

            return false;
        }

        if ($auth->is_banned()) {
            return false;
        }

        $member = ee('Model')->get('Member', $auth->member('member_id'))->with('PrimaryRole', 'Roles', 'RoleGroups')->first();

        // Banned through any role, as the member model sees it. There is no
        // second step in this API, so members who must use MFA cannot use it.
        if (! $member || $member->isBanned() || ee()->auth->requires_mfa($member)) {
            return false;
        }

        // A session that came with the request, valid or not, must not belong to someone else
        $session_member = ee()->session->getMember();

        foreach (array($session_member ? $session_member->member_id : 0, ee()->session->userdata('member_id')) as $session_member_id) {
            if ((int) $session_member_id !== 0 && (int) $session_member_id !== (int) $member->member_id) {
                return false;
            }
        }

        /** -------------------------------------------------
        /**  Find Assigned Channels
        /** -------------------------------------------------*/
        $channels = $member->getAssignedChannels();
        $assigned_channels = $channels->getDictionary('channel_id', 'channel_title');

        if (empty($assigned_channels)) {
            return false; // Nowhere to Post!!
        }

        // Entry and file code reads the current member from the session
        // library. Every call carries its own credentials, so the member is
        // described there for this request only: no session is kept.
        try {
            $session_id = ee()->session->create_new_session($member->member_id);
            ee()->db->where('session_id', $session_id)->delete('sessions');
        } catch (\Exception $e) {
            return false;
        }

        // Expired failed attempts are pruned, as after any other sign-in
        try {
            ee()->session->delete_password_lockout();
        } catch (\Exception $e) {
            log_message('error', 'MetaWeblog API: expired sign-in attempts could not be removed.');
        }

        $this->member = $member;
        $this->channel_sites = $channels->getDictionary('channel_id', 'site_id');
        $this->userdata['assigned_channels'] = $assigned_channels;

        // Who the member is, as a sign-in with a kept session would describe them
        foreach (array('username', 'screen_name', 'email') as $key) {
            $this->userdata[$key] = $member->$key;
        }

        $this->userdata['group_id'] = $member->role_id;

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

        if (($error = $this->parse_channel($parameters['0'])) !== null) {
            return $error;
        }

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

        if (($error = $this->parse_channel($parameters['0'])) !== null) {
            return $error;
        }

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
     * @return	object|null	Error response, or NULL when the member may use the channel
     */
    public function parse_channel($channel_id)
    {
        $channel_id = $this->valid_id($channel_id);
        $this->status = 'open';

        $channel = ($channel_id !== false) ? ee('Model')->get('Channel', $channel_id)->first() : null;

        if (! $channel) {
            return ee()->xmlrpc->send_error_message('804', ee()->lang->line('invalid_channel'));
        }

        if (! array_key_exists($channel->channel_id, $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
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
     * @return	object|null	Error response, or NULL when every category belongs to the channel
     */
    public function check_categories($array, $debug = '0')
    {
        $categories = array();

        // Category IDs or names only
        foreach ((array) $array as $category) {
            if (! is_string($category) && ! is_int($category)) {
                return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
            }

            $categories[] = (string) $category;
        }

        $categories = array_unique($categories);

        // An ID may come with surrounding spaces or leading zeros, as the
        // channel and entry IDs may
        $category_ids = array();

        foreach ($categories as $category) {
            $id = trim($category);
            $category_ids[$category] = ctype_digit($id) ? ltrim($id, '0') : null;
        }

        $sql = "SELECT exp_categories.cat_id, exp_categories.cat_name, exp_categories.parent_id, exp_categories.group_id
				FROM exp_categories
				LEFT JOIN exp_channel_category_groups ON exp_categories.group_id = exp_channel_category_groups.group_id
				LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id = exp_channels.channel_id
				WHERE exp_channels.channel_id = '" . ee()->db->escape_str($this->channel_id) . "'";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
        }

        // //$this->categories[cat_group_id_1] = array(2,4);

        // Every category asked for must be one of the channel's, by ID or
        // name exactly as sent, or else by its ID written another way
        foreach ($categories as $category) {
            $found = array();

            foreach ($query->result_array() as $row) {
                if ($category === (string) $row['cat_id'] || $category === $row['cat_name']) {
                    $found[] = $row['cat_id'];
                }
            }

            if (empty($found)) {
                foreach ($query->result_array() as $row) {
                    if ($category_ids[$category] === (string) $row['cat_id']) {
                        $found[] = $row['cat_id'];
                    }
                }
            }

            if (empty($found)) {
                return ee()->xmlrpc->send_error_message('807', ee()->lang->line('invalid_categories'));
            }

            foreach ($found as $cat_id) {
                $this->categories[$cat_id] = $cat_id;
            }
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

        $entry_id = $this->valid_id($parameters['1']);

        $entry = ($entry_id !== false)
            ? ee('Model')->get('ChannelEntry')->filter('entry_id', $entry_id)->first()
            : null;

        if (! $entry) {
            return ee()->xmlrpc->send_error_message('805', ee()->lang->line('no_entry_found'));
        }

        if (! array_key_exists($entry->channel_id, $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        // Same per-channel permissions as deleting in the Control Panel
        $action = ($entry->author_id == $this->member->member_id) ? 'delete_self_entries' : 'delete_all_entries';

        if (! $this->member_can($action, $entry->channel_id)) {
            return ee()->xmlrpc->send_error_message('808', ee()->lang->line('invalid_access'));
        }

        // Deleted as part of its own site, so that site's records of the
        // entry (such as its page URI) go with it
        if (($error = $this->parse_channel($entry->channel_id)) !== null) {
            return $error;
        }

        // Delete the entry
        $entry->delete();

        ee()->functions->clear_caching('all', '');

        // -------------------------------------------
        // 'delete_entries_end' hook.
        //  - Add additional processing for entry deletion
        //
        ee()->extensions->call('delete_entries_end');
        //
        // -------------------------------------------

        return ee()->xmlrpc->send_response(array(1,'boolean'));
    }

    /**
     * METAWEBLOG API: new media object
     *
     * @access	public
     * @param	parameter list
     * @return	void
     */
    public function newMediaObject($plist)
    {
        $parameters = $plist->output_parameters();

        if (! $this->fetch_member_data($parameters['1'], $parameters['2'])) {
            return ee()->xmlrpc->send_error_message('802', ee()->lang->line('invalid_access'));
        }

        if (empty($this->upload_dir)) {
            return ee()->xmlrpc->send_error_message('801', ee()->lang->line('invalid_access'));
        }

        $blog_id = $this->valid_id($parameters['0']);

        if ($blog_id === false || ! array_key_exists($blog_id, $this->userdata['assigned_channels'])) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_channel'));
        }

        // Same requirements as uploading in the Control Panel: the upload
        // permission and access to a directory of the file manager (not one
        // that belongs to an add-on, such as avatars)
        if (! $this->member->can('upload_new_files')) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        $destination = ee('Model')->get('UploadDestination', $this->upload_dir)
            ->filter('site_id', 'IN', array(0, ee()->config->item('site_id')))
            ->filter('module_id', 0)
            ->first();

        if (! $destination || ! in_array($destination->getId(), $this->member->getAssignedUploadDestinations()->pluck('id'))) {
            return ee()->xmlrpc->send_error_message('803', ee()->lang->line('invalid_access'));
        }

        if (! isset($parameters['3']['name'], $parameters['3']['bits']) || ! is_string($parameters['3']['name']) || ! is_string($parameters['3']['bits'])) {
            return ee()->xmlrpc->send_error_message('810', ee()->lang->line('unable_to_upload'));
        }

        /** -------------------------------------
        /**  upload the image
        /** -------------------------------------*/
        ee()->load->library('filemanager');

        $dir = array_merge($destination->toArray(), array('upload_destination' => $destination));

        // Only the name of the file is used, never a path sent with it
        $filename = basename(ee()->filemanager->clean_subdir_and_filename(
            basename(str_replace('\\', '/', $parameters['3']['name'])),
            $dir['id'],
            array('ignore_dupes' => true)
        ));

        // The directory's own limits apply, and the content of the file is
        // inspected as for an upload in the Control Panel
        $inspect = $this->inspects_uploads();

        if (! $inspect) {
            ee()->filemanager->xss_clean_off();
        }

        $config = array(
            'upload_destination' => $destination,
            'file_name' => $filename,
            'upload_path' => $dir['server_path'],
            'max_size' => round((int) $dir['max_size'], 3),
            'max_width' => $dir['max_width'],
            'max_height' => $dir['max_height'],
            'auto_resize' => true,
            'xss_clean' => $inspect
        );

        if ($dir['allowed_types'] == array('img')) {
            $config['is_image'] = true;
        }

        ee()->load->library('upload');
        ee()->upload->initialize($config);

        $uploaded = ee()->upload->raw_upload($filename, $parameters['3']['bits']);

        // raw_upload() keeps its working copy of the data, remove it when done
        $temp_file = isset($_FILES['userfile']['tmp_name']) ? $_FILES['userfile']['tmp_name'] : '';

        if ($uploaded === false) {
            @unlink($temp_file);

            return ee()->xmlrpc->send_error_message(
                '810',
                trim(ee()->lang->line('unable_to_upload') . ' ' . strip_tags(ee()->upload->display_errors('', ' ')))
            );
        }

        $file = ee()->upload->data();

        // Record the file
        $result = array('status' => false, 'message' => ee()->lang->line('unable_to_upload'));

        if ($file['file_type']) {
            $result = ee()->filemanager->save_file(
                $destination->getFilesystem()->relative($file['full_path']),
                $dir,
                array(
                    'upload_location_id' => $dir['id'],
                    'directory_id' => 0,
                    'site_id' => ee()->config->item('site_id'),
                    'title' => $file['file_name'],
                    'file_name' => $file['file_name'],
                    'orig_name' => $parameters['3']['name'],
                    'file_data_orig_name' => $file['orig_name'],
                    'is_image' => $file['is_image'],
                    'mime_type' => $file['file_type'],
                    'modified_by_member_id' => $this->member->member_id,
                    'uploaded_by_member_id' => $this->member->member_id,
                    'file_size' => $file['file_size'] * 1024,
                    'file_height' => $file['image_height'],
                    'file_width' => $file['image_width'],
                    'file_hw_original' => $file['image_height'] . ' ' . $file['image_width'],
                    'max_width' => $dir['max_width'],
                    'max_height' => $dir['max_height'],
                    'temp_file' => $temp_file,
                    'image_processed' => $file['image_processed']
                )
            );
        }

        @unlink($temp_file);

        // A file that was not accepted does not stay in the directory, on
        // whichever storage the directory uses
        if ($result['status'] === false) {
            try {
                $filesystem = $destination->getFilesystem();
                $stored = $filesystem->relative($file['full_path']);

                if ($filesystem->exists($stored)) {
                    $filesystem->delete($stored);
                }
            } catch (\Exception $e) {
                log_message('error', 'MetaWeblog API: a file that was not accepted could not be removed.');
            }

            ee()->filemanager->_upload_error(
                $result['message'],
                array('file_name' => $file['file_name'], 'directory_id' => $dir['id'])
            );

            return ee()->xmlrpc->send_error_message('810', $result['message']);
        }

        // Build XMLRPC response
        $response = array(
            array(
                'url' => array(
                    $destination->url . $file['file_name'],
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
        // The MT API calls this method without credentials, so it only names
        // the formatting options every installation has
        $plugin_list = array('none', 'br', 'xhtml');

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
