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
 * Command to update config values
 */
class CommandBackupDatabase extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Backup Database';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'backup:database';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php backup:database';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
    }
}
