<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\FrontEdit;

/**
 * Frontend edit service
 */
class FrontEdit
{
    public function validateField($field, $data)
    {
        return true;
    }

    /**
     * Get edit link for entry field
     *
     * @param int $site_id Site id
     * @param int $channel_id Channel id
     * @param int $entry_id Entry id
     * @param string $field_id_or_name Field ID or short name of the field is not custom
     */
    public function entryFieldEditLink($site_id, $channel_id, $entry_id, $field_id_or_name)
    {
        if ($this->fronteditIsDisabled()) {
            return '';
        }
        if (!is_numeric($site_id) || !is_numeric($channel_id) || !is_numeric($entry_id)) {
            return '';
        }
        return '{frontedit_link site_id=@' . $site_id . '@ channel_id=@' . $channel_id . '@ entry_id=@' . $entry_id . '@ field_id=@' . $field_id_or_name . '@}';
    }

    /**
     * Check whether frontedit should be completely disabled
     *
     * @return bool
     */
    public function fronteditIsDisabled()
    {
        if (ee()->config->item('enable_frontedit') !== false && ee()->config->item('enable_frontedit') != 'y') {
            return true;
        }
        if (ee()->config->item('enable_dock') !== false && ee()->config->item('enable_dock') != 'y') {
            return true;
        }
        if (ee('LivePreview')->hasEntryData()) {
            return true;
        }
        return false;
    }

    /**
     * Prepase the template by injecting pseudo links for front-end editing
     *
     * @param string $tagdata
     * @param string $prefix
     * @return string
     */
    public function prepareTemplate($tagdata, $prefix = '')
    {
        //if a config setting is missing, we assume it's on
        if (ee()->config->item('automatic_frontedit_links') !== false && ee()->config->item('automatic_frontedit_links') != 'y') {
            return $tagdata;
        }
        if ($this->fronteditIsDisabled()) {
            return $tagdata;
        }


        $shouldInjectLinks = false;
        //check license status, permissions, etc
        if (ee('pro:Access')->shouldInjectLinks()) {
            //check template setting
            if (isset(ee()->TMPL) && is_object(ee()->TMPL) && in_array(ee()->TMPL->template_type, ['webpage']) && !empty(ee()->TMPL->template_id) && ee()->TMPL->enable_frontedit != 'n') {
                $shouldInjectLinks = true;
            }
        }
        //find the fields, evaluate context and add edit pseudo-link
        if ($shouldInjectLinks) {
            //temporary manual links, so we could preserve those in disabled blocks
            $tagdata = str_replace(':frontedit', ':MAGIC_frontedit_HAPPENING', $tagdata);

            //get EE tags list
            $tags_regexp = '/{exp:([a-zA-z0-9:]*)/';
            $tags = [];
            if (preg_match_all($tags_regexp, $tagdata, $matches)) {
                $tags = array_unique($matches[1]);
            }
            //get all field names
            $field_regexps = [];
            $allFields = ee('Model')->get('ChannelField')->fields('field_id', 'field_type', 'field_name')->filter('enable_frontedit', 'y')->order('field_name', 'desc')->all(true);
            $fields = [
                'title' => 'title'
            ];
            $relFields = [];
            $gridFields = [];
            $gridLikeFieldtypes = static::getComplexFieldtypes();
            foreach ($allFields as $field) {
                if ($field->field_type == 'relationship') {
                    $relFields[$prefix . $field->field_name] = $field->field_id;
                } elseif (in_array($field->field_type, $gridLikeFieldtypes)) {
                    $gridFields[$prefix . $field->field_name] = $field->field_id;
                } else {
                    $fields[$prefix . $field->field_name] = $field->field_id;
                }
            }
            $fields_orig = $fields;
            $gridFields_orig = $gridFields;

            if (!empty($relFields)) {
                $field_regexps[] = '/\{(' . implode('|', array_keys($relFields)) . ')(?!:frontedit)[\s]?([^:\{\}]*)\}/s';
                foreach ($relFields as $rel_field_name => $rel_field_id) {
                    $fields = [];
                    if (!empty($fields_orig)) {
                        foreach ($fields_orig as $field_name => $field_id) {
                            $fields[$rel_field_name . ':' . $field_name] = $field_id;
                        }
                        $field_regexps[] = '/\{(' . implode('|', array_keys($fields)) . ')(?!:frontedit)[:\s]?([^\{\}]*)\}/s';
                    }
                    $fields = [];
                    if (!empty($gridFields_orig)) {
                        foreach ($gridFields_orig as $field_name => $field_id) {
                            $fields[$rel_field_name . ':' . $field_name] = $field_id;
                        }
                        $field_regexps[] = '/\{(' . implode('|', array_keys($fields)) . ')(?!:frontedit)(?:\s|:table|:sum|:average|:highest|:lowest|:length|:total_rows)?([^:\{\}]*)\}/s';
                    }
                }
            }

            if (!empty($fields_orig)) {
                $field_regexps[] = '/\{(' . implode('|', array_keys($fields_orig)) . ')(?!:frontedit)[:\s]?([^\{\}]*)\}/s';
            }
            if (!empty($gridFields_orig)) {
                $field_regexps[] = '/\{(' . implode('|', array_keys($gridFields_orig)) . ')(?!:frontedit)(?:\s|:table|:sum|:average|:highest|:lowest|:length|:total_rows)?([^:\{\}]*)\}/s';
            }

            $injectPositions = []; //remember where we already injected the link so we don't do
            foreach ($field_regexps as $field_regexp) {
                if (preg_match_all($field_regexp, $tagdata, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    $matches = array_reverse($matches);
                    foreach ($matches as $match) {
                        if (strpos($match[2][0], 'disable') !== false && strpos($match[2][0], 'frontedit') !== false) {
                            continue;
                        }
                        $tag = '{' . $match[1][0] . ':frontedit}';
                        $tagdata = substr_replace($tagdata, $tag, $match[0][1], 0);
                        ee()->TMPL->var_single[$match[1][0] . ':frontedit'] = $match[1][0] . ':frontedit';
                    }
                }
            }

            $stripFromTags = [
                '/\<head.+(?P<link>\{[\w:]+\:frontedit\}).?\<\/head\>/si' => false,
                '/\<script.+(?P<link>\{[\w:]+\:frontedit\}).?\<\/script\>/si' => false,
                '/\<style.+(?P<link>\{[\w:]+\:frontedit\}).?\<\/style\>/si' => false,
                '/\<[\w\-]+?\s[^\>]*?[\w\-]*?=[\"\']?((?![\"\>]).)*?(?P<link>\{[\w:]+\:frontedit\}).*?\>/si' => true,//HTML tag parameters
                '/\<[\w\-]+?\s[^\>]*?(?P<link>\{[\w:]+\:frontedit\}).*?\>/si' => true,//in the middle of tag
                //'/\<a\s.*?\>.*?(?P<link>\{[\w:]+\:frontedit\}).*?\<\/a\>/si' => true,//inside A tag
                '/\{if\s[^\}]*?(?P<link>\{[\w:]+\:frontedit\}).*?\}/s' => true,//EE {if} conditional
                '/\{if\s([^\}]*?\{[\w:]+\}[^\}]*?)+(?P<link>\{[\w:]+\:frontedit\}).*?\}/s' => false,//EE {if} conditional (more complex one)
                '/\{encode=[^\}]*?(?P<link>\{[\w:]+\:frontedit\}).*?\}/s' => true,//EE {encode} tag
                '/\<\!--\s*disable\s*frontedit\s*--\>.*?(?P<link>\{[\w:]+\:frontedit\}).*?\<\!--\s*\/\/\s*disable\s*frontedit\s*--\>/si' => false,
            ];

            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $stripFromTags['/\{exp:' . $tag . '((?!\{exp:' . $tag . ').)*?\}((?!\{exp:' . $tag . ').)*?(?P<link>\{[\w:]+\:frontedit\})((?!\{exp:' . $tag . ').)*?\{\/exp:' . $tag . '\}/si'] = true;//EE plugin tagdata
                    $stripFromTags['/\{exp:' . $tag . '((?!\{\/exp:' . $tag . ').)*?=[\"\'](?P<link>\{[\w:]+\:frontedit\}).*?\}/si'] = true;//EE tag parameters
                }
            }

            //perform several iterations before we are all clean
            $iterations = 0;
            do {
                $allClean = true;
                foreach ($stripFromTags as $tag => $preserve) {
                    $tagPresent = preg_match_all($tag, $tagdata, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
                    if ($tagPresent) {
                        $cleanupReplacements = [];
                        $allClean = false;
                        $matches = array_reverse($matches);
                        //inject the links that need to be preserved
                        foreach ($matches as $match) {
                            if ($preserve && strpos($match['link'][0], ':frontedit') !== false) {
                                $tagdata = substr_replace($tagdata, $match['link'][0], $match[0][1], 0);
                            }
                        }
                        //now prepare to strip the links that are not in proper context
                        foreach ($matches as $match) {
                            if (strpos($match['link'][0], ':frontedit') !== false) {
                                $clean_match = str_replace($match['link'][0], '', $match[0][0]);
                                $cleanupReplacements[$match[0][0]] = $clean_match;
                            }
                        }
                    }
                }

                //actually strip the links that are not in proper context
                if (!empty($cleanupReplacements)) {
                    $tagdata = str_replace(array_keys($cleanupReplacements), $cleanupReplacements, $tagdata);
                }
                $iterations++;
            } while (!$allClean && $iterations < 10);


            // avoid duplicates.
            if (preg_match_all('/\{[\w:]+\:frontedit\}/si', $tagdata, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $orig_tagdata = $tagdata;
                foreach ($matches as $i => $match) {
                    $tag = $match[0][0];
                    // Find and strip the edit links that are right after themselves
                    do {
                        $tagdata = preg_replace('/' . preg_quote($tag) . '\s*' . preg_quote($tag) . '/si', $tag, $tagdata);
                    } while (strpos($tagdata, $tag . $tag) !== false);
                    // if the link follows itself divided by opening tag, strip the first one
                    // (might be caused by if conditionals)

                    if (isset($matches[$i + 1]) && $matches[$i + 1][0][0] == $tag) {
                        $substrOrig = substr($orig_tagdata, $match[0][1], $matches[$i + 1][0][1] - $match[0][1] + strlen($tag));
                        // :frontedit tag cannot be inside of tag pair
                        // (case when using image modifiers)
                        $substr = preg_replace_callback('/{([a-zA-Z0-9_-]*):frontedit}(?!(\{\1[\}\s]))(.*?){\/\1}/s', function ($substrMatches) {
                            if (preg_match('/{' . $substrMatches[1] . '[\}\s]/s', $substrMatches[0], $check)) {
                                return $substrMatches[0];
                            }
                            return preg_replace('/{' . $substrMatches[1] . ':frontedit}/', '', $substrMatches[0]);
                        }, $substrOrig);
                        $tagdata = str_replace($substrOrig, $substr, $tagdata);
                        //there is some tag between, but no closing tag
                        if (
                            (strpos($substr, '<') === false && strpos($substr, '>') === false) ||
                            (strpos($substr, '<') !== false && strpos($substr, '>') !== false && strpos($substr, '</') === false)
                        ) {
                            $tagdata = str_replace($substr, $tag . str_replace($tag, '', $substr), $tagdata);
                        }
                    }
                }
            }

            // inject manual links back
            $tagdata = str_replace(':MAGIC_frontedit_HAPPENING', ':frontedit', $tagdata);
        }

        return $tagdata;
    }

    /**
     * Load assets required for front-edit and do some cleanup
     *
     * @param string $output the almost-ready output
     * @return string final output
     */
    public function loadFrontEditAssets($output)
    {
        ee()->TMPL->log_item("Pro: Performing replacements required for front-end editing.");
        $output = $this->_stripFrontEditWhenNotInContext($output);

        $fields = ee('Model')->get('ChannelField')->fields('field_id', 'field_name', 'field_label', 'field_type')->all();
        $fieldsById = $fields->indexBy('field_id');
        $fieldsByName = $fields->indexBy('field_name');

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        $fieldEditUrl = ee('CP/URL')->make(
            'publish/edit/entry/ENTRY_ID',
            [
                'entry_ids' => ['ENTRY_ID'],
                'field_id' => 'FIELD_ID',
                'site_id' => 'SITE_ID',
                'modal_form' => 'y',
                'hide_closer' => 'y',
                'return' => urlencode(ee()->functions->fetch_current_uri())
            ],
            ee()->config->item('cp_url')
        );

        $elementId = "editable-field-FIELD_ID-KEYGEN";
        $actionId = ee('Model')->get('Action')
                        ->filter('class', 'File')
                        ->filter('method', 'addonIcon')
                        ->first();

        $pencilUrl = URL_PRO_THEMES . 'img/edit.svg';

        $altText = lang('pencil_icon');

        $element = '<span class="eeFrontEdit MARKER_CLASS" data-ee-editable data-editableid="'
                    . $elementId
                    . '" data-editableurl="'
                    . $fieldEditUrl
                    . '" data-entry_id="ENTRY_ID" data-site_id="SITE_ID" data-size="WINDOW_SIZE" title="FIELD_NAME">'
                    . '<img src="' . $pencilUrl . '" width="24px" height="24px" alt="' . $altText . '" style="cursor:pointer !important; filter: drop-shadow(0 1px 3px rgba(0,0,0,.20)) !important; vertical-align: bottom !important; border-radius: 0 !important; width: unset !important;" />'
                    . '</span>';
        $frontEditPermission = [];
        $entry_data = [];
        if (preg_match_all("/{\s*frontedit_link\s+.*\}/sU", $output, $tags)) {
            foreach ($tags[0] as $tag) {
                $replace = [];
                if (preg_match_all("/([a-zA-Z]+(?:_id|_name|lass)*)=[\"\'@]([a-zA-Z0-9_-]+)[\"\'@]/s", $tag, $params)) {
                    $replace['class'] = '';
                    foreach ($params[1] as $i => $key) {
                        $replace[$key] = $params[2][$i];
                    }
                    // at the minimum, we expect to have entry_id and field_id here
                    if (!isset($replace['field_id']) && isset($replace['field_name'])) {
                        $replace['field_id'] = isset($fieldsByName[$replace['field_name']]) ? $fieldsByName[$replace['field_name']]->field_id : $replace['field_name'];
                    }
                    if (!isset($replace['entry_id']) || !isset($replace['field_id']) || empty($replace['entry_id']) || empty($replace['field_id'])) {
                        continue;
                    }
                    //get the missing entry variables
                    if (!isset($replace['site_id']) || !isset($replace['channel_id'])) {
                        if (!isset($entry_data[$replace['entry_id']])) {
                            $entry_data[$replace['entry_id']] = ee('Model')->get('ChannelEntry', $replace['entry_id'])->fields('site_id', 'channel_id')->first();
                        }
                        $replace['site_id'] = $entry_data[$replace['entry_id']]->site_id;
                        $replace['channel_id'] = $entry_data[$replace['entry_id']]->channel_id;
                    }
                    //check entry permissions
                    $frontEditPermissionKey = $replace['channel_id'] . '__' . $replace['entry_id'];
                    if (!isset($frontEditPermission[$frontEditPermissionKey])) {
                        $frontEditPermission[$frontEditPermissionKey] = ee('pro:Access')->hasFrontEditPermission($replace['channel_id'], $replace['entry_id']);
                    }
                    if ($frontEditPermission[$frontEditPermissionKey]) {
                        $windowSize = '';
                        if (isset($fieldsById[$replace['field_id']])) {
                            $field = $fieldsById[$replace['field_id']];
                            $fieldName = $field->field_label;
                            if (isset(ee()->api_channel_fields->field_types[$field->field_type])) {
                                $ft = ee()->api_channel_fields->field_types[$field->field_type];
                                $windowSize = isset($ft->size) ? $ft->size : 'basic';
                            }
                        } else {
                            // @TODO, it may not be title
                            $fieldName = lang('title');
                        }

                        $keyGen = $this->randomKeyGen();
                        $editLink = str_replace(
                            ['SITE_ID', 'CHANNEL_ID', 'ENTRY_ID', 'FIELD_ID', 'KEYGEN', 'FIELD_NAME', 'WINDOW_SIZE', 'MARKER_CLASS'],
                            [$replace['site_id'], $replace['channel_id'], $replace['entry_id'], $replace['field_id'], $keyGen, $fieldName, $windowSize, $replace['class']],
                            $element
                        );
                    } else {
                        $editLink = '';
                    }
                    $output = str_replace($tag, $editLink, $output);
                }
            }
        }

        return $output;
    }

    /**
     * Removes all unparsed frontedit functionality
     *
     * @param string $output the dirty output
     * @return string clean output
     */
    public function clearFrontEdit($output)
    {
        $output = preg_replace("/\{frontedit_link\s+(.*)\}/sU", '', $this->_stripFrontEditWhenNotInContext($output));
        $output = str_replace([
            '<!-- disable frontedit -->',
            '<!--disable frontedit-->',
            '<!-- // disable frontedit -->',
            '<!-- //disable frontedit -->',
            '<!--//disable frontedit-->'
        ], '', $output);
        return $output;
    }
    /**
     * Making sure we have entry ID, or doing our best to get it
     *
     * @return void
     */
    public function ensureEntryId()
    {
        if (isset(ee()->session->cache['channel']['entry_ids'])) {
            return;
        }

        $entry_id = null;
        $qstring = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;

        if (isset(ee()->session->cache['channel']['single_entry_id'])) {
            $entry_id = ee()->session->cache['channel']['single_entry_id'];
        }

        if (empty($qstring)) {
            return;
        }

        /** --------------------------------------
        /**  Do we have a pure ID number?
        /** --------------------------------------*/
        if (!empty($entry_id) && is_numeric($qstring)) {
            $entry_id = $qstring;
        }

        /** --------------------------------------
        /**  Parse day
        /** --------------------------------------*/
        if (!empty($entry_id) && preg_match("#(^|\/)(\d{4}/\d{2}/\d{2})#", $qstring, $match)) {
            $qstring = trim_slashes(str_replace($match[0], '', $qstring));
        }

        /** --------------------------------------
        /**  Parse /year/month/
        /** --------------------------------------*/
        if (!empty($entry_id) && preg_match("#(^|\/)(\d{4}/\d{2})(\/|$)#", $qstring, $match)) {
            $qstring = trim_slashes(str_replace($match[2], '', $qstring));
        }

        /** --------------------------------------
        /**  Parse ID indicator
        /** --------------------------------------*/
        if (!empty($entry_id) && preg_match("#^(\d+)(.*)#", $qstring, $match)) {
            $seg = (! isset($match[2])) ? '' : $match[2];
            if (substr($seg, 0, 1) == "/" or $seg == '') {
                $entry_id = $match[1];
                $qstring = trim_slashes(preg_replace("#^" . $match[1] . "#", '', $qstring));
            }
        }

        /** --------------------------------------
        /**  Parse page number
        /** --------------------------------------*/
        if (!empty($entry_id) && preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match)) {
            $qstring = trim_slashes(str_replace($match[0], '', $qstring));
        }

        /** --------------------------------------
        /**  Parse category indicator
        /** --------------------------------------*/
        if (!empty($entry_id)) {
            if (in_array(ee()->config->item('reserved_category_word'), explode("/", $qstring))) {
                return;
            }
            ee()->load->helper('segment');
            $cat_id = parse_category(ee()->uri->query_string);
            if (is_numeric($cat_id) and $cat_id !== false) {
                return;
            }
        }

        /** --------------------------------------
        /**  Remove "N"
        /** --------------------------------------*/

        if (!empty($entry_id) && preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match)) {
            $qstring = trim_slashes(str_replace($match[0], '', $qstring));
        }

        /** --------------------------------------
        /**  Parse URL title
        /** --------------------------------------*/
        if (!empty($entry_id) && strpos($qstring, '/') !== false) {
            $xe = explode('/', $qstring);
            $qstring = current($xe);
        }

        //get entry info
        $filter = ['site_id' => ee()->config->item('site_id')];
        if (!empty($entry_id)) {
            $filter['entry_id'] = (int) $entry_id;
        } else {
            $filter['url_title'] = $qstring;
        }
        $q = ee()->db->select('entry_id, channel_id')->where($filter)->get('channel_titles');
        if ($q->num_rows() == 1) {
            ee()->session->cache['channel']['entry_ids'] = [$q->row('entry_id')];
            ee()->session->cache['channel']['channel_ids'] = [$q->row('channel_id')];
        }
    }

    /**
     * Removes 'edit this' links from the page parts where it's not allowed
     * - html head
     * - script and style tags
     * - html attributes
     * - portions enclosed in comment <!-- disable frontedit -->...<!-- // disable frontedit -->
     *
     * @param string $output the almost-ready output
     * @return string clean output
     */
    private function _stripFrontEditWhenNotInContext($output)
    {
        $stripFromTags = [
            '/\<head.*?\<\/head\>/si',
            '/\<script.*?\<\/script\>/si',
            '/\<style.*?\<\/style\>/si',
            '/[^\s]=[\"\']?[^\"\s]*?\{frontedit_link\s+.*?\}/s',
            '/\<\!--\s*disable\s*frontedit\s*--\>.*?\<\!--\s*\/\/\s*disable\s*frontedit\s*--\>/si'
        ];
        foreach ($stripFromTags as $tag) {
            $tagPresent = preg_match_all($tag, $output, $matches);
            if ($tagPresent) {
                foreach ($matches[0] as $match) {
                    if (is_array($match)) {
                        $match = $match[0];
                    }
                    $replace = preg_replace("/\{frontedit_link\s+(.*)\}/sU", '', $match);
                    $output = str_replace($match, $replace, $output);
                }
            }
        }
        if (preg_match_all('/\{frontedit_link\s+(.*)\}/sUi', $output, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $i => $match) {
                $tag = $match[0][0];
                // Find and strip the edit links that are right after themselves
                do {
                    $output = preg_replace('/' . preg_quote($tag) . '\s*' . preg_quote($tag) . '/sUi', $tag, $output);
                } while (strpos($output, $tag . $tag) !== false);
            }
        }

        return $output;
    }

    /**
     * Return list of fieldtypes that needs to be treated similar to Grid or Fluid
     *
     * @return array[string]
     */
    private static function getComplexFieldtypes()
    {
        static $complexFieldtypes;
        if (empty($complexFieldtypes)) {
            $cache_key = '/FrontEdit/ComplexFieldtypes';
            $complexFieldtypes = ee()->cache->get($cache_key);
            if (empty($complexFieldtypes)) {
                $complexFieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');
                ee()->legacy_api->instantiate('channel_fields');
                $complexFieldtypes = array_filter($complexFieldtypes, function ($fieldtype) {
                    $ftClassName = ee()->api_channel_fields->include_handler($fieldtype);
                    $reflection = new \ReflectionClass($ftClassName);
                    $instance = $reflection->newInstanceWithoutConstructor();
                    if (isset($instance->complex_data_structure)) {
                        return (bool) $instance->complex_data_structure;
                    } else {
                        return false;
                    }
                });
                ee()->cache->save($cache_key, $complexFieldtypes);
            }
        }

        return $complexFieldtypes;
    }

    private function randomKeyGen()
    {
        $bytes = openssl_random_pseudo_bytes(16);
        return bin2hex($bytes);
    }
}

// EOF
