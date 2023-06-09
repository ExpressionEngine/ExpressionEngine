<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Category;

use ExpressionEngine\Model\Content\FieldModel;

/**
 * Category Field Model
 */
class CategoryField extends FieldModel
{
    protected static $_primary_key = 'field_id';
    protected static $_table_name = 'category_fields';

    protected static $_hook_id = 'category_field';

    protected static $_typed_columns = array(
        'field_ta_rows' => 'int',
        'field_maxl' => 'int',
        'field_required' => 'boolString',
        'field_show_fmt' => 'boolString',
        'field_order' => 'int',
        'field_settings' => 'json',
        'legacy_field_data' => 'boolString',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'CategoryGroup' => array(
            'type' => 'belongsTo'
        )
    );

    protected static $_events = array(
        'beforeInsert'
    );

    protected static $_validation_rules = array(
        'field_type' => 'required|enum[text,textarea,select]',
        'field_label' => 'required|xss|noHtml|maxLength[50]',
        'field_name' => 'required|alphaDash|unique[site_id]|validateNameIsNotReserved|maxLength[32]',
        'field_ta_rows' => 'integer',
        'field_maxl' => 'integer',
        'field_required' => 'enum[y,n]',
        'field_show_fmt' => 'enum[y,n]',
        'field_order' => 'integer',
        'legacy_field_data' => 'enum[y,n]'
    );

    protected $field_id;
    protected $site_id;
    protected $group_id;
    protected $field_name;
    protected $field_label;
    protected $field_type;
    protected $field_list_items;
    protected $field_maxl;
    protected $field_ta_rows;
    protected $field_default_fmt;
    protected $field_show_fmt;
    protected $field_text_direction;
    protected $field_required;
    protected $field_order;
    protected $field_settings;
    protected $legacy_field_data;

    public function getSettingsValues()
    {
        $values = parent::getSettingsValues();

        $this->getField($values)->setFormat($this->getProperty('field_default_fmt'));

        $values['field_settings'] = $this->getProperty('field_settings') ?: array();

        $values['field_settings']['field_show_file_selector'] = 'n';

        return $values;
    }

    public function set(array $data = array())
    {
        parent::set($data);

        $field = $this->getField($this->getSettingsValues());
        $this->setProperty('field_settings', $field->saveSettingsForm($data));

        return $this;
    }

    public function getContentType()
    {
        return 'category';
    }

    protected function getForeignKey()
    {
        return 'cat_id';
    }

    /**
     * New fields get appended
     */
    public function onBeforeInsert()
    {
        if ($this->getProperty('field_list_items') == null) {
            $this->setProperty('field_list_items', '');
        }

        $field_order = $this->getProperty('field_order');

        if (empty($field_order)) {
            $count = $this->getModelFacade()->get('CategoryField')
                ->filter('group_id', $this->getProperty('group_id'))
                ->count();
            $this->setProperty('field_order', $count + 1);
        }
    }

    /**
     * Update field formatting on existing categories
     *
     * @return void
     */
    public function updateFormattingOnExisting()
    {
        ee()->db->update(
            $this->getDataStorageTable(),
            array('field_ft_' . $this->field_id => $this->field_default_fmt)
        );
    }

    public function getStructure()
    {
        return $this->getCategoryGroup();
    }

    public function getDataTable()
    {
        return 'category_field_data';
    }
}

// EOF
