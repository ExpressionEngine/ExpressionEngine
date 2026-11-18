<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtGetDirectoriesUploadDestinationQueryStub
{
    /** @var array<int|string, string> */
    private $dictionary;

    /** @var array<int, array<int, string>> */
    public $fieldsCalls = [];

    /** @var array<int, array<int, mixed>> */
    public $filterCalls = [];

    /** @var array<int, bool> */
    public $allCalls = [];

    /** @var array<int, array<int, string>> */
    public $getDictionaryCalls = [];

    /**
     * Seed the upload-destination dictionary returned by getDirectories().
     *
     * @param array<int|string, string> $dictionary
     * @return void
     */
    public function __construct(array $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    /**
     * Record the selected model fields.
     *
     * @param string ...$fields
     * @return self
     */
    public function fields(...$fields)
    {
        $this->fieldsCalls[] = $fields;

        return $this;
    }

    /**
     * Record model filters and keep the builder chainable.
     *
     * @param mixed ...$arguments
     * @return self
     */
    public function filter(...$arguments)
    {
        $this->filterCalls[] = $arguments;

        return $this;
    }

    /**
     * Record array-mode hydration and keep the builder chainable.
     *
     * @param bool $all
     * @return self
     */
    public function all($all)
    {
        $this->allCalls[] = $all;

        return $this;
    }

    /**
     * Return the configured upload-destination dictionary.
     *
     * @param string $key
     * @param string $value
     * @return array<int|string, string>
     */
    public function getDictionary($key, $value)
    {
        $this->getDictionaryCalls[] = [$key, $value];

        return $this->dictionary;
    }
}

class FileFtGetDirectoriesModelStub
{
    /** @var FileFtGetDirectoriesUploadDestinationQueryStub */
    private $uploadDestinationQuery;

    /** @var array<int, string> */
    public $getCalls = [];

    /**
     * Seed the upload-destination query returned by ee('Model').
     *
     * @param FileFtGetDirectoriesUploadDestinationQueryStub $uploadDestinationQuery
     * @return void
     */
    public function __construct(FileFtGetDirectoriesUploadDestinationQueryStub $uploadDestinationQuery)
    {
        $this->uploadDestinationQuery = $uploadDestinationQuery;
    }

    /**
     * Return the configured query builder for the requested model.
     *
     * @param string $model
     * @return FileFtGetDirectoriesUploadDestinationQueryStub
     */
    public function get($model)
    {
        $this->getCalls[] = $model;

        return $this->uploadDestinationQuery;
    }
}

class FileFtGetDirectoriesTest extends FileFtTestBase
{
    /**
     * Assert getDirectories() loads upload destinations for the global site
     * and the active site on the first lookup.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDirectoriesLoadsUploadDestinationsForGlobalAndCurrentSite()
    {
        $query = new FileFtGetDirectoriesUploadDestinationQueryStub([
            2 => 'Banners',
            9 => 'Documents',
        ]);
        $model = new FileFtGetDirectoriesModelStub($query);

        ee()->setMock('Model', $model);
        $this->configMock->items['site_id'] = 7;

        $result = $this->callGetDirectories($this->makeFieldtype());

        $this->assertSame([
            2 => 'Banners',
            9 => 'Documents',
        ], $result);
        $this->assertSame(['UploadDestination'], $model->getCalls);
        $this->assertSame([['id', 'name']], $query->fieldsCalls);
        $this->assertSame([
            ['site_id', 'IN', [0, 7]],
            ['module_id', 0],
        ], $query->filterCalls);
        $this->assertSame([true], $query->allCalls);
        $this->assertSame([['id', 'name']], $query->getDictionaryCalls);
    }

    /**
     * Assert getDirectories() reuses the static cache after a successful
     * lookup instead of hitting the model service again.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDirectoriesReusesStaticCacheAcrossCalls()
    {
        $firstQuery = new FileFtGetDirectoriesUploadDestinationQueryStub([
            4 => 'Downloads',
        ]);
        $firstModel = new FileFtGetDirectoriesModelStub($firstQuery);
        $fieldtype = $this->makeFieldtype();

        ee()->setMock('Model', $firstModel);
        $this->configMock->items['site_id'] = 12;

        $firstResult = $this->callGetDirectories($fieldtype);

        $secondQuery = new FileFtGetDirectoriesUploadDestinationQueryStub([
            99 => 'Should Not Load',
        ]);
        $secondModel = new FileFtGetDirectoriesModelStub($secondQuery);

        ee()->setMock('Model', $secondModel);
        $this->configMock->items['site_id'] = 99;

        $secondResult = $this->callGetDirectories($fieldtype);

        $this->assertSame([
            4 => 'Downloads',
        ], $firstResult);
        $this->assertSame($firstResult, $secondResult);
        $this->assertSame(['UploadDestination'], $firstModel->getCalls);
        $this->assertSame([], $secondModel->getCalls);
        $this->assertSame([], $secondQuery->fieldsCalls);
        $this->assertSame([], $secondQuery->filterCalls);
        $this->assertSame([], $secondQuery->allCalls);
        $this->assertSame([], $secondQuery->getDictionaryCalls);
    }

    /**
     * Assert getDirectories() re-queries after an empty result because the
     * current implementation does not treat an empty array as cached state.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDirectoriesReloadsAfterEmptyDirectoryList()
    {
        $firstQuery = new FileFtGetDirectoriesUploadDestinationQueryStub([]);
        $firstModel = new FileFtGetDirectoriesModelStub($firstQuery);
        $fieldtype = $this->makeFieldtype();

        ee()->setMock('Model', $firstModel);
        $this->configMock->items['site_id'] = 3;

        $firstResult = $this->callGetDirectories($fieldtype);

        $secondQuery = new FileFtGetDirectoriesUploadDestinationQueryStub([
            7 => 'Reloaded',
        ]);
        $secondModel = new FileFtGetDirectoriesModelStub($secondQuery);

        ee()->setMock('Model', $secondModel);
        $this->configMock->items['site_id'] = 8;

        $secondResult = $this->callGetDirectories($fieldtype);

        $this->assertSame([], $firstResult);
        $this->assertSame([
            7 => 'Reloaded',
        ], $secondResult);
        $this->assertSame(['UploadDestination'], $firstModel->getCalls);
        $this->assertSame(['UploadDestination'], $secondModel->getCalls);
        $this->assertSame([
            ['site_id', 'IN', [0, 3]],
            ['module_id', 0],
        ], $firstQuery->filterCalls);
        $this->assertSame([
            ['site_id', 'IN', [0, 8]],
            ['module_id', 0],
        ], $secondQuery->filterCalls);
    }

    /**
     * Invoke the private getDirectories() helper through reflection.
     *
     * @param File_ft $fieldtype
     * @return array<int|string, string>
     * @throws \ReflectionException
     */
    private function callGetDirectories($fieldtype)
    {
        $method = new ReflectionMethod(File_ft::class, 'getDirectories');
        \TestReflectionHelper::makeAccessible($method);

        return $method->invoke($fieldtype);
    }
}
