<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetChannelTypeTest extends StructureTestBase
{
	public function testReturnsListingWhenChannelIdIsInListingCids()
	{
		ee()->setMock('input', new class {
			public function get_post($key) { return 77; }
		});

		// get_data_cids(true) queries exp_structure and returns [entry_id => listing_cid]
		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
			['entry_id' => 200, 'listing_cid' => 0],
		]);

		$type = $this->structure->get_channel_type();
		$this->assertSame('listing', $type);
	}

	public function testReturnsStaticWhenChannelIdNotInListingCids()
	{
		ee()->setMock('input', new class {
			public function get_post($key) { return 88; }
		});

		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
			['entry_id' => 200, 'listing_cid' => 0],
		]);

		$type = $this->structure->get_channel_type();
		$this->assertSame('static', $type);
	}

	public function testReturnsStaticWhenNoPostedChannelId()
	{
		ee()->setMock('input', new class {
			public function get_post($key) { return null; }
		});

		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
		]);

		$type = $this->structure->get_channel_type();
		$this->assertSame('static', $type);
	}

	public function testCachesChannelTypeAcrossCalls()
	{
		// First call sets to listing
		ee()->setMock('input', new class {
			public function get_post($key) { return 77; }
		});
		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
		]);
		$this->assertSame('listing', $this->structure->get_channel_type());

		// Change posted id; value should remain cached
		ee()->setMock('input', new class {
			public function get_post($key) { return 0; }
		});
		$this->setDbRows([]);
		$this->assertSame('listing', $this->structure->get_channel_type());
	}

	public function testStringChannelIdMatchesNumericListingCid()
	{
		ee()->setMock('input', new class {
			public function get_post($key) { return '77'; }
		});

		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
		]);

		$type = $this->structure->get_channel_type();
		$this->assertSame('listing', $type);
	}

	public function testPresetChannelTypeSkipsDbCheck()
	{
		// Simulate cached value
		$ref = new ReflectionProperty($this->structure, 'channel_type');
		\TestReflectionHelper::makePropertyAccessible($ref);
		$ref->setValue($this->structure, 'listing');

		ee()->setMock('input', new class {
			public function get_post($key) { return 0; }
		});
		$this->setDbRows([]);

		$this->assertSame('listing', $this->structure->get_channel_type());
	}

	public function testZeroOrNonNumericPostedChannelIdReturnsStatic()
	{
		ee()->setMock('input', new class {
			public function get_post($key) { return '0'; }
		});
		$this->setDbRows([
			['entry_id' => 100, 'listing_cid' => 77],
		]);
		$this->assertSame('static', $this->structure->get_channel_type());

		ee()->setMock('input', new class {
			public function get_post($key) { return 'abc'; }
		});
		$this->assertSame('static', $this->structure->get_channel_type());
	}
}


