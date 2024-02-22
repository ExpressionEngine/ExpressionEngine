<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;

class Rte_ft extends EE_Fieldtype
{

    public $has_array_data = true;

    public $can_be_cloned = true;

    public $entry_manager_compatible = true;

    public $size = 'large';

    public $info = [
        'name' => 'Rich Text Editor',
        'version' => '2.1.0'
    ];

    public $defaultEvaluationRule = 'isNotEmpty';

    /**
     * Implements EntryManager\ColumnInterface
     */
    public function renderTableCell($data, $field_id, $entry)
    {
        $out = strip_tags(str_replace('&nbsp;', ' ', $this->replace_excerpt($data, [])));
        if (strlen($out) > 255) {
            $out = substr($out, 0, min(255, strpos($out, " ", 240))) . '&hellip;';
        }

        return html_entity_decode($out);
    }

    // --------------------------------------------------------------------

    /**
     * Display Field Settings.
     *
     * @param  $settings
     *
     * @return array $formFields Ready to be used in the EE Shared Form View
     */
    public function display_settings($settings)
    {
        $settings = $this->_fieldSettings($settings);

        return array(
            'field_options_rte' => array(
                'label' => 'field_options',
                'group' => 'rte',
                'settings' => $settings
            )
        );
    }

    /**
     * Display Grid cell settings.
     *
     * @param  $settings
     *
     * @return array $formFields Ready to be used in the EE Shared Form View
     */
    public function grid_display_settings($settings)
    {
        return array('field_options' => $this->_fieldSettings($settings));
    }

    // --------------------------------------------------------------------

    /**
     * Save Field Settings.
     *
     * @param @settings
     *
     * @return array $settings
     */
    public function save_settings($settings)
    {
        $settings = ee('Request')->post('rte');

        // Give it the full width
        $settings['field_wide'] = true;

        $settings['field_fmt'] = 'none';
        $settings['field_show_fmt'] = 'n';

        return $settings;
    }

    /**
     * Save Grid cell settings.
     *
     * @param @settings
     *
     * @return array $settings
     */
    public function grid_save_settings($settings)
    {
        $settings = $settings['rte'];

        return $settings;
    }

    /**
     * Modify DB column
     *
     * @param Array $data
     * @return Array
     */
    public function settings_modify_column($data)
    {
        return $this->get_column_type($data);
    }

    /**
     * Modify DB grid column
     *
     * @param array $data The field data
     * @return array  [column => column_definition]
     */
    public function grid_settings_modify_column($data)
    {
        return $this->get_column_type($data, true);
    }

    /**
     * Helper method for column definitions
     *
     * @param array $data The field data
     * @param bool  $grid Is grid field?
     * @return array  [column => column_definition]
     */
    protected function get_column_type($data, $grid = false)
    {
        $column = ($grid) ? 'col' : 'field';

        $settings = ($grid) ? $data : $data[$column . '_settings'];
        $field_content_type = isset($settings['db_column_type']) ? $settings['db_column_type'] : 'text';

        $fields = [
            $column . '_id_' . $data[$column . '_id'] => [
                'type' => $field_content_type,
                'null' => true
            ]
        ];

        return $fields;
    }

    // --------------------------------------------------------------------

    /**
     * Display the field.
     *
     * @param string $data field data
     *
     * @return string $field
     */
    public function display_field($data)
    {
        $toolsetId = (isset($this->settings['toolset_id'])) ? (int) $this->settings['toolset_id'] : (!empty(ee()->config->item('rte_default_toolset')) ? (int) ee()->config->item('rte_default_toolset') : null);
        if (!empty($toolsetId)) {
            $toolset = ee('Model')->get('rte:Toolset')->filter('toolset_id', $toolsetId)->first();
        } else {
            $toolset = ee('Model')->get('rte:Toolset')->first();
        }

        // Load proper toolset
        $serviceName = ucfirst($toolset->toolset_type) . 'Service';
        $configHandle = ee('rte:' . $serviceName)->init($this->settings, $toolset);

        $id = str_replace(array('[', ']'), array('_', ''), $this->field_name);
        $defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y')
                    ? true
                    : false;

        if (strpos($id, '_new_field_0') === false && strpos($id, '_new_row_0') === false) {
            ee()->cp->add_to_foot('<script type="text/javascript">new Rte("' . $id . '", "' . $configHandle . '", ' . ($defer ? 'true' : 'false') . ');</script>');
        }

        // convert file tags to URLs
        RteHelper::replaceFileTags($data);

        // convert site page tags to URLs
        RteHelper::replacePageTags($data);

        //Third party conversion
        RteHelper::replaceExtraTags($data);

        if (ee()->extensions->active_hook('rte_before_display')) {
            $data = ee()->extensions->call('rte_before_display', $this, $data);
        }

        ee()->load->helper('form');

        $field = array(
            'name' => $this->field_name,
            'value' => $data,
            'id' => $id,
            'rows' => 10,
            'data-config' => $configHandle,
            'class' => ee('rte:' . $serviceName)->getClass(),
            'data-defer' => ($defer ? 'y' : 'n')
        );

        return form_textarea($field);
    }

    /**
     * Display the field in a Grid cell
     *
     * @param string $data field data
     *
     * @return string $field
     */
    public function grid_display_field($data)
    {
        $toolsetId = (isset($this->settings['toolset_id'])) ? (int) $this->settings['toolset_id'] : (!empty(ee()->config->item('rte_default_toolset')) ? (int) ee()->config->item('rte_default_toolset') : null);
        if (!empty($toolsetId)) {
            $toolset = ee('Model')->get('rte:Toolset')->filter('toolset_id', $toolsetId)->first();
        } else {
            $toolset = ee('Model')->get('rte:Toolset')->first();
        }

        // Load proper toolset
        $serviceName = ucfirst($toolset->toolset_type) . 'Service';
        $configHandle = ee('rte:' . $serviceName)->init($this->settings, $toolset);

        // get the cache
        if (! isset(ee()->session->cache['rte'])) {
            ee()->session->cache['rte'] = array();
        }
        $cache = & ee()->session->cache['rte'];

        if (! isset($cache['displayed_grid_cols'])) {
            $cache['displayed_grid_cols'] = array();
        }

        if (! isset($cache['displayed_grid_cols'][$this->settings['col_id']])) {
            $defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

            ee()->javascript->output('Rte.gridColConfigs.col_id_' . $this->settings['col_id'] . ' = ["' . $configHandle . '", ' . $defer . '];');

            $cache['displayed_grid_cols'][$this->settings['col_id']] = true;
        }

        // convert file tags to URLs
        RteHelper::replaceFileTags($data);

        // convert asset tags to URLs
        RteHelper::replaceExtraTags($data);

        // convert site page tags to URLs
        RteHelper::replacePageTags($data);

        if (ee()->extensions->active_hook('rte_before_display')) {
            $data = ee()->extensions->call('rte_before_display', $this, $data);
        }

        ee()->load->helper('form');

        $field = array(
            'name' => $this->field_name,
            'value' => $data,
            'rows' => 10,
            'data-config' => $configHandle
        );

        return form_textarea($field);
    }

    /**
     * Display the field for Pro Variables
     *
     * @param mixed $data field data
     *
     * @return string $field
     */
    public function var_display_field($data)
    {
        return $this->display_field($data);
    }

    // --------------------------------------------------------------------

    /**
     * Validate the posted data.
     *
     * @param mixed $data
     *
     * @return mixed $result Error message in case of failed validation.
     */
    public function validate($data)
    {
        // is this a required field?
        if ($this->settings['field_required'] == 'y' && ! $data) {
            return lang('required');
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Save the field.
     *
     * @param string $data
     *
     * @return string $data
     */
    public function save($data)
    {
        // Trim out any whitespace/empty tags
        $data = preg_replace('/^(\s|<(\w+)>(&nbsp;|\s)*<\/\2>|<br \/>)*/', '', $data);
        $data = preg_replace('/(\s|<(\w+)>(&nbsp;|\s)*<\/\2>|<br \/>)*$/', '', $data);

        // Remove any ?cachebuster:X query strings
        $data = preg_replace('/\?cachebuster:\d+/', '', $data);

        // Entitize curly braces within codeblocks
        $data = preg_replace_callback('/<code>(.*?)<\/code>/s', function ($matches) {
            return str_replace(array("{","}"), array("&#123;","&#125;"), $matches[0]);
        }, $data);

        // Remove Firebug 1.5.2+ div
        $data = preg_replace('/<div firebugversion=(.|\t|\n|\s)*<\\/div>/', '', $data);

        // Decode double quote entities (&quot;)
        //  - Eventually CKEditor will stop converting these in the first place
        //    http://dev.ckeditor.com/ticket/6645
        $data = str_replace('&quot;', '"', $data);

        // Convert file URLs to tags
        RteHelper::replaceFileUrls($data);

        // Convert page URLs to tags
        RteHelper::replacePageUrls($data);

        if (ee()->extensions->active_hook('rte_before_save')) {
            $data = ee()->extensions->call('rte_before_save', $this, $data);
        }

        return $data;
    }

    public function save_cell($data)
    {
        return $this->save($data);
    }

    // --------------------------------------------------------------------

    /**
     * Pre-process the data before displaying.
     *
     * @param string $data
     *
     * @return string $data
     */
    public function pre_process($data)
    {
        $entrySiteId = (isset($this->row['entry_site_id']) ? $this->row['entry_site_id'] : null);

        // convert file tags to URLs
        RteHelper::replaceFileTags($data);

        // convert site page tags to URLs
        RteHelper::replacePageTags($data, $entrySiteId, true);

        // convert asset tags to URLs
        RteHelper::replaceExtraTags($data);

        ee()->load->library('typography');

        $tmp_encode_email = ee()->typography->encode_email;
        ee()->typography->encode_email = false;

        $tmp_convert_curly = ee()->typography->convert_curly;
        ee()->typography->convert_curly = false;

        $data = ee()->typography->parse_type($data, array(
            'text_format' => 'none',
            'html_format' => 'all',
            'auto_links' => (isset($this->row['channel_auto_link_urls']) ? $this->row['channel_auto_link_urls'] : 'n'),
            'allow_img_url' => (isset($this->row['channel_allow_img_urls']) ? $this->row['channel_allow_img_urls'] : 'y')
        ));

        ee()->typography->encode_email = $tmp_encode_email;
        ee()->typography->convert_curly = $tmp_convert_curly;

        // use normal quotes
        $data = str_replace('&quot;', '"', $data);

        return $data;
    }

    /**
     * Replace the {fieldname} tag in template.
     *
     * @param string $data field data
     * @param array  $params field parameters
     * @param string $tagdata template data
     *
     * @return string $data parsed template data
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        if (ee()->extensions->active_hook('rte_before_replace')) {
            $data = ee()->extensions->call('rte_before_replace', $this, $data);
        }

        //strip "read more" separator
        $data = preg_replace('/(<figure>)?<div class=\"readmore"><span[^<]+<\/span><\/div>(<\/figure>)?/', '', (string) $data);

        // return images only?
        if (isset($params['images_only']) && $params['images_only'] == 'yes') {
            $data = $this->_parseImages($data, $params, $tagdata);
        } elseif (isset($params['text_only']) && $params['text_only'] == 'yes') {
            // Text only?
            // Strip out the HTML tags
            $data = preg_replace('/<[^<]+?>/', '', $data);
        } else {
            // Remove images?
            if (isset($params['remove_images']) && $params['remove_images'] == 'yes') {
                $data = preg_replace('/<img(.*)>/Ums', '', $data);
            }
        }

        // added 01/15/2018 for additional transcribe support
        if (ee()->extensions->active_hook('rte_before_replace_end')) {
            $data = ee()->extensions->call('rte_before_replace_end', $this, $data);
        }

        return $data;
    }

    // --------------------------------------------------------------------

    /**
     * Replace the {fieldname:has_excerpt} tag.
     *
     * @param string $data field data
     *
     * @return string $result 'y'|''
     */
    public function replace_has_excerpt($data)
    {
        return (strpos($data, '<div class="readmore') !== false) ? 'y' : '';
    }

    /**
     * Replace the {fieldname:excerpt} tag.
     *
     * @param string $data field data
     * @param array  $params field parameters
     *
     * @return string $data The excerpt
     */
    public function replace_excerpt($data, $params)
    {
        if (($read_more_tag_pos = strpos($data, '<div class="readmore')) !== false) {
            $data = substr($data, 0, $read_more_tag_pos);
        }

        return $this->replace_tag($data, $params);
    }

    /**
     * Replace Extended Tag
     *
     * @param string $data field data
     * @param array  $params field parameters
     *
     * @return string $data The part after the excerpt
     */
    public function replace_extended($data, $params)
    {
        if (($read_more_tag_pos = strpos($data, '<div class="readmore')) !== false) {
            $data = substr($data, $read_more_tag_pos);
        } else {
            $data = '';
        }

        return $this->replace_tag($data, $params);
    }

    /**
     * Display Low Variable field value.
     *
     * @param string $data field data
     *
     * @return string $data
     */
    public function var_display_tag($data)
    {
        return $this->replace_tag($this->pre_process($data));
    }

    /**
     * Returns true if content type is accepted.
     *
     * @param string $name
     *
     * @return bool $result
     */
    public function accepts_content_type($name)
    {
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Returns field settings as array of form fields ready for EE Shared Form View.
     *
     * @param array $settings pre-existing setting values
     *
     * @return array $formFields
     */
    private function _fieldSettings($settings)
    {
        $settings = array_merge([
            'toolset_id' => ee()->config->item('rte_default_toolset'),
            'defer' => 'n'
        ], $settings);

        // load the language file
        ee()->lang->loadfile('rte');

        $configModels = ee('Model')->get('rte:Toolset')->all(true);
        $configOptions = array();
        foreach ($configModels as $model) {
            $configOptions[$model->toolset_id] = $model->toolset_name;
        }

        if (!empty($configOptions)) {
            $configFields = array(
                'rte[toolset_id]' => array(
                    'type' => 'select',
                    'choices' => $configOptions,
                    'value' => $settings['toolset_id']
                ),
                array(
                    'type' => 'html',
                    'content' => '(<a href="' . ee('CP/URL')->make('addons/settings/rte')->compile() . '">' . lang('rte_edit_configs') . '</a>)'
                )
            );
        } else {
            $configFields = array(
                array(
                    'type' => 'html',
                    'content' => '<a href="' . ee('CP/URL')->make('addons/settings/rte/edit_toolset')->compile() . '">' . lang('rte_create_config') . '</a>'
                )
            );
        }

        $settings = array(
            array(
                'title' => lang('rte_editor_config'),
                'fields' => $configFields
            ),
            array(
                'title' => lang('rte_defer'),
                'fields' => array(
                    'rte[defer]' => array(
                        'type' => 'yes_no',
                        'value' => (isset($settings['defer']) && $settings['defer'] == 'y') ? 'y' : 'n'
                    )
                )
            ),
            array(
                'title' => 'db_column_type',
                'desc' => 'db_column_type_desc',
                'fields' => array(
                    'rte[db_column_type]' => array(
                        'type' => 'radio',
                        'choices' => [
                            'text' => lang('TEXT'),
                            'mediumtext' => lang('MEDIUMTEXT')
                        ],
                        'value' => isset($settings['db_column_type']) ? $settings['db_column_type'] : 'text'
                    )
                )
            )
        );

        return $settings;
    }

    /**
     * Return just the information about images in the field data.
     *
     * @param string $data field data
     * @param array  $params field parameters
     * @param string $tagdata template string
     *
     * @return string $html resulting template string
     */
    private function _parseImages($data, $params, $tagdata)
    {
        $images = array();

        if ($tagdata) {
            $p = !empty($params['var_prefix']) ? rtrim($params['var_prefix'], ':') . ':' : '';
        }

        // find all the image tags
        preg_match_all('/<img(.*)>/Ums', $data, $img_matches, PREG_SET_ORDER);

        foreach ($img_matches as $i => $img_match) {
            if ($tagdata) {
                $img = array();

                // find all the attributes
                preg_match_all('/\s([\w-]+)=([\'"])([^\2]*?)\2/', $img_match[1], $attr_matches, PREG_SET_ORDER);

                foreach ($attr_matches as $attr_match) {
                    $img[$p . $attr_match[1]] = $attr_match[3];
                }

                // ignore image if it doesn't have a source
                if (empty($img[$p . 'src'])) {
                    continue;
                }

                // find all the styles
                if (! empty($img[$p . 'style'])) {
                    $styles = array_filter(explode(';', trim($img[$p . 'style'])));

                    foreach ($styles as $style) {
                        $style = explode(':', $style, 2);
                        $img[$p . 'style:' . trim($style[0])] = trim($style[1]);
                    }
                }

                // use the width and height styles if they're set
                if (! empty($img[$p . 'style:width']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p . 'style:width'], $width_match)) {
                    $img[$p . 'width'] = $width_match[1];
                    if ($width_match[2] == '%') {
                        $img[$p . 'width'] .= '%';
                    }
                }

                if (! empty($img[$p . 'style:height']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p . 'style:height'], $height_match)) {
                    $img[$p . 'height'] = $height_match[1];
                    if ($height_match[2] == '%') {
                        $img[$p . 'height'] .= '%';
                    }
                }

                $images[] = $img;
            } else {
                $images[] = $img_match[0];
            }
        }

        // ignore if there were no valid images
        if (! $images) {
            return;
        }

        if ($tagdata) {
            // get the absolute number of files before we run the filters
            $constants[$p . 'absolute_total_images'] = (!empty($images) ? count($images) : 0);
        }

        // offset and limit params
        if (isset($params['offset']) || isset($params['limit'])) {
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
            $limit = isset($params['limit']) ? (int) $params['limit'] : (!empty($images) ? count($images) : 0);

            $images = array_splice($images, $offset, $limit);
        }

        // ignore if there are no post-filter images
        if (! $images) {
            return;
        }

        if ($tagdata) {
            // get the filtered number of files
            $constants[$p . 'total_images'] = (!empty($images) ? count($images) : 0);

            // parse {total_images} and {absolute_total_images} first, since they'll never change
            $tagdata = ee()->TMPL->parse_variables_row($tagdata, $constants);

            // now parse all
            $r = ee()->TMPL->parse_variables($tagdata, $images);
        } else {
            $delimiter = isset($params['delimiter']) ? $params['delimiter'] : '<br />';
            $r = implode($delimiter, $images);
        }

        // backspace param
        if (!empty($params['backspace'])) {
            $chop = strlen($r) - $params['backspace'];
            $r = substr($r, 0, $chop);
        }

        return $r;
    }
}
