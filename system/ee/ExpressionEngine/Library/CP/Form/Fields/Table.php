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

use ExpressionEngine\Library\CP\Url;

class Table extends Html
{
    /**
     * @param array $columns
     * @return $this
     */
    public function setColumns(array $columns): Table
    {
        $this->set('columns', $columns);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns(): ?array
    {
        return $this->get('columns');
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): Table
    {
        $this->set('table_options', $options);
        return $this;
    }

    /**
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->get('table_options');
    }

    /**
     * @param string $text
     * @param $action_text
     * @param $action_link
     * @param $external
     * @return $this
     */
    public function setNoResultsText(string $text, string $action_text = '', Url $action_link = null, bool $external = false): Table
    {
        $arr = ['text' => $text, 'action_text' => $action_text, 'action_link' => $action_link, 'external' => $external];
        $this->set('no_results_text', $arr);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoResultsText()
    {
        return $this->get('no_results_text');
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data): Table
    {
        $this->set('data', $data);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData(): array
    {
        return $this->get('data') ? $this->get('data') : [];
    }

    /**
     * @param array $row
     * @return $this
     */
    public function addRow(array $row): Table
    {
        $rows = $this->getData();
        $rows[] = $row;
        $this->set('data', $rows);
        return $this;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setBaseUrl(Url $url = null): Table
    {
        $this->set('base_url', $url);
        return $this;
    }

    /**
     * @return Url
     */
    public function getBaseUrl(): ?Url
    {
        return $this->get('base_url');
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $this->setContent(trim($this->renderTable()));
        $this->set('type', 'html');
        return parent::toArray();
    }

    /**
     * @return string
     */
    protected function renderTable(): string
    {
        $options = is_array($this->getOptions()) ? $this->getOptions() : [];
        $table = ee('CP/Table', $options);
        $table->setColumns($this->getColumns());

        $no_results = $this->getNoResultsText();
        if (is_array($no_results)) {
            $table->setNoResultsText($no_results['text'], $no_results['action_text'], $no_results['action_link'], $no_results['external']);
        }

        $table->setData($this->getData());
        return ee('View')->make('ee:_shared/table')->render($table->viewData($this->getBaseUrl()));;
    }
}
