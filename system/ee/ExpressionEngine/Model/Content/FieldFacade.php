<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Content;

/**
 * Content Field Facade
 */
class FieldFacade
{
    private $id;
    private $data; // field_id_*
    private $format;  // field_ft_*
    private $timezone; // field_dt_*
    private $hidden; // field_hide_*
    private $width = 100;
    private $metadata;
    private $required;
    private $field_name;
    private $content_id;
    private $content_type;
    private $value;
    private $api;
    private $icon;
    private $conditionSets;

    /**
     * @var Flag to ensure defaults are only loaded once
     */
    private $populated = false;

    public function __construct($field_id, array $metadata)
    {
        ee()->legacy_api->instantiate('channel_fields');
        $this->api = clone ee()->api_channel_fields;

        $this->id = $field_id;
        $this->metadata = $metadata;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->field_name = $name;
    }

    public function getName()
    {
        return $this->field_name;
    }

    public function getShortName()
    {
        return $this->getItem('field_name') ?: $this->getName();
    }

    public function setContentId($id)
    {
        $this->content_id = $id;
    }

    public function getContentId()
    {
        return $this->content_id;
    }

    public function setContentType($type)
    {
        $this->content_type = $type;
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function setTimezone($tz)
    {
        $this->timezone = $tz;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setHidden($hidden)
    {
        $this->hidden = ($hidden === 'y' || $hidden === true) ? 'y' : 'n';
    }

    public function getHidden()
    {
        return $this->hidden;
    }


    public function getSettings()
    {
        return isset($this->metadata['field_settings']) ? $this->metadata['field_settings'] : [];
    }

    protected function ensurePopulatedDefaults()
    {
        if ($this->populated) {
            return;
        }

        $this->populated = true;

        if ($callback = $this->getItem('populateCallback')) {
            call_user_func($callback, $this);
        } elseif ($data = $this->getItem('field_data')) {
            $this->setData($data);
        }
    }

    public function setData($data)
    {
        $this->ensurePopulatedDefaults();
        $this->data = $data;
    }

    public function getData()
    {
        $this->ensurePopulatedDefaults();

        return $this->data;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function isRequired()
    {
        $required = $this->getItem('field_required');

        return ($required === true || $required === 'y');
    }

    public function getItem($field)
    {
        if (array_key_exists($field, $this->metadata)) {
            return $this->metadata[$field];
        }

        return null;
    }

    public function setItem($field, $value)
    {
        $this->metadata[$field] = $value;
    }

    public function getType()
    {
        return $this->getItem('field_type');
    }

    public function getTypeName()
    {
        $fts = $this->api->fetch_all_fieldtypes();
        $type = $this->getType();

        return $fts[$type]['name'];
    }

    public function getIcon()
    {
        if (empty($this->icon)) {
            $error_reporting = error_reporting(0);
            $fts = $this->api->fetch_all_fieldtypes();
            $addon = ee('Addon')->get($fts[$this->getType()]['package']);
            $this->icon = $addon->getIconUrl('field.svg');
            error_reporting($error_reporting);
        }

        return $this->icon;
    }

    public function validate($value)
    {
        $this->initField();

        $result = $this->api->apply('validate', array($value));

        if (is_array($result)) {
            if (isset($result['value'])) {
                $this->setData($result['value']);

                $result = (isset($result['error'])) ? $result['error'] : true;
            }

            if (isset($result['error'])) {
                $result = $result['error'];
            }
        }

        if (is_string($result) && strlen($result) > 0) {
            return $result;
        }

        return true;
    }

    public function save($model = null)
    {
        $this->ensurePopulatedDefaults();

        $value = $this->data;
        $this->initField();

        return $this->data = $this->api->apply('save', array($value, $model));
    }

    public function postSave()
    {
        $value = $this->data;
        $this->initField();

        return $this->data = $this->api->apply('post_save', array($value));
    }

    public function hasReindex()
    {
        $ft = $this->getNativeField();

        return method_exists($ft, 'reindex');
    }

    public function reindex($model = null)
    {
        if (! $this->hasReindex()) {
            return false;
        }

        $this->ensurePopulatedDefaults();

        $value = $this->data;
        $this->initField();

        return $this->data = $this->api->apply('reindex', array($value, $model));
    }

    public function getForm()
    {
        $data = $this->initField();

        $field_value = $data['field_data'];

        return $this->api->apply('display_publish_field', array($field_value));
    }

    public function getSupportedEvaluationRules()
    {
        ee()->lang->load('fieldtypes');
        $rulesList = [];
        $supportedEvaluationRules = [];
        $ft = $this->getNativeField();
        if (!property_exists($ft, 'supportedEvaluationRules')) {
            if (property_exists($ft, 'has_array_data') && $ft->has_array_data === true) {
                $rulesList = ['isEmpty', 'isNotEmpty'];
            } else {
                $rulesList = ['equal', 'notEqual', 'isEmpty', 'isNotEmpty', 'contains', 'notContains'];
            }
        } elseif (!empty($ft->supportedEvaluationRules)) {
            $rulesList = $ft->supportedEvaluationRules;
        }

        foreach ($rulesList as $ruleName) {
            $rule = ee('ConditionalFields')->make($ruleName, $this->getType());
            $supportedEvaluationRules[$ruleName] = [
                'text'      => lang($rule->getLanguageKey()),
                'type'      => $rule->getConditionalFieldInputType()
            ];
        }

        if (property_exists($ft, 'defaultEvaluationRule') && isset($supportedEvaluationRules[$ft->defaultEvaluationRule])) {
            $supportedEvaluationRules[$ft->defaultEvaluationRule]['default'] = true;
        } elseif (!empty($rulesList)) {
            $supportedEvaluationRules[$rulesList[0]]['default'] = true;
        }

        return $supportedEvaluationRules;
    }

    public function getPossibleValuesForEvaluation()
    {
        $data = $this->initField();

        return $this->api->apply('getPossibleValuesForEvaluation', [$data]);
    }

    public function getSettingsForm()
    {
        ee()->load->library('table');
        $data = $this->initField();
        $out = $this->api->apply('display_settings', array($data));

        if ($out == '') {
            return ee()->table->rows;
        }

        return $out;
    }

    public function validateSettingsForm($settings)
    {
        $this->initField();

        return $this->api->apply('validate_settings', array($settings));
    }

    public function saveSettingsForm($data)
    {
        $this->initField();

        return $this->api->apply('save_settings', array($data));
    }

    /**
     * Fires post_save_settings on the fieldtype
     */
    public function postSaveSettings($data)
    {
        $this->initField();

        return $this->api->apply('post_save_settings', array($data));
    }

    public function delete()
    {
        $this->initField();

        return $this->api->apply('delete', array(array($this->getContentId())));
    }

    public function getStatus()
    {
        $data = $this->initField();

        $field_value = set_value(
            $this->getName(),
            $data['field_data']
        );

        return $this->api->apply('get_field_status', array($field_value));
    }

    public function replaceTag($tagdata, $params = array(), $modifier = '', $full_modifier = '')
    {
        $ft = $this->getNativeField();

        $this->initField();

        $data = $this->getItem('row');

        $this->api->apply('_init', array(array(
            'row' => $data,
            'content_id' => $this->content_id,
            'content_type' => $this->content_type,
        )));

        $data = $this->api->apply('pre_process', array(
            $data['field_id_' . $this->getId()]
        ));

        $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

        $output = '';

        if (method_exists($ft, $parse_fnc)) {
            $output = $this->api->apply($parse_fnc, array($data, $params, $tagdata));
        }
        // Go to catchall and include modifier
        elseif (method_exists($ft, 'replace_tag_catchall') and $modifier !== '') {
            $modifier = $full_modifier ?: $modifier;
            $output = $this->api->apply('replace_tag_catchall', array($data, $params, $tagdata, $modifier));
        }

        return $output;
    }

    public function acceptsContentType($name)
    {
        $ft = $this->getNativeField();

        return $ft->accepts_content_type($name);
    }

    // TODO THIS WILL MOST DEFINITELY GO AWAY! BAD DEVELOPER!
    public function getNativeField()
    {
        $data = $this->initField();
        $ft = $this->api->setup_handler($this->getType(), true);
        ee()->api_channel_fields->field_type = $this->api->field_type;
        ee()->api_channel_fields->field_types = array_merge(ee()->api_channel_fields->field_types, $this->api->field_types);
        ee()->api_channel_fields->ft_paths = array_merge(ee()->api_channel_fields->ft_paths, $this->api->ft_paths);

        return $ft;
    }

    public function implementsInterface($interface)
    {
        $fieldtype = $this->getNativeField();
        $interfaces = class_implements(get_class($fieldtype));

        return isset($interfaces[$interface]);
    }

    /**
     * Forward methods to implementers of EntryManager\ColumnInterface
     */
    public function getTableColumnLabel()
    {
        return $this->getNativeField()->getTableColumnLabel();
    }

    public function getTableColumnConfig()
    {
        return $this->getNativeField()->getTableColumnConfig();
    }

    public function getEntryManagerColumnModels()
    {
        return $this->getNativeField()->getEntryManagerColumnModels();
    }

    public function getEntryManagerColumnFields()
    {
        return $this->getNativeField()->getEntryManagerColumnFields();
    }

    public function getEntryManagerColumnSortField()
    {
        return $this->getNativeField()->getEntryManagerColumnSortField();
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $ft = $this->getNativeField();
        $ft->settings = $this->getItem('field_settings');

        return $ft->renderTableCell($data, $field_id, $entry);
    }

    public function getConditionSets()
    {
        // Field is not conditional, so there should be no conditional sets
        if (! $this->getItem('field_is_conditional')) {
            return [];
        }
        
        if($this->conditionSets) {
            return $this->conditionSets;
        }

        $field = ee('Model')->get('ChannelField', $this->getId())->first();
        return $field->FieldConditionSets;
    }

    public function setConditionSets($conditionSets = [])
    {
        $this->conditionSets = $conditionSets;
    }

    public function initField()
    {
        $this->ensurePopulatedDefaults();

        // not all custom field tables will specify all of these things
        $defaults = array(
            'field_instructions' => '',
            'field_text_direction' => 'rtl',
            'field_settings' => array()
        );

        $info = $this->metadata;
        $info = array_merge($defaults, $info);

        if (is_null($this->getFormat()) && isset($info['field_fmt'])) {
            $this->setFormat($info['field_fmt']);
        }

        if (is_null($this->timezone) && isset($info['field_dt'])) {
            $this->setTimezone($info['field_dt']);
        }

        if (is_null($this->getHidden()) && isset($info['field_hidden'])) {
            $this->setHidden($info['field_hidden']);
        }

        $data = $this->setupField();

        $this->api->setup_handler($data['field_id']);
        $this->api->apply('_init', array(array(
            'content_id' => $this->content_id,
            'content_type' => $this->content_type
        )));

        return $data;
    }

    protected function setupField()
    {
        $field_dt = $this->timezone;
        $field_fmt = $this->getFormat();
        $field_hidden = $this->getHidden();
        $field_data = $this->data;
        $field_name = $this->getName();

        // not all custom field tables will specify all of these things
        $defaults = array(
            'field_instructions' => '',
            'field_text_direction' => 'rtl',
            'field_settings' => array()
        );

        $info = $this->metadata;
        $info = array_merge($defaults, $info);

        $settings = array(
            'field_instructions' => trim((string) $info['field_instructions']),
            'field_text_direction' => ($info['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
            'field_fmt' => $field_fmt,
            'field_hidden' => $field_hidden,
            'field_dt' => $field_dt,
            'field_data' => $field_data,
            'field_name' => $field_name
        );

        $field_settings = empty($info['field_settings']) ? array() : $info['field_settings'];

        $settings = array_merge($info, $settings, $field_settings);

        $this->api->set_settings($info['field_id'], $settings);

        return $settings;
    }
}

// EOF
