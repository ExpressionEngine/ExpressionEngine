<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureCreatePageUriTest extends StructureTestBase
{
	public function testCreatePageUriJoinsParentAndChildWithSingleSlashes()
	{
		$uri = $this->structure->create_page_uri('/parent/', 'child');
		$this->assertSame('/parent/child', $uri);
	}

	public function testCreatePageUriBoundaryProducesDoubleSlashWithLeadingChild()
	{
		// After fix: '/parent/' + '/child' results in '/parent/child'
		$uri = $this->structure->create_page_uri('/parent/', '/child');
		$this->assertSame('/parent/child', $uri);
	}

	public function testCreatePageUriHandlesEmptyChild()
	{
		$uri = $this->structure->create_page_uri('/parent', '');
		$this->assertSame('/parent', $uri);
	}

	public function testCreatePageUriHandlesEmptyParent()
	{
		$uri = $this->structure->create_page_uri('', 'child');
		$this->assertSame('/child', $uri);
	}

	public function testCreatePageUriNormalizesParentWithoutLeadingSlash()
	{
		$uri = $this->structure->create_page_uri('parent', 'child');
		$this->assertSame('/parent/child', $uri);
	}

	public function testCreatePageUriCollapsesInternalDoubleSlashesInParent()
	{
		$uri = $this->structure->create_page_uri('/a//b', 'c');
		$this->assertSame('/a/b/c', $uri);
	}

	public function testCreatePageUriPreservesQueryAndFragmentInChild()
	{
		$uri = $this->structure->create_page_uri('/parent', 'child?x=1#frag');
		$this->assertSame('/parent/child?x=1#frag', $uri);
	}

	public function testCreatePageUriBothEmptyReturnsRoot()
	{
		$uri = $this->structure->create_page_uri('', '');
		$this->assertSame('/', $uri);
	}

	public function testCreatePageUriRootParentAndVariousChildForms()
	{
		$this->assertSame('/child', $this->structure->create_page_uri('/', 'child'));
		$this->assertSame('/child', $this->structure->create_page_uri('/', '/child'));
	}

	public function testCreatePageUriCollapsesDoubleSlashesInChild()
	{
		$uri = $this->structure->create_page_uri('/x', 'a//b');
		$this->assertSame('/x/a/b', $uri);
	}

	public function testCreatePageUriPreservesNonAsciiChild()
	{
		$uri = $this->structure->create_page_uri('/parent', 'ümlaut');
		$this->assertSame('/parent/ümlaut', $uri);
	}
}


