<?php

namespace ExpressionEngine\Cli\Commands\Migration\Templates;

use ExpressionEngine\Library\Filesystem\Filesystem;

abstract class AbstractTemplate
{
    public $vars;

    public function __construct($vars)
    {
        $this->vars = $vars;
    }

    public function getParsedTemplate()
    {
        $this->checkForRequiredVars();

        $templateText = $this->getTemplateText();

        foreach ($this->vars as $var => $value) {
            $templateText = str_replace('{' . $var . '}', $value, $templateText);
        }

        return $templateText;
    }

    public function checkForRequiredVars()
    {
        $missing = array();
        foreach ($this->requiredVars() as $requiredVar) {
            if (! array_key_exists($requiredVar, $this->vars)) {
                $missing[] = $requiredVar;
            }
        }

        // If we found a required variable that is missing, let the user know
        if (!empty($missing)) {
            echo "Missing required variable for parsing migration template:\n";
            echo implode("\n", $missing);
            echo "\n";
            exit();
        }
    }

    protected function requiredVars()
    {
        return array('classname');
    }

    abstract protected function getTemplateText();
}
