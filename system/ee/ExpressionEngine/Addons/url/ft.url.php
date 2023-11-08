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
 * URL Fieldtype
 */
class Url_Ft extends EE_Fieldtype
{

    /**
     * @var array $info Legacy Fieldtype info array
     */
    public $info = array(
        'name' => 'URL',
        'version' => '1.0.0'
    );

    /**
     * @var bool $has_array_data Whether or not this Fieldtype is setup to parse as a tag pair
     */
    public $has_array_data = false;

    public $defaultEvaluationRule = 'isNotEmpty';

    /**
     * Validate Field
     *
     *  Note, we can't use filter_var() here, as FILTER_VALIDATE_URL will not
     *  validate internationalized URLs that contain non-ASCII characters.
     *  Plus, FILTER_VALIDATE_URL uses parse_url() internally for some bits
     *  anyway, go figure.
     *
     * @param  array  $data  Field data
     * @return mixed  TRUE when valid, an error string when not
     */
    public function validate($data)
    {
        ee()->lang->loadfile('fieldtypes');

        if ($data == '') {
            return true;
        }

        // we will save the URL ready to use in HTML attributes, so validate on how it will be stored
        $data = $this->prepForStorage($data);

        // nothing gets past this
        if (! $parsed_url = parse_url($data)) {
            return lang('url_ft_invalid_url');
        }

        // is the scheme valid?
        if (! isset($parsed_url['host']) or ! isset($parsed_url['scheme'])) {
            // check for protocol relativity allowance before bailing
            if (
                in_array('/', $this->get_setting('allowed_url_schemes'))
                && strncasecmp($data, '/', 1) === 0
                ) {
                // I'll allow it!
                return true;
            }

            if (
                in_array('//', $this->get_setting('allowed_url_schemes'))
                && strncasecmp($data, '//', 2) === 0
                ) {
                // I'll allow it!
                return true;
            }

            // mailto: won't have a 'host', but should have a 'path'
            if (
                isset($parsed_url['scheme'], $parsed_url['path']) &&
                $parsed_url['scheme'] == 'mailto' &&
                in_array('mailto:', $this->get_setting('allowed_url_schemes'))
            ) {
                return true;
            }

            return sprintf(lang('url_ft_invalid_url_scheme'), '<code>' . implode('</code>, <code>', $this->get_setting('allowed_url_schemes')) . '</code>');
        }

        $scheme = $parsed_url['scheme'] . '://';

        if (! in_array($scheme, $this->get_setting('allowed_url_schemes'))) {
            return sprintf(lang('url_ft_invalid_url_scheme'), '<code>' . implode('</code>, <code>', $this->get_setting('allowed_url_schemes')) . '</code>');
        }

        return true;
    }

    /**
     * Save Field
     *
     * @param  array   $data  Field data
     * @return string  Prepped Form field
     */
    public function save($data)
    {
        return $this->prepForStorage($data);
    }

    /**
     * Display Field
     *
     * @param  array   $data  Field data
     * @return string  Form field
     */
    public function display_field($data)
    {
        $default_scheme = $this->get_setting('url_scheme_placeholder');

        $field = array(
            'name' => $this->field_name,
            'value' => $data,
            'placeholder' => $default_scheme
        );

        if ($this->get_setting('field_disabled')) {
            $field['disabled'] = 'disabled';
        }

        return form_input($field);
    }

    /**
     * Replace Tag
     *
     * @param  string  $data     The URL
     * @param  array   $params   Variable tag parameters
     * @param  mixed   $tagdata  The tagdata if a var pair, FALSE if not
     * @return string  Parsed string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        return ee()->functions->encode_ee_tags($data);
    }

    /**
     * Display Settings
     *
     * @param  array  $data  Field Settings
     * @return array  Field options
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');

        $schemes  =$this->getSchemes();

        $settings = array(
            array(
                'title' => 'url_ft_allowed_url_schemes',
                'fields' => array(
                    'allowed_url_schemes' => array(
                        'type' => 'checkbox',
                        'choices' => $schemes,
                        'value' => (isset($data['allowed_url_schemes'])) ? $data['allowed_url_schemes'] : $this->getSchemes(true),
                        'required' => true
                    )
                )
            ),
            array(
                'title' => 'url_ft_url_scheme_placeholder',
                'desc' => 'url_ft_url_scheme_placeholder_desc',
                'fields' => array(
                    'url_scheme_placeholder' => array(
                        'type' => 'radio',
                        'choices' => $schemes,
                        'value' => (isset($data['url_scheme_placeholder'])) ? $data['url_scheme_placeholder'] : array_shift($schemes),
                        'required' => true
                    )
                )
            )
        );

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_url' => array(
            'label' => 'field_options',
            'group' => 'url',
            'settings' => $settings
        ));
    }

    /**
     * Save Settings
     *
     * @param  array  $data  Field data
     * @return array  Settings to save
     */
    public function save_settings($data)
    {
        $defaults = array(
            'allowed_url_schemes' => $this->getSchemes(true),
            'url_scheme_placeholder' => ''
        );

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }

    /**
     * Accept all content types.
     *
     * @param  string  The name of the content type
     * @return bool    Accepts all content types
     */
    public function accepts_content_type($name)
    {
        return true;
    }

    /**
     * Get Schemes
     *
     * @param  bool   $only_defaults  Whether or not to only return the default set
     * @return array  Valid URL Scheme Options
     */
    private function getSchemes($only_defaults = false)
    {
        $protocols = array(
            'http://' => 'http://',
            'https://' => 'https://'
        );

        if ($only_defaults) {
            return $protocols;
        }

        $protocols += [
            '/' => '/ (' . lang('url_ft_single_slash_protocol_relative_url') . ')',
            '//' => '// (' . lang('url_ft_protocol_relative_url') . ')',
            'ftp://' => 'ftp://',
            'mailto:' => 'mailto:',
            'sftp://' => 'sftp://',
            'ssh://' => 'ssh://',
            'tel://' => 'tel://',
        ];

        return $protocols;
    }

    /**
     * Prep For Storage
     *
     * @param  string  $url  The URL to store
     * @return string  A sanitized string, ready for storage and use in HTML attributes
     */
    private function prepForStorage($url)
    {
        // disable $double_encode so entities don't bubble out of control on edits
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false);
    }
}
// END CLASS

// EOF
