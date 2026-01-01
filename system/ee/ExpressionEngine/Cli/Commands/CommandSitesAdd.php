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
use ExpressionEngine\Service\Model\Collection;

/**
 * Command to add a new site
 */
class CommandSitesAdd extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Add Site';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sites:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sites:add [--name="site_name"] [--label="Site Label"] [--description="Site Description"] [--color="Color Label"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'name,n:' => 'command_sites_name',
        'label,l:' => 'command_sites_label',
        'description,d:' => 'command_sites_desc',
        'color,c:' => 'command_sites_color',
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

        $this->data['site_name'] = $this->getOptionOrAsk('--name', lang('command_sites_ask_name'), '', true);
        $this->data['site_label'] = $this->getOptionOrAsk('--label', lang('command_sites_ask_label'), '', true);
        $this->data['site_description'] = $this->getOptionOrAsk('--description', lang('command_sites_ask_description'), '', false);
        $this->data['site_color'] = $this->getOptionOrAsk('--color', lang('command_sites_ask_color'), '', false);

        $this->data['site_bootstrap_checksums'] = [];
        $this->data['site_pages'] = [];

        if (!empty($this->data['site_color'])) {
            $this->data['custom_site_color'] = 'y';
            $this->data['site_color'] = ltrim($this->data['site_color'], '#');
        }

        $this->info(lang('command_sites_adding_site'));

        $site = ee('Model')->make('Site', $this->data);
        $validation = $site->validate();
        if ($validation->failed()) {
            foreach ($validation->getAllErrors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }
            $this->fail(lang('command_sites_site_not_added'));
        }

        $site->save();
        $this->complete(lang('command_sites_site_added'));
    }
}
