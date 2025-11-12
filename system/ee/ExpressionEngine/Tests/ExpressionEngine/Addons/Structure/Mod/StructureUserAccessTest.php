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
}



