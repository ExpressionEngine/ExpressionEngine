<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelGetExtraDataTest extends ChannelTestBase
{
    public function testMergesChunkDataIntoQueryResult()
    {
        // Prepare one chunk with a mock field that yields table and columns
        $field = new class {
            public function getId(){ return 12; }
            public function getColumnNames(){ return ['field_id_12','field_ft_12']; }
        };
        $refObj = new ReflectionObject($this->channel);
        $propChunks = $refObj->getProperty('chunks');
        $propChunks->setAccessible(true);
        $propChunks->setValue($this->channel, [[ $field ]]);
        $propEntryIds = $refObj->getProperty('entry_ids');
        $propEntryIds->setAccessible(true);
        $propEntryIds->setValue($this->channel, [7]);

        // DB returns extra columns for entry_id=7
        $this->setDbRows([['entry_id'=>7,'field_id_12'=>'X','field_ft_12'=>'none']]);

        $ref = new ReflectionClass($this->channel);
        $m = $ref->getMethod('getExtraData');
        $m->setAccessible(true);

        $original = [['entry_id'=>7, 'title'=>'T']];
        $merged = $m->invoke($this->channel, $original);

        $this->assertSame('X', $merged[0]['field_id_12']);
        $this->assertSame('none', $merged[0]['field_ft_12']);
    }
}


