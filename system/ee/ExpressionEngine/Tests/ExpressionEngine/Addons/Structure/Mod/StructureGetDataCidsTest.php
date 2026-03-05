<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetDataCidsTest extends StructureTestBase
{
	public function testReturnsChannelIdsByEntryWhenNotListings()
	{
		ee()->db->setRows([
			['entry_id' => 10, 'channel_id' => 3],
			['entry_id' => 20, 'channel_id' => 0], // should be ignored
			['entry_id' => 30, 'channel_id' => 7],
		]);
		$result = $this->structure->get_data_cids(false);
		$this->assertSame(['10' => 3, '30' => 7], array_map('intval', $result));
	}

	public function testReturnsListingCidsWhenListingsTrue()
	{
		ee()->db->setRows([
			['entry_id' => 1, 'listing_cid' => 9],
			['entry_id' => 2, 'listing_cid' => 0],
			['entry_id' => 3, 'listing_cid' => 4],
		]);
		$result = $this->structure->get_data_cids(true);
		$this->assertSame(['1' => 9, '3' => 4], array_map('intval', $result));
	}

	public function testReturnsEmptyArrayWhenNoRows()
	{
		ee()->db->setRows([]);
		$this->assertSame([], $this->structure->get_data_cids(false));
		$this->assertSame([], $this->structure->get_data_cids(true));
	}
}



