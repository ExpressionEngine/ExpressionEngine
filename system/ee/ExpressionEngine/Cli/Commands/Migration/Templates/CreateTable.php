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

class CreateTable extends AbstractTemplate
{
    protected function requiredVars()
    {
        return array('classname', 'table');
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
        \$fields = array(
            '{table}_id' => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'auto_increment' => true),
            'site_id' => array('type' => 'int', 'constraint' => '4', 'unsigned' => true, 'default' => 1),
            'data' => array('type' => 'text'),
            // 'name' => array('type' => 'varchar', 'constraint' => '50'),
            // 'yes_or_no' => array('type' => 'ENUM("Yes","No")'),
            // 'amount' => array('type' => 'double'),
            // 'last_updated' => array('type' => 'datetime', 'null' => false),
        );

        ee()->dbforge->add_field(\$fields);
        ee()->dbforge->add_key('{table}_id', true);
        ee()->dbforge->create_table('{table}');
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->dbforge->drop_table('{table}');
    }
}

TEMPLATETEXT;
    }
}
