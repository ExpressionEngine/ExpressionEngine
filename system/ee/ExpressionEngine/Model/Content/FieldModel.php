<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Content;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Content Field Model abstract
 */
abstract class FieldModel extends Model
{
    protected static $_events = array(
        'afterInsert',
        'afterUpdate',
        'afterDelete',
    );

    protected $_field_facade;

    /**
     * Return the storing table
     */
    abstract public function getDataTable();

    /**
     *
     */
    abstract public function getStructure();

    /**
     *
     */
    public function getField($override = array())
    {
        $field_type = $this->getFieldType();

        if (empty($field_type)) {
            throw new \Exception('Cannot get field of unknown type "' . $field_type . '".');
        }

        if (! isset($this->_field_facade) ||
            $this->_field_facade->getType() != $this->getFieldType() ||
            $this->_field_facade->getId() != $this->getId()) {
            $values = array_merge($this->getValues(), $override);

            $this->_field_facade = new FieldFacade($this->getId(), $values);
            $this->_field_facade->setContentType($this->getContentType());
        }

        if (isset($this->field_fmt)) {
            $this->_field_facade->setFormat($this->field_fmt);
        }

        return $this->_field_facade;
    }

    public function getSupportedEvaluationRules()
    {
        return $this->getField($this->getSettingsValues())->getSupportedEvaluationRules();
    }

    public function getPossibleValuesForEvaluation()
    {
        return $this->getField($this->getSettingsValues())->getPossibleValuesForEvaluation();
    }

    public function getSettingsForm()
    {
        return $this->getField($this->getSettingsValues())->getSettingsForm();
    }

    public function getSettingsValues()
    {
        return $this->getValues();
    }

    protected function getContentType()
    {
        return $this->getStructure()->getContentType();
    }

    public function set(array $data = array())
    {
        // getField() requires that we have a fieldtype, but we might be trying
        // to set it! So, if we are, we'll do that first.
        if (isset($data['field_type'])) {
            $this->setProperty('field_type', $data['field_type']);
        }

        $field = $this->getField($this->getSettingsValues());
        $data = array_merge($field->saveSettingsForm($data), $data);

        return parent::set($data);
    }

    public function validate()
    {
        $result = parent::validate();

        $settings = $this->getSettingsValues();

        if (isset($settings['field_settings'])) {
            $field = $this->getField($this->getSettingsValues());

            $settings_result = $field->validateSettingsForm(array_merge($settings, $settings['field_settings']));

            if ($settings_result instanceof ValidationResult && $settings_result->failed()) {
                foreach ($settings_result->getFailed() as $name => $rules) {
                    foreach ($rules as $rule) {
                        if ($name == 'field_pre_field_id') {
                            // because this field is not visually present on page, we set error on parent field
                            $name = 'field_pre_populate_id';
                        }
                        $result->addFailed($name, $rule);
                    }
                }
            }
        }

        //validate assigned conditions


        return $result;
    }

    /**
     * Calling the Post Save Settings after every save. Grid (and others?)
     * saves its settings in the post_save_settings call.
     */
    public function save()
    {
        parent::save();
    }

    /**
     * After inserting, add the columns to the data table
     */
    public function onAfterInsert()
    {
        $this->createTable();
        $this->callPostSaveSettings();
    }

    /**
     * After deleting, drop the columns
     */
    public function onAfterDelete()
    {
        $installed = $this->getModelFacade()->get('Fieldtype')
            ->filter('name', $this->getFieldType())
            ->count();

        if ($installed) {
            $ft = $this->getFieldtypeInstance();
            $this->callSettingsModify($ft, 'delete');
        }

        $this->dropTable();

        if (! $this->hasProperty($this->getColumnPrefix() . 'legacy_field_data')
            || $this->getProperty($this->getColumnPrefix() . 'legacy_field_data') == true) {
            $this->dropColumns($this->getColumns());
        }
    }

    /**
     * If the update changes the field_type, we need to sync the columns
     * on the data table
     */
    public function onAfterUpdate($changed)
    {
        $this->callPostSaveSettings();

        $old_type = (isset($changed['field_type'])) ? $changed['field_type'] : $this->field_type;
        $old_action = (isset($changed['field_type'])) ? 'delete' : 'get_info';

        $old_ft = $this->getFieldtypeInstance($old_type, $changed);
        $old_columns = $this->callSettingsModify($old_ft, $old_action, $changed);

        $new_ft = $this->getFieldtypeInstance();
        $new_columns = $this->callSettingsModify($new_ft, 'get_info');

        if (! empty($old_columns) || ! empty($new_columns)) {
            $this->diffColumns($old_columns, $new_columns);
        }
    }

    protected function callSettingsModify($ft, $action, $changed = array())
    {
        $data = $this->getValues();
        $data = array_merge($data, $changed);

        if (! isset($data['field_settings'])) {
            $data['field_settings'] = array();
        }

        $data['ee_action'] = $action;

        return $ft->settings_modify_column($data);
    }

    /**
     * Calls post_save_settings on the fieldtype
     */
    protected function callPostSaveSettings()
    {
        $data = $this->getValues();
        $field = $this->getField($this->getSettingsValues());
        $field->postSaveSettings($data);
    }

    /**
     * Get the instance of the current fieldtype
     */
    protected function getFieldtypeInstance($field_type = null, $changed = array())
    {
        $field_type = $field_type ?: $this->getFieldType();
        $values = array_merge($this->getValues(), $changed);

        $facade = new FieldFacade($this->getId(), $values);
        $facade->setContentType($this->getContentType());

        return $facade->getNativeField();
    }

    /**
     * Simple getter for fieldtype, override if your fieldtype property has a
     * different name.
     *
     * @access protected
     * @return string The fieldtype.
     */
    protected function getFieldType()
    {
        return $this->field_type;
    }

    /**
     *
     */
    private function diffColumns($old, $new)
    {
        $old = $this->ensureDefaultColumns($old);
        $new = $this->ensureDefaultColumns($new);

        $drop = array();
        $change = array();

        foreach ($old as $name => $prefs) {
            if (! isset($new[$name])) {
                $drop[$name] = $old[$name];
            } elseif ($prefs != $new[$name]) {
                $change[$name] = $new[$name];
                unset($new[$name]);
            } else {
                unset($new[$name]);
            }
        }

        $this->dropColumns($drop);
        $this->modifyColumns($change);
    }

    /**
     * Modify columns that were changed
     *
     * @param Array $columns List of [column name => column definition]
     */
    private function modifyColumns($columns)
    {
        if (empty($columns)) {
            return;
        }

        $data_table = $this->getTableName();

        if (! $this->hasProperty($this->getColumnPrefix() . 'legacy_field_data')
            || $this->getProperty($this->getColumnPrefix() . 'legacy_field_data') == true) {
            $data_table = $this->getDataTable();
        }

        foreach ($columns as $name => &$column) {
            if (! isset($column['name'])) {
                $column['name'] = $name;
            }
        }

        ee()->load->dbforge();
        ee()->dbforge->modify_column($data_table, $columns);
    }

    /**
     * Drop columns, including the defaults
     *
     * @param Array $columns List of column definitions as in createColumns, but
     *						 only the keys are actually used
     */
    private function dropColumns($columns)
    {
        if (empty($columns)) {
            return;
        }

        $columns = array_keys($columns);

        $data_table = $this->getDataTable();

        ee()->load->dbforge();

        foreach ($columns as $column) {
            ee()->dbforge->drop_column($data_table, $column);
        }
    }

    /**
     * Add the default columns if they don't exist
     *
     * @param Array $columns Column definitions
     * @return array Updated column definitions
     */
    private function ensureDefaultColumns($columns)
    {
        $id_field_name = $this->getColumnPrefix() . 'field_id_' . $this->getId();
        $ft_field_name = $this->getColumnPrefix() . 'field_ft_' . $this->getId();

        if (! isset($columns[$id_field_name])) {
            $columns[$id_field_name] = array(
                'type' => 'text',
                'null' => true
            );
        }

        if (! isset($columns[$ft_field_name])) {
            $columns[$ft_field_name] = array(
                'type' => 'tinytext',
                'null' => true
            );
        }

        return $columns;
    }

    private function getColumns()
    {
        $ft = $this->getFieldtypeInstance();
        $data = $this->getValues();
        $data['ee_action'] = 'add';
        $columns = array();

        foreach ($ft->settings_modify_column($data) as $key => $values) {
            $columns[$this->getColumnPrefix() . $key] = $values;
        }

        return $this->ensureDefaultColumns($columns);
    }

    private function getCacheKey()
    {
        return $cache_key = '/' . get_class($this) . '/' . $this->getId();
    }

    public function getColumnNames()
    {
        $cache_key = $this->getCacheKey();
        $names = ee()->cache->get($cache_key);

        if ($names === false) {
            $names = array_keys($this->getColumns());
            ee()->cache->save($cache_key, $names, 0);
        }

        return $names;
    }

    /**
     * Set a prefix on the default columns we manage for fields
     *
     * @return	String	Prefix string to use
     */
    public function getColumnPrefix()
    {
        return '';
    }

    public function getTableName()
    {
        return $this->getDataTable() . '_field_' . $this->getId();
    }

    public function getDataStorageTable()
    {
        if (! $this->hasProperty($this->getColumnPrefix() . 'legacy_field_data')
            || $this->getProperty($this->getColumnPrefix() . 'legacy_field_data') == true) {
            return $this->getDataTable();
        }

        return $this->getTableName();
    }

    protected function getForeignKey()
    {
        return 'entry_id';
    }

    /**
     * Create the table for the field
     */
    public function createTable()
    {
        if (ee()->db->table_exists($this->getTableName())) {
            return;
        }

        $fields = array(
            'id' => array(
                'type' => 'int',
                'constraint' => 10,
                'null' => false,
                'unsigned' => true,
                'auto_increment' => true
            ),
            $this->getForeignKey() => array(
                'type' => 'int',
                'constraint' => 10,
                'null' => false,
                'unsigned' => true,
            )
        );

        $fields = array_merge($fields, $this->getColumns());

        ee()->load->dbforge();
        ee()->load->library('smartforge');
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key($this->getForeignKey());
        ee()->dbforge->create_table($this->getTableName(), true);

        // Pre-populate the cache...
        $this->getColumnNames();
    }

    /**
     * Drops the table for the field
     */
    private function dropTable()
    {
        ee()->load->library('smartforge');
        ee()->smartforge->drop_table($this->getTableName());

        ee()->cache->delete($this->getCacheKey());
    }

    /**
     * TEMPORARY, VOLATILE, DO NOT USE
     *
     * @param	mixed	$data			Data for this field
     * @param	int		$content_id		Content ID to pass to the fieldtype
     * @param	string	$content_type	Content type to pass to the fieldtype
     * @param	array	$variable_mods		Variable modifiers and parameters, if present
     * @param	string	$tagdata		Tagdata to perform the replacement in
     * @param	string	$row			Row array to set on the fieldtype
     * @return	string	String with variable parsed
     */
    public function parse($data, $content_id, $content_type, $variable_mods, $tagdata, $row, $tag = false)
    {
        $fieldtype = $this->getFieldtypeInstance();
        $settings = $this->getSettingsValues();
        $field_fmt = isset($this->field_fmt) ? $this->field_fmt : $this->field_default_fmt;
        $settings['field_settings'] = array_merge($settings['field_settings'], array('field_fmt' => $field_fmt));
        $modifier = (! empty($variable_mods['modifier'])) ? $variable_mods['modifier'] : '';
        $params = (! empty($variable_mods['params'])) ? $variable_mods['params'] : array();

        if ($this->field_type == 'date') {
            // Set 0 to NULL, kill any formatting
            //$row['field_ft_'.$dval] = 'none';
            $data = ($data == 0) ? null : $data;
        }

        $fieldtype->_init(array(
            'row' => $row,
            'field_id' => $this->getId(),
            'content_id' => $content_id,
            'content_type' => $content_type,
            'field_fmt' => $field_fmt,
            'settings' => $settings['field_settings']
        ));

        if (isset($variable_mods['all_modifiers']) && !empty($variable_mods['all_modifiers'])) {
            foreach ($variable_mods['all_modifiers'] as $tag_modifier => $modifier_params) {
                $parse_fnc = ($tag_modifier) ? 'replace_' . $tag_modifier : 'replace_tag';
                if (method_exists($fieldtype, $parse_fnc) || ee('Variables/Modifiers')->has($tag_modifier)) {
                    $data = ee()->api_channel_fields->apply($parse_fnc, array(
                        $data,
                        $modifier_params,
                        false
                    ));
                }
            }
        } else {
            $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';
            if (method_exists($fieldtype, $parse_fnc) || ee('Variables/Modifiers')->has($modifier)) {
                $data = ee()->api_channel_fields->apply($parse_fnc, array(
                    $data,
                    $params,
                    false
                ));
            }
        }
        if (is_null($data)) {
            $data = '';
        }
        if (is_null($data)) {
            $data = '';
        }
        if ($tag) {
            return str_replace(LD . $tag . RD, $data, $tagdata);
        }
        $tag = $this->field_name;
        if ($modifier) {
            $tag = $tag . ':' . $modifier;
        }

        return str_replace(LD . $tag . RD, $data, $tagdata);
    }

    /**
     * If this entity is not new (an edit) then we cannot change this entity's
     * type to something incompatible with its initial type.
     */
    public function validateIsCompatibleWithPreviousValue($key, $value, $params, $rule)
    {
        if (! $this->isNew()) {
            $previous_value = $this->getBackup('field_type');

            if ($previous_value) {
                $compatibility = $this->getCompatibleFieldtypes();

                // If what we are set to now is not compatible to what we were
                // set to before the change, then we are invalid.
                if (! isset($compatibility[$previous_value])) {
                    // Reset it and return an error.
                    $this->field_type = $previous_value;

                    return lang('invalid_field_type');
                }
            }
        }

        return true;
    }

    /**
     * Validate the field name to avoid variable name collisions
     */
    public function validateNameIsNotReserved($key, $value, $params, $rule)
    {
        if (in_array($value, ee()->cp->invalid_custom_field_names())) {
            return lang('reserved_word');
        }

        return true;
    }

    /**
     * Fieldtypes that are compatible
     *
     * @return array
     */
    public function getCompatibleFieldtypes()
    {
        $fieldtypes = array();
        $compatibility = array();

        foreach (ee('Addon')->installed() as $addon) {
            if ($addon->hasFieldtype()) {
                foreach ($addon->get('fieldtypes', array()) as $fieldtype => $metadata) {
                    if (isset($metadata['compatibility'])) {
                        $compatibility[$fieldtype] = $metadata['compatibility'];
                    }
                }

                $fieldtypes = array_merge($fieldtypes, $addon->getFieldtypeNames());
            }
        }

        if ($this->getFieldType()) {
            if (! isset($compatibility[$this->getFieldType()])) {
                return array($this->getFieldType() => $fieldtypes[$this->getFieldType()]);
            }

            $my_type = $compatibility[$this->getFieldType()];

            $compatible = array_filter($compatibility, function ($v) use ($my_type) {
                return $v == $my_type;
            });

            $fieldtypes = array_intersect_key($fieldtypes, $compatible);
        }

        asort($fieldtypes);

        return $fieldtypes;
    }

    /**
     * Fieldtypes that can be used by given model
     *
     * @return array
     */
    public function getUsableFieldtypes($modelName = 'ChannelField')
    {
        $use = array();

        foreach (ee('Addon')->installed() as $addon) {
            if ($addon->hasFieldtype()) {
                $fieldtypeNames = $addon->getFieldtypeNames();
                foreach ($addon->get('fieldtypes', array()) as $fieldtype => $metadata) {
                    if ($modelName == 'ChannelField' || (isset($metadata['use']) && in_array($modelName, $metadata['use']))) {
                        $use[$fieldtype] = $fieldtypeNames[$fieldtype];
                    }
                }
            }
        }

        asort($use);

        return $use;
    }
}
// EOF
