<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Model\Channel\ChannelEntry;

/**
 * Pages Module Tab
 */
class Pages_tab
{
    /**
     * Creates the fields that will be displayed on the publish page.
     *
     * @param int $channel_id Channel ID where the entry is being created or
     *   edited
     * @param int $enry_id Entry ID if this is an edit, empty otherwise
     * @return array A multidimensional associative array specifying the display
     *   settings and values associated with each of your fields.
     */
    public function display($channel_id, $entry_id = '')
    {
        ee()->lang->loadfile('pages');

        $site_id = ee()->config->item('site_id');
        $settings = array();

        $no_templates = null;
        $pages = ee()->config->item('site_pages');

        $pages_template_id = 0;
        $pages_dropdown = array();
        $pages_uri = '';

        if ($entry_id !== 0) {
            if (isset($pages[$site_id]['uris'][$entry_id])) {
                $pages_uri = $pages[$site_id]['uris'][$entry_id];
            }

            if (isset($pages[$site_id]['templates'][$entry_id])) {
                $pages_template_id = $pages[$site_id]['templates'][$entry_id];
            }

        } else {
            $qry = ee()->db->select('configuration_value')
                ->where('configuration_name', 'template_channel_' . $channel_id)
                ->where('site_id', (int) $site_id)
                ->get('pages_configuration');

            if ($qry->num_rows() > 0) {
                $pages_template_id = (int) $qry->row('configuration_value');
            }
        }

        if ($pages_uri == '') {
            $qry = ee()->db->select('configuration_value')
                ->where('configuration_name', 'template_channel_' . $channel_id)
                ->where('site_id', (int) $site_id)
                ->get('pages_configuration');

            if ($qry->num_rows() > 0) {
                $pages_template_id = (int) $qry->row('configuration_value');
            }
        }

        ee()->load->model('template_model');
        $templates = ee()->template_model->get_templates($site_id);

        foreach ($templates->result() as $template) {
            $pages_dropdown[$template->group_name][$template->template_id] = "{$template->group_name}/{$template->template_name}";
        }

        if ($templates->num_rows() === 0) {
            ee()->lang->load('design');
            $no_templates = ee('View')->make('ee:_shared/form/no_results')->render(array(
                'text' => sprintf(lang('no_found'), lang('pages_templates')),
                'link_text' => lang('create_new_template'),
                'link_href' => ee('CP/URL', 'design'),
            ));
        }

        $settings = array(
            'pages_uri' => array(
                'field_id' => 'pages_uri',
                'field_label' => lang('pages_uri'),
                'field_type' => 'text',
                'field_required' => 'n',
                'field_data' => $pages_uri,
                'field_text_direction' => 'ltr',
                'field_maxl' => 200,
                'field_instructions' => '',
                'field_placeholder' => lang('example_uri')
            ),
            'pages_template_id' => array(
                'field_id' => 'pages_template_id',
                'field_label' => lang('template'),
                'field_type' => 'select',
                'field_required' => 'n',
                'field_pre_populate' => 'n',
                'field_list_items' => $pages_dropdown,
                'field_data' => $pages_template_id,
                'options' => $pages_dropdown,
                'selected' => $pages_template_id,
                'field_text_direction' => 'ltr',
                'field_maxl' => 100,
                'field_instructions' => '',
                'string_override' => $no_templates,
                'ignore_section_label' => true
            ),
        );

        foreach ($settings as $k => $v) {
            ee()->api_channel_fields->set_settings($k, $v);
        }

        return $settings;
    }

    /**
     * Clones the page data for cloned entry
     *
     * @param ExpressionEngine\Model\Channel\ChannelEntry $entry
     * @param array $values An associative array of field => value
     * @return array $values modified array of values
     */
    public function cloneData(ChannelEntry $entry, $values)
    {
        if ($values['pages_uri'] == '') {
            return $values;
        }
        //check if submitted URI exists
        $static_pages = ee()->config->item('site_pages');
        $uris = $static_pages[ee()->config->item('site_id')]['uris'];

        //exclude current page from check
        if (isset($uris[$entry->entry_id])) {
            unset($uris[$entry->entry_id]);
        }
        //ensure leading slash is present
        $value = '/' . trim($values['pages_uri'], '/');

        $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
        while (in_array($value, $uris)) {
            $value = 'copy' . $word_separator . $value;
        }
        $_POST['pages__pages_uri'] = $values['pages_uri'] = $value;

        return $values;
    }

    /**
     * Validates our publish tab data
     *
     * @param ExpressionEngine\Model\Channel\ChannelEntry $entry
     * @param array $values An associative array of field => value
     * @return ExpressionEngine\Service\Validation\Result A result
     *  object.
     */
    public function validate($entry, $values)
    {
        $validator = ee('Validation')->make(array(
            'pages_template_id' => 'whenURI[pages_uri]|required|validTemplate',
            'pages_uri' => 'validURI|validSegmentCount|notDuplicated',
        ));

        $validator->defineRule('validTemplate', $this->makeValidTemplateRule($values));
        $validator->defineRule('validURI', $this->makeValidURIRule());
        $validator->defineRule('validSegmentCount', $this->makeValidSegmentCountRule());
        $validator->defineRule('notDuplicated', $this->makeNotDuplicatedRule($entry));
        $validator->defineRule('validHasTemplate', $this->makeValidHasTemplateRule($values));

        $data = $_POST;
        $validator->defineRule('whenURI', function ($key, $value, $parameters, $rule) use ($data) {
            if (empty($data['pages__pages_uri']) or $data['pages__pages_uri'] == lang('example_uri')) {
                return $rule->skip();
            }

            return true;
        });

        return $validator->validate($values);
    }

    /**
     * Validates whether a pages URI has been selected with a template
     * @param  array 	$values array of pages values coming from tab
     * @return Closure The logic needed to validate the data.
     */
    private function makeValidHasTemplateRule($values)
    {
        return function ($field, $value) use ($values) {
            if (
                $values['pages_uri'] === ""
                || ($values['pages_uri'] !== "" && $values['pages_template_id'] === "")) {
                return 'invalid_template';
            }

            return true;
        };
    }

    /**
     * Generates and returns a Closure suitable for a Validation Rule that
     * ensures the submitted value (a Template ID) is both set and is a number,
     * provided that * a Page URI was also set.
     *
     * @returns Closure The logic needed to validate the data.
     */
    private function makeValidTemplateRule($values)
    {
        return function ($field, $value) use ($values) {
            $pages_uri = (isset($values['pages_uri'])) ? $values['pages_uri'] : '';
            $pages = ee()->config->item('site_pages');

            if ($pages !== false && $pages_uri != '' && $pages_uri !== lang('example_uri')) {
                if (! isset($value) or
                     ! is_numeric($value)) {
                    return 'invalid_template';
                }
            }

            return true;
        };
    }

    /**
     * Generates and returns a Closure suitable for a Validation Rule that
     * ensures the submitted value (a URI) contains only letters, numbers,
     * underscores, dashes, or periods.
     *
     * @returns Closure The logic needed to validate the data.
     */
    private function makeValidURIRule()
    {
        return function ($field, $value) {
            if (empty($value)) {
                return true;
            }
            $c_page_uri = preg_replace(
                "#[^a-zA-Z0-9_\-/\.]+$#i",
                '',
                str_replace(ee()->config->item('site_url'), '', $value)
            );

            if ($c_page_uri !== $value) {
                return 'invalid_page_uri';
            }

            return true;
        };
    }

    /**
     * Generates and returns a Closure suitable for a Validation Rule that
     * ensures the submitted value (a URI) has no more than 8 segments.
     *
     * @returns Closure The logic needed to validate the data.
     */
    private function makeValidSegmentCountRule()
    {
        return function ($field, $value) {
            if (empty($value)) {
                return true;
            }
            $value_segs = substr_count(trim($value, '/'), '/');

            // More than 9 pages URI segs?  goodbye
            if ($value_segs > 8) {
                return 'invalid_page_num_segs';
            }

            return true;
        };
    }

    /**
     * Generates and returns a Closure suitable for a Validation Rule that
     * ensures the submitted value (a URI) does not already exist
     *
     * @returns Closure The logic needed to validate the data.
     */
    private function makeNotDuplicatedRule($entry)
    {
        return function ($field, $value) use ($entry) {
            if (empty($value)) {
                return true;
            }
            $static_pages = ee()->config->item('site_pages');
            $uris = $static_pages[ee()->config->item('site_id')]['uris'];

            //exclude current page from check
            if (isset($uris[$entry->entry_id])) {
                unset($uris[$entry->entry_id]);
            }
            //ensure leading slash is present
            $value = '/' . trim($value, '/');

            if (in_array($value, $uris)) {
                $entry_id = array_search($value, $uris);
                $entry = ee('Model')->get('ChannelEntry', $entry_id)->fields('entry_id', 'title')->first();
                if ($entry) {
                    $edit_link = ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);
                    return sprintf(lang('duplicate_page_uri_used'), $edit_link, htmlentities($entry->title, ENT_QUOTES, 'UTF-8'));
                }
            }

            return true;
        };
    }

    /**
     * Prepares the site pages array with the submitted values
     *
     * @param ExpressionEngine\Model\Channel\ChannelEntry $entry
     *  An instance of the ChannelEntry entity.
     * @param array $values An associative array of field => value
     * @return	array A multidimensional array of site page data
     */
    public function prepareSitePagesData($entry, $values)
    {
        $site_id = ee()->config->item('site_id');
        $site_pages = ee()->config->item('site_pages');

        if ($site_pages !== false
            && isset($values['pages_uri'])
            && $values['pages_uri'] != lang('example_uri')
            && $values['pages_uri'] != '') {
            if (isset($values['pages_template_id'])
                && is_numeric($values['pages_template_id'])) {
                $page = preg_replace(
                    "#[^a-zA-Z0-9_\-/\.]+$#i",
                    '',
                    str_replace(
                        ee()->config->item('site_url'),
                        '',
                        $values['pages_uri']
                    )
                );

                $page = '/' . trim($page, '/');

                $site_pages[$site_id]['uris'][$entry->entry_id] = $page;
                $site_pages[$site_id]['templates'][$entry->entry_id] = preg_replace(
                    "#[^0-9]+$#i",
                    '',
                    $values['pages_template_id']
                );

                if ($site_pages[$site_id]['uris'][$entry->entry_id] == '//') {
                    $site_pages[$site_id]['uris'][$entry->entry_id] = '/';
                }
            }
        }

        return $site_pages;
    }

    /**
     * Saves the page's publish form data. This function is called in the
     * ChannelEntry's afterSave() event.
     *
     * @param ExpressionEngine\Model\Channel\ChannelEntry $entry
     *  An instance of the ChannelEntry entity.
     * @param array $values An associative array of field => value
     * @return 	void
     */
    public function save($entry, $values)
    {
        $site_id = ee()->config->item('site_id');
        $site_pages = $this->prepareSitePagesData($entry, $values);

        if ($site_pages !== false) {
            ee()->config->set_item('site_pages', $site_pages);
            $site = ee('Model')->get('Site', $site_id)->first();
            $site->site_pages = $site_pages;
            $site->save();
        }
    }

    /**
     * Removes pages from the site_pages structure. This function is called in the
     * ChannelEntry's beforeDelete() event.
     *
     * @param int[] $entry_ids An array of entry IDs that were deleted
     * @return 	void
     */
    public function delete($entry_ids)
    {
        $site_pages = ee()->config->item('site_pages');
        $site_id = ee()->config->item('site_id');

        foreach ($entry_ids as $entry_id) {
            unset($site_pages[$site_id]['uris'][$entry_id]);
            unset($site_pages[$site_id]['templates'][$entry_id]);
        }

        $site = ee('Model')->get('Site', $site_id)->first();
        $site->site_pages = $site_pages;
        $site->save();
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $site_pages = ee()->config->item('site_pages');
        $site_id = ee()->config->item('site_id');
        $uri = array_key_exists($entry->entry_id, $site_pages[$site_id]['uris']) ? $site_pages[$site_id]['uris'][$entry->entry_id] : '';
        if (!empty($uri)) {
            return '<a href="' . str_replace('//', '/', ee()->functions->fetch_site_index(0, 0) . $uri) . '" target="_blank"><i class="fal fa-link"></i></a>';
        }
        return '';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }
}

// EOF
