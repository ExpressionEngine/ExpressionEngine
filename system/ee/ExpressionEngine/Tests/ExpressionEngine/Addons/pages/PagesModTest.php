<?php

require_once __DIR__ . '/PagesTestBase.php';
require_once __DIR__ . '/../../../../Addons/pages/mod.pages.php';

class PagesModTest extends PagesTestBase
{
    public function testLoadSitePagesMergesPagesAndAlwaysIncludesCurrentSite(): void
    {
        $captured = (object) [
            'setItems' => [],
            'filterValues' => [],
        ];

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = '')
            {
                return 'alpha|beta';
            }
        });

        ee()->setMock('config', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function item($key)
            {
                if ($key === 'site_short_name') {
                    return 'current';
                }
                return null;
            }
            public function set_item($key, $value)
            {
                $this->captured->setItems[] = [$key, $value];
            }
        });

        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function filter($field, $value)
                    {
                        $this->captured->filterValues[] = [$field, $value];
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['site_pages' => [1 => ['uris' => [100 => '/alpha'], 'templates' => [100 => 9]]]],
                            (object) ['site_pages' => [2 => ['uris' => [200 => '/beta'], 'templates' => [200 => 10]]]],
                            (object) ['site_pages' => 'not-an-array'],
                        ];
                    }
                };
            }
        });

        $mod = new Pages();
        $this->assertSame('', $mod->load_site_pages());

        $this->assertSame('site_name', $captured->filterValues[0][0]);
        $this->assertContains('current', $captured->filterValues[0][1]);
        $this->assertSame('site_pages', $captured->setItems[0][0]);
        $this->assertArrayHasKey(1, $captured->setItems[0][1]);
        $this->assertArrayHasKey(2, $captured->setItems[0][1]);
    }

    public function testLoadSitePagesDoesNotDuplicateCurrentSiteInFilter(): void
    {
        $captured = (object) ['filterValues' => []];

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = '')
            {
                return 'alpha|current';
            }
        });

        ee()->setMock('config', new class {
            public function item($key)
            {
                return $key === 'site_short_name' ? 'current' : null;
            }
            public function set_item($key, $value)
            {
            }
        });

        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function filter($field, $value)
                    {
                        $this->captured->filterValues[] = $value;
                        return $this;
                    }
                    public function all()
                    {
                        return [];
                    }
                };
            }
        });

        $mod = new Pages();
        $mod->load_site_pages();

        $sites = $captured->filterValues[0];
        $this->assertSame(1, count(array_keys($sites, 'current', true)));
    }

    public function testLoadSitePagesKeepsFirstSiteWhenKeysCollide(): void
    {
        $captured = (object) ['setItems' => []];

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = '')
            {
                return 'alpha|beta';
            }
        });
        ee()->setMock('config', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function item($key)
            {
                return $key === 'site_short_name' ? 'current' : null;
            }
            public function set_item($key, $value)
            {
                $this->captured->setItems[] = [$key, $value];
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity)
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function filter($field, $value)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['site_pages' => [1 => ['uris' => [100 => '/alpha'], 'templates' => [100 => 9]]]],
                            (object) ['site_pages' => [1 => ['uris' => [100 => '/beta-override'], 'templates' => [100 => 10]]]],
                        ];
                    }
                };
            }
        });

        (new Pages())->load_site_pages();

        $merged = $captured->setItems[0][1];
        $this->assertSame('/alpha', $merged[1]['uris'][100]);
        $this->assertSame(9, $merged[1]['templates'][100]);
    }

    public function testLoadSitePagesSetsEmptyArrayWhenNoSiteProvidesArrayPages(): void
    {
        $captured = (object) ['setItems' => []];

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = '')
            {
                return '';
            }
        });
        ee()->setMock('config', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function item($key)
            {
                return $key === 'site_short_name' ? 'current' : null;
            }
            public function set_item($key, $value)
            {
                $this->captured->setItems[] = [$key, $value];
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity)
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function filter($field, $value)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['site_pages' => null],
                            (object) ['site_pages' => 'invalid'],
                        ];
                    }
                };
            }
        });

        (new Pages())->load_site_pages();
        $this->assertSame([], $captured->setItems[0][1]);
    }
}
