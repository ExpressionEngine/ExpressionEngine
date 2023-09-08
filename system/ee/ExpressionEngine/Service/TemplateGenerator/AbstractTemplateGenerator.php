<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

abstract class AbstractTemplateGenerator implements TemplateGeneratorInterface
{
    /**
     * Generator name to be displayed in the UI
     *
     * @var string
     */
    protected $name;

    /**
     * The list of templates that this generator can create
     * We expect the array key to be the template name and the value to be the template description
     *
     * @var array
     */
    protected $templates = [];

    /**
     * Custom options supported by this generator
     *
     * @var array
     */
    protected $options = [];

    /**
     * Validation rules for the options passed to this generator
     *
     * @var array
     */
    protected $_validation_rules = [];

    /**
     * Return the name of the generator
     * Allows using lang keys for the name
     *
     * @return string
     */
    public function getName(): string
    {
        return lang($this->name);
    }

    /**
     * Return list of templates provided by this generator
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Return list of options provided by this generator
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Return validation rules for the options passed to this generator
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->_validation_rules;
    }
}
