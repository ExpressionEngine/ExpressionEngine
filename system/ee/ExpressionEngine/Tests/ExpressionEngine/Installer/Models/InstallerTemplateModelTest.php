<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Models;

use PHPUnit\Framework\TestCase;

class InstallerTemplateModelTest extends TestCase
{
    private static $stubRoot;

    public static function setUpBeforeClass(): void
    {
        self::$stubRoot = sys_get_temp_dir() . '/installer-template-model-stub-' . uniqid('', true);
        $modelsDir = self::$stubRoot . '/models';

        if (! is_dir($modelsDir)) {
            mkdir($modelsDir, 0777, true);
        }

        $stub = <<<'PHP'
<?php
class Template_Entity {}
class Template_model
{
    public $saved = [];
    public function save_to_database(Template_Entity $entity)
    {
        $this->saved[] = $entity;
        return 'saved';
    }
}
PHP;

        file_put_contents($modelsDir . '/template_model.php', $stub);

        if (! defined('EE_APPPATH')) {
            define('EE_APPPATH', self::$stubRoot . '/');
        }

        require_once SYSPATH . 'ee/installer/models/installer_template_model.php';
    }

    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testSaveToDatabaseAddsProtectJavascriptColumnAndDelegatesToParent()
    {
        $smartforge = new class {
            public $addColumnCalls = [];

            public function add_column($table, $columns)
            {
                $this->addColumnCalls[] = [$table, $columns];
            }
        };
        ee()->setMock('smartforge', $smartforge);

        $model = new \Installer_template_model();
        $entity = new \Template_Entity();

        $result = $model->save_to_database($entity);

        $this->assertSame('saved', $result);
        $this->assertCount(1, $smartforge->addColumnCalls);
        $this->assertSame('templates', $smartforge->addColumnCalls[0][0]);
        $this->assertSame(
            [
                'protect_javascript' => [
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n',
                ],
            ],
            $smartforge->addColumnCalls[0][1]
        );
        $this->assertSame([$entity], $model->saved);
    }
}
