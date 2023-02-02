<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\ChannelSet;

/**
 * Channel Set Service: Import Result
 */
class ImportResult
{
    /**
     * @var Bool Is valid import?
     */
    private $valid = true;

    /**
     * @var Array of fatal errors
     */
    private $errors = array();

    /**
     * @var Array of fatal model errors
     */
    private $model_errors = array();

    /**
     * @var Array of errors we can recover from with user input
     */
    private $fixable_errors = array();

    /**
     * Add a fatal error.
     *
     * @param String $error Fatal error description
     * @return $this
     */
    public function addError($error)
    {
        $this->valid = false;
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Add a failed model. We'll check if it's recoverable by user input and
     * categorize them accordingly.
     *
     * @param String $heading Descriptive heading for the model in question
     * @param Model $model The model that failed
     * @param String $field The field that errored
     * @param Array $rules The rules that failed
     * @return void
     */
    public function addModelError($heading, $model, $field, $rules)
    {
        $this->valid = false;

        if ($this->errorIsRecoverable($model, $field, $rules)) {
            $ident_field = Structure::getIdentityFieldFor($model);
            $identity = $model->$ident_field;

            $this->fixable_errors[$heading][] = array($model, $field, $identity, $rules);
        } else {
            $this->model_errors[$heading][] = array($model, $field, $rules);
        }
    }

    /**
     * Get fatal errors
     *
     * @return array Fatal errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get model errors that were not recoverable
     *
     * @return array Model errors
     */
    public function getModelErrors()
    {
        return $this->model_errors;
    }

    /**
     * Get recoverable errors
     *
     * @return array Recoverable errors
     */
    public function getRecoverableErrors()
    {
        return $this->fixable_errors;
    }

    /**
     * Is this import valid?
     *
     * @return bool Valid?
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Is this import failure recoverable?
     *
     * @return bool Recoverable?
     */
    public function isRecoverable()
    {
        return (count($this->errors) + count($this->model_errors) == 0);
    }

    /**
     * Proxy for cleaner controller code. Move it?
     */
    public function getLongFieldIfShortened($model, $field)
    {
        return Structure::getLongFieldIfShortened($model, $field);
    }

    /**
     * Proxy for cleaner controlelr code. Move it?
     */
    public function getTitleFieldFor($model)
    {
        return Structure::getTitleFieldFor($model);
    }

    /**
     * Check if an error is recoverable.
     *
     * This is a little heavy handed and doesn't nearly cover all the cases
     * that we might be able to recover from, but a lot of them happen inside
     * model callbacks, which means they fire in a closure and introspection is
     * subpar. Can be worked around, but this was faster and catches the common
     * cases.
     *
     * @param Model $model Model that errored
     * @param string $field Field name that errored
     * @param array $rules Rules that failed
     * @return bool Can recover?
     */
    private function errorIsRecoverable($model, $field, $rules)
    {
        $recoverable = array(
            'ee:Channel' => array('channel_title', 'channel_name'),
            'ee:ChannelFieldGroup' => array('group_name'),
            'ee:CategoryGroup' => array('group_name'),
            'ee:ChannelField' => array('field_name'),
            'ee:UploadDestination' => array('name', 'server_path', 'url')
        );

        if (isset($recoverable[$model->getName()])) {
            if (in_array($field, $recoverable[$model->getName()])) {
                return true;
            }
        }

        return false;
    }
}
