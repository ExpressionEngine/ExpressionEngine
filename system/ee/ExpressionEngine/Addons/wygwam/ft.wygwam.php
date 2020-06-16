<?php

use ExpressionEngine\Addons\Wygwam\Helper;

/**
 * Wygwam Fieldtype Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) 2016 Pixel & Tonic, Inc
 */
class Wygwam_ft extends EE_Fieldtype
{
    public $has_array_data = true;

    public $entry_manager_compatible = true;

    public $info = [
        'name' => 'Wygwam',
        'version' => '6.0.0'
    ];


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
            'field_options_wygwam' => array(
                'label' => 'field_options',
                'group' => 'wygwam',
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


    /**
     * Display Matrix cell settings.
     *
     * @param  $settings
     *
     * @return array $formFields Ready to be used in the EE Shared Form View
     */
    public function display_cell_settings($settings)
    {
        $settings = $this->display_settings($settings);


        $html = '';
        foreach ($settings as $name => $setting) {
            $html .= ee('View')->make('ee:_shared/form/section')
                ->render(array('name' => $name, 'settings' => $setting));
        }

        return $html;
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
        $settings = ee('Request')->post('wygwam');

        // Give it the full width
        $settings['field_wide'] = true;

        // cross the T's
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
        $settings = $settings['wygwam'];

        return $settings;
    }

    /**
     * Save Matrix cell settings.
     *
     * @param @settings
     *
     * @return array $settings
     */
    public function save_cell_settings($settings)
    {
        $settings = $settings['wygwam'];

        return $settings;
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
        Helper::includeFieldResources();
        $configHandle = Helper::insertConfigJsById(!empty($this->settings['config_id']) ? $this->settings['config_id'] : null);

        $id = str_replace(array('[', ']'), array('_', ''), $this->field_name);
        $defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

        if (strpos($id, '_new_') === false) {
            Helper::insertJs('new Wygwam("'.$id.'", "'.$configHandle.'", '.$defer.');');
        }

        // pass the data through form_prep() if this is SafeCracker
        if (REQ == 'PAGE') {
            $data = form_prep($data, $this->field_name);
        }

        // convert file tags to URLs
        Helper::replaceFileTags($data);

        // convert asset tags to URLs
        $assetInfo = Helper::replaceAssetTags($data);

        // convert site page tags to URLs
        Helper::replacePageTags($data);

        if (ee()->extensions->active_hook('wygwam_before_display')) {
            $data = ee()->extensions->call('wygwam_before_display', $this, $data);
        }

        return '<div class="wygwam"><textarea id="'.$id.'" name="'.$this->field_name.'" rows="10" data-config="'.$configHandle.'" class="wygwam-textarea" data-defer="'.(isset($this->settings['defer']) && !empty($this->settings['defer']) && $this->settings['defer'] == 'y' ? 'y' : 'n').'">'.$data.'</textarea></div>'.$this->_generateAssetInputsString($assetInfo);
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
        Helper::includeFieldResources();
        $configHandle = Helper::insertConfigJsById(!empty($this->settings['config_id']) ? $this->settings['config_id'] : null);

        // get the cache
        if (! isset(ee()->session->cache['wygwam'])) {
            ee()->session->cache['wygwam'] = array();
        }
        $cache =& ee()->session->cache['wygwam'];

        if (! isset($cache['displayed_grid_cols'])) {
            Helper::includeThemeJs('scripts/grid.js');
            $cache['displayed_grid_cols'] = array();
        }

        if (! isset($cache['displayed_grid_cols'][$this->settings['col_id']])) {
            $defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

            Helper::insertJs('Wygwam.gridColConfigs.col_id_'.$this->settings['col_id'].' = ["'.$configHandle.'", '.$defer.'];');

            $cache['displayed_grid_cols'][$this->settings['col_id']] = true;
        }

        // convert file tags to URLs
        Helper::replaceFileTags($data);

        // convert asset tags to URLs
        $assetInfo = Helper::replaceAssetTags($data);

        // convert site page tags to URLs
        Helper::replacePageTags($data);

        if (ee()->extensions->active_hook('wygwam_before_display')) {
            $data = ee()->extensions->call('wygwam_before_display', $this, $data);
        }

        return '<textarea name="'.$this->field_name.'" rows="10" data-config="'.$configHandle.'">'.$data.'</textarea>'.$this->_generateAssetInputsString($assetInfo);
    }

    /**
     * Display the field for Matrix
     *
     * @param mixed $data field data
     *
     * @return string $field
     */
    public function display_cell($data)
    {
        Helper::includeFieldResources();
        $configHandle = Helper::insertConfigJsById(!empty($this->settings['config_id']) ? $this->settings['config_id'] : null);

        // get the cache
        if (! isset(ee()->session->cache['wygwam'])) {
            ee()->session->cache['wygwam'] = array();
        }
        $cache =& ee()->session->cache['wygwam'];

        if (! isset($cache['displayed_cols'])) {
            Helper::includeThemeJs('scripts/matrix2.js');
            $cache['displayed_cols'] = array();
        }

        if (! isset($cache['displayed_cols'][$this->settings['col_id']])) {
            $defer = (isset($this->settings['defer']) && $this->settings['defer'] == 'y') ? 'true' : 'false';

            Helper::insertJs('Wygwam.matrixColConfigs.col_id_'.$this->settings['col_id'].' = ["'.$configHandle.'", '.$defer.'];');

            $cache['displayed_cols'][$this->settings['col_id']] = true;
        }

        // convert file tags to URLs
        Helper::replaceFileTags($data);

        // convert asset tags to URLs
        $assetInfo = Helper::replaceAssetTags($data);

        // convert site page tags to URLs
        Helper::replacePageTags($data);

        if (ee()->extensions->active_hook('wygwam_before_display')) {
            $data = ee()->extensions->call('wygwam_before_display', $this, $data);
        }

        return '<textarea class="wygwam-textarea" name="'.$this->cell_name.'" rows="10" data-config="'.$configHandle.'">'.$data.'</textarea>'.$this->_generateAssetInputsString($assetInfo);
    }

    /**
     * Display the field for Low Variables
     *
     * @param mixed $data field data
     *
     * @return string $field
     */
    public function var_display_field($data)
    {
        // Low Variables doesn't mix in the fieldtype's global settings,
        // so we'll do it manually here
        $this->settings = array_merge($this->settings, Helper::getGlobalSettings());

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

        // Convert URLs to tags if we have to.
        $preventConversion = ee()->config->item('wygwam_prevent_url_conversion');

        if (!$preventConversion || $preventConversion == "n" || $preventConversion == "no") {
            $data = $this->_convertUrlsToTags($data);
        }

        // Preserve Read More comments
        //  - For whatever reason, SafeCracker is converting HTML comment brackets into entities
        $data = str_replace('&lt;!--read_more--&gt;', '<!--read_more-->', $data);

        if (ee()->extensions->active_hook('wygwam_before_save')) {
            $data = ee()->extensions->call('wygwam_before_save', $this, $data);
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
        Helper::$entrySiteId = (isset($this->row['entry_site_id']) ? $this->row['entry_site_id'] : null);

        // convert file tags to URLs
        Helper::replaceFileTags($data);

        // convert asset tags to URLs
        Helper::replaceAssetTags($data);

        // convert site page tags to URLs
        Helper::replacePageTags($data);

        ee()->load->library('typography');

        $tmp_encode_email = ee()->typography->encode_email;
        ee()->typography->encode_email = false;

        $tmp_convert_curly = ee()->typography->convert_curly;
        ee()->typography->convert_curly = false;

        $data = ee()->typography->parse_type($data, array(
            'text_format'   => 'none',
            'html_format'   => 'all',
            'auto_links'    => (isset($this->row['channel_auto_link_urls']) ? $this->row['channel_auto_link_urls'] : 'n'),
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
        // return images only?
        if (isset($params['images_only']) && $params['images_only'] == 'yes') {
            $data = $this->_parseImages($data, $params, $tagdata);
        }

        // Text only?
        elseif (isset($params['text_only']) && $params['text_only'] == 'yes') {
            // Strip out the HTML tags
            $data = preg_replace('/<[^<]+?>/', '', $data);
        } else {
            // Remove images?
            if (isset($params['remove_images']) && $params['remove_images'] == 'yes') {
                $data = preg_replace('/<img(.*)>/Ums', '', $data);
            }

            // strip out the {read_more} tag
            $data = str_replace('<!--read_more-->', '', $data);
        }

        if (ee()->extensions->active_hook('wygwam_before_replace')) {
            $data = ee()->extensions->call('wygwam_before_replace', $this, $data);
        }

        // convert file tags to URLs
        Helper::replaceFileTags($data);

        // convert asset tags to URLs
        Helper::replaceAssetTags($data);

        // convert site page tags to URLs
        Helper::replacePageTags($data);

        // added 01/15/2018 for additional transcribe support
        if (ee()->extensions->active_hook('wygwam_before_replace_end')) {
            $data = ee()->extensions->call('wygwam_before_replace_end', $this, $data);
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
        return (strpos($data, '<!--read_more-->') !== false) ? 'y' : '';
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
        if (($read_more_tag_pos = strpos($data, '<!--read_more-->')) !== false) {
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
        if (($read_more_tag_pos = strpos($data, '<!--read_more-->')) !== false) {
            $data = substr($data, $read_more_tag_pos + 16);
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
        return in_array($name, array('channel', 'grid', 'low_variables', 'fluid_field', 'blocks/1'));
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
        $settings = array_merge(Helper::defaultSettings(), $settings);

        // load the language file
        ee()->lang->loadfile('wygwam');

        $configModels = ee('Model')->get('wygwam:Config')->all();
        $configOptions = array();
        foreach ($configModels as $model) {
            $configOptions[$model->config_id] = $model->config_name;
        }

        if (!empty($configOptions)) {
            $configFields = array(
                'wygwam[config_id]' => array(
                    'type'    => 'select',
                    'choices' => $configOptions,
                    'value'   => $settings['config_id']
                ),
                array(
                    'type'    => 'html',
                    'content' => '(<a href="'.Helper::getMcpUrl('index').'">'.lang('wygwam_edit_configs').'</a>)'
                )
            );
        } else {
            $configFields = array(
                array(
                    'type'    => 'html',
                    'content' => '<a href="'.Helper::getMcpUrl('editConfig').'">'.lang('wygwam_create_config').'</a>'
                )
            );
        }

        $settings = array(
            array(
                'title' => lang('wygwam_editor_config'),
                'fields' => $configFields
            ),
            array(
                'title' => lang('wygwam_defer'),
                'fields' => array(
                    'wygwam[defer]' => array(
                        'type' => 'yes_no',
                        'value' => (isset($settings['defer']) && $settings['defer'] == 'y') ? 'y' : 'n'
                    )
                )
            )
        );

        return $settings;
    }

    /**
     * Convert URLs to Wygwam Tags.
     *
     * @param string $html
     *
     * @return string $resultingHtml
     */
    private function _convertUrlsToTags($html)
    {
        /**
         * @var $request EllisLab\ExpressionEngine\Core\Request;
         */
        $request = ee('Request');

        $assetIds = $request->post('wygwam_asset_ids');
        $assetUrls = $request->post('wygwam_asset_urls');

        // If they select any files using Assets.
        if (!empty($assetIds) && !empty($assetUrls) && count($assetIds) == count($assetUrls)) {
            // Convert Asset URLs to tags
            Helper::replaceAssetUrls($html, $assetIds, $assetUrls);
        }

        // Convert file URLs to tags
        Helper::replaceFileUrls($html);

        // Convert page URLs to tags
        Helper::replacePageUrls($html);

        return $html;
    }

    /**
     * Generate Asset input string for fields where Assets is used as file picker.
     *
     * @param $assetInfo
     *
     * @return string $inputString
     */
    private function _generateAssetInputsString($assetInfo)
    {
        $inputString = '';
        $num_assets = (!empty($assetInfo['ids']) ? count($assetInfo['ids']) : 0);

        for ($counter = 0; $counter < $num_assets; $counter++) {
            $inputString .= '<input type="hidden" name="wygwam_asset_ids[]" value="'.$assetInfo['ids'][$counter].'" />';
            $inputString .= '<input type="hidden" name="wygwam_asset_urls[]" value="'.$assetInfo['urls'][$counter].'" />';
        }

        return $inputString;
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
            $p = !empty($params['var_prefix']) ? rtrim($params['var_prefix'], ':').':' : '';
        }

        // find all the image tags
        preg_match_all('/<img(.*)>/Ums', $data, $img_matches, PREG_SET_ORDER);

        foreach ($img_matches as $i => $img_match) {
            if ($tagdata) {
                $img = array();

                // find all the attributes
                preg_match_all('/\s([\w-]+)=([\'"])([^\2]*?)\2/', $img_match[1], $attr_matches, PREG_SET_ORDER);

                foreach ($attr_matches as $attr_match) {
                    $img[$p.$attr_match[1]] = $attr_match[3];
                }

                // ignore image if it doesn't have a source
                if (empty($img[$p.'src'])) {
                    continue;
                }

                // find all the styles
                if (! empty($img[$p.'style'])) {
                    $styles = array_filter(explode(';', trim($img[$p.'style'])));

                    foreach ($styles as $style) {
                        $style = explode(':', $style, 2);
                        $img[$p.'style:'.trim($style[0])] = trim($style[1]);
                    }
                }

                // use the width and height styles if they're set
                if (! empty($img[$p.'style:width']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p.'style:width'], $width_match)) {
                    $img[$p.'width'] = $width_match[1];
                    if ($width_match[2] == '%') {
                        $img[$p.'width'] .= '%';
                    }
                }

                if (! empty($img[$p.'style:height']) && preg_match('/(\d+?\.?\d+)(px|%)/', $img[$p.'style:height'], $height_match)) {
                    $img[$p.'height'] = $height_match[1];
                    if ($height_match[2] == '%') {
                        $img[$p.'height'] .= '%';
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
            $constants[$p.'absolute_total_images'] = (!empty($images) ? count($images) : 0);
        }

        // offset and limit params
        if (isset($params['offset']) || isset($params['limit'])) {
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
            $limit  = isset($params['limit'])  ? (int) $params['limit']  : (!empty($images) ? count($images) : 0);

            $images = array_splice($images, $offset, $limit);
        }

        // ignore if there are no post-filter images
        if (! $images) {
            return;
        }

        if ($tagdata) {
            // get the filtered number of files
            $constants[$p.'total_images'] = (!empty($images) ? count($images) : 0);

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
