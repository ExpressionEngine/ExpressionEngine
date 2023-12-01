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

class ConfigAdvisor
{
    public function checkBasePath()
    {
        if (!empty(ee()->config->item('base_path'))) {
            if (is_dir(ee()->config->item('base_path'))) {
                return true;
            }
        }

        $configValues = ee()->config->get_cached_site_prefs(ee()->config->item('site_id'));
        foreach ($configValues as $key => $value) {
            if (strpos($value, 'base_path') !== false) {
                return false;
            }
        }

        $uploadPaths = ee('db')->select('server_path')
            ->from('upload_prefs')
            ->where('adapter', 'local')
            ->get();
        if ($uploadPaths->num_rows() > 0) {
            foreach ($uploadPaths->result_array() as $row) {
                if (strpos($row['server_path'], 'base_path') !== false) {
                    return false;
                }
            }
        }

        return true;
    }
}

// EOF
