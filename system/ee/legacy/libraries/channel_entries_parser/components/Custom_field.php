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
 * Channel Parser Component (Custom Fields)
 */
class EE_Channel_custom_field_parser implements EE_Channel_parser_component
{
    /**
     * Check if custom fields are enabled.
     *
     * @param array     A list of "disabled" features
     * @return Boolean  Is disabled?
     */
    public function disabled(array $disabled, EE_Channel_preparser $pre)
    {
        return in_array('custom_fields', $disabled);
    }

    /**
     * @todo Find all of the tags like the custom date fields?
     *
     * @param String    The tagdata to be parsed
     * @param Object    The preparser object.
     * @return Object   Channel fields api, to reduce a lookup (for now)
     */
    public function pre_process($tagdata, EE_Channel_preparser $pre)
    {
        return ee()->api_channel_fields;
    }

    /**
     * Replace all of the custom channel fields.
     *
     * @param String    The tagdata to be parsed
     * @param Object    The channel parser object
     * @param Mixed     The results from the preparse method
     *
     * @return String   The processed tagdata
     */
    public function replace($tagdata, EE_Channel_data_parser $obj, $ft_api)
    {
        $tag = $obj->tag();
        $data = $orig_data = $obj->row();
        $prefix = $obj->prefix();

        $site_id = $data['site_id'];
        $cfields = $obj->channel()->cfields;
        $rfields = $obj->channel()->rfields;
        $gfields = $obj->channel()->gfields;
        $msfields = $obj->channel()->msfields;
        $ffields = $obj->channel()->ffields;

        $rfields = isset($rfields[$site_id]) ? $rfields[$site_id] : [];
        $cfields = isset($cfields[$site_id]) ? $cfields[$site_id] : [];
        $gfields = isset($gfields[$site_id]) ? $gfields[$site_id] : [];
        $msfields = isset($msfields[$site_id]) ? $msfields[$site_id] : [];
        $ffields = isset($ffields[$site_id]) ? $ffields[$site_id] : [];

        $cfields = array_diff_key($cfields, $rfields);

        if (empty($cfields)) {
            return $tagdata;
        }

        $field = ee('Variables/Parser')->parseVariableProperties($tag, $prefix);

        if ($field['invalid_modifier']) {
            return $tagdata;
        }

        if (isset($cfields[$field['field_name']])) {
            $entry = '';
            $field_id = $cfields[$field['field_name']];

            //if the field is conditionally hidden, do not parse
            if (isset($obj->channel()->hidden_fields[$data['entry_id']]) && in_array($field_id, $obj->channel()->hidden_fields[$data['entry_id']])) {
                $tagdata = str_replace(LD . $tag . RD, '', $tagdata);

                return $tagdata;
            }

            if (
                (isset($data['field_id_' . $field_id]) && $data['field_id_' . $field_id] !== '') or
                array_key_exists($field['field_name'], $gfields) or // is a Grid single
                array_key_exists($field['field_name'], $msfields) or // is a Member select single
                array_key_exists($field['field_name'], $ffields) // is a Fluid single
            ) {
                $obj = $ft_api->setup_handler($field_id, true);

                if ($obj) {
                    $_ft_path = $ft_api->ft_paths[$ft_api->field_type];
                    ee()->load->add_package_path($_ft_path, false);

                    $obj->_init(array(
                        'row'          => $data,
                        'content_id'   => $data['entry_id'],
                        'content_type' => 'channel'
                    ));

                    $data = $ft_api->apply('pre_process', array(
                        $data['field_id_' . $field_id]
                    ));

                    $checkNextModifier = method_exists($obj, 'getChainableModifiersThatRequireArray');
                    if ($checkNextModifier) {
                        $modifiersRequireArray = $obj->getChainableModifiersThatRequireArray($data);
                    }

                    if (isset($field['all_modifiers']) && !empty($field['all_modifiers'])) {
                        $modifiers = array_keys($field['all_modifiers']);
                        $modifiersCounter = 0;
                        foreach ($field['all_modifiers'] as $modifier => $params) {
                            unset($modifiers[$modifiersCounter]);
                            $modifiersCounter++;
                            $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

                            // if there is next modifier, make sure to return array
                            $content_param = ($checkNextModifier && isset($modifiers[$modifiersCounter]) && in_array($modifiers[$modifiersCounter], $modifiersRequireArray)) ? null : false;
                            if (method_exists($obj, $parse_fnc) || ee('Variables/Modifiers')->has($modifier)) {
                                $entry = $ft_api->apply($parse_fnc, array(
                                    $data,
                                    $params,
                                    $content_param
                                ));
                            } elseif (method_exists($obj, 'replace_tag_catchall')) {
                                $entry = $ft_api->apply('replace_tag_catchall', array(
                                    $data,
                                    $params,
                                    $content_param,
                                    is_null($content_param) ? $modifier : $field['full_modifier']
                                ));
                            }
                            if (!is_null($content_param)) {
                                $entry = (string) $entry;
                            }
                            // set the data to parsed variable for next cycle
                            $data = $entry;
                        }
                    } else {
                        $modifier = $field['modifier'];

                        $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

                        if (method_exists($obj, $parse_fnc) || ee('Variables/Modifiers')->has($modifier)) {
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
                    }

                    ee()->load->remove_package_path($_ft_path);

                    //frontend edit link

                    if ($ft_api->field_type != 'fluid_field') {
                        $frontedit_disabled = false;
                        $frontEditLink = '';
                        if (isset($obj->disable_frontedit) && $obj->disable_frontedit == true) {
                            $frontedit_disabled = true;
                        } elseif (isset($field['params']['disable'])) {
                            $disable = explode("|", $field['params']['disable']);
                            if (in_array('frontedit', $disable)) {
                                $frontedit_disabled = true;
                            }
                        }
                        if (!$frontedit_disabled) {
                            $frontEditLink = ee('pro:FrontEdit')->entryFieldEditLink($orig_data['site_id'], $orig_data['channel_id'], $orig_data['entry_id'], $field_id);
                        }
                        $fulltag = $tag;
                        if ($prefix != '' && strpos($tag, $prefix) !== 0) {
                            $fulltag = $prefix . $tag;
                        }
                        $tagdata = str_replace(LD . $fulltag . ($modifier != 'frontedit' ? ':frontedit' : '') . RD, $frontEditLink, $tagdata);
                        $tag = trim(str_replace(['disable="frontedit"', "disable='frontedit'"], '', $tag));
                    }
                } else {
                    // Couldn't find a fieldtype
                    $entry = ee()->typography->parse_type(
                        ee()->functions->encode_ee_tags($data['field_id_' . $field_id]),
                        array(
                            'text_format'   => $data['field_ft_' . $field_id],
                            'html_format'   => $data['channel_html_formatting'],
                            'auto_links'    => $data['channel_auto_link_urls'],
                            'allow_img_url' => $data['channel_allow_img_urls']
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

                // taking the relationship field name out of the field for channel forms.
                /*$channel_form_field_name = $tag;
                if(!empty($rfields))
                {
                  foreach($rfields as $field_name => $num)
                  {
                                $channel_form_field_name = str_replace($field_name.':', '', $tag);
                                var_dump($channel_form_field_name);
                                var_dump($num);
                                var_dump($field['field_name']);
                                var_dump($field_id);
                                echo '<hr>';
                  }
                        }*/

                $tagdata = str_replace(LD . $tag . RD, $entry, $tagdata);
            }

            $tagdata = str_replace(LD . $tag . RD, '', $tagdata);
        }

        return $tagdata;
    }
}
