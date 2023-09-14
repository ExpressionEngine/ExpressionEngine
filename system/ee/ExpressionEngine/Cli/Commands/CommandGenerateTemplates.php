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
        'themes,t'      => 'command_generate_templates_list_themes',
        'show,s'        => 'command_generate_templates_show_template_content'
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();

       /* ee()->load->library('api');
            ee()->legacy_api->instantiate('template_structure');
            dd(ee()->api_template_structure->all_file_extensions());*/

        // do we need to just list all possible generators?
        if ($this->option('--list', false)) {
            $this->info('command_generate_templates_listing_generators');
            $this->data['table'] = [];
            array_walk($generatorsList, function ($info, $key) {
                ee('TemplateGenerator')->setGenerator($key);
                $name = ee('TemplateGenerator')->getGenerator()->getInstance()->getName();
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
        if ($this->option('--themes', false)) {
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
        }

        if (! $this->option('--help', false)) {
            $this->info('command_generate_templates_started');
        }

        //get the generator to use
        $askText = lang('command_generate_templates_ask_generator');
        array_walk($generatorsList, function ($info, $key) use (&$askText) {
            ee('TemplateGenerator')->setGenerator($key);
            $name = ee('TemplateGenerator')->getGenerator()->getInstance()->getName();
            $askText .= "\n" . $key . ' : ' . $name;
        });
        $askText .= "\n: ";
        $generatorKey = $this->getFirstUnnamedArgument($askText, null, true);
        if (!isset($generatorsList[$generatorKey])) {
            $this->fail('command_generate_templates_invalid_generator');
        }
        try {
            ee('TemplateGenerator')->setGenerator($generatorKey);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $showOnly = $this->option('--show', false);

        // instantiate the generator
        $generator = ee('TemplateGenerator')->getGenerator()->getInstance();
        $this->data['options'] = [];

        // get the options list for the generator
        $options = ee('TemplateGenerator')->getOptions();

        // set up validator for the options
        $validator = ee('TemplateGenerator')->getValidator();

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
            // populate the choices, if dynamic
            if (isset($optionParams['callback']) && !empty($optionParams['callback'])) {
                $optionParams['choices'] = ee('TemplateGenerator')->populateOptionCallback($optionParams['callback'], $this->data['options']);
            }
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
                    $askText .= "\n" . lang('separate_choices_commas') . ":";
                } else {
                    $askText .= "\n: ";
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

            // if there is validation rule for this option, process it (e.g. template group needs to be unique)
            $validationResult = $validator->validate($this->data['options']);
            if ($validationResult->isNotValid()) {
                $this->fail(implode("\n", $validationResult->getErrors($option)));
            }

            // options are inter-dependant, set those each time
            ee('TemplateGenerator')->setOptionValues($this->data['options']);
        }

        // Generate the templates

        // get the list of templates
        $templates = $generator->getTemplates();
        if (isset($this->data['options']['templates']) && !empty($this->data['options']['templates']) && reset($this->data['options']['templates']) != 'all') {
            $templates = array_filter($templates, function ($key) {
                return in_array($key, $this->data['options']['templates']);
            }, ARRAY_FILTER_USE_KEY);
        }
        if (empty($templates)) {
            $this->fail('command_generate_templates_no_templates');
        }

        // for each of the templates, run the build process and pass it on for saving

        try {
            if (!$showOnly) {
                $group = ee('TemplateGenerator')->createTemplateGroup($this->data['options']['template_group']);
            }
            // we'll start with index templates
            if (isset($templates['index'])) {
                $indexTmpl = $templates['index'];
                unset($templates['index']);
                $templates = array_merge(['index' => $indexTmpl], $templates); // we want index to be created first
            }
            foreach ($templates as $template => $templateDescription) {
                $this->info('command_generate_templates_building_template');
                $this->info($this->data['options']['template_group'] . '/' . $template . ': ' . $templateDescription);
                $templateData = ee('TemplateGenerator')->generate($template);

                if ($showOnly) {
                    $this->info($templateData);
                }

                if (!$showOnly) {
                    // now we need to save the template
                    $templateInfo = [
                        'template_data' => $templateData,
                        'template_notes' => $templateDescription
                    ];
                    ee('TemplateGenerator')->createTemplate($group, $template, $templateInfo);
                }
            }
        } catch (\Exception $e) {
            // note: if the exception was triggered in embed, we might still get part of template
            // because embed is echo'ing stuff instead of returning
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_generate_templates_created_successfully');
    }
}