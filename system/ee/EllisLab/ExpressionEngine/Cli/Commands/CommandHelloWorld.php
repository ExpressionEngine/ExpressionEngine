<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;

class CommandHelloWorld extends Cli
{

    /**
     * name of command
     * @var string
     */
    public $name = 'Hello World';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'hello';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'The most basic of commands';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This is a sample command used to test the CLI';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli hello';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'verbose,v'     => 'Hello world, but longer',
        'interactive,i' => 'Let\'s interact!',
        'confirm,c'     => 'Test the confirmation',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $verbose = $this->option('-v', false);

        $interactive = $this->option('-i', false);

        $confirm = $this->option('-c', false);

        $this->info('Hello world');

        if ($interactive) {
            $name = $this->ask('What\'s your name?');

            $name
                ? $this->info("Pleasure to meet you, {$name}!")
                : $this->info("I mean, you don't have to tell me, I suppose. 😭");
        }

        if ($confirm) {
            $answer = $this->confirm("Are you liking these questions?");

            $answer
                ? $this->info("That's good to hear!")
                : $this->info("Well, that's no good.");
        }

        if ($verbose) {
            $this->info('Happy ' . date('l') . '!');
        }
    }
}
