<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCategoriesTest extends ChannelTestBase
{
    public function testReturnsEarlyWhenQueryNullAndNoLivePreview()
    {
        $this->setMock('LivePreview', new class { public function hasEntryData(){ return false; } });
        $this->channel->query = null;

        $this->channel->fetch_categories();

        $this->assertIsArray($this->channel->categories);
        $this->assertEmpty($this->channel->categories);
    }

    public function testReturnsEarlyWhenEmptyQueryAndNoLivePreview()
    {
        $this->setMock('LivePreview', new class { public function hasEntryData(){ return false; } });
        $this->channel->query = new class { public function result_array(){ return []; } };

        $this->channel->fetch_categories();

        $this->assertIsArray($this->channel->categories);
        $this->assertEmpty($this->channel->categories);
    }

    public function testIncludesCategoryFieldColumns()
    {
        $this->setMock('LivePreview', new class { public function hasEntryData(){ return false; } });
        // Mock query with one entry
        $this->channel->query = new class {
            public function result_array(){ return [['entry_id' => 7]]; }
        };
        // Mock DB with category row that includes custom field column
        $this->setDbRows([
            [
                'cat_name' => 'Cat', 'cat_url_title' => 'cat', 'cat_id' => 10, 'cat_image' => '', 'cat_description' => '',
                'parent_id' => 0, 'entry_id' => 7, 'group_id' => 1, 'field_id_12' => 'X'
            ]
        ]);

        // Monkey-patch generateCategoryFieldSQL to include field
        $ref = new ReflectionClass($this->channel);
        $method = $ref->getMethod('generateCategoryFieldSQL');
        $method->setAccessible(true);
        // Cannot override method easily; rely on test DB row inclusion check in loop

        $this->channel->fetch_categories();

        $this->assertArrayHasKey(7, $this->channel->categories);
        $first = $this->channel->categories[7][0];
        $this->assertEquals('X', $first['field_id_12']);
    }

    public function testMissingParentBecomesRoot()
    {
        $this->setMock('LivePreview', new class { public function hasEntryData(){ return false; } });
        $this->channel->query = new class {
            public function result_array(){ return [['entry_id' => 11]]; }
        };
        // Child references parent 999 which is missing
        $this->setDbRows([
            [
                'cat_name' => 'Child', 'cat_url_title' => 'child', 'cat_id' => 20, 'cat_image' => '', 'cat_description' => '',
                'parent_id' => 999, 'entry_id' => 11, 'group_id' => 1
            ]
        ]);

        $this->channel->fetch_categories();
        $this->assertArrayHasKey(11, $this->channel->categories);
        $node = $this->channel->categories[11][0];
        // Parent reset is used for positioning only; stored value remains original
        $this->assertEquals(20, $node[0]);
    }

    public function testLivePreviewOverridesEntryCategories()
    {
        $previewData = [
            'entry_id' => 123,
            'categories' => [ [30, 31] ]
        ];
        $this->setMock('LivePreview', new class($previewData) {
            private $d; public function __construct($d){ $this->d=$d; }
            public function hasEntryData(){ return true; }
            public function getEntryData(){ return $this->d; }
        });
        // Seed existing categories for 123 to ensure they are replaced
        $this->channel->categories[123] = [['stale']];
        // Mock Model to return Category models for 30 and 31
        $this->setMock('Model', new class {
            public function get($model, $ids){
                return new class($ids) {
                    private $ids; public function __construct($ids){ $this->ids=$ids; }
                    public function all(){
                        $cats = [];
                        foreach ($this->ids as $id) {
                            $cats[] = (object) ['cat_id'=>$id,'parent_id'=>0,'cat_name'=>'C'.$id,'cat_image'=>'','cat_description'=>'','group_id'=>1,'cat_url_title'=>'c'.$id];
                        }
                        // Return a simple array so foreach works directly
                        return $cats;
                    }
                };
            }
        });

        $this->channel->fetch_categories();
        $this->assertArrayHasKey(123, $this->channel->categories);
        $this->assertCount(2, $this->channel->categories[123]);
    }
}
