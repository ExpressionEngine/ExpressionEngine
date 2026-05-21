<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/database/DB_driver.php';

if (! class_exists('CI_Model')) {
    require_once BASEPATH . 'core/Model.php';
}

require_once BASEPATH . 'models/channel_model.php';

class ChannelModelFieldSearchTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testFullWordFieldSearchUsesIcuBoundaryForMysqlEight(): void
    {
        ee()->setMock('db', new ChannelModelFieldSearchDbStub('8.0.44'));

        $sql = (new Channel_model())->field_search_sql('\Wterm', 'field_id_1');

        $this->assertStringContainsString('REGEXP "(\\\\b|^)term(\\\\b|$)"', $sql);
        $this->assertStringNotContainsString('[[:<:]]', $sql);
        $this->assertStringNotContainsString('[[:>:]]', $sql);
    }

    public function testFullWordFieldSearchUsesLegacyBoundaryForMariaDb(): void
    {
        ee()->setMock('db', new ChannelModelFieldSearchDbStub('5.5.5-10.6.18-MariaDB'));

        $sql = (new Channel_model())->field_search_sql('\Wterm', 'field_id_1');

        $this->assertStringContainsString('REGEXP "([[:<:]]|^)term([[:>:]]|$)"', $sql);
    }

    public function testNegatedFullWordFieldSearchKeepsNullFallback(): void
    {
        ee()->setMock('db', new ChannelModelFieldSearchDbStub('8.0.44'));

        $sql = (new Channel_model())->field_search_sql('not \Wterm', 'field_id_1');

        $this->assertStringContainsString('NOT REGEXP "(\\\\b|^)term(\\\\b|$)"', $sql);
        $this->assertStringContainsString('OR (field_id_1 IS NULL)', $sql);
    }
}

class ChannelModelFieldSearchDbStub extends CI_DB_driver
{
    private $versionString;

    public function __construct($versionString)
    {
        $this->versionString = $versionString;

        parent::__construct([]);
    }

    public function version()
    {
        return $this->versionString;
    }

    public function escape_str($str, $like = false)
    {
        return addslashes($str);
    }
}
