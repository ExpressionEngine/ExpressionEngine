<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form\Fields;

class Grid extends Table
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'content' => '',
        'add' => 'add'
    ];

    /**
     * @param array $row
     * @return $this
     */
    public function defineRow(array $row): Grid
    {
        $this->set('row_definition', $row);
        return $this;
    }

    /**
     * @return string
     */
    protected function renderTable(): string
    {
        $options = is_array($this->getOptions()) ? $this->getOptions() : [];
        $grid = ee('CP/GridInput', $options);
        $grid->setColumns($this->getColumns());

        $no_results = $this->getNoResultsText();
        if (is_array($no_results)) {
            $grid->setNoResultsText($no_results['text'], $no_results['action_text'], $no_results['action_link'], $no_results['external']);
        }

        $grid->setBlankRow($this->generateBlankRow());
        $grid->setData($this->generateDataStructure());
        $grid->loadAssets();
        return ee('View')->make('ee:_shared/table')->render($grid->viewData());;
    }

    /**
     * @return array
     */
    protected function generateBlankRow(): array
    {
        $return = [];
        $default_rows = $this->get('row_definition');
        if (!$default_rows) {
            return $return;
        }

        foreach ($default_rows as $column) {
            $method = 'generate' . ucfirst($column['type']) . 'Input';
            $choices = isset($column['choices']) ? $column['choices'] : [];
            if (method_exists($this, $method)) {
                $return[] = $this->$method($column['name'], $column);
            } else {
                $return[] = $column['type'] . ' INVALID';
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    protected function generateDataStructure(): array
    {
        $return = [];
        $data = $this->getData();
        if (!$data) {
            return $return;
        }

        $row_prototype = $this->get('row_definition');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->convertPostData();
        }

        foreach ($data as $key => $value) {
            $row_data = [];
            foreach ($row_prototype as $proto_key => $proto_value) {
                if (isset($value[$proto_value['name']])) {
                    $method = 'generate' . ucfirst($proto_value['type']) . 'Input';
                    if (method_exists($this, $method)) {
                        $proto_value['value'] = $value[$proto_value['name']];
                        $row_data[] = $this->$method($proto_value['name'], $proto_value);
                    } else {
                        $row_data[] = $proto_value['type'] . ' INVALID';
                    }
                }
            }

            $return[] = [
                'attrs' => [
                    'row_id' => $key,
                ],
                'columns' => $row_data
            ];
        }

        return $return;
    }

    /**
     * With Grid, we have to handle our own POST processing to handle
     * existing values so we do that here. It looks worse than it it,
     * but due to how some input elements (file, checkbox, for example)
     * won't include a POST value based on "reasons" we use our definition
     * as a base since otherwise we'll miss some parameters.
     * @return array
     */
    protected function convertPostData(): array
    {
        $post_data = $_POST[$this->getName()]['rows'];
        $row_prototype = $this->get('row_definition');
        $return = [];
        foreach ($post_data as $row_id => $row) {
            $data = [];
            foreach ($row_prototype as $k => $v) {
                $data[$v['name']] = '';
                if (isset($row[$v['name']])) {
                    $data[$v['name']] = $row[$v['name']];
                }
            }

            $return[] = $data;
        }

        return $return;
    }

    /**
     * @param string $name
     * @param array $settings
     * @return string
     */
    protected function generateTextInput(string $name, array $settings): string
    {
        return form_input($name, element('value', $settings));
    }

    /**
     * @param string $name
     * @param array $settings
     * @return string
     */
    protected function generateSelectInput(string $name, array $settings): string
    {
        return form_dropdown($name, element('choices', $settings), element('value', $settings));
    }

    /**
     * @param string $name
     * @param array $settings
     * @return string
     */
    protected function generatePasswordInput(string $name, array $settings): string
    {
        return form_password($name, element('value', $settings));
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    protected function generateCheckboxInput(string $name, array $settings): string
    {
        $checked = element('value', $settings) == 1;
        return form_checkbox($name, 1, $checked);
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    protected function generateTextareaInput(string $name, array $settings): string
    {
        $data = [
            'name' => $name,
            'value' => element('value', $settings),
            'cols' => element('cols', $settings, 90),
            'rows' => element('rows', $settings, 12)
        ];

        return form_textarea($data);
    }

    /**
     * @param $name
     * @param array $settings
     * @return string
     */
    protected function generateFileInput(string $name, array $settings): string
    {
        return form_upload($name, $value = '', $extra = '');
    }
}
