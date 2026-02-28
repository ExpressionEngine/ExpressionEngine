<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureHasChangedTest extends StructureTestBase
{
	public function testNoChangeReturnsFalse()
	{
		$node = [
			'channel_id' => 10,
			'listing_cid' => 2,
			'uri' => '/about/',
			'parent_id' => 1,
			'hidden' => 'n',
			'template_id' => 5,
		];
		$data = [
			'entry_id' => 123,
			'listing_cid' => 2,
			'uri' => '/about/',
			'parent_id' => 1,
			'hidden' => 'n',
			'template_id' => 5,
		];

		$this->assertFalse($this->structure->has_changed($node, $data));
	}

	public function testListingChannelChangedReturnsTrue()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 9, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$this->assertTrue($this->structure->has_changed($node, $data));
	}

	public function testUriChangedReturnsSelf()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 2, 'uri' => '/b', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$this->assertSame('self', $this->structure->has_changed($node, $data));
	}

	public function testParentChangedReturnsParentWhenOnlyParentChanged()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 2, 'hidden' => 'n', 'template_id' => 5 ];
		$this->assertSame('parent', $this->structure->has_changed($node, $data));
	}

	public function testMultipleChangesUltimatelyReturnTemplatePerCurrentOrder()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 9, 'uri' => '/b', 'parent_id' => 2, 'hidden' => 'y', 'template_id' => 7 ];
		$this->assertSame('template', $this->structure->has_changed($node, $data));
	}

	public function testHiddenChangedReturnsHidden()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'y', 'template_id' => 5 ];
		$this->assertSame('hidden', $this->structure->has_changed($node, $data));
	}

	public function testTemplateChangedReturnsTemplate()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 6 ];
		$this->assertSame('template', $this->structure->has_changed($node, $data));
	}

	public function testReturnsFalseWhenEntryIdZeroEvenIfFieldsDiffer()
	{
		$node = [ 'channel_id' => 10, 'listing_cid' => 2, 'uri' => '/one', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 0, 'listing_cid' => 9, 'uri' => '/two', 'parent_id' => 2, 'hidden' => 'y', 'template_id' => 6 ];
		$this->assertFalse($this->structure->has_changed($node, $data));
	}

	public function testListingChangeIgnoredWhenNodeChannelIdFalsy()
	{
		$node = [ 'channel_id' => 0, 'listing_cid' => 2, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$data = [ 'entry_id' => 1, 'listing_cid' => 9, 'uri' => '/a', 'parent_id' => 1, 'hidden' => 'n', 'template_id' => 5 ];
		$this->assertFalse($this->structure->has_changed($node, $data));
	}
}



