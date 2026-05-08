<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategoryHeadingAndNameTest extends ChannelTestBase
{
    /**
     * Category URL title matches use the first category when groups share slugs.
     *
     * @return void
     */
    public function testCategoryHeadingUsesFirstMatchingCategoryUrlTitle()
    {
        $this->setCategoryHeadingRenderingMocks();
        $this->setTemplateParams([
            'channel' => 'products',
            'category_url_title' => 'shared-category',
        ]);
        $this->setTemplateTagdata('{category_id}:{category_name}');
        $this->setCategoryHeadingRows(
            [
                ['channel_id' => 1, 'group_id' => 2],
                ['channel_id' => 1, 'group_id' => 3],
            ],
            [
                ['cat_id' => 12, 'group_id' => 2, 'parent_id' => 0, 'cat_name' => 'First Shared', 'cat_url_title' => 'shared-category'],
                ['cat_id' => 15, 'group_id' => 3, 'parent_id' => 0, 'cat_name' => 'Second Shared', 'cat_url_title' => 'shared-category'],
            ]
        );

        $out = $this->channel->category_heading();

        $this->assertEquals('12:First Shared', $out);
    }

    /**
     * The category_group parameter narrows duplicate category URL title matches.
     *
     * @return void
     */
    public function testCategoryHeadingCategoryGroupSelectsLaterDuplicate()
    {
        $this->setCategoryHeadingRenderingMocks();
        $this->setTemplateParams([
            'channel' => 'products',
            'category_url_title' => 'shared-category',
            'category_group' => '3',
        ]);
        $this->setTemplateTagdata('{category_id}:{category_name}');
        $this->setCategoryHeadingRows(
            [
                ['channel_id' => 1, 'group_id' => 2],
                ['channel_id' => 1, 'group_id' => 3],
            ],
            [
                ['cat_id' => 12, 'group_id' => 2, 'parent_id' => 0, 'cat_name' => 'First Shared', 'cat_url_title' => 'shared-category'],
                ['cat_id' => 15, 'group_id' => 3, 'parent_id' => 0, 'cat_name' => 'Second Shared', 'cat_url_title' => 'shared-category'],
            ]
        );

        $out = $this->channel->category_heading();

        $this->assertEquals('15:Second Shared', $out);
    }

    /**
     * The category_group parameter supports excluding category groups.
     *
     * @return void
     */
    public function testCategoryHeadingCategoryGroupCanExcludeMatches()
    {
        $this->setCategoryHeadingRenderingMocks();
        $this->setTemplateParams([
            'channel' => 'products',
            'category_url_title' => 'shared-category',
            'category_group' => 'not 2',
        ]);
        $this->setTemplateTagdata('{category_id}:{category_name}');
        $this->setCategoryHeadingRows(
            [
                ['channel_id' => 1, 'group_id' => 2],
                ['channel_id' => 1, 'group_id' => 3],
            ],
            [
                ['cat_id' => 12, 'group_id' => 2, 'parent_id' => 0, 'cat_name' => 'First Shared', 'cat_url_title' => 'shared-category'],
                ['cat_id' => 15, 'group_id' => 3, 'parent_id' => 0, 'cat_name' => 'Second Shared', 'cat_url_title' => 'shared-category'],
            ]
        );

        $out = $this->channel->category_heading();

        $this->assertEquals('15:Second Shared', $out);
    }

    /**
     * The category_group parameter returns no results when no groups remain.
     *
     * @return void
     */
    public function testCategoryHeadingCategoryGroupReturnsNoResultsWhenNoGroupsMatch()
    {
        $this->setCategoryHeadingRenderingMocks();
        $this->setTemplateParams([
            'channel' => 'products',
            'category_url_title' => 'shared-category',
            'category_group' => '9',
        ]);
        $this->setTemplateTagdata('{category_id}:{category_name}');
        $this->setCategoryHeadingRows(
            [
                ['channel_id' => 1, 'group_id' => 2],
                ['channel_id' => 1, 'group_id' => 3],
            ],
            [
                ['cat_id' => 12, 'group_id' => 2, 'parent_id' => 0, 'cat_name' => 'First Shared', 'cat_url_title' => 'shared-category'],
                ['cat_id' => 15, 'group_id' => 3, 'parent_id' => 0, 'cat_name' => 'Second Shared', 'cat_url_title' => 'shared-category'],
            ]
        );

        $out = $this->channel->category_heading();

        $this->assertEquals('NO_RESULTS', $out);
    }

    public function testCategoryHeadingReturnsNoResultsWhenNoCriteria()
    {
        $this->channel->query_string = '';
        $this->setTemplateParams([]);
        $this->setTemplateTagdata('x');
        $out = $this->channel->category_heading();
        $this->assertEquals('NO_RESULTS', $out);
    }

    public function testCategoryHeadingHookOverrides()
    {
        $this->channel->query_string = 'C10';
        $this->setTemplateParams(['channel' => 'news']);
        $this->setTemplateTagdata('x');
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($name){ return $name === 'channel_module_category_heading_start'; }
            public function call($name){ return 'HOOKED'; }
        });
        $out = $this->channel->category_heading();
        $this->assertEquals('HOOKED', $out);
    }

    public function testChannelNameNoParamReturnsEmpty()
    {
        $this->setTemplateParams(['channel' => '']);
        $out = $this->channel->channel_name();
        $this->assertEquals('', $out);
    }

    public function testChannelNameResolvesTitleByName()
    {
        $this->setTemplateParams(['channel' => 'blog']);
        // exp_channels.channel_title fetched
        ee()->db->setRows([['channel_title' => 'Blog Title']]);
        $out = $this->channel->channel_name();
        $this->assertEquals('Blog Title', $out);
    }

    /**
     * Configure mocks needed to render a category heading result.
     *
     * @return void
     */
    private function setCategoryHeadingRenderingMocks()
    {
        $this->setMock('functions', new class extends FakeFunctions {
            public function encode_ee_tags($str)
            {
                return $str;
            }
        });
        $this->setMock('api_channel_fields', new class {
            public $field_types = [];

            public function include_handler($name)
            {
            }

            public function setup_handler($name, $native = false)
            {
                return new class {
                    public function replace_tag($data, $params = [], $tagdata = false)
                    {
                        return $data;
                    }
                };
            }
        });
    }

    /**
     * Configure category heading database rows for channel groups and categories.
     *
     * @param array $groupRows Category group rows.
     * @param array $categoryRows Category rows.
     * @return void
     */
    private function setCategoryHeadingRows(array $groupRows, array $categoryRows)
    {
        $this->setMock('db', new class($groupRows, $categoryRows) extends FakeDb {
            private $groupRows;
            private $categoryRows;

            public function __construct(array $groupRows, array $categoryRows)
            {
                $this->groupRows = $groupRows;
                $this->categoryRows = $categoryRows;
            }

            public function query($sql)
            {
                if (strpos($sql, 'exp_channel_category_groups') !== false) {
                    return new eeDbResultMock($this->groupRows);
                }

                if (strpos($sql, 'SELECT cat_id FROM exp_categories') !== false) {
                    return new eeDbResultMock($this->matchingCategoryIdRows($sql));
                }

                if (strpos($sql, 'FROM exp_categories AS c') !== false) {
                    return new eeDbResultMock($this->matchingCategoryRows($sql));
                }

                return new eeDbResultMock([]);
            }

            private function matchingCategoryIdRows($sql)
            {
                $matches = $this->matchingCategoryRows($sql);
                $ids = [];

                foreach ($matches as $row) {
                    $ids[] = ['cat_id' => $row['cat_id']];
                }

                return $ids;
            }

            private function matchingCategoryRows($sql)
            {
                $matches = $this->categoryRows;

                if (preg_match("/cat_url_title='([^']+)'/", $sql, $match)) {
                    $urlTitle = stripslashes($match[1]);
                    $matches = array_values(array_filter($matches, function ($row) use ($urlTitle) {
                        return $row['cat_url_title'] === $urlTitle;
                    }));
                }

                if (preg_match("/group_id IN \\('([^']*)'\\)/", $sql, $match)) {
                    $groupIds = explode("','", $match[1]);
                    $matches = array_values(array_filter($matches, function ($row) use ($groupIds) {
                        return in_array((string) $row['group_id'], $groupIds);
                    }));
                }

                if (preg_match("/c\\.cat_id = '([^']+)'/", $sql, $match)) {
                    $catId = $match[1];
                    $matches = array_values(array_filter($matches, function ($row) use ($catId) {
                        return (string) $row['cat_id'] === $catId;
                    }));
                }

                usort($matches, function ($left, $right) {
                    return $left['cat_id'] <=> $right['cat_id'];
                });

                if (strpos($sql, 'LIMIT 1') !== false) {
                    $matches = array_slice($matches, 0, 1);
                }

                return $matches;
            }
        });
    }
}

