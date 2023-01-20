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
 * Command to clear selected caches
 */
class CommandClearCaches extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Clear Cache';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cache:clear';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cache:clear --type=tag | php eecli.php cache:clear -t tag';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'type,t:' => 'command_cache_clear_option_type',
    ];

    /**
     * list of available caches
     * @var array
     */
    private $availableCaches = [
        'all',
        'page',
        'tag',
        'db',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->summary = $this->getSummaryText();
    }

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('-t', 'all');

        if (! in_array($type, $this->availableCaches)) {
            $this->fail('command_cache_clear_cache_does_not_exist');
        }

        ee()->load->driver('cache');
        ee()->load->library('functions');

        ee()->functions->clear_caching($type);

        $this->info(ucfirst($type) . lang('command_cache_clear_caches_cleared'));
    }

    /**
     * Get summary text for help function
     * @return string
     */
    private function getSummaryText()
    {
        return <<<HELPTEXT
This allows for EE caches to be cleared.

    Available options:
    'all': Clear all caches
    'page': Clear template caches
    'tag': Clear tag caches
    'db': Clear database caches
HELPTEXT;
    }
}
