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
 * Channel Parser Component (Custom Member Fields)
 */
class EE_Channel_custom_member_field_parser implements EE_Channel_parser_component
{
    protected $member_field_models = array();

    /**
     * Check if member fields are enabled.
     *
     * @param array A list of "disabled" features
     * @return bool  Is disabled?
     */
    public function disabled(array $disabled, EE_Channel_preparser $pre)
    {
        return in_array('member_data', $disabled) or empty($pre->channel()->mfields);
    }

    /**
     * Reset the processed member tags cache.
     *
     * @todo Find all fields like the custom dates?
     *
     * @param string    The tagdata to be parsed
     * @param object    The preparser object.
     * @return void
     */
    public function pre_process($tagdata, EE_Channel_preparser $pre)
    {
        return ee()->api_channel_fields;
    }

    /**
     * Replace all of the custom member data fields.
     *
     * @param string    The tagdata to be parsed
     * @param object    The channel parser object
     * @param mixed     The results from the preparse method
     *
     * @return string   The processed tagdata
     */
    public function replace($tagdata, EE_Channel_data_parser $obj, $ft_api)
    {
        $mfields = $obj->channel()->mfields;

        $tag = $obj->tag();
        $val = $obj->tag_options();

        $data = $obj->row();
        $prefix = $obj->prefix();

        $field = ee('Variables/Parser')->parseVariableProperties($tag, $prefix);

        if (! isset($mfields[$field['field_name']])) {
            return $tagdata;
        }

        $field_id = $mfields[$field['field_name']][0];

        $entry = '';

        if (
            array_key_exists('m_field_id_' . $field_id, $data)
            && $data['m_field_id_' . $field_id] != ''
        ) {
            $modifier = $field['modifier'];

            $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

            $obj = $ft_api->setup_handler('m_' . $field_id, true);

            if ($obj) {
                $_ft_path = $ft_api->ft_paths[$ft_api->field_type];
                ee()->load->add_package_path($_ft_path, false);

                $obj->_init(array(
                    'row'          => $data,
                    'content_id'   => $data['entry_id'],
                    'content_type' => 'channel'
                ));

                $data = $ft_api->apply('pre_process', array(
                    $data['m_field_id_' . $field_id]
                ));

                if ($ft_api->field_type == 'date') {
                    // Set 0 to NULL, kill any formatting
                    $data = ($data == 0) ? null : $data;
                }

                if (method_exists($obj, $parse_fnc)) {
                    $entry = (string) $ft_api->apply($parse_fnc, array(
                        $data,
                        $field['params'],
                        false
                    ));
                } elseif (method_exists($obj, 'replace_tag_catchall')) {
                    $entry = (string) $ft_api->apply('replace_tag_catchall', array(
                        $data,
                        $field['params'],
                        false,
                        $field['full_modifier']
                    ));
                }

                ee()->load->remove_package_path($_ft_path);
            } else {
                // Couldn't find a fieldtype
                $entry = ee()->typography->parse_type(
                    ee()->functions->encode_ee_tags($data['m_field_id_' . $field_id]),
                    array(
                        'text_format'   => 'none',
                        'html_format'   => 'safe',
                        'auto_links'    => 'y',
                        'allow_img_url' => 'n'
                    )
                );
            }

            // prevent accidental parsing of other channel variables in custom field data
            if (strpos($entry, '{') !== false) {
                $entry = str_replace(
                    array('{', '}'),
                    array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')),
                    $entry
                );
            }

            $tagdata = str_replace(LD . $tag . RD, $entry, $tagdata);
        }

        $tagdata = str_replace(LD . $tag . RD, '', $tagdata);

        return $tagdata;
    }
}

// EOF
