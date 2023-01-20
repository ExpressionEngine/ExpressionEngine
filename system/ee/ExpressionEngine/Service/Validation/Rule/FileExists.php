<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * File Exists Validation Rule
 */
class FileExists extends ValidationRule
{
    protected $all_values = array();

    public function validate($key, $value)
    {
        $filesystem = ee('Filesystem');

        if(array_key_exists('filesystem_provider', $this->all_values)) {
            try {
                $provider = $this->all_values['filesystem_provider'];
                $filesystem = $provider->getFilesystem();
                unset($this->all_values['filesystem_provider']);
            }catch(\Exception $e) {
                $this->stop();
            }
        }

        if ($filesystem->exists(parse_config_variables($value, $this->all_values))) {
            return true;
        }

        // STOP if not exists, there's no point in further validating an
        // invalid file path
        if ($value !== null && $value !== '') {
            return $this->stop();
        }

        return false;
    }

    public function getLanguageKey()
    {
        return 'invalid_path';
    }

    public function setAllValues(array $values)
    {
        $this->all_values = $values;
    }
}

// EOF
