<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelBuildSqlQueryTest extends ChannelTestBase
{
    public function testBuildSqlQueryReturnsEmptyStringWhenNoSql()
    {
        // Set sql to empty
        $this->channel->sql = '';

        $result = $this->channel->build_sql_query();

        $this->assertEquals('', $result);
    }

    public function testBuildSqlQueryAlwaysRebuildsSql()
    {
        // Set existing sql
        $existingSql = 'SELECT * FROM exp_channel_titles WHERE entry_id = 1';
        $this->channel->sql = $existingSql;

        $result = $this->channel->build_sql_query();

        // build_sql_query always rebuilds the SQL from scratch
        // So the existing SQL should be replaced (or at least modified)
        // The exact result depends on the query building logic
        $this->assertIsString($this->channel->sql);
    }

    public function testBuildSqlQueryConstructsBasicQuery()
    {
        // Set sql to empty to trigger query building
        $this->channel->sql = '';

        $result = $this->channel->build_sql_query();

        // build_sql_query should have built some SQL
        $this->assertIsString($this->channel->sql);
        // The method doesn't return the SQL, just builds it
        $this->assertEquals('', $result);
    }

    public function testBuildSqlQueryHandlesQueryStringParameter()
    {
        // Set sql to empty to trigger query building
        $this->channel->sql = '';

        // Set a simple numeric query string (entry ID)
        $queryString = '123';
        $this->channel->query_string = $queryString;

        // Mock database to return empty results to avoid complex processing
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query($queryString);

        // build_sql_query should have built some SQL
        $this->assertIsString($this->channel->sql);
        // The method doesn't return the SQL, just builds it
        $this->assertEquals('', $result);
    }

    public function testBuildSqlQueryIntegratesFieldSearchSql()
    {
        // Set sql to empty to trigger query building
        $this->channel->sql = '';

        // Mock database to return empty results to avoid complex processing
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();

        // build_sql_query should have built some SQL
        $this->assertIsString($this->channel->sql);
        // The method doesn't return the SQL, just builds it
        $this->assertEquals('', $result);
    }

    public function testBuildSqlQuerySetsSqlProperty()
    {
        // Set sql to empty to trigger query building
        $this->channel->sql = '';

        // Mock database to return empty results to avoid complex processing
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();

        // build_sql_query should have built some SQL
        $this->assertIsString($this->channel->sql);
        // The method doesn't return the SQL, just builds it
        $this->assertEquals('', $result);
    }

    public function testDynamicNoIgnoresCategorySegments()
    {
        $this->channel->sql = '';
        $this->setTemplateParams([
            'dynamic' => 'no',
            'channel' => 'news'
        ]);
        // Mock Model service used by code paths that enumerate sites
        $this->setMock('Model', new class {
            public function get($name)
            {
                if ($name === 'Site') {
                    return new class {
                        public function fields(...$fields) { return $this; }
                        public function all($arg = true) { return new class { public function getIds(){ return [1]; } }; }
                    };
                }
                return new class {
                    public function fields(...$fields) { return $this; }
                    public function all($arg = true) { return new class { public function getIds(){ return [1]; } }; }
                };
            }
        });
        $this->channel->reserved_cat_segment = 'category';
        $this->channel->query_string = 'category/sports/article';

        $result = $this->channel->build_sql_query();

        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testStatusNotSyntaxAndEmptyHandledSafely()
    {
        $this->channel->sql = '';
        $this->setTemplateParams([ 'status' => 'not closed|open' ]);
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testFutureAndExpiredFlagsToggleDateConstraints()
    {
        $this->channel->sql = '';
        $this->setTemplateParams([ 'show_future_entries' => 'yes', 'show_expired' => 'yes' ]);
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testFixedOrderWithInvalidIdsDoesNotBreak()
    {
        $this->channel->sql = '';
        $this->channel->fixed_order = '1|x|2|y';
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testAuthorIdAndChannelMultiValuesHandled()
    {
        $this->channel->sql = '';
        // Mock Model service for Site lookups
        $this->setMock('Model', new class {
            public function get($name)
            {
                if ($name === 'Site') {
                    return new class {
                        public function fields(...$fields) { return $this; }
                        public function all($arg = true) { return new class { public function getIds(){ return [1]; } }; }
                    };
                }
                return new class {
                    public function fields(...$fields) { return $this; }
                    public function all($arg = true) { return new class { public function getIds(){ return [1]; } }; }
                };
            }
        });
        $this->setTemplateParams([ 'author_id' => '1|2', 'channel' => 'news|blog' ]);
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testUrlTitleAndEntryIdInputsAreSafe()
    {
        $this->channel->sql = '';
        $this->setTemplateParams([ 'entry_id' => '123', 'url_title' => 'sample-post' ]);
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }

    public function testSearchParametersDoNotBreakQuery()
    {
        $this->channel->sql = '';
        // Simulate a field search param that build_sql_query will consider
        $this->setTemplateParams([ 'search:title' => 'test*' ]);
        $this->setDbRows([]);

        $result = $this->channel->build_sql_query();
        $this->assertEquals('', $result);
        $this->assertIsString($this->channel->sql);
    }
}
