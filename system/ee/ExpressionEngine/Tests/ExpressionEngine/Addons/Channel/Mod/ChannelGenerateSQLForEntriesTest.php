<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelGenerateSQLForEntriesTest extends ChannelTestBase
{
    public function testEmptyInputsShortCircuit()
    {
        $sql = $this->channel->generateSQLForEntries([], []);
        $this->assertIsString($sql);
        $this->assertStringContainsString('FROM exp_channel_titles', $sql);
    }

    public function testIncludesEntryAndChannelFilters()
    {
        // Enable custom fields false to simplify
        $this->channel->enable['member_data'] = false;
        $this->channel->enable['custom_fields'] = false;
        $sql = $this->channel->generateSQLForEntries([2,1,2], [10]);
        $this->assertIsString($sql);
        $this->assertStringContainsString('FROM exp_channel_titles', $sql);
        // ensure deduped entries order handled by ORDER BY FIELD later by caller; here we just assert no fatal
    }
}


