<?php

namespace {{namespace}};

use ExpressionEngine\Cli\Cli;

class Command{{class}} extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = '{{name}}';

    /**
     * signature of command
     * @var string
     */
    public $signature = '{{signature}}';

    /**
     * Public description of command
     * @var string
     */
    public $description = '{{description}}';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = '{{description}}';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php {{signature}}';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [

    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('Hello World!');
    }
}
