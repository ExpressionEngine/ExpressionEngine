<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureCreateFullUriTest extends StructureTestBase
{
    public function testCreateFullUriTrimsAndJoinsWithLeadingSlash()
    {
        $uri = $this->structure->create_full_uri('/parent/', 'child');
        $this->assertSame('/parent/child', $uri);
    }

    public function testCreateFullUriNormalizesDoubleSlashes()
    {
        $uri = $this->structure->create_full_uri('/parent/', '/child');
        $this->assertSame('/parent/child', $uri);
    }

    public function testCreateFullUriHandlesEmptyParent()
    {
        $uri = $this->structure->create_full_uri('', 'child');
        $this->assertSame('/child', $uri);
    }

    public function testCreateFullUriHandlesEmptyListingSlug()
    {
        $uri = $this->structure->create_full_uri('/parent', '');
        $this->assertSame('/parent', $uri);
    }

    public function testCreateFullUriSanitizesListingSlugBeforeJoining()
    {
        $uri = $this->structure->create_full_uri('/parent', '__Hello World!__');
        $this->assertSame('/parent/HelloWorld', $uri);
    }

    public function testCreateFullUriReturnsRootWhenParentAndSanitizedListingAreEmpty()
    {
        $uri = $this->structure->create_full_uri('', '@#$%^&*()');
        $this->assertSame('/', $uri);
    }
}


