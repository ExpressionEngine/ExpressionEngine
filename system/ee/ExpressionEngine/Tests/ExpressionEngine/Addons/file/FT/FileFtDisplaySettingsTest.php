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

class FileFtDisplaySettingsLangStub
{
    /** @var array<int, string> */
    public $loadfileCalls = [];

    /**
     * Record requested language files.
     *
     * @param string $file
     * @return void
     */
    public function loadfile($file)
    {
        $this->loadfileCalls[] = $file;
    }
}

class FileFtDisplaySettingsUploadDestinationQueryStub
{
    /** @var array<int|string, string> */
    private $dictionary;

    /** @var array<int, array<int, string>> */
    public $fieldsCalls = [];

    /** @var array<int, array<int, mixed>> */
    public $filterCalls = [];

    /** @var array<int, array<int, mixed>> */
    public $orderCalls = [];

    /** @var array<int, bool> */
    public $allCalls = [];

    /** @var array<int, array<int, string>> */
    public $getDictionaryCalls = [];

    /**
     * Seed the upload-destination dictionary returned to display_settings().
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
     * Record model ordering and keep the builder chainable.
     *
     * @param mixed ...$arguments
     * @return self
     */
    public function order(...$arguments)
    {
        $this->orderCalls[] = $arguments;

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

class FileFtDisplaySettingsModelStub
{
    /** @var FileFtDisplaySettingsUploadDestinationQueryStub */
    private $uploadDestinationQuery;

    /** @var array<int, mixed> */
    private $selectedDirectories = [];

    /** @var array<int, string> */
    public $getCalls = [];

    /**
     * Seed the upload-destination query returned by ee('Model').
     *
     * @param FileFtDisplaySettingsUploadDestinationQueryStub $uploadDestinationQuery
     * @return void
     */
    public function __construct(FileFtDisplaySettingsUploadDestinationQueryStub $uploadDestinationQuery)
    {
        $this->uploadDestinationQuery = $uploadDestinationQuery;
    }

    /**
     * Seed the directory returned when display_settings() reloads a saved
     * cross-site upload destination.
     *
     * @param int $directoryId
     * @param mixed $directory
     * @return void
     */
    public function setFirstResult($directoryId, $directory)
    {
        $this->selectedDirectories[$directoryId] = $directory;
    }

    /**
     * Return the configured query builder for the requested model.
     *
     * @param string $model
     * @param mixed ...$arguments
     * @return FileFtDisplaySettingsUploadDestinationQueryStub|FileFtDisplaySettingsSelectedDirectoryQueryStub
     */
    public function get($model, ...$arguments)
    {
        $this->getCalls[] = $model;

        if (count($arguments) > 0) {
            return new FileFtDisplaySettingsSelectedDirectoryQueryStub(
                $this->selectedDirectories[$arguments[0]] ?? null
            );
        }

        return $this->uploadDestinationQuery;
    }
}

class FileFtDisplaySettingsSelectedDirectoryQueryStub
{
    /** @var mixed */
    private $directory;

    /** @var array<int, string> */
    public $withCalls = [];

    /**
     * Seed the directory returned by first().
     *
     * @param mixed $directory
     * @return void
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Record eager-loaded relationships and keep the query chainable.
     *
     * @param string $relationship
     * @return self
     */
    public function with($relationship)
    {
        $this->withCalls[] = $relationship;

        return $this;
    }

    /**
     * Return the configured directory model.
     *
     * @return mixed
     */
    public function first()
    {
        return $this->directory;
    }
}

class FileFtDisplaySettingsAlertServiceStub
{
    /** @var array<int, string> */
    public $makeInlineCalls = [];

    /** @var FileFtDisplaySettingsInlineAlertStub */
    public $lastInlineAlert;

    /**
     * Return an inline-alert builder for the requested key.
     *
     * @param string $key
     * @return FileFtDisplaySettingsInlineAlertStub
     */
    public function makeInline($key)
    {
        $this->makeInlineCalls[] = $key;
        $this->lastInlineAlert = new FileFtDisplaySettingsInlineAlertStub($key);

        return $this->lastInlineAlert;
    }
}

class FileFtDisplaySettingsInlineAlertStub
{
    /** @var string */
    private $key;

    /** @var int */
    public $asImportantCalls = 0;

    /** @var array<int, string> */
    public $bodyCalls = [];

    /** @var int */
    public $cannotCloseCalls = 0;

    /**
     * Seed the alert key used during render().
     *
     * @param string $key
     * @return void
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Record the important-alert flag and keep the chainable builder.
     *
     * @return self
     */
    public function asImportant()
    {
        $this->asImportantCalls++;

        return $this;
    }

    /**
     * Record the alert body and keep the chainable builder.
     *
     * @param string $body
     * @return self
     */
    public function addToBody($body)
    {
        $this->bodyCalls[] = $body;

        return $this;
    }

    /**
     * Record the close-button suppression and keep the chainable builder.
     *
     * @return self
     */
    public function cannotClose()
    {
        $this->cannotCloseCalls++;

        return $this;
    }

    /**
     * Return a stable rendered-alert marker for assertions.
     *
     * @return string
     */
    public function render()
    {
        return 'rendered-alert:' . $this->key;
    }
}

class FileFtDisplaySettingsTest extends FileFtTestBase
{
    /**
     * Assert display_settings() builds the default field configuration and
     * loads the upload-destination choices for the active site.
     *
     * @return void
     */
    public function testDisplaySettingsBuildsDefaultConfiguration()
    {
        $lang = new FileFtDisplaySettingsLangStub();
        $query = new FileFtDisplaySettingsUploadDestinationQueryStub([
            2 => 'Banners',
            9 => 'Documents',
        ]);
        $model = new FileFtDisplaySettingsModelStub($query);

        ee()->setMock('lang', $lang);
        ee()->setMock('Model', $model);
        $this->configMock->items['site_id'] = 7;

        $result = $this->makeFieldtype()->display_settings([]);

        $this->assertSame(['fieldtypes'], $lang->loadfileCalls);
        $this->assertSame(['file_upload_preferences_model'], $this->loadRecorder->models);
        $this->assertSame(['UploadDestination'], $model->getCalls);
        $this->assertSame([['id', 'name']], $query->fieldsCalls);
        $this->assertSame([
            ['site_id', 'IN', [0, 7]],
            ['module_id', 0],
        ], $query->filterCalls);
        $this->assertSame([['name', 'asc']], $query->orderCalls);
        $this->assertSame([true], $query->allCalls);
        $this->assertSame([['id', 'name']], $query->getDictionaryCalls);
        $this->assertSame([
            'field_options_file',
            'channel_form_settings_file',
        ], array_keys($result));
        $this->assertSame([
            'all' => 'all',
            2 => 'Banners',
            9 => 'Documents',
        ], $result['field_options_file']['settings'][1]['fields']['allowed_directories']['choices']);
        $this->assertSame('all', $result['field_options_file']['settings'][0]['fields']['field_content_type']['value']);
        $this->assertSame([
            'all' => 'all',
            'image' => 'type_image',
        ], $result['field_options_file']['settings'][0]['fields']['field_content_type']['choices']);
        $this->assertSame('all', $result['field_options_file']['settings'][1]['fields']['allowed_directories']['value']);
        $this->assertSame('no_found', $result['field_options_file']['settings'][1]['fields']['allowed_directories']['no_results']['text']);
        $this->assertSame('add_new', $result['field_options_file']['settings'][1]['fields']['allowed_directories']['no_results']['link_text']);
        $this->assertSame($this->cpUrlFactory->lastCompiledUrl, $result['field_options_file']['settings'][1]['fields']['allowed_directories']['no_results']['link_href']);
        $this->assertSame([
            [
                'path' => 'files/uploads/create',
                'params' => [],
            ],
        ], $this->cpUrlFactory->makeCalls);
        $this->assertSame('y', $result['channel_form_settings_file']['settings'][0]['fields']['show_existing']['value']);
        $this->assertSame(50, $result['channel_form_settings_file']['settings'][1]['fields']['num_existing']['value']);
    }

    /**
     * Assert display_settings() preserves explicit field settings, including
     * the zero-limit boundary for showing all existing files.
     *
     * @return void
     */
    public function testDisplaySettingsPreservesExplicitConfiguration()
    {
        $query = new FileFtDisplaySettingsUploadDestinationQueryStub([
            4 => 'Downloads',
        ]);

        ee()->setMock('lang', new FileFtDisplaySettingsLangStub());
        ee()->setMock('Model', new FileFtDisplaySettingsModelStub($query));
        $this->configMock->items['site_id'] = 12;

        $result = $this->makeFieldtype()->display_settings([
            'allowed_directories' => 4,
            'show_existing' => 'n',
            'num_existing' => 0,
            'field_content_type' => 'image',
        ]);

        $this->assertSame('image', $result['field_options_file']['settings'][0]['fields']['field_content_type']['value']);
        $this->assertSame(4, $result['field_options_file']['settings'][1]['fields']['allowed_directories']['value']);
        $this->assertSame('n', $result['channel_form_settings_file']['settings'][0]['fields']['show_existing']['value']);
        $this->assertSame(0, $result['channel_form_settings_file']['settings'][1]['fields']['num_existing']['value']);
    }

    /**
     * Assert display_settings() surfaces the cross-site warning when the
     * saved directory no longer belongs to the current site choices.
     *
     * @return void
     */
    public function testDisplaySettingsAddsWarningForCrossSiteSavedDirectory()
    {
        $query = new FileFtDisplaySettingsUploadDestinationQueryStub([
            4 => 'Downloads',
        ]);
        $model = new FileFtDisplaySettingsModelStub($query);
        $alert = new FileFtDisplaySettingsAlertServiceStub();
        $selectedDirectory = (object) [
            'name' => 'Shared Assets',
            'Site' => (object) ['site_label' => 'Secondary Site'],
        ];

        $model->setFirstResult(42, $selectedDirectory);

        ee()->setMock('lang', new FileFtDisplaySettingsLangStub());
        ee()->setMock('Model', $model);
        ee()->setMock('CP/Alert', $alert);
        $this->configMock->items['site_id'] = 12;

        $result = $this->makeFieldtype()->display_settings([
            'allowed_directories' => 42,
        ]);

        $this->assertSame(['UploadDestination', 'UploadDestination'], $model->getCalls);
        $this->assertSame(['file_field_msm_warning'], $alert->makeInlineCalls);
        $this->assertSame(1, $alert->lastInlineAlert->asImportantCalls);
        $this->assertSame(['file_field_msm_warning'], $alert->lastInlineAlert->bodyCalls);
        $this->assertSame(1, $alert->lastInlineAlert->cannotCloseCalls);
        $this->assertSame([
            'type' => 'html',
            'content' => 'rendered-alert:file_field_msm_warning',
        ], $result['field_options_file']['settings'][1]['fields']['file_field_msm_warning']);
    }

    /**
     * Assert display_settings() skips the warning block when the missing
     * saved directory can no longer be resolved.
     *
     * @return void
     */
    public function testDisplaySettingsSkipsWarningWhenSavedDirectoryCannotBeResolved()
    {
        $query = new FileFtDisplaySettingsUploadDestinationQueryStub([
            4 => 'Downloads',
        ]);
        $model = new FileFtDisplaySettingsModelStub($query);
        $alert = new FileFtDisplaySettingsAlertServiceStub();

        ee()->setMock('lang', new FileFtDisplaySettingsLangStub());
        ee()->setMock('Model', $model);
        ee()->setMock('CP/Alert', $alert);
        $this->configMock->items['site_id'] = 12;

        $result = $this->makeFieldtype()->display_settings([
            'allowed_directories' => 99,
        ]);

        $this->assertSame(['UploadDestination', 'UploadDestination'], $model->getCalls);
        $this->assertArrayNotHasKey(
            'file_field_msm_warning',
            $result['field_options_file']['settings'][1]['fields']
        );
        $this->assertSame([], $alert->makeInlineCalls);
    }
}
