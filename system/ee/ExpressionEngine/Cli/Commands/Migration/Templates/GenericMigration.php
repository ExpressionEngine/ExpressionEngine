<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands\Migration\Templates;

use ExpressionEngine\Library\Filesystem\Filesystem;

class GenericMigration extends AbstractTemplate
{
    protected function requiredVars()
    {
        return array('classname');
    }

    protected function getTemplateText()
    {
        return <<<TEMPLATETEXT
<?php

use ExpressionEngine\Service\Migration\Migration;

class {classname} extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        // Write migration code here
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        // Write migration rollback code here
    }
}

TEMPLATETEXT;
    }
}
