<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Advisor;

class FieldtypeAdvisor
{
    public function getMissingFieldtypes()
    {
        $used_fts = $this->guessAllUsedFieldtypes();
        $installed_fts = $this->getInstalledFieldtypes();
        $missing_fts = array_diff(array_keys($used_fts), array_keys($installed_fts));

        $data = [];
        foreach ($missing_fts as $ft) {
            $data[$ft] = $used_fts[$ft];
        }

        return $data;
    }

    public function getMissingFieldtypeCount()
    {
        return count($this->getMissingFieldtypes());
    }

    public function getUsedFieldtypes()
    {
        $all_fields = array_keys($this->guessAllUsedFieldtypes());
        $installed_fts = $this->getInstalledFieldtypes();

        $data = [];
        foreach ($all_fields as $field) {
            if (isset($installed_fts[$field])) {
                $data[$field] = $installed_fts[$field];
            } else {
                $data[$field] = $field;
            }
        }
        sort($data);

        return $data;
    }

    public function getUnusedFieldtypes()
    {
        $used_fts = $this->guessAllUsedFieldtypes();
        $installed_fts = $this->getInstalledFieldtypes();
        $unused_fts = array_diff(array_keys($installed_fts), array_keys($used_fts));

        $data = [];
        foreach ($unused_fts as $ft) {
            $data[] = $installed_fts[$ft];
        }

        return $data;
    }

    // Get fieldtypes used by other addons
    public function guessAllUsedFieldtypes()
    {
        static $used_fts;

        if (empty($used_fts)) {
            $used_fts = $this->getUsedChannelFieldtypes();
            $db_tables = ee()->db->list_tables();
            foreach ($db_tables as $table) {
                $columns = ee()->db->query("SHOW COLUMNS FROM " . $table);
                foreach ($columns->result_array() as $row) {
                    $column = $row['Field'];
                    if (strrpos($column, 'col_type') === strlen($column) - 8) {
                        $fts_q = ee()->db->select($column)->get($table);
                        foreach ($fts_q->result_array() as $ft_row) {
                            if (!isset($used_fts[$ft_row[$column]])) {
                                $used_fts[$ft_row[$column]] = [$table];
                            } else {
                                $used_fts[$ft_row[$column]][$table] = $table;
                            }
                        }
                    }
                }
            }
        }

        return $used_fts;
    }

    // Get fieldtypes used by channels
    private function getUsedChannelFieldtypes()
    {
        $channel_fields = ee()->db->select('field_type')->order_by('field_type')->get('channel_fields');
        $data = [];
        foreach ($channel_fields->result_array() as $ft) {
            $data[$ft['field_type']] = ['exp_channel_fields'];
        }

        return $data;
    }

    // Get fieldtypes from exp_fieldtypes table
    public function getFieldtypesFromTable()
    {
        $used_fieldtypes = ee()->db->select('name')->order_by('name')->get('fieldtypes');

        return array_column($used_fieldtypes->result_array(), 'name');
    }

    // Get installed fieldtypes using models
    public function getInstalledFieldtypes()
    {
        static $installed_fts;

        if (empty($installed_fts)) {
            $installed_fts = array();
            foreach (ee('Addon')->all() as $name => $info) {
                $info = ee('Addon')->get($name);

                if ($info->isInstalled()) {
                    $installed_fts = array_merge($installed_fts, $info->getFieldtypeNames());
                }
            }
        }

        return $installed_fts;
    }
}

// EOF
