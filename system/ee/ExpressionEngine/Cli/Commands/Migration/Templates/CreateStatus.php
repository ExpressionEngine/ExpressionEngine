<?php

namespace ExpressionEngine\Cli\Commands\Migration\Templates;

use ExpressionEngine\Library\Filesystem\Filesystem;

class CreateStatus extends AbstractTemplate
{
    protected function requiredVars()
    {
        return ['classname'];
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
        \$status = ee('Model')->make('Status');
        \$status->status = '{status}';
        // \$status->highlight = '000000';
        \$status->save();
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        \$status = ee('Model')->get('Status')->filter('status', '{status}')->first();
        \$status->delete();
    }
}


TEMPLATETEXT;
    }
}
