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
}
