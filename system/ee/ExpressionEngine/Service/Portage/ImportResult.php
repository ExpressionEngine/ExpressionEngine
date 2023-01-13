<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Portage;

/**
 * Portage Service: Import Result
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

    public static $short_names = array(
        'ee:Channel' => array('channel_name' => 'channel_title'),
        'ee:ChannelField' => array('field_name' => 'field_label')
    );

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
     * @param Model $model The model that failed
     * @param String $field The field that errored
     * @param Array $rules The rules that failed
     * @return void
     */
    public function addModelError($model, $field, $rules)
    {
        $this->valid = false;

        if ($this->errorIsRecoverable($model, $field, $rules)) {
            $this->fixable_errors[$model->uuid][] = array($model, $field, $rules);
        } else {
            $this->model_errors[$model->uuid][] = array($model, $field, $rules);
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
     * For a given short name, grab the field that it's related to.
     *
     * For example, for `(channel, url_title)` this would return `title`
     *
     * @param Model $model The model instance
     * @param String $field The potential shortname
     * @return String Title field
     */
    public static function getLongFieldIfShortened($model, $field)
    {
        $name = $model->getName();

        if (array_key_exists($name, static::$short_names)) {
            if (array_key_exists($field, static::$short_names[$name])) {
                return static::$short_names[$name][$field];
            }
        }

        return null;
    }

    /**
     * Proxy for cleaner controlelr code. Move it?
     */
    public function getTitleFieldFor($model)
    {
        foreach ($model->getFields() as $field) {
            foreach (['title', 'label', 'name', 'status'] as $string) {
                if (strpos($field, $string) !== false) {
                    return $field;
                }
            }
        }
        return null;
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
        foreach (['title', 'label', 'name', 'url', 'path', 'status'] as $string) {
            if (strpos($field, $string) !== false) {
                return true;
            }
        }

        return false;
    }
}
