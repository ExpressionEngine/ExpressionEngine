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
 * Channel Parser Component (Relationships)
 */
class EE_Channel_relationship_parser implements EE_Channel_parser_component
{
    /**
     * Check if relationships are enabled.
     *
     * @param array		A list of "disabled" features
     * @return Boolean	Is disabled?
     */
    public function disabled(array $disabled, EE_Channel_preparser $pre)
    {
        return empty($pre->channel()->rfields) or in_array('relationships', $disabled);
    }

    /**
     * Set up the relationship parser's tree and data pre-caching.
     *
     * The returned object will be passed to replace() as a third parameter.
     *
     * @param String	The tagdata to be parsed
     * @param Object	The preparser object.
     * @return Array	The relationship parser object
     */
    public function pre_process($tagdata, EE_Channel_preparser $pre)
    {
        if (empty($pre->channel()->rfields)) {
            return null;
        }

        ee()->load->library('relationships_parser');
        ee()->load->model('grid_model');

        try {
            $grid_relationships = [];
            $gfields = $pre->channel()->gfields;

            foreach ($pre->site_ids() as $site_id) {
                // Skip a site if it has no Grid fields
                if (! isset($gfields[$site_id]) or empty($gfields[$site_id])) {
                    continue;
                }

                // Cache all fields for this site for lookup below
                ee()->grid_model->get_columns_for_field(array_values($gfields[$site_id]), 'channel');

                foreach ($gfields[$site_id] as $field_name => $field_id) {
                    $prefix = $field_name . ':';

                    $columns = ee()->grid_model->get_columns_for_field($field_id, 'channel');

                    foreach ($columns as $col) {
                        if ($col['col_type'] == 'relationship') {
                            $grid_relationships[$prefix . $col['col_name']] = $col['col_id'];
                        }
                    }
                }
            }

            $disabledFeatures = $pre->disabledFeatures();
            if (strpos($tagdata, 'categories') === false) {
                $disableCategories = true;
                $tagStrings = array_merge($pre->pairs, $pre->singles);
                if (!empty($tagStrings)) {
                    foreach ($tagStrings as $string => $data) {
                        if (strpos($string, 'category') !== false) {
                            $disableCategories = false;
                            break;
                        }
                    }
                }
                if ($disableCategories) {
                    $disabledFeatures[] = 'relationship_categories';
                }
            }

            return ee()->relationships_parser->create(
                $pre->channel()->rfields,
                $pre->entry_ids(),
                null,
                $grid_relationships,
                null,
                null,
                $disabledFeatures
            );
        } catch (EE_Relationship_exception $e) {
            ee()->TMPL->log_item($e->getMessage());
        }

        return null;
    }

    /**
     * Replace all of the relationship fields in one fell swoop.
     *
     * @param String	The tagdata to be parsed
     * @param Object	The channel parser object
     * @param Mixed		The results from the preparse method
     *
     * @return String	The processed tagdata
     */
    public function replace($tagdata, EE_Channel_data_parser $obj, $relationship_parser)
    {
        ee()->TMPL->log_item('Replace all of the relationship fields');
        if (! isset($relationship_parser)) {
            return $tagdata;
        }

        $row = $obj->row();
        $channel = $obj->channel();

        try {
            return $relationship_parser->parse($row['entry_id'], $tagdata, $channel);
        } catch (EE_Relationship_exception $e) {
            return $tagdata;
        }
    }
}

// EOF
