<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

class ImportResult {

    private $valid = TRUE;
	private $errors = array();
	private $model_errors = array();
	private $fixable_errors = array();

	public function addError($error)
	{
		$this->valid = FALSE;
		$this->errors[] = $error;

		return $this;
	}

    public function addModelError($heading, $model, $field, $rules)
    {
        $this->valid = FALSE;

        if ($this->errorIsRecoverable($model, $field, $rules))
        {
            $value = $model->$field;
            $this->fixable_errors[$heading][] = array($model, $field, $value, $rules);
        }
        else
        {
            $this->model_errors[$heading][] = array($model, $field, $rules);
        }
    }

	public function getErrors()
	{
		return $this->errors;
	}

    public function getModelErrors()
    {
        return $this->model_errors;
    }

    public function getRecoverableErrors()
    {
        return $this->fixable_errors;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function isRecoverable()
    {
        return (count($this->errors) + count($this->model_errors) == 0);
    }

    private function errorIsRecoverable($model, $field, $rules)
    {
        $recoverable = array(
            'ee:Channel' => array('channel_name'),
            'ee:ChannelFieldGroup' => array('group_name')
        );

        if (isset($recoverable[$model->getName()]))
        {
            if (in_array($field, $recoverable[$model->getName()]))
            {
                return TRUE;
            }
        }

        return FALSE;

/* todo requires some clever coding to get the right fields
        foreach ($rules as $rule)
        {
            if ($rule->getName() != 'callback' || $rule->getLanguageKey() != 'unique')
            {
                return FALSE;
            }
        }

        return TRUE;
*/
    }
}
