<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation;

use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Validator Factory
 */
class Factory
{
    /**
     * Make a new validator for a set of rules
     */
    public function make($rules = array())
    {
        return new Validator($rules);
    }

    /**
     * Check to see if a value passes a rule's validation
     *
     * @param  string $rule The rule to check
     * @param  string $value The value to check
     * @return boolean TRUE if the check passes
     */
    public function check($rule, $value)
    {
        return $this->make(array('check' => $rule))
            ->validate(array('check' => $value))
            ->isValid();
    }

    /**
     * Takes a model validation result object and checks for errors on the
     * posted 'ee_fv_field' and returns an error message, or success message
     * but only if the request was an AJAX request.
     *
     * @param ExpressionEngine\Service\Validation\Result $result A model validation result
     * @return array|NULL NULL if the request was not via AJAX, otherwise an
     *   an array with an error message or a success notification.
     */
    public function ajax(ValidationResult $result)
    {
        $field = str_replace('[]', '', ee()->input->post('ee_fv_field'));

        if ($field && ee()->input->is_ajax_request()) {
            if ($result->hasErrors($field)) {
                return ['error' => $result->renderError($field)];
            }

            // Get the parent field name
            $field = preg_replace('/\[.+?\]/', '', $field);

            if ($result->hasErrors($field)) {
                return ['error' => $result->renderError($field)];
            }

            return ['success'];
        }

        return null;
    }
}

// EOF
