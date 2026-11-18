<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureUserAccessTest extends StructureTestBase
{
	public function testSuperAdminAlwaysTrue()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 1]]);
		$this->assertTrue($this->structure->user_access('perm_something'));
	}

	public function testChecksSettingsArrayWhenProvided()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 5]]);
		$settings = [ 'perm_admin_structure_5' => 'y' ];
		$this->assertTrue($this->structure->user_access('perm_delete', $settings));
	}

	public function testFalseWhenNoPermInSettings()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 6]]);
		$settings = ['foo' => 'bar']; // non-empty, but no perms
		$this->assertFalse($this->structure->user_access('perm_delete', $settings));
	}

	public function testDbLookupWhenNoSettingsProvided()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 7]]);
		// Simulate AR builder that incorrectly expects num_rows() directly on builder
		ee()->setMock('db', new class extends eeDbArMock {
			public function select($f = null){ return $this; }
			public function from($t = null){ return $this; }
			public function where($f=null,$v=null){ return $this; }
			public function or_where($f=null,$v=null){ return $this; }
			public function num_rows(){ return 1; }
		});
		$this->assertTrue($this->structure->user_access('perm_delete'));
	}

	public function testDbLookupWhenNoSettingsProvidedAndNoRows()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 8]]);
		ee()->setMock('db', new class extends eeDbArMock {
			public function select($f = null){ return $this; }
			public function from($t = null){ return $this; }
			public function where($f=null,$v=null){ return $this; }
			public function or_where($f=null,$v=null){ return $this; }
			public function num_rows(){ return 0; }
		});
		$this->assertFalse($this->structure->user_access('perm_delete'));
	}

	public function testSpecificPermInSettingsGrantsAccess()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 9]]);
		$settings = [ 'perm_delete_9' => 'y' ];
		$this->assertTrue($this->structure->user_access('perm_delete', $settings));
	}

	public function testAdminPermissionKeyPresenceGrantsAccessEvenWhenDisabled()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 10]]);
		$settings = [ 'perm_admin_structure_10' => 'n' ];

		$this->assertTrue($this->structure->user_access('perm_delete', $settings));
	}

	public function testSuperAdminSkipsDatabaseLookup()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 1]]);
		ee()->setMock('db', new class extends eeDbArMock {
			public function select($field = null)
			{
				throw new RuntimeException('Super admins should return before database lookup.');
			}
		});

		$this->assertTrue($this->structure->user_access('perm_delete'));
	}

	public function testProvidedSettingsSkipDatabaseLookup()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 11]]);
		ee()->setMock('db', new class extends eeDbArMock {
			public function select($field = null)
			{
				throw new RuntimeException('Database lookup should be skipped when settings are provided.');
			}
		});

		$this->assertFalse($this->structure->user_access('perm_delete', ['foo' => 'bar']));
	}

	public function testDbLookupUsesExpectedPermissionKeys()
	{
		ee()->setMock('session', (object) ['userdata' => ['group_id' => 12]]);

		$db = new class extends eeDbArMock {
			public $selected = [];
			public $fromTable;
			public $whereCalls = [];
			public $orWhereCalls = [];

			public function select($field = null)
			{
				$this->selected[] = $field;

				return $this;
			}

			public function from($table = null)
			{
				$this->fromTable = $table;

				return $this;
			}

			public function where($field = null, $value = null)
			{
				$this->whereCalls[] = [$field, $value];

				return $this;
			}

			public function or_where($field = null, $value = null)
			{
				$this->orWhereCalls[] = [$field, $value];

				return $this;
			}

			public function num_rows()
			{
				return 1;
			}
		};

		ee()->setMock('db', $db);

		$this->assertTrue($this->structure->user_access('perm_delete'));
		$this->assertSame(['var'], $db->selected);
		$this->assertSame('structure_settings', $db->fromTable);
		$this->assertSame([['var', 'perm_admin_structure_12']], $db->whereCalls);
		$this->assertSame([['var', 'perm_delete_12']], $db->orWhereCalls);
	}
}

