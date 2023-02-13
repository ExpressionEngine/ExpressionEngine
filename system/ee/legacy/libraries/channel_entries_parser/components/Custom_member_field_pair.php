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
 * Channel Parser Component (Custom Member Field Pairs)
 */
class EE_Channel_custom_member_field_pair_parser implements EE_Channel_parser_component
{
    /**
     * Check if custom fields are enabled.
     *
     * @param array     A list of "disabled" features
     * @return Boolean  Is disabled?
     */
    public function disabled(array $disabled, EE_Channel_preparser $pre)
    {
        return in_array('member_data', $disabled) or empty($pre->channel()->mpfields);
    }

    /**
     * Find any {field} {/field} tag pair chunks in the template and
     * extract them for easier parsing in the main loop.
     *
     * The returned chunks will be passed to replace() as a third parameter.
     *
     * @param String    The tagdata to be parsed
     * @param Object    The preparser object.
     * @return Array    The found custom field pair chunks
     */
    public function pre_process($tagdata, EE_Channel_preparser $pre)
    {
        $mpfield_chunk = array();

        $prefix = $pre->prefix();
        $channel = $pre->channel();

        foreach ($channel->mpfields as $field_name => $field_info) {
            if (! $pre->has_tag_pair($field_name)) {
                continue;
            }

            $mpfield_chunk[$field_name] = ee()->api_channel_fields->get_pair_field(
                $tagdata,
                $field_name,
                $prefix
            );
        }

        return $mpfield_chunk;
    }

    /**
     * Replace all of the custom channel pair fields.
     *
     * @param String    The tagdata to be parsed
     * @param Object    The channel parser object
     * @param Mixed     The results from the preparse method
     *
     * @return String   The processed tagdata
     */
    public function replace($tagdata, EE_Channel_data_parser $obj, $pfield_chunks)
    {
        $data = $obj->row();
        $prefix = $obj->prefix();

        $mfields = $obj->channel()->mfields;

        if (empty($mfields)) {
            return $tagdata;
        }

        $ft_api = ee()->api_channel_fields;

        // Check to see if the pair field chunks still exist; if not, check
        // the tagdata in case they've been modified since pre-processing.
        // This check appears before the main loop below in case any custom
        // fields were removed from the tagdata.
        foreach ($pfield_chunks as $tag_name => $chunks) {
            foreach ($chunks as $chk_data) {
                if (strpos($tagdata, $chk_data[3]) === false) {
                    $pfield_chunks[$tag_name] = ee()->api_channel_fields->get_pair_field(
                        $tagdata,
                        $tag_name,
                        $prefix
                    );

                    $obj->preparsed()->set_once_data($this, $pfield_chunks);

                    break;
                }
            }
        }

        foreach ($pfield_chunks as $tag_name => $chunks) {
            $field_name = preg_replace('/^' . $prefix . '/', '', $tag_name);
            $field_name = substr($field_name, strpos($field_name, ' '));
            if (!isset($mfields[$field_name])) {
                continue;
            }
            $field_id = $mfields[$field_name][0];

            $ft = $ft_api->setup_handler('m_' . $field_id, true);
            $ft_name = $ft_api->field_type;

            if ($ft) {
                $_ft_path = $ft_api->ft_paths[$ft_api->field_type];
                ee()->load->add_package_path($_ft_path, false);

                $ft->_init(array(
                    'row' => $data,
                    'content_id' => $data['entry_id'],
                    'content_type' => 'channel'
                ));

                $pre_processed = '';
                if (array_key_exists('m_field_id_' . $field_id, $data)) {
                    $pre_processed = $ft_api->apply('pre_process', array(
                        $data['m_field_id_' . $field_id]
                    ));
                }

                foreach ($chunks as $chk_data) {
                    // If some how the fieldtype that the channel fields
                    // API is referencing changed to another fieldtype
                    // (Grid may cause this), get it back on track to
                    // parse the next chunk
                    if ($ft_name != $ft_api->field_type || $ft->id() != $field_id) {
                        $ft_api->setup_handler($field_id);
                    }

                    list($modifier, $content, $params, $chunk) = $chk_data;

                    $tpl_chunk = '';

                    // Set up parse function name based on whether or not
                    // we have a modifier
                    $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

                    if (method_exists($ft, $parse_fnc)) {
                        $tpl_chunk = $ft_api->apply($parse_fnc, array(
                            $pre_processed,
                            $params,
                            $content
                        ));
                    }
                    // Go to catchall and include modifier
                    elseif (method_exists($ft, 'replace_tag_catchall') and $modifier !== '') {
                        $tpl_chunk = $ft_api->apply('replace_tag_catchall', array(
                            $pre_processed,
                            $params,
                            $content,
                            $modifier
                        ));
                    }

                    // if the fieldtype returned NULL, set to empty string
                    // as templates are working with strings
                    if (is_null($tpl_chunk)) {
                        $tpl_chunk = '';
                    }

                    $tagdata = str_replace($chunk, $tpl_chunk, $tagdata);
                }

                ee()->load->remove_package_path($_ft_path);
            }
        }

        return $tagdata;
    }
}
