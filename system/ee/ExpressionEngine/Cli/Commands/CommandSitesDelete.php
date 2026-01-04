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
 * Command to delete a site
 */
class CommandSitesDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Site';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sites:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sites:delete --site_id=<site_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site_id,i:' => 'command_sites_id',
        'force,f' => 'command_sites_force_delete',
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

        $sites = ee('Model')->get('Site')->all()->getDictionary('site_id', 'site_label');

        if (count($sites) <= 1) {
            $this->fail('command_sites_no_sites');
            return;
        }

        $this->data['site_id'] = $this->getOptionValue('site_id', [
            'type' => 'select',
            'desc' => 'command_sites_delete_ask',
            'choices' => $sites,
            'default' => null,
            'required' => true
        ]);

        if (empty($this->data['site_id'])) {
            $this->fail(lang('command_sites_site_not_found'));
        }

        $site = ee('Model')->get('Site', $this->data['site_id'])->first();
        if (!$site) {
            $this->fail(lang('command_sites_site_not_found'));
        }

        if ($site->site_id == 1) {
            $this->fail(sprintf(lang('cannot_remove_site_1'), $site->site_label));
        }

        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_sites_delete_confirm'), $site->site_label), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_sites_site_not_deleted'));
        }

        $this->info(lang('command_sites_deleting_site'));

        $site->delete();

        $this->complete(lang('command_sites_site_deleted'));
    }
}
