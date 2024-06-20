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

/**
 * Requirements for the Template Generators
 */
interface TemplateGeneratorInterface
{
    /**
     * Every generator is required to return it's name that will be used in the UI
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Every generator is required to return list of templates that it's capable to create
     * We expect the array key to be the template name and the value to be template description (saved as notes)
     * Template type will default to webpage, can be changed by using appropriate extension (e.g. entries.rss)
     * Each template needs to have corresponding file in stubs folder with .php extension (e.g. entries.rss.php)
     *
     * @return array
     */
    public function getTemplates(): array;

    /**
     * Generators are required to return list of options that are specific to them
     * These will be combined with the options provided by Factory
     * Can be empty array
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Options that are passed to generator might need validation
     * If that is the case, generator is required to return array of validation rules
     * The format is [option_name => 'required|integer']
     *
     * @return array
     */
    public function getValidationRules(): array;

    /**
     * Generators are expected to return an array of variables and their values
     * Which will be used for replacement in stubs by the Factory using View service
     *
     * @return array
     */
    public function getVariables(): array;
}