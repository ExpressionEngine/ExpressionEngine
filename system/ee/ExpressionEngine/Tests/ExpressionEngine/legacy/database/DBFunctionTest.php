<?php

require_once SYSPATH . 'ee/legacy/database/DB.php';

use PHPUnit\Framework\TestCase;

class DBFunctionTest extends TestCase
{
    private $databaseService;

    protected function setUp(): void
    {
        $this->databaseService = new DBFunctionDatabaseServiceStub();
        ee()->setMock('Database', $this->databaseService);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testDBReturnsNewQueryWithoutMutatingConfigWhenParamsAreEmpty(): void
    {
        $result = DB();

        $this->assertSame($this->databaseService->queryResult, $result);
        $this->assertCount(0, $this->databaseService->setConfigCalls);
        $this->assertSame([], $this->databaseService->config->values);
    }

    public function testDBAppliesRequiredParametersToDatabaseConfig(): void
    {
        $params = [
            'hostname' => '127.0.0.1',
            'database' => 'expressionengine',
            'username' => 'ee_user',
            'password' => 'secret',
            'dbprefix' => 'exp_',
        ];

        $result = DB($params);

        $this->assertSame($this->databaseService->queryResult, $result);
        $this->assertCount(1, $this->databaseService->setConfigCalls);
        $this->assertSame('127.0.0.1', $this->databaseService->config->values['hostname']);
        $this->assertSame('expressionengine', $this->databaseService->config->values['database']);
        $this->assertSame('ee_user', $this->databaseService->config->values['username']);
        $this->assertSame('secret', $this->databaseService->config->values['password']);
        $this->assertSame('exp_', $this->databaseService->config->values['dbprefix']);
        $this->assertArrayNotHasKey('char_set', $this->databaseService->config->values);
        $this->assertArrayNotHasKey('dbcollat', $this->databaseService->config->values);
        $this->assertArrayNotHasKey('port', $this->databaseService->config->values);
    }

    public function testDBAppliesOptionalParametersWhenProvided(): void
    {
        $params = [
            'hostname' => 'localhost',
            'database' => 'ee',
            'username' => 'root',
            'password' => 'pw',
            'dbprefix' => 'exp_',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'port' => 3306,
        ];

        DB($params);

        $this->assertSame('utf8mb4', $this->databaseService->config->values['char_set']);
        $this->assertSame('utf8mb4_unicode_ci', $this->databaseService->config->values['dbcollat']);
        $this->assertSame(3306, $this->databaseService->config->values['port']);
    }
}

class DBFunctionDatabaseServiceStub
{
    public $config;
    public $setConfigCalls = [];
    public $queryResult;

    public function __construct()
    {
        $this->config = new DBFunctionDatabaseConfigStub();
        $this->queryResult = new stdClass();
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config): void
    {
        $this->setConfigCalls[] = $config;
        $this->config = $config;
    }

    public function newQuery()
    {
        return $this->queryResult;
    }
}

class DBFunctionDatabaseConfigStub
{
    public $values = [];

    public function set(string $key, $value): void
    {
        $this->values[$key] = $value;
    }
}
