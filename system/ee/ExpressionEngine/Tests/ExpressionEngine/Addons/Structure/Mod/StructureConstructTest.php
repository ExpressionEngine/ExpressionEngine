<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureConstructTest extends StructureTestBase
{
    public function testConstructorInitializesParentStateAndStructureCollaborators()
    {
        $this->prepareConstructorEnvironment(
            [
                'site_id' => 1,
                'reserved_category_word' => 'topics',
                'use_category_name' => 'y',
                'site_pages' => [
                    1 => [
                        'url' => '/',
                        'uris' => [7 => '/about'],
                        'templates' => [7 => 2],
                    ],
                ],
            ],
            'from/page',
            'fallback/query'
        );

        $structure = new Structure();

        $this->assertInstanceOf(Sql_structure::class, $structure->sql);
        $this->assertSame(1, $structure->sql->site_id);
        $this->assertInstanceOf(Structure_Nestedset::class, $structure->nset);
        $this->assertSame('from/page', $structure->query_string);
        $this->assertSame('topics', $structure->cat_trigger);
        $this->assertSame('y', $structure->use_category_names);
        $this->assertSame('topics', $structure->reserved_cat_segment);
        $this->assertSame(
            [
                'url' => '/',
                'uris' => [7 => '/about/'],
                'templates' => [7 => 2],
            ],
            $structure->site_pages
        );

        ee()->config->items['site_pages'][1]['uris'][7] = '/changed';
        $this->assertSame('/about/', $structure->site_pages['uris'][7]);

        $adapter = $this->getPrivateProperty($structure->nset, 'adapter');
        $this->assertInstanceOf(Structure_Nestedset_Adapter_Ee::class, $adapter);
        $this->assertSame('exp_structure', $this->getPrivateProperty($adapter, 'table'));
        $this->assertSame('lft', $this->getPrivateProperty($adapter, 'leftCol'));
        $this->assertSame('rgt', $this->getPrivateProperty($adapter, 'rightCol'));
        $this->assertSame('entry_id', $this->getPrivateProperty($adapter, 'idCol'));
        $this->assertSame(1, $this->getPrivateProperty($adapter, 'site_id'));
    }

    public function testConstructorFallsBackToUriQueryStringAndBlankSitePages()
    {
        $this->prepareConstructorEnvironment(
            [
                'site_id' => 1,
                'reserved_category_word' => 'category',
                'use_category_name' => 'n',
            ],
            '',
            'fallback/query'
        );

        $structure = new Structure();

        $this->assertSame('fallback/query', $structure->query_string);
        $this->assertFalse($structure->use_category_names);
        $this->assertSame('', $structure->reserved_cat_segment);
        $this->assertSame('category', $structure->cat_trigger);
        $this->assertSame(
            [
                'url' => '',
                'uris' => [],
                'templates' => [],
            ],
            $structure->site_pages
        );
    }

    private function prepareConstructorEnvironment(array $configItems, string $pageQueryString, string $queryString): void
    {
        ee()->config->items = array_merge(ee()->config->items, $configItems);
        ee()->uri->page_query_string = $pageQueryString;
        ee()->uri->query_string = $queryString;

        $this->setMock('pagination', new class {
            public function create()
            {
                return new stdClass();
            }
        });

        $this->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return $name === 'structure';
            }
        });

        $this->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public function num_rows()
                    {
                        return 0;
                    }

                    public function result_array()
                    {
                        return [];
                    }
                };
            }
        });
    }

    private function getPrivateProperty($object, string $property)
    {
        $reflection = new ReflectionProperty($object, $property);
        \TestReflectionHelper::makeAccessible($reflection);

        return $reflection->getValue($object);
    }
}
