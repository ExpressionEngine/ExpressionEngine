<?php

namespace Cypress\CliUpdate;

use ExpressionEngine\Cli\Cli;

class CommandDeleteField extends Cli
{
    public $name = 'Delete a Cypress test field';
    public $signature = 'cypress:delete-field';
    public $commandOptions = [
        'field-id:' => 'Field ID',
    ];

    public function handle()
    {
        ee('Model')->get('ChannelField', $this->option('--field-id'))->first()->delete();
        $this->info('Field deleted');
    }
}
