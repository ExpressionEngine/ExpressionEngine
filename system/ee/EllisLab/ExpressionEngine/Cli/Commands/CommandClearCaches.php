<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;

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
    public $signature = 'cache-clear';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Clears all EE caches';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary;

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli cache:clear --type=tag | php eecli cache:clear --t tag';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'type,t:'   => 'Type of cache to clear (default: all)',
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
            $this->fail('Cache does not exist. Use --help to see available caches.');
        }

        ee()->functions->clear_caching($type);

        $this->info(ucfirst($type) . ' caches are cleared!');
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
