<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureRemoveLastSegmentTest extends StructureTestBase
{
	public function testRemoveLastSegmentMiddlePath()
	{
		$this->assertSame('/foo/', $this->structure->remove_last_segment('/foo/bar/'));
	}

	public function testRemoveLastSegmentRoot()
	{
		$this->assertSame('//', $this->structure->remove_last_segment('/'));
	}

	public function testRemoveLastSegmentTrailingSlashless()
	{
		$this->assertSame('//', $this->structure->remove_last_segment('/foo'));
	}

	public function testRemoveLastSegmentEmptyString()
	{
		$this->assertSame('//', $this->structure->remove_last_segment(''));
	}

	public function testRemoveLastSegmentMultipleSlashes()
	{
		$this->assertSame('/foo//', $this->structure->remove_last_segment('/foo//bar/'));
	}
}
