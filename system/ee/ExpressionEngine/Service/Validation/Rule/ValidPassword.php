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

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Password Validation Rule
 */
class ValidPassword extends ValidationRule
{
    protected $all_values = array();
    protected $last_error = '';

    public function validate($key, $password)
    {
        $password = (string) $password;
        ee()->lang->loadfile('myaccount');

        $pw_length = ee()->config->item('pw_min_len');
        if (strlen($password) < $pw_length) {
            $this->last_error = sprintf(lang('password_too_short'), $pw_length);
            return false;
        }

        // Is password max length correct?
        if (strlen($password) > PASSWORD_MAX_LENGTH) {
            $this->last_error = 'password_too_long';
            return false;
        }

        //  Make UN/PW lowercase for testing
        $lc_user = strtolower($this->all_values['username']);
        $lc_pass = strtolower($password);
        $nm_pass = strtr($lc_pass, 'elos', '3105');

        if ($lc_user == $lc_pass or $lc_user == strrev($lc_pass) or $lc_user == $nm_pass or $lc_user == strrev($nm_pass)) {
            $this->last_error = 'password_based_on_username';
            return false;
        }

        // Does password exist in dictionary?
        if (ee()->config->item('allow_dictionary_pw') != 'y') {
            $file = !empty(ee()->config->item('name_of_dictionary_file')) ? ee()->config->item('name_of_dictionary_file') : 'dictionary.txt';
            $path = reduce_double_slashes(PATH_DICT . $file);
            if (file_exists($path)) {
                $word_file = file($path);
                foreach ($word_file as $word) {
                    if (trim(strtolower($word)) == $lc_pass) {
                        $this->last_error = 'password_in_dictionary';
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function setAllValues(array $values)
    {
        $this->all_values = $values;
    }

    public function getLanguageKey()
    {
        return $this->last_error;
    }
}

// EOF
