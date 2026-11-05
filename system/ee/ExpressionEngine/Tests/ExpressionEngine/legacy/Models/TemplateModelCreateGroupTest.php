<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'models/template_model.php';

class TemplateModelCreateGroupTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testNewGroupGrantsItsCreatorRoleAccess(): void
    {
        $db = new class {
            public $inserts = [];

            public function count_all($table) { return 0; }
            public function insert($table, $data) { $this->inserts[$table] = $data; }
            public function insert_id() { return 42; }
        };

        ee()->setMock('db', $db);
        ee()->setMock('Permission', new class {
            public function isSuperAdmin() { return false; }
        });
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                return ['member_id' => 7, 'group_id' => 9][$key];
            }
        });

        $groupId = (new Template_model())->create_group([
            'group_name' => 'new_group',
            'is_site_default' => 'n',
            'site_id' => 1,
        ]);

        $this->assertSame(42, $groupId);
        $this->assertSame(
            ['role_id' => 9, 'template_group_id' => 42],
            $db->inserts['template_groups_roles']
        );
    }
}
