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
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to edit site
 */
class CommandSitesEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Site';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sites:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sites:edit --site_id=<site_id> --fields="name,label,description,color,status" [--name="site_name"] [--label="Site Label"] [--description="Site Description"] [--color="Color Label"] [--status="online|offline"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site_id,i:' => 'command_sites_id',
        'fields,f:' => 'command_sites_fields',
        'name,n:' => 'command_sites_name',
        'label,l:' => 'command_sites_label',
        'description,d:' => 'command_sites_desc',
        'color,c:' => 'command_sites_color',
        'status,s:' => 'command_sites_status',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->load('sites');

        if (!bool_config_item('multiple_sites_enabled')) {
            $this->fail('command_sites_multiple_sites_disabled');
            return;
        }

        $this->data['site_id'] = $this->getMsmSiteId('first');

        $site = ee('Model')->get('Site', $this->data['site_id'])->first();
        if (!$site) {
            $this->fail(lang('command_sites_site_not_found'));
        }

        $this->info(sprintf(lang('command_sites_editing_site'), $site->site_label));

        $fields = $this->getOptionValue('fields', [
            'type' => 'checkbox',
            'desc' => 'command_sites_fields',
            'choices' => [
                'name' => lang('command_sites_name'),
                'label' => lang('command_sites_label'),
                'description' => lang('command_sites_desc'),
                'color' => lang('command_sites_color'),
                'status' => lang('command_sites_status')
            ],
            'default' => 'name, label, description, color, status',
            'required' => true
        ]);

        foreach ($fields as $field) {
            if ($field == 'status') {
                if ($this->option('--status')) {
                    $status = $this->option('--status');
                } else {
                    $site_on = ee('Model')->get('Config')
                        ->filter('site_id', $site->site_id)
                        ->filter('key', 'is_site_on')
                        ->first();
                    $currentStatus = ($site_on && $site_on->value == 'y') ? 'online' : 'offline';
                    $status = $this->askFromList(lang('command_sites_site_status') . ' [' . $currentStatus . ']', ['online' => lang('online'), 'offline' => lang('offline')], lang('command_sites_ask_status'), $currentStatus);
                }
                ee()->config->update_site_prefs(['is_site_on' => ($status == 'online') ? 'y' : 'n'], [$site->site_id]);
                continue;
            }
            $this->data['site_' . $field] = $this->getOptionOrAsk('--' . $field, lang('command_sites_ask_' . $field), $site->{'site_' . $field}, ($field == 'name' || $field == 'label'));
        }

        if (isset($this->data['site_color'])) {
            $this->data['site_color'] = ltrim($this->data['site_color'], '#');
        }

        $this->info(lang('command_sites_saving_site'));

        $site->set($this->data);
        $this->validateModel($site, lang('command_sites_site_not_saved'));

        $site->save();
        $this->info(lang('command_sites_site_saved'));
    }
}
