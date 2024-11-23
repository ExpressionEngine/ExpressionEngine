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
    public $usage = 'php eecli.php generate:templates [generator] [--options]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'list,l'        => 'command_generate_templates_list_generators',
        /* 'themes,t'      => 'command_generate_templates_list_themes', */
        'show,s'        => 'command_generate_templates_show_template_content'
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Get all the template generators
        $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();

        // do we need to just list all possible generators?
        if ($this->option('--list', false)) {
            $this->info('command_generate_templates_listing_generators');
            $this->data['table'] = [];
            array_walk($generatorsList, function ($generator, $key) {
                $name = $generator->getName();
                if (!empty($name)) {
                    $this->data['table'][] = [$key, $name];
                }
            });
            $this->table([
                lang('name'),
                lang('description'),
            ], $this->data['table']);
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

        if (! $this->option('--help', false)) {
            $this->info('generate_templates_started');
        }

        // Get the generator from the first argument
        $generatorKey = $this->getFirstUnnamedArgument();

        // If its not a valid generator, ask the user to select one
        if (!$generatorKey || !isset($generatorsList[$generatorKey])) {
            $askText = lang('command_generate_templates_ask_generator');

            $genList = [];
            array_walk($generatorsList, function ($generator, $key) use (&$genList) {
                $name = $generator->getName();
                $genList[$key] = $name;
            });

            $generatorKey = $this->askFromList($askText, $genList, null);
        }

        // Check to see if the generator is valid
        if (!isset($generatorsList[$generatorKey])) {
            $this->fail('command_generate_templates_invalid_generator');
        }

        // instantiate the generator
        try {
            $generator = ee('TemplateGenerator')->make($generatorKey);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $showOnly = $this->option('--show', false);

        // If the generator is disabled for the CP, we will only show the templates rather than trying to generate them as files
        if($generator->generatorDisabledForLocation('CP')) {
            $showOnly = true;
        }

        $this->data['options'] = [];

        // get the options list for the generator
        $options = $generator->getOptions();

        // for each of the options, we need to get name, type, whether is required and options
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

        if ($this->option('--help', false)) {
            return $this->help();
        }

        foreach ($options as $option => $optionParams) {
            if ($showOnly && $option == 'template_group') {
                $this->data['options']['template_group'] = '';
                continue; // need not to ask if we just show template on screen
            }
            $default = isset($optionParams['default']) ? $optionParams['default'] : '';
            $required = isset($optionParams['required']) ? $optionParams['required'] : false;
            if (
                in_array($optionParams['type'], ['radio', 'select']) &&
                (
                    !isset($optionParams['choices']) || //no choice
                    empty($optionParams['choices']) || // choice is empty
                    (count($optionParams['choices']) == 1 && array_key_first($optionParams['choices']) == $default) // there just 1 choice, which is default
                )
            ) {
                continue; // do not ask if we have no choice
            }
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
            $optionValue = $this->getOptionOrAsk(
                '--' . $option,
                $askText,
                $default,
                $required
            );
            // ensure the checkbox options receive an array
            // comma is expected separator, but we'll also allow | for convenience
            if ($optionParams['type'] == 'checkbox' && !is_array($optionValue)) {
                $optionValue = explode('|', str_replace(',', '|', $optionValue));
                $optionValue = array_map('trim', $optionValue);
            } elseif (is_string($optionValue)) {
                $optionValue = trim($optionValue);
            }
            $this->data['options'][$option] = $optionValue;

            // if there is a validation rule for this option, process it (e.g. template group needs to be unique)
            $validationResult = $generator->validatePartial($this->data['options']);
            if ($validationResult->isNotValid() && $validationResult->hasErrors($option)) {
                $this->fail(implode("\n", $validationResult->getErrors($option)));
            }
        }

        try {
            $this->info('command_generate_templates_building_templates');
            $this->info('');
            $result = $generator->generate($this->data['options'], !$showOnly);

            foreach ($result['templates'] as $templateName => $template) {
                $this->info($this->data['options']['template_group'] . '/' . $templateName . ': ' . $template['template_notes']);

                if ($showOnly) {
                    $this->info($template['template_data']);
                }
            }
        } catch (\Exception $e) {
            // note: if the exception was triggered in embed, we might still get part of template
            // because embed is echo'ing stuff instead of returning
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('');
        $this->info('generate_templates_created_successfully');
    }
}
