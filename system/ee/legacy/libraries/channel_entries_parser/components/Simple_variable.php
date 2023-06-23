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
 * Channel Parser Component (Basic Variables)
 */
class EE_Channel_simple_variable_parser implements EE_Channel_parser_component
{
    // bring in the :modifier methods
    use ExpressionEngine\Service\Template\Variables\ModifiableTrait;

    public $conditional_vars = array();

    /**
     * There are always simple variables. Let me tell you ...
     *
     * @param array     A list of "disabled" features
     * @return Boolean  Is disabled?
     */
    public function disabled(array $disabled, EE_Channel_preparser $pre)
    {
        return false;
    }

    /**
     * Parse out $search_link for the {member_search_path} variable
     *
     * @param String    The tagdata to be parsed
     * @param Object    The preparser object.
     * @return String   The $search_link path
     */
    public function pre_process($tagdata, EE_Channel_preparser $pre)
    {
        $result_path = (preg_match("/" . LD . $pre->prefix() . "member_search_path\s*=(.*?)" . RD . "/s", $tagdata, $match)) ? $match[1] : 'search/results';
        $result_path = str_replace(array('"',"'"), "", $result_path);

        return (strpos($tagdata, 'member_search_path') !== false) ? ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Search', 'do_search') . '&amp;result_path=' . $result_path . '&amp;mbr=' : '';
    }

    /**
     * Replace all variables.
     *
     * @param String    The tagdata to be parsed
     * @param Object    The channel parser object
     * @param Mixed     The results from the preparse method
     *
     * @return String   The processed tagdata
     */
    public function replace($tagdata, EE_Channel_data_parser $obj, $search_link)
    {
        $tag = $obj->tag();
        $tag_options = $obj->tag_options();

        $data = $obj->row();
        $prefix = $obj->prefix();

        $overrides = ee()->config->get_cached_site_prefs($data['entry_site_id']);
        $data['channel_url'] = parse_config_variables($data['channel_url'], $overrides);
        $data['comment_url'] = parse_config_variables($data['comment_url'], $overrides);

        // I decided to split the huge if statement into educated guesses
        // so we spend less time doing silly comparisons
        if (strpos($tag, '_path') !== false or strpos($tag, 'permalink') !== false) {
            return $this->_paths($data, $tagdata, $tag, $tag_options, $prefix, $search_link);
        }

        if (strpos($tag, 'url') !== false) {
            return $this->_urls($data, $tagdata, $tag, $tag_options, $prefix, $obj->channel()->mfields);
        }

        // @todo remove
        $key = $tag;
        $val = $tag_options;
        if (strpos($key, 'disable') !== false && strpos($key, 'frontedit') !== false) {
            $key = trim(str_replace(['disable="frontedit"', "disable='frontedit'"], '', $key));
            $tagdata = str_replace($tag, $key, $tagdata);
        }

        if ($key == $prefix . 'title:frontedit') {
            $frontEditLink = ee('pro:FrontEdit')->entryFieldEditLink($data['site_id'], $data['channel_id'], $data['entry_id'], 'title');
            $tagdata = str_replace(LD . $key . RD, $frontEditLink, $tagdata);
        }
        //  parse {title}
        if ($key == $prefix . 'title') {
            $tagdata = str_replace(
                LD . $key . RD,
                ee()->typography->formatTitle($data['title']),
                $tagdata
            );
        }

        //  {author}
        elseif ($key == $prefix . "author") {
            $tagdata = str_replace(LD . $val . RD, ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'], $tagdata);
        }

        //  {channel}
        elseif ($key == $prefix . "channel") {
            $tagdata = str_replace(LD . $val . RD, $data['channel_title'], $tagdata);
        }

        //  {channel_short_name}
        elseif ($key == $prefix . "channel_short_name") {
            $tagdata = str_replace(LD . $val . RD, $data['channel_name'], $tagdata);
        }

        //  {relative_date}
        elseif ($key == $prefix . "relative_date") {
            $tagdata = str_replace(LD . $val . RD, timespan($data['entry_date']), $tagdata);
        }

        //  {signature}
        elseif ($key == $prefix . "signature") {
            if (ee()->session->userdata('display_signatures') == 'n' or $data['signature'] == '') {
                $tagdata = str_replace(LD . $key . RD, '', $tagdata);
            } else {
                $tagdata = str_replace(
                    LD . $key . RD,
                    ee()->typography->parse_type(
                        $data['signature'],
                        array(
                            'text_format' => 'xhtml',
                            'html_format' => 'safe',
                            'auto_links' => 'y',
                            'allow_img_url' => ee()->config->item('sig_allow_img_hotlink')
                        )
                    ),
                    $tagdata
                );
            }
        } else {
            return $this->_basic($data, $tagdata, $tag, $tag_options, $prefix);
        }

        return $tagdata;
    }

    /**
     * Handle variables that end in _path or contain "permalink".
     *
     * @param Array     The row data
     * @param String    The template text
     * @param String    The var_single key (tag name)
     * @param String    The var_single value
     * @param String    The current parsing prefix
     * @param String    The search link for search paths
     *
     * @return String   The processed tagdata
     */
    protected function _paths($data, $tagdata, $key, $val, $prefix, $search_link)
    {
        $unprefixed = substr($key, 0, strcspn($key, ' ='));
        $unprefixed = preg_replace('/^' . $prefix . '/', '', $unprefixed);

        //  parse profile path
        if ($unprefixed == 'profile_path') {
            $tagdata = str_replace(
                LD . $key . RD,
                ee()->functions->create_url(ee()->functions->extract_path($key) . '/' . $data['member_id']),
                $tagdata
            );
        }

        //  {member_search_path}
        elseif ($unprefixed == 'member_search_path') {
            $tagdata = str_replace(
                LD . $key . RD,
                $search_link . $data['member_id'],
                $tagdata
            );
        }

        //  parse comment_path
        elseif ($unprefixed == 'comment_path' or $unprefixed == 'entry_id_path') {
            $extracted_path = ee()->functions->extract_path($key);
            $path = ($extracted_path != '' and $extracted_path != 'SITE_INDEX') ? $extracted_path . '/' . $data['entry_id'] : $data['entry_id'];

            $tagdata = str_replace(
                LD . $key . RD,
                ee()->functions->create_url($path),
                $tagdata
            );
        }

        //  parse URL title path
        elseif ($unprefixed == 'url_title_path') {
            $extracted_path = ee()->functions->extract_path($key);
            $path = ($extracted_path != '' and $extracted_path != 'SITE_INDEX') ? $extracted_path . '/' . $data['url_title'] : $data['url_title'];

            $tagdata = str_replace(
                LD . $key . RD,
                ee()->functions->create_url($path),
                $tagdata
            );
        }

        //  parse title permalink
        elseif ($unprefixed == 'title_permalink') {
            $extracted_path = ee()->functions->extract_path($key);
            $path = ($extracted_path != '' and $extracted_path != 'SITE_INDEX') ? $extracted_path . '/' . $data['url_title'] : $data['url_title'];

            $tagdata = str_replace(
                LD . $key . RD,
                ee()->functions->create_url($path, false),
                $tagdata
            );
        }

        //  parse permalink
        elseif ($unprefixed == 'permalink') {
            $extracted_path = ee()->functions->extract_path($key);
            $path = ($extracted_path != '' and $extracted_path != 'SITE_INDEX') ? $extracted_path . '/' . $data['entry_id'] : $data['entry_id'];

            $tagdata = str_replace(
                LD . $key . RD,
                ee()->functions->create_url($path, false),
                $tagdata
            );
        }

        //  {comment_auto_path}
        elseif ($key == $prefix . "comment_auto_path") {
            $path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

            $tagdata = str_replace(LD . $key . RD, $path, $tagdata);
        }

        //  {comment_url_title_auto_path}
        elseif ($key == $prefix . "comment_url_title_auto_path") {
            $path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

            $tagdata = str_replace(
                LD . $key . RD,
                reduce_double_slashes($path . '/' . $data['url_title']),
                $tagdata
            );
        }

        //  {comment_entry_id_auto_path}
        elseif ($key == $prefix . "comment_entry_id_auto_path") {
            $path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

            $tagdata = str_replace(
                LD . $key . RD,
                reduce_double_slashes($path . '/' . $data['entry_id']),
                $tagdata
            );
        } else {
            return $this->_basic($data, $tagdata, $key, $val, $prefix);
        }

        return $tagdata;
    }

    /**
     * Handle variables that end in _url.
     *
     * @param Array     The row data
     * @param String    The template text
     * @param String    The var_single key (tag name)
     * @param String    The var_single value
     * @param String    The current parsing prefix
     *
     * @return String   The processed tagdata
     */
    protected function _urls($data, $tagdata, $key, $val, $prefix, $mfields)
    {
        // URL was moved to a custom member field or dropped
        $member_url = (isset($mfields['url'])) ? $data['m_field_id_' . $mfields['url'][0]] : '';

        if ($key == $prefix . 'url_title') {
            $tagdata = str_replace(LD . $val . RD, $data['url_title'], $tagdata);
        }

        //  {trimmed_url} - used by Atom feeds
        elseif ($key == $prefix . "trimmed_url") {
            $channel_url = (isset($data['channel_url']) and $data['channel_url'] != '') ? $data['channel_url'] : '';

            $channel_url = str_replace(array('http://', 'www.'), '', $channel_url);
            $xe = explode("/", $channel_url);
            $channel_url = current($xe);

            $tagdata = str_replace(LD . $val . RD, $channel_url, $tagdata);
        }

        //  {relative_url} - used by Atom feeds
        elseif ($key == $prefix . "relative_url") {
            $channel_url = (isset($data['channel_url']) and $data['channel_url'] != '') ? $data['channel_url'] : '';
            $channel_url = str_replace('http://', '', $channel_url);

            if ($x = strpos($channel_url, "/")) {
                $channel_url = substr($channel_url, $x + 1);
            }

            $channel_url = rtrim($channel_url, '/');

            $tagdata = str_replace(LD . $val . RD, $channel_url, $tagdata);
        }

        //  {url_or_email}
        elseif ($key == $prefix . "url_or_email") {
            $tagdata = str_replace(LD . $val . RD, ($member_url != '') ? $member_url : $data['email'], $tagdata);
        }

        //  {url_or_email_as_author}
        elseif ($key == $prefix . "url_or_email_as_author") {
            $name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

            if ($member_url != '') {
                $tagdata = str_replace(LD . $val . RD, "<a href=\"" . $member_url . "\">" . $name . "</a>", $tagdata);
            } else {
                $tagdata = str_replace(LD . $val . RD, ee()->typography->encode_email($data['email'], $name), $tagdata);
            }
        }

        //  {url_or_email_as_link}
        elseif ($key == $prefix . "url_or_email_as_link") {
            if ($member_url != '') {
                $tagdata = str_replace(LD . $val . RD, "<a href=\"" . $member_url . "\">" . $member_url . "</a>", $tagdata);
            } else {
                $tagdata = str_replace(LD . $val . RD, ee()->typography->encode_email($data['email']), $tagdata);
            }
        } elseif ($key == $prefix . "signature_image_url") {
            if (ee()->session->userdata('display_signatures') == 'n' or $data['sig_img_filename'] == '' or ee()->session->userdata('display_signatures') == 'n') {
                $tagdata = str_replace(LD . $key . RD, '', $tagdata);
                $tagdata = str_replace(LD . $prefix . 'signature_image_width' . RD, '', $tagdata);
                $tagdata = str_replace(LD . $prefix . 'signature_image_height' . RD, '', $tagdata);
            } else {
                $tagdata = str_replace(LD . $key . RD, ee()->config->slash_item('sig_img_url') . $data['sig_img_filename'], $tagdata);
                $tagdata = str_replace(LD . $prefix . 'signature_image_width' . RD, $data['sig_img_width'], $tagdata);
                $tagdata = str_replace(LD . $prefix . 'signature_image_height' . RD, $data['sig_img_height'], $tagdata);
            }
        } elseif ($key == $prefix . "avatar_url") {
            $avatar_url = '';

            if ($data['avatar_filename'] != '') {
                $avatar_url = ee()->config->slash_item('avatar_url') . $data['avatar_filename'];
            }

            $tagdata = str_replace(LD . $key . RD, $avatar_url, $tagdata);
            $tagdata = str_replace(LD . $prefix . 'avatar_image_width' . RD, (string) $data['avatar_width'], $tagdata);
            $tagdata = str_replace(LD . $prefix . 'avatar_image_height' . RD, (string) $data['avatar_height'], $tagdata);
        } elseif ($key == $prefix . "photo_url") {
            if (ee()->session->userdata('display_photos') == 'n' or $data['photo_filename'] == '' or ee()->session->userdata('display_photos') == 'n') {
                $tagdata = str_replace(LD . $key . RD, '', $tagdata);
                $tagdata = str_replace(LD . $prefix . 'photo_image_width' . RD, '', $tagdata);
                $tagdata = str_replace(LD . $prefix . 'photo_image_height' . RD, '', $tagdata);
            } else {
                $tagdata = str_replace(LD . $key . RD, ee()->config->slash_item('photo_url') . $data['photo_filename'], $tagdata);
                $tagdata = str_replace(LD . $prefix . 'photo_image_width' . RD, $data['photo_width'], $tagdata);
                $tagdata = str_replace(LD . $prefix . 'photo_image_height' . RD, $data['photo_height'], $tagdata);
            }
        } else {
            return $this->_basic($data, $tagdata, $key, $val, $prefix);
        }

        return $tagdata;
    }

    /**
     * Handle regular fields as basic replacements.
     *
     * This is used as a fallback in case the tag does not match any of our
     * presets. We fallback on urls and paths because third parties can add
     * anything they want to the entry data. (@see bug #19337)
     *
     * @param Array     The row data
     * @param String    The template text
     * @param String    The var_single key (tag name)
     * @param String    The var_single value
     * @param String    The current parsing prefix
     *
     * @return String   The processed tagdata
     */
    protected function _basic($data, $tagdata, $key, $val, $prefix)
    {
        if ($raw_val = preg_replace('/^' . $prefix . '/', '', $val)) {
            if (array_key_exists($raw_val, $data)) {
                // cast the data to string
                if (is_null($data[$raw_val]) || $data[$raw_val] === false) {
                    $data[$raw_val] = '';
                }
                $tagdata = str_replace(LD . $val . RD, $data[$raw_val], $tagdata);
            } else {
                $field = ee('Variables/Parser')->parseVariableProperties($key, $prefix);

                // some variables like {channel_short_name} don't directly map to the schema, so we can define
                // methods here like getChannelShortName() to provide the correct content
                $mismatch_getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field['field_name'])));

                if (isset($field['all_modifiers']) && !empty($field['all_modifiers'])) {
                    foreach ($field['all_modifiers'] as $modifier => $params) {
                        $method = 'replace_' . $modifier;

                        if (! method_exists($this, $method) && ! ee('Variables/Modifiers')->has($modifier)) {
                            continue;
                        }

                        if (isset($content)) {
                            // subsequental runs
                            $content = $this->$method($content, $params);
                        } elseif (array_key_exists($field['field_name'], $data)) {
                            // first run
                            $content = $this->$method($data[$field['field_name']], $params);
                        } elseif (method_exists($this, $mismatch_getter)) {
                            // first run on variable with mismatched name
                            $content = $this->$method($this->$mismatch_getter($data), $params);
                        }
                    }
                } else {
                    $method = 'replace_' . $field['modifier'];

                    if (! method_exists($this, $method) && ! ee('Variables/Modifiers')->has($field['modifier'])) {
                        return $tagdata;
                    }

                    if (array_key_exists($field['field_name'], $data)) {
                        $content = $this->$method($data[$field['field_name']], $field['params']);
                    } elseif (method_exists($this, $mismatch_getter)) {
                        $content = $this->$method($this->$mismatch_getter($data), $field['params']);
                    } else {
                        // variable must not exist
                        return $tagdata;
                    }
                }

                // no matches, return unparsed
                if (!isset($content)) {
                    return $tagdata;
                }

                $this->conditional_vars[$key] = $content;

                $tagdata = str_replace(LD . $val . RD, $content, $tagdata);
            }
        }

        return $tagdata;
    }

    private function _apply_modifiers($data, $tagdata, $modifier, $field_name, $params)
    {

    }

    /**
     * {channel} variable/schema mismatch getter
     *
     * @param  array $data Channel entry row
     * @return string the Channel name
     */
    private function getChannel($data)
    {
        return (isset($data['channel_title'])) ? $data['channel_title'] : '';
    }

    /**
     * {channel_short_name} variable/schema mismatch getter
     *
     * @param  array $data Channel entry row
     * @return string the Channel short name
     */
    private function getChannelShortName($data)
    {
        return (isset($data['channel_name'])) ? $data['channel_name'] : '';
    }

    /**
     * {author} variable/schema mismatch getter
     *
     * @param  array $data Channel entry row
     * @return string the Channel name
     */
    private function getAuthor($data)
    {
        if (! empty($data['screen_name'])) {
            return $data['screen_name'];
        }

        return (isset($data['username'])) ? $data['username'] : '';
    }
}

// EOF
