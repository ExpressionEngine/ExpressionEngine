<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to make action files for addons
 */
class CommandGenerateTemplates extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Generate Templates';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'generate:templates';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php generate:templates [generator] [--options] [--json] [--options-json] [--code]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'list,l'        => 'command_generate_templates_list_generators',
        /* 'themes,t'      => 'command_generate_templates_list_themes', */
        'show,s'        => 'command_generate_templates_show_template_content',
        'code,c'        => 'command_generate_templates_show_template_code',
        'json,j'        => 'command_generate_templates_show_template_content_json',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Get all the template generators
        $generatorsList = $this->getAllGenerators();

        // Handle list operations
        if ($this->option('--list', false)) {
            $this->handleListGenerators($generatorsList);
            $this->complete();
        }

        // list the themes that are additionally available
        /* if ($this->option('--themes', false)) {
            $this->info('command_generate_templates_listing_themes');
            $this->data['table'] = [];
            $themes = ee('TemplateGenerator')->getThemes();
            array_walk($themes, function ($name, $key) {
                $this->data['table'][] = [$key, $name];
            });
            $this->table([
                lang('name'),
                lang('description'),
            ], $this->data['table']);
            $this->complete();
        }*/

        // Get the generator from the first argument
        $generatorKey = $this->getFirstUnnamedArgument();

        // Get or select a valid generator
        $generatorKey = $this->getOrSelectGenerator($generatorKey, $generatorsList);
        $generator = $this->instantiateGenerator($generatorKey);

        // Check for JSON options output first (before any other messages)
        if ($this->option('--json', false)) {
            // Show JSON for specific generator options
            $this->displayGeneratorOptionsJson($generator, $generatorKey);
            $this->complete();
        }

        // Determine if we should only output raw template code
        $codeOnly = $this->option('--code', false);

        if (! $this->option('--help', false) && !$codeOnly) {
            $this->info('generate_templates_started');
        }

        $showOnly = $this->option('--show', false) || $codeOnly;

        // If the generator is disabled for the CP, we will only show the templates rather than trying to generate them as files
        if($generator->generatorDisabledForLocation('CP')) {
            $showOnly = true;
        }

        $this->data['options'] = [];

        // Setup generator options
        $options = $generator->getOptions();
        $this->setupCommandOptions($options);

        if ($this->option('--help', false)) {
            return $this->help();
        }

        // Process generator options
        $this->processGeneratorOptions($options, $generator, $showOnly);

        // Generate templates
        $this->generateTemplates($generator, $showOnly, $codeOnly);

        if (!$codeOnly) {
            $this->info('');
            $this->info('generate_templates_created_successfully');
        }
    }

    private function displayJson($data = null)
    {
        // If no data is provided, generate comprehensive JSON with all generators and their options
        if ($data === null) {
            $data = $this->generateAllGeneratorsJson();
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Generate comprehensive JSON data for all available template generators
     * including their options, templates, and metadata
     *
     * @return array
     */
    private function generateAllGeneratorsJson()
    {
        $result = [
            'generators' => [],
            'total_generators' => 0,
            'generated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Get all the template generators
            $generatorsList = $this->getAllGenerators();

            foreach ($generatorsList as $generatorKey => $generator) {
                $generatorData = $this->buildGeneratorData($generatorKey, $generator);
                $result['generators'][$generatorKey] = $generatorData;
                $result['total_generators']++;
            }

        } catch (\Exception $e) {
            $result['error'] = 'Failed to load template generators: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Format generator options for JSON output
     *
     * @param array $options
     * @return array
     */
    private function formatGeneratorOptions($options)
    {
        $formattedOptions = [];

        foreach ($options as $optionKey => $optionData) {
            $formattedOption = [
                'key' => $optionKey,
                'type' => $optionData['type'] ?? 'text',
                'description' => $optionData['desc'] ?? $optionKey,
                'required' => $optionData['required'] ?? false,
                'default' => $optionData['default'] ?? null
            ];

            // Handle choices/options for select, radio, checkbox types
            if (isset($optionData['choices'])) {
                if (is_array($optionData['choices'])) {
                    $formattedOption['choices'] = $optionData['choices'];
                } else {
                    // If choices is a method name, we can't call it here safely
                    // but we can indicate that choices are available
                    $formattedOption['choices_method'] = $optionData['choices'];
                    $formattedOption['choices'] = [];
                }
            }

            // Add any additional properties
            foreach ($optionData as $key => $value) {
                if (!in_array($key, ['type', 'desc', 'required', 'default', 'choices'])) {
                    $formattedOption[$key] = $value;
                }
            }

            $formattedOptions[$optionKey] = $formattedOption;
        }

        return $formattedOptions;
    }

    /**
     * Display JSON for a specific generator's options
     *
     * @param mixed $generator
     * @param string $generatorKey
     */
    private function displayGeneratorOptionsJson($generator, $generatorKey)
    {
        $generatorData = [
            'generator' => [
                'key' => $generatorKey,
                'name' => $generator->getName(),
                'templates' => $generator->getTemplates(),
                'options' => $this->formatGeneratorOptions($generator->getOptions()),
                'validation_rules' => $generator->getValidationRules(),
                'is_disabled_for_cp' => $generator->generatorDisabledForLocation('CP'),
                'is_disabled_for_cli' => $generator->generatorDisabledForLocation('CLI')
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];

        $this->displayJson($generatorData);
    }


    /**
     * Get all available template generators
     *
     * @return array
     */
    private function getAllGenerators()
    {
        return ee('TemplateGenerator')->registerAllTemplateGenerators();
    }

    /**
     * Build generator data for JSON output
     *
     * @param string $generatorKey
     * @param mixed $generator
     * @return array
     */
    private function buildGeneratorData($generatorKey, $generator)
    {
        try {
            // Get generator instance to access its methods
            $generatorInstance = ee('TemplateGenerator')->make($generatorKey);

            return [
                'key' => $generatorKey,
                'name' => $generatorInstance->getName(),
                'templates' => $generatorInstance->getTemplates(),
                'options' => $this->formatGeneratorOptions($generatorInstance->getOptions()),
                'validation_rules' => $generatorInstance->getValidationRules(),
                'is_disabled_for_cp' => $generatorInstance->generatorDisabledForLocation('CP'),
                'is_disabled_for_cli' => $generatorInstance->generatorDisabledForLocation('CLI')
            ];

        } catch (\Exception $e) {
            // If we can't instantiate a generator, still include basic info
            return [
                'key' => $generatorKey,
                'name' => $generator->getName(),
                'error' => 'Could not load generator: ' . $e->getMessage(),
                'templates' => [],
                'options' => [],
                'validation_rules' => [],
                'is_disabled_for_cp' => false,
                'is_disabled_for_cli' => false
            ];
        }
    }

    /**
     * Handle listing generators with optional JSON output
     *
     * @param array $generatorsList
     */
    private function handleListGenerators($generatorsList)
    {
        if ($this->option('--json', false)) {
            // Show comprehensive JSON for all generators (equivalent to old --json-all)
            $this->displayJson();
        } else {
            $this->info('command_generate_templates_listing_generators');
            $tableData = $this->buildGeneratorTable($generatorsList);
            $this->table([
                lang('name'),
                lang('description'),
            ], $tableData);
        }
    }

    /**
     * Build table data from generators list
     *
     * @param array $generatorsList
     * @return array
     */
    private function buildGeneratorTable($generatorsList)
    {
        $tableData = [];
        array_walk($generatorsList, function ($generator, $key) use (&$tableData) {
            $name = $generator->getName();
            if (!empty($name)) {
                $tableData[] = [$key, $name];
            }
        });
        return $tableData;
    }

    /**
     * Get or select a valid generator key
     *
     * @param string|null $generatorKey
     * @param array $generatorsList
     * @return string
     */
    private function getOrSelectGenerator($generatorKey, $generatorsList)
    {
        // If its not a valid generator, ask the user to select one
        if (!$generatorKey || !isset($generatorsList[$generatorKey])) {
            $askText = lang('command_generate_templates_ask_generator');
            $genList = $this->buildGeneratorList($generatorsList);
            $generatorKey = $this->askFromList($askText, $genList, null);
        }

        // Check to see if the generator is valid
        if (!isset($generatorsList[$generatorKey])) {
            $this->fail('command_generate_templates_invalid_generator');
        }

        return $generatorKey;
    }

    /**
     * Build generator list for selection
     *
     * @param array $generatorsList
     * @return array
     */
    private function buildGeneratorList($generatorsList)
    {
        $genList = [];
        array_walk($generatorsList, function ($generator, $key) use (&$genList) {
            $name = $generator->getName();
            $genList[$key] = $name;
        });
        return $genList;
    }

    /**
     * Instantiate a generator with error handling
     *
     * @param string $generatorKey
     * @return mixed
     */
    private function instantiateGenerator($generatorKey)
    {
        try {
            return ee('TemplateGenerator')->make($generatorKey);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Setup command options from generator options
     *
     * @param array $options
     */
    private function setupCommandOptions($options)
    {
        $normalizedOptions = [];
        foreach ($options as $option => $optionParams) {
            $command = $option;
            if (isset($optionParams['type']) && $optionParams['type'] == 'checkbox') {
                $command .= '*';
            }
            $command .= ':';
            if (isset($optionParams['required']) && $optionParams['required']) {
                $command .= ':';
            }
            $normalizedOptions[$command] = isset($optionParams['desc']) ? $optionParams['desc'] : $option;
        }

        $this->commandOptions = array_merge($normalizedOptions, $this->commandOptions);
        $this->loadOptions(); // need to have those re-loaded now
    }

    /**
     * Process generator options and collect user input
     *
     * @param array $options
     * @param mixed $generator
     * @param bool $showOnly
     */
    private function processGeneratorOptions($options, $generator, $showOnly)
    {
        foreach ($options as $option => $optionParams) {
            if ($showOnly && $option == 'template_group') {
                $this->data['options']['template_group'] = '';
                continue; // need not to ask if we just show template on screen
            }

            if ($this->shouldSkipOption($optionParams)) {
                continue; // do not ask if we have no choice
            }

            $optionValue = $this->getOptionValue($option, $optionParams);
            $this->data['options'][$option] = $optionValue;

            // Validate the option
            $this->validateOption($generator, $option);
        }
    }

    /**
     * Check if an option should be skipped
     *
     * @param array $optionParams
     * @return bool
     */
    private function shouldSkipOption($optionParams)
    {
        return in_array($optionParams['type'], ['radio', 'select']) &&
            (
                !isset($optionParams['choices']) || //no choice
                empty($optionParams['choices']) || // choice is empty
                (count($optionParams['choices']) == 1 && array_key_first($optionParams['choices']) == ($optionParams['default'] ?? null)) // there just 1 choice, which is default
            );
    }

    /**
     * Get option value from user input
     *
     * @param string $option
     * @param array $optionParams
     * @return mixed
     */
    private function getOptionValue($option, $optionParams)
    {
        $default = $optionParams['default'] ?? '';
        $required = $optionParams['required'] ?? false;
        $askText = $this->buildAskText($option, $optionParams);

        $optionValue = $this->getOptionOrAsk(
            '--' . $option,
            $askText,
            $default,
            $required
        );

        return $this->processOptionValue($optionValue, $optionParams);
    }

    /**
     * Build the ask text for an option
     *
     * @param string $option
     * @param array $optionParams
     * @return string
     */
    private function buildAskText($option, $optionParams)
    {
        $askText = isset($optionParams['desc']) ? lang($optionParams['desc']) : lang($option);

        if (isset($optionParams['choices']) && !empty($optionParams['choices'])) {
            foreach ($optionParams['choices'] as $key => $val) {
                $askText .= "\n - " . $key . " : " . lang($val);
            }
            if ($optionParams['type'] == 'checkbox') {
                $askText .= "\n\n" . lang('separate_choices_commas') . ":";
            } else {
                $askText .= "\n\n: ";
            }
        }

        return $askText;
    }

    /**
     * Process option value based on its type
     *
     * @param mixed $optionValue
     * @param array $optionParams
     * @return mixed
     */
    private function processOptionValue($optionValue, $optionParams)
    {
        // ensure the checkbox options receive an array
        // comma is expected separator, but we'll also allow | for convenience
        if ($optionParams['type'] == 'checkbox' && !is_array($optionValue)) {
            $optionValue = explode('|', str_replace(',', '|', $optionValue));
            $optionValue = array_map('trim', $optionValue);
        } elseif (is_string($optionValue)) {
            $optionValue = trim($optionValue);
        }

        return $optionValue;
    }

    /**
     * Validate an option
     *
     * @param mixed $generator
     * @param string $option
     */
    private function validateOption($generator, $option)
    {
        $validationResult = $generator->validatePartial($this->data['options']);
        if ($validationResult->isNotValid() && $validationResult->hasErrors($option)) {
            $this->fail(implode("\n", $validationResult->getErrors($option)));
        }
    }

    /**
     * Generate templates using the generator
     *
     * @param mixed $generator
     * @param bool $showOnly
     */
    private function generateTemplates($generator, $showOnly, $codeOnly = false)
    {
        try {
            if (!$codeOnly) {
                $this->info('command_generate_templates_building_templates');
                $this->info('');
            }
            $result = $generator->generate($this->data['options'], !$showOnly);

            foreach ($result['templates'] as $templateName => $template) {
                if (!$codeOnly) {
                    $this->info($this->data['options']['template_group'] . '/' . $templateName . ': ' . $template['template_notes']);
                }

                if ($showOnly) {
                    if ($codeOnly) {
                        // Output only raw template code
                        $this->output->outln($template['template_data']);
                    } else {
                        $this->info($template['template_data']);
                    }
                }
            }
        } catch (\Exception $e) {
            // note: if the exception was triggered in embed, we might still get part of template
            // because embed is echo'ing stuff instead of returning
            $this->fail(addslashes($e->getMessage()));
        }
    }
}
