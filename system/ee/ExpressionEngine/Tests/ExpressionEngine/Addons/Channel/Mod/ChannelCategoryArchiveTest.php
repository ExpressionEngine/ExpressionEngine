<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategoryArchiveTest extends ChannelTestBase
{
    public function testCategoryArchiveReturnsNoResultsWhenNoGroups()
    {
        $this->setTemplateParams([]);
        $this->setDbRows([]);
        $result = $this->channel->category_archive();
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testCategoryArchiveSortAndStickyFlagsDoNotError()
    {
        $this->setTemplateParams([
            'sticky' => 'only',
            'orderby' => 'date',
            'sort' => 'desc'
        ]);
        $this->setDbRows([]);
        $result = $this->channel->category_archive();
        $this->assertIsString($result);
    }
}


