<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\FluidField\Model\FluidField;
use ExpressionEngine\Service\Model\Collection;

/**
 * Fluid Field Parser
 */
class Fluid_field_parser
{
    public $modifiers = [];
    protected $data;
    protected $possible_fields = [];
    protected $fluid_fields = [];
    protected $_prefix;
    protected $replacements = [];

    public function __construct()
    {
        // The pre processor will accept these modifiers as fields that need querying
        $this->modifiers = [
            'first',
            'last',
            'count',
            'index',
            'current_field_name',
            'next_field_name',
            'prev_field_name',
            'current_field_type',
            'next_fieldtype',
            'prev_fieldtype',
            'length',
            'total_fields'
        ];
    }

    /**
     * Called before each channel entries loop to gather the information
     * needed to efficiently query the Fluid Field data we need
     *
     * @param string $tagdata Tag data for entire channel entries loop
     * @param object $pre_parser Channel preparser object
     * @param array $fluid_field_fields An array of fluid field fields
     * @param string $content_type The type of content being processed
     */
    public function pre_process($tagdata, $pre_parser, array $fluid_field_fields, $content_type = 'channel')
    {
        // Bail out if there are no fluid field fields present to parse
        if (
            ! preg_match_all(
                "/" . LD . '\/?(' . preg_quote($pre_parser->prefix()) . '(?:(?:' . implode('|', array_flip($fluid_field_fields)) . '):?))\b([^}{]*)?' . RD . "/",
                $tagdata,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            return false;
        }

        $this->fluid_fields = array_flip($fluid_field_fields);

        $this->_prefix = $pre_parser->prefix();

        $fluid_field_ids = [];

        // Validate matches
        foreach ($matches as $key => $match) {
            $field_name = str_replace($this->_prefix, '', $match[1]);

            // Analyze the field to see if its modifier matches any of our
            // reserved modifier names
            $field = ee('Variables/Parser')->parseVariableProperties($match[2], $field_name);

            // Throw out variables and closing tags, we'll deal with them
            // in the parsing stage
            if (
                (! in_array($field['field_name'], $this->modifiers) && substr($match[1], -1) == ':')
                || substr($match[0], 0, 2) == LD . '/'
            ) {
                unset($matches[$key]);

                continue;
            }

            $field_name = rtrim($field_name, ':');

            // Make sure the supposed field name is an actual Fluid Field field
            if (! isset($fluid_field_fields[$field_name])) {
                return false;
            }

            // Collect field IDs so we can gather the column data for these fields
            $fluid_field_ids[] = $fluid_field_fields[$field_name];
        }

        $this->data = $this->fetchFluidFields($pre_parser->entry_ids(), array_unique($fluid_field_ids));

        return true;
    }

    /**
     * Gets a list of field names for a given set of field ids
     *
     * @param array A list of channel field ids
     * @return array A list of field_names
     */
    private function getPossibleFields(array $field_channel_fields)
    {
        $cache_key = 'ChannelFields/' . implode(',', $field_channel_fields) . '/field_name';

        if (($possible_fields = ee()->session->cache(__CLASS__, $cache_key, false)) === false) {
            $possible_fields = ee('Model')->get('ChannelField', $field_channel_fields)
                ->fields('field_id', 'field_name', 'field_type')
                ->all()
                ->indexBy('field_id');

            ee()->session->set_cache(__CLASS__, $cache_key, $possible_fields);
        }

        return $possible_fields;
    }

    /**
     * Given a list of entry ids, fluid field ids, and field ids used in the
     * fluid fields, this bulk-fetches all the needed data for the field fields.
     *
     * @param array $entry_id A list of entry ids
     * @param array $fluid_field_ids A list of fluid field ids
     * @return Collection A Colletion of FluidField model entities
     */
    private function fetchFluidFields(array $entry_ids, array $fluid_field_ids)
    {
        if (empty($entry_ids) || empty($fluid_field_ids)) {
            return new Collection([]);
        }

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            $entry_ids = array_filter($entry_ids, function ($entry_id) use ($data) {
                return $entry_id != $data['entry_id'];
            });
        }

        if (empty($entry_ids)) {
            $fluid_field_data = new Collection([]);
        } else {
            $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->with('ChannelField')
                ->with('ChannelFieldGroup')
                ->filter('fluid_field_id', 'IN', $fluid_field_ids)
                ->filter('entry_id', 'IN', $entry_ids)
                ->order('fluid_field_id')
                ->order('entry_id')
                ->order('order')
                ->all();

            // Since we store the data in the field's table, and each field has its
            // own table, we'll group our fluid field data by the field_id. This will
            // allow us to run one query per field, fetching all the data across
            // all the fluid fields & entries for each field.
            $fields = [];

            foreach ($fluid_field_data as $fluid_field) {
                if (! array_key_exists($fluid_field->field_id, $fields)) {
                    $fields[$fluid_field->field_id] = [];
                }

                $fields[$fluid_field->field_id][$fluid_field->field_data_id] = $fluid_field;
            }

            foreach ($fields as $field_id => $fluid_fields) {
                $field_data_ids = array_keys($fluid_fields);

                // Captain Obvious says: here we be gettin' the data, Arrrr!
                ee()->db->where_in('id', $field_data_ids);
                $rows = ee()->db->get('channel_data_field_' . $field_id)->result_array();

                foreach ($rows as $row) {
                    $fluid_fields[$row['id']]->setFieldData($row);
                }
            }
        }

        return $this->overrideWithPreviewData($fluid_field_data, $fluid_field_ids);
    }

    /**
     * Replaces data with preview data when said data is available.
     *
     * @param obj Fluid field Collection
     * @param array An array of fluid field ids
     * @return Collection A Colletion of FluidField model entities
     */
    public function overrideWithPreviewData(Collection $fluid_field_data, array $fluid_field_ids)
    {
        $fluid_fields = $fluid_field_data->asArray();

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            $entry_id = $data['entry_id'];

            // This is needed to resolve the 'group' for an existing fluid field
            $entry_fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->filter('fluid_field_id', 'IN', $fluid_field_ids)
                ->filter('entry_id', $entry_id)
                ->all()
                ->indexBy('id');

            // Remove existing fields for the previewed entry, we'll create dummy fields
            // in their place
            $fluid_fields = array_filter($fluid_fields, function ($field) use ($entry_id) {
                return $field->entry_id != $entry_id;
            });

            foreach ($fluid_field_ids as $fluid_field_id) {
                $i = 1;
                if (
                    ! isset($data["field_id_{$fluid_field_id}"])
                    || ! isset($data["field_id_{$fluid_field_id}"]['fields'])
                ) {
                    continue;
                }

                // $fields = $data["field_id_{$fluid_field_id}"]['fields'];
                $previous_field_group_id = null;
                $previous_group_key = null;
                $g = 0;

                foreach ($data["field_id_{$fluid_field_id}"]['fields'] as $key => $value) {
                    if ($key == 'new_field_0') {
                        continue;
                    }

                    $field_group_id = null;
                    $group_key = $key;

                    if (strpos($key, 'new_field_for_group_') === 0) {
                        $group_key = str_replace('new_field_for_', '', $key);
                    } elseif (strpos($key, 'field_') === 0) {
                        $id = str_replace('field_', '', $key);
                        $group_key = array_key_exists($id, $entry_fluid_field_data) ? "group_{$entry_fluid_field_data[$id]->group}" : $key;
                    }

                    foreach (array_keys($value) as $k) {
                        if (strpos($k, 'field_group_id_') === 0) {
                            $field_group_id = (int) str_replace('field_group_id_', '', $k);

                            break;
                        }
                    }

                    // If the field_group is null we do not have a group and are always incrementing
                    // If the field_group is not the previous_field_group then we increment the group
                    // If the field_group is the previous_field_group but the key has changed
                    if (
                        $field_group_id == null
                        || (is_null($field_group_id) && is_null($previous_field_group_id))
                        || ($field_group_id !== $previous_field_group_id)
                        || ($field_group_id === $previous_field_group_id && $group_key !== $previous_group_key)
                    ) {
                        $g++;
                    }

                    $value = reset($value);

                    $field_id = null;
                    foreach (array_keys($value) as $k) {
                        if (strpos($k, 'field_id_') === 0) {
                            $field_id = (int) str_replace('field_id_', '', $k);
                            $fluid_field = ee('Model')->make('fluid_field:FluidField');
                            $fluid_field->setId("field_id_{$fluid_field_id},{$key}");
                            $fluid_field->fluid_field_id = $fluid_field_id;
                            $fluid_field->entry_id = $entry_id;
                            $fluid_field->field_id = $field_id;
                            $fluid_field->field_group_id = $field_group_id;
                            $fluid_field->group = $g; // use the order passed in request
                            $fluid_field->order = $i;
                            $fluid_field->field_data_id = $i;

                            $value['entry_id'] = $entry_id;
                            $fluid_field->setFieldData($value);
                            $fluid_fields[] = $fluid_field;

                            $i++;
                            // break;
                        }
                    }

                    $previous_group_key = $group_key;
                    $previous_field_group_id = $field_group_id;

                }
            }
        }

        return new Collection($fluid_fields);
    }

    /**
     * Handles ft.fluid_field.php's replace_tag(), called with each loop of the
     * channel entries parser
     *
     * @param array Channel entry row data typically sent to fieldtypes
     * @param int  Field ID of field being parsed so we can make sure
     * @param array Parameters array, unvalidated
     * @param string Tag data of our field pair
     * @return string Parsed field data
     */
    public function parse(array $channel_row, $fluid_field_id, array $params, $tagdata, $content_type = 'channel')
    {
        if (empty($tagdata)) {
            return '';
        }

        $fluid_field_name = $this->_prefix . $this->fluid_fields[$fluid_field_id];

        $entry_id = $channel_row['entry_id'];

        // We bulk-fetch all entry's fluid fields in the pre parser. This filters that Collection
        // down to just the data for this fluid field on this entry.
        $fluid_field_data = $this->data->filter(function ($fluid_field) use ($entry_id, $fluid_field_id) {
            return ($fluid_field->entry_id == $entry_id && $fluid_field->fluid_field_id == $fluid_field_id);
        })
        // Sort by ChannelField->field_order
        ->sortBy(function ($item) {
            return $item->ChannelField->field_order;
        });

        $groups = [];
        foreach ($fluid_field_data as $field) {
            $groupKey = $field->group;
            if (is_null($groupKey)) {
                $groupKey = $field->order;
            }
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'name' => $field->ChannelFieldGroup ? $field->ChannelFieldGroup->group_name : $field->ChannelField->field_label,
                    'short_name' => $field->ChannelFieldGroup ? $field->ChannelFieldGroup->short_name : $field->ChannelField->field_name,
                    'is_field_group' => $field->ChannelFieldGroup ? true : false,
                    'fields' => []
                ];
            }
            $groups[$groupKey]['fields'][] = $field;
        }
        // Sort groups by $groupKey ascending
        ksort($groups);

        $vars = ee('Variables/Parser')->extractVariables($tagdata);
        $singles = array_filter($vars['var_single'], function ($val) use ($fluid_field_name) {
            return (strpos($val, $fluid_field_name . ':') === 0);
        });

        $cond = [];
        foreach (array_keys($vars['var_pair']) as $field) {
            // Must start with the fluid field name
            if (strpos($field, $fluid_field_name . ':') === 0) {
                $cond[$field] = false;
            }
        }

        // The field blocks inside a Fluid field are essentially `{if fluid:field}...{/if}`
        // so we'll rewrite them and use the Conditional parser to get what we want each pass
        $cond_tagdata = $this->rewriteFluidTagsAsConditionals($tagdata, array_keys($cond));
        // Protect {fields}...{/fields} which needs separate conditional evaluation
        $cond_tagdata = $this->replaceInnerFieldsPairs($cond_tagdata);

        $output = '';

        $total_fields = count($fluid_field_data);
        $total_groups = count($groups);

        $group_cond_keys = array_fill_keys(array_map(function ($name) use ($fluid_field_name) {
            return "{$fluid_field_name}:$name";
        }, array_filter(array_unique(array_column($groups, 'short_name')))), null);

        $i = 0;

        $groups = array_values($groups);

        foreach ($groups as $g => $group) {

            $cond[$fluid_field_name . ':' . $group['short_name']] = true;

            $group_cond = array_intersect_key($cond, $group_cond_keys);
            $has_group = $group['is_field_group'] && !empty(array_filter($group_cond));
            $group_tagdata = ee()->functions->prep_conditionals($cond_tagdata, $group_cond);
            // Restore {fields}
            $group_tagdata = $this->restoreInnerFieldsPairs($group_tagdata);

            $group_prefix = $group['short_name'];
            $group_tags = [];
            $group_output = '';

            $chunks = [
                ['content' => $group_tagdata, 'chunk' => $group_tagdata]
            ];

            $group_meta = [
                $fluid_field_name . ':first_group' => (int) ($g == 0),
                $fluid_field_name . ':last_group' => (int) (($g + 1) == $total_groups),
                $fluid_field_name . ':count_group' => $g + 1,
                $fluid_field_name . ':index_group' => $g,
                $fluid_field_name . ':current_group_name' => $group['name'],
                $fluid_field_name . ':current_group_short_name' => $group['short_name'],
                $fluid_field_name . ':next_group_name' => (($g + 1) < $total_groups) ? $groups[$g + 1]['name'] : '',
                $fluid_field_name . ':next_group_short_name' => (($g + 1) < $total_groups) ? $groups[$g + 1]['short_name'] : '',
                $fluid_field_name . ':prev_group_name' => ($g > 0) ? $groups[$g - 1]['name'] : '',
                $fluid_field_name . ':prev_group_short_name' => ($g > 0) ? $groups[$g - 1]['short_name'] : ''
            ];

            // aliases to cover some additionally intuitive names
            $group_meta[$fluid_field_name . ':this_group_name'] = $group_meta[$fluid_field_name . ':current_group_name'];

            if ($has_group) {
                $chunks = [];
                // we need the chunk inside of {fields}...{/fields} only
                $pairs = ee()->api_channel_fields->get_pair_field($group_tagdata, 'fields');

                foreach ($pairs as $chk_data) {
                    list($modifier, $content, $params, $chunk) = $chk_data;
                    $chunks[] = compact('modifier', 'content', 'params', 'chunk');
                }
            }

            // Setup $group_tags with $field data;
            foreach ($group['fields'] as $fluid_field) {
                $field_name = $fluid_field->ChannelField->field_name;
                $field = $fluid_field->getField();
                $row = array_merge($channel_row, $fluid_field->getFieldData()->getValues());
                $row['entry_id'] = $entry_id; // the merge can sometimes wipe this out
                $field->setItem('row', $row);

                $group_tags["$group_prefix:$field_name"] = $field;
            }

            // sort group_tags by field type to ensure proper parse order later
            uasort($group_tags, function ($a, $b) {
                $priority = ['relationship'];
                $aPriority = in_array($a->getType(), $priority);
                $bPriority = in_array($b->getType(), $priority);

                return ($aPriority && $bPriority) ? 0 : (($aPriority && !$bPriority) ? -1 : 1);
            });

            // Since we can have multiple chunks we need to store the current field
            // index and reset it for each chunk so that our meta variables are correct
            $chunk_i = $i;
            foreach ($chunks as $chunk) {
                $i = $chunk_i;
                $chunk_output = '';
                $fieldCountInGroup = count($group['fields']);

                // Process Fixed Order parameter {fields fixed_order="field_1|field_2"}
                $fixed_order = (empty($chunk['params']['fixed_order'] ?? '')) ? null : explode('|', $chunk['params']['fixed_order']);

                if(!empty($fixed_order)) {
                    $fields = [];
                    $count = count($fixed_order);
                    $fixed_order = array_flip($fixed_order);

                    foreach($group['fields'] as $field_index => $field) {
                        $name = $field->ChannelField->field_name;
                        $index = (array_key_exists($name, $fixed_order)) ? $fixed_order[$name] : $count + $field_index;
                        $fields[$index] = $field;
                    }
                    $group['fields'] = $fields;
                }

                $order = $chunk['params']['order'] ?? 'asc';
                $order == 'asc' ? ksort($group['fields']) : krsort($group['fields']);

                $fieldCount = 0;
                foreach ($group['fields'] as $fluid_field) {
                    $field_name = $fluid_field->ChannelField->field_name;

                    // Flip this field's conditional to TRUE so all the other fields will be
                    // removed from the tagdata
                    $cond[$fluid_field_name . ':' . $field_name] = true;
                    $my_tagdata = ee()->functions->prep_conditionals($chunk['content'], $cond);
                    $conditionalUsed = strlen($my_tagdata) !== strlen($chunk['content']);
                    $cond[$fluid_field_name . ':' . $field_name] = false; // Reset for the next pass

                    $firstInGroup = ($fieldCount == 0);
                    $lastInGroup = (($fieldCount + 1) == $fieldCountInGroup);
                    $prevField = ($firstInGroup) ? ($g > 0 && isset($groups[$g - 1]) ? $groups[$g - 1]['fields'][count($groups[$g - 1]['fields']) - 1] : null) : $group['fields'][$fieldCount - 1];
                    $nextField = ($lastInGroup) ? ($g < $total_groups && isset($groups[$g + 1]) ? $groups[$g + 1]['fields'][0] : null) : $group['fields'][$fieldCount + 1];

                    $meta = [
                        $fluid_field_name . ':first' => (int) ($g == 0 && $firstInGroup),
                        $fluid_field_name . ':last' => (int) (($g + 1) == $total_groups && $lastInGroup),
                        $fluid_field_name . ':count' => $i + 1,
                        $fluid_field_name . ':index' => $i,
                        $fluid_field_name . ':first_in_group' => (int) $firstInGroup,
                        $fluid_field_name . ':last_in_group' => (int) $lastInGroup,
                        $fluid_field_name . ':count_in_group' => $fieldCount + 1,
                        $fluid_field_name . ':index_in_group' => $fieldCount,
                        $fluid_field_name . ':current_field_name' => $field_name,
                        $fluid_field_name . ':next_field_name' => ($nextField) ? $nextField->ChannelField->field_name : null,
                        $fluid_field_name . ':prev_field_name' => ($prevField) ? $prevField->ChannelField->field_name : null,
                        $fluid_field_name . ':current_fieldtype' => $groups[$g]['fields'][$fieldCount]->ChannelField->field_type,
                        $fluid_field_name . ':next_fieldtype' => ($nextField) ? $nextField->ChannelField->field_type : null,
                        $fluid_field_name . ':prev_fieldtype' => ($prevField) ? $prevField->ChannelField->field_type : null,
                    ];

                    // a couple aliases to cover some additionally intuitive names
                    $meta[$fluid_field_name . ':this_field_name'] = $meta[$fluid_field_name . ':current_field_name'];
                    $meta[$fluid_field_name . ':this_fieldtype'] = $meta[$fluid_field_name . ':current_fieldtype'];

                    // Templates can include things like `{fluid:count type="text"}` which we can easily
                    // evaluate and toss into this meta array for processing, so...why not?
                    foreach ($singles as $key => $value) {
                        if (!array_key_exists($key, $meta)) {
                            $meta_value = $this->evaluateSingleVariable($value, $fluid_field_data, $fluid_field);
                            if (!is_null($meta_value)) {
                                $meta[$key] = $meta_value;
                            }
                        }
                    }
                    // Make group meta fields available inside field conditional
                    $meta = array_merge($meta, $group_meta);

                    $tag = ee('fluid_field:Tag', $my_tagdata);
                    $field = $group_tags["$group_prefix:$field_name"];

                    $parsed = $tag->parse($field, $meta);
                    $chunk_output .= $parsed;
                    $i++;
                    $fieldCount++;
                }

                if ($has_group) {
                    // search for the content inside {fields} tags and replace it with our generated output inside this chunk
                    $group_output = str_replace($chunk['chunk'], $chunk_output, $group_tagdata);
                    $group_tagdata = $group_output;
                } else {
                    $group_output = $chunk_output;
                }
            }

            // If we didn't have any chunks and our output is empty pass group_tagdata along
            if(empty($chunks) && empty($group_output)) {
                $i += count($group['fields']);
                $group_output = $group_tagdata;
            }

            if ($has_group) {
                // Replace Group metadata that exists outside of a field conditional
                foreach ($group_meta as $name => $value) {
                    $tag = LD . $name . RD;
                    $group_output = str_replace($tag, $value, $group_output);
                }

                // handle tag replacements
                foreach ($group_tags as $tag => $field) {
                    if (strpos($group_output, LD . $tag) === false) {
                        continue;
                    }

                    $group_output = ee('fluid_field:Tag', $group_output)->setTag($tag)->parse($field);
                }
            }

            $cond[$fluid_field_name . ':' . $group['short_name']] = false; // Reset for the next group
            $output .= $group_output;
        }

        return $output;
    }

    /**
     * Takes the tag data and a list of field names (i.e. 'fluid:field') and
     * rewrites the field tag pairs as conditionals.
     *
     * @param string The template tagdata to change
     * @param array An array of field names (i.e. 'fluid:field')
     * @return string The template with conditionals for the field tag pairs
     */
    private function rewriteFluidTagsAsConditionals($tagdata, array $field_names)
    {
        foreach ($field_names as $field) {
            $tagdata = str_replace(LD . $field . RD, LD . 'if ' . $field . RD, $tagdata);
            $tagdata = str_replace(LD . '/' . $field . RD, LD . '/if' . RD, $tagdata);
        }

        return $tagdata;
    }

    /**
     * A helper function to replace {fields}...{/fields} content with a placeholder
     * so that conditional evaluation does not effect the contents of a field group
     *
     * @param string $tagdata
     * @return string
     */
    private function replaceInnerFieldsPairs($tagdata)
    {
        $pairs = ee()->api_channel_fields->get_pair_field($tagdata, 'fields');
        $this->replacements = [];

        foreach($pairs as $key => $tag)
        {
            $this->replacements[$key] = $tag[3];
            $tagdata = str_replace($tag[3], "{!-- ff:fields:$key --}", $tagdata);
        }

        return $tagdata;
    }

    /**
     * A helper function to restore {!-- ff:fields --} placeholders with the original tagdata
     * so that conditional evaluation does not effect the contents of a field group
     *
     * @param string $tagdata
     * @return string
     */
    private function restoreInnerFieldsPairs($tagdata)
    {
        foreach ($this->replacements as $key => $tag) {
            $tagdata = str_replace("{!-- ff:fields:$key --}", $tag, $tagdata);
            // $tagdata = str_replace(LD . '/' . $field . RD, LD . '/if' . RD, $tagdata);
        }

        return $tagdata;
    }

    /**
     * Takes a single variable tag (i.e. {fluid:count type="textarea"}) and
     * evaluates it, returning its value.
     *
     * @param string The variable tag
     * @param obj  A collection of FluidField model entities
     * @param obj  The current field in the Fluid being processed
     * @return int  The evaulated value
     */
    private function evaluateSingleVariable($var, Collection $fluid_field_data, FluidField $current_field)
    {
        $properties = ee('Variables/Parser')->parseVariableProperties($var);
        $params = $properties['params'];

        if (isset($params['type'])) {
            $fluid_field_data = $fluid_field_data->filter(function ($datum) use ($params) {
                return ($params['type'] == $datum->ChannelField->field_type);
            });
        }

        if (isset($params['name'])) {
            $fluid_field_data = $fluid_field_data->filter(function ($datum) use ($params) {
                return ($params['name'] == $datum->ChannelField->field_name);
            });
        }

        $return = null;

        if ($fluid_field_data->count() < 1) {
            return 0;
        }

        switch ($properties['modifier']) {
            case 'first':
                $return = (int) ($current_field->getId() == $fluid_field_data[0]->getId());

                break;

            case 'last':
                $return = (int) ($current_field->getId() == $fluid_field_data->last()->getId());

                break;

            case 'count':
                $return = "''";
                foreach ($fluid_field_data as $i => $field) {
                    if ($current_field->getId() == $field->getId()) {
                        $return = $i + 1;

                        break;
                    }
                }

                break;

            case 'index':
                $return = "''";
                foreach ($fluid_field_data as $i => $field) {
                    if ($current_field->getId() == $field->getId()) {
                        $return = $i;

                        break;
                    }
                }

                break;
        }

        return $return;
    }
}

// EOF
