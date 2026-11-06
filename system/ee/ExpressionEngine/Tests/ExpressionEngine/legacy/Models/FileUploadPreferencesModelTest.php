<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

require_once SYSPATH . 'ee/legacy/models/file_upload_preferences_model.php';

if (! function_exists('parse_config_variables')) {
    function parse_config_variables($value, $variables = array())
    {
        return $value;
    }
}

class FileUploadPreferencesModelTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testSingleLookupIncludesDirectoriesSharedAcrossSites(): void
    {
        $state = $this->mockDatabase(['id' => 5, 'site_id' => 0, 'url' => '/media/', 'server_path' => '/var/www/media/']);

        $prefs = $this->model()->get_file_upload_preferences(null, 5);

        $this->assertContains(['where', 'id', 5], $state->calls);
        $this->assertContains(['where_in', 'site_id', [0, 2]], $state->calls);
        $this->assertNotContains(['where', 'site_id', 2], $state->calls);
        $this->assertSame('/var/www/media/', $prefs['server_path']);
    }

    public function testIgnoreSiteIdSkipsTheSiteFilter(): void
    {
        $state = $this->mockDatabase(['id' => 5, 'site_id' => 0, 'url' => '/media/', 'server_path' => '/var/www/media/']);

        $this->model()->get_file_upload_preferences(null, 5, true);

        foreach ($state->calls as $call) {
            $this->assertNotSame('site_id', $call[1] ?? null);
        }
    }

    private function model()
    {
        return new class() extends File_upload_preferences_model {
            public function __get($key)
            {
                return ee()->$key;
            }
        };
    }

    private function mockDatabase(array $row)
    {
        $state = (object) ['calls' => [], 'row' => $row];

        ee()->setMock('config', new class() {
            public function item($key)
            {
                return $key === 'site_id' ? 2 : false;
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where($key, $value)
            {
                $this->state->calls[] = ['where', $key, $value];

                return $this;
            }

            public function where_in($key, $values)
            {
                $this->state->calls[] = ['where_in', $key, $values];

                return $this;
            }

            public function from($table)
            {
                $this->state->calls[] = ['from', $table];

                return $this;
            }

            public function order_by($column, $direction = '')
            {
                $this->state->calls[] = ['order_by', $column, $direction];

                return $this;
            }

            public function get()
            {
                $state = $this->state;

                return new class($state) {
                    private $state;

                    public function __construct($state)
                    {
                        $this->state = $state;
                    }

                    public function row_array()
                    {
                        return $this->state->row;
                    }

                    public function result_array()
                    {
                        return [$this->state->row];
                    }
                };
            }
        });

        return $state;
    }
}
