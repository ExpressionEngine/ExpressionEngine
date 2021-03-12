<?php

namespace ExpressionEngine\Cli\Commands\Migration\Templates;

use ExpressionEngine\Library\Filesystem\Filesystem;

class UpdateTable extends AbstractTemplate
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
        ee()->dbforge->add_column('{table}', array(
            'new_column' => array('type' => 'text')
        ));
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->dbforge->drop_column('{table}', 'new_column');
    }
}

TEMPLATETEXT;
    }
}
