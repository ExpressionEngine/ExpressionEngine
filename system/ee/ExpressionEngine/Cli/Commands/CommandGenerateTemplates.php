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

        $this->info('command_generate_templates_started');

        //get the generator to use
        $generatorKey = $this->getFirstUnnamedArgument("command_generate_templates_ask_generator", null, true);
        if (!isset($generatorsList[$generatorKey])) {
            $this->fail('command_generate_templates_invalid_generator');
        }
        try {
            ee('TemplateGenerator')->setGenerator($generatorKey);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

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

        foreach ($options as $option => $optionParams) {
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
            $askText = isset($optionParams['desc']) ? $optionParams['desc'] : $option;
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
                $optionValue = explode(',', str_replace(',', '|', $optionValue));
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
        }

        // Now that we have all options, ask to confirm those
        ee('TemplateGenerator')->setOptionValues($this->data['options']);

        // set the theme engine
        if (isset($this->data['options']['theme']) && !empty($this->data['options']['theme']) && $this->data['options']['theme'] != 'none') {
            $theme = $this->data['options']['theme'];
            $themeRegistry = ee('TemplateGenerator')->registerThemes();
            if (isset($themeRegistry[$theme]['engine'])) {
                ee('TemplateGenerator')->setTemplateEngine($themeRegistry[$theme]['engine']);
            }
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

        $this->info('command_generate_templates_building_action');

        $showOnly = $this->option('--show', false);

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
                $this->info($this->data['options']['template_group'] . '/' . $template . (isset($templateDescription['notes']) ? ': ' . $templateDescription['notes'] : ''));
                $templateData = ee('TemplateGenerator')->generate($template);

                if ($showOnly) {
                    echo $templateData;
                }

                if (!$showOnly) {
                    // now we need to save the template
                    ee('TemplateGenerator')->createTemplate($group, $template, $templateData);
                }
            }
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_generate_templates_created_successfully');
    }
}