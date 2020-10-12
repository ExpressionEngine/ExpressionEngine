<?php

namespace ExpressionEngine\Cli\Commands;

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
    public $usage = 'php eecli {{signature}}';

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

        // Make magic, my friend

    }

}
