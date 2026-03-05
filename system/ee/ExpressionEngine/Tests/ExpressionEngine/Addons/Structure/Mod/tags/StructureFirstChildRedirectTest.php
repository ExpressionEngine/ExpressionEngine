<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureFirstChildRedirectTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Provide uri and headers capturing
        ee()->setMock('uri', new class {
            public function uri_string() { return ''; }
        });

        // Mock site_pages and url
        $this->structure->site_pages = [
            'url' => 'https://example.com',
            'uris' => [
                100 => '/parent',
                101 => '/parent/child-1',
            ],
        ];

        // Stub sql get_uri and db query to return first child
        $this->structure->sql = new class {
            public function get_uri() { return '/parent'; }
            public function get_site_pages()
            {
                return [
                    'url' => 'https://example.com',
                    'uris' => [
                        100 => '/parent',
                        101 => '/parent/child-1',
                    ],
                ];
            }
        };

        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                return new eeDbResultMock([['entry_id' => 101]]);
            }
        });
    }

    public function testFirstChildRedirectReturnsFalseWhenNoChildFound()
    {
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                return new class {
                    public $num_rows = 0;
                    public function row($column = null)
                    {
                        return null;
                    }
                };
            }
        });

        $this->assertFalse($this->structure->first_child_redirect());
    }

    public function testFirstChildRedirectCoversRedirectHeaderBranchBeforeExit()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.com/';
                }
                return null;
            }
        });

        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                return new class {
                    public $num_rows = 1;
                    public function row($column = null)
                    {
                        return 101;
                    }
                };
            }
        });

        // Ensure header() emits warning so we can stop before hard exit().
        echo 'headers already sent';

        set_error_handler(function ($severity, $message) {
            throw new RuntimeException($message);
        });

        try {
            $this->structure->first_child_redirect();
            $this->fail('Expected header warning interruption before exit().');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Cannot modify header information', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }
}
