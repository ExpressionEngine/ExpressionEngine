<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;
use ExpressionEngine\Model\Channel\ChannelField;
use ExpressionEngine\Model\Content\FieldFacade;

/**
 * Module Tab Column
 */
class ModuleTab extends Column
{
    private $tab;
    private $module;

    public function __construct($identifier)
    {
        parent::__construct($identifier);
        if (empty($this->tab)) {
            $module_name = substr($identifier, 4); // strip 'tab_'
            $this->module = ee('Addon')->get($module_name);

            if (!$this->module->isInstalled()) {
                return;
            }

            include_once($this->module->getPath() . '/tab.' . $module_name . '.php');
            $class_name = ucfirst($module_name) . '_tab';
            $this->tab = new $class_name();
        }
    }

    public function getTableColumnLabel()
    {
        return $this->module->getName();
    }

    public function getTableColumnConfig()
    {
        if (method_exists($this->tab, 'getTableColumnConfig')) {
            return $this->tab->getTableColumnConfig();
        }

        return parent::getTableColumnConfig();
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return $this->tab->renderTableCell($data, $field_id, $entry);
    }
}
