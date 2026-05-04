<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/File_field.php';

use PHPUnit\Framework\TestCase;

class FileFieldFieldHarness extends \File_field
{
    /** @var mixed */
    public $parseFieldReturn = false;

    /** @var mixed */
    public $fileModelReturn = null;

    /** @var array<int, string> */
    public $parseFieldCalls = [];

    /** @var array<int, string> */
    public $getFileModelCalls = [];

    /**
     * Return configured parse payload for deterministic branch tests.
     *
     * @param string $str Raw file field value.
     * @return mixed
     */
    public function parse_field($str)
    {
        $this->parseFieldCalls[] = $str;

        return $this->parseFieldReturn;
    }

    /**
     * Return configured file model for thumbnail rendering.
     *
     * @param string $data Raw file field value.
     * @return mixed
     */
    public function getFileModelForFieldData($data)
    {
        $this->getFileModelCalls[] = $data;

        return $this->fileModelReturn;
    }
}

class FileFieldLoadMock
{
    /** @var array<int, string> */
    public $libraries = [];

    /** @var array<int, array<int, string>> */
    public $helpers = [];

    /** @var array<int, array<int, string>> */
    public $models = [];

    /** @var array<int, string> */
    public $packagePaths = [];

    /**
     * Record loaded library names.
     *
     * @param string $name Library alias.
     * @return void
     */
    public function library($name)
    {
        $this->libraries[] = $name;
    }

    /**
     * Record loaded helper names.
     *
     * @param array<int, string> $helpers Helper aliases.
     * @return void
     */
    public function helper($helpers)
    {
        $this->helpers[] = $helpers;
    }

    /**
     * Record loaded model names.
     *
     * @param array<int, string> $models Model aliases.
     * @return void
     */
    public function model($models)
    {
        $this->models[] = $models;
    }

    /**
     * Record package path registration.
     *
     * @param string $path Package path.
     * @return void
     */
    public function add_package_path($path)
    {
        $this->packagePaths[] = $path;
    }
}

class FileFieldUploadPreferencesMock
{
    /** @var array<int, array<string, mixed>> */
    public $calls = [];

    /** @var array<string, mixed> */
    private $dropdown;

    /**
     * @param array<string, mixed> $dropdown
     */
    public function __construct(array $dropdown)
    {
        $this->dropdown = $dropdown;
    }

    /**
     * Return configured directory choices.
     *
     * @param mixed $memberId
     * @param mixed $allowedDirs
     * @param array<string, mixed> $uploadDirs
     * @return array<string, mixed>
     */
    public function get_dropdown_array($memberId, $allowedDirs, array $uploadDirs)
    {
        $this->calls[] = [
            'member_id' => $memberId,
            'allowed_dirs' => $allowedDirs,
            'upload_dirs' => $uploadDirs,
        ];

        return $this->dropdown;
    }
}

class FileFieldManagerMock
{
    /** @var array<int, array<string, mixed>> */
    public $calls = [];

    /**
     * Return deterministic thumbnail metadata.
     *
     * @param string $filename
     * @param mixed $directoryId
     * @return array<string, string>
     */
    public function get_thumb($filename, $directoryId)
    {
        $this->calls[] = [
            'filename' => $filename,
            'directory_id' => $directoryId,
        ];

        return ['thumb' => 'legacy-thumb'];
    }
}

class FileFieldThumbnailMock
{
    /** @var array<int, mixed> */
    public $calls = [];

    /** @var string */
    private $tag;

    /** @var string */
    private $url;

    public function __construct($tag = 'thumb-tag', $url = 'thumb-url')
    {
        $this->tag = $tag;
        $this->url = $url;
    }

    /**
     * Return a thumbnail object with the configured tag string.
     *
     * @param mixed $file
     * @return object
     */
    public function get($file)
    {
        $this->calls[] = $file;

        return (object) ['tag' => $this->tag, 'url' => $this->url];
    }
}

class FileFieldLangMock
{
    /** @var array<int, string> */
    public $loaded = [];

    /**
     * Track requested language file loads.
     *
     * @param string $file Language file alias.
     * @return void
     */
    public function loadfile($file)
    {
        $this->loaded[] = $file;
    }

    /**
     * Mirror eeLangMock behavior and return key names.
     *
     * @param string $key Language key.
     * @return string
     */
    public function line($key)
    {
        return $key;
    }
}

class FileFieldUploadDestinationMock
{
    /** @var int */
    public $id;

    /** @var int */
    public $module_id;

    /** @var string */
    public $default_modal_view;

    /** @var bool */
    private $memberHasAccess;

    /** @var array<int, mixed> */
    public $memberChecks = [];

    /**
     * @param int $id Upload destination id.
     * @param int $moduleId Module owner id.
     * @param string $modalView Default picker view.
     * @param bool $memberHasAccess Allowed state for member access checks.
     */
    public function __construct($id, $moduleId = 0, $modalView = 'list', $memberHasAccess = true)
    {
        $this->id = $id;
        $this->module_id = $moduleId;
        $this->default_modal_view = $modalView;
        $this->memberHasAccess = $memberHasAccess;
    }

    /**
     * Return configured access result for upload destination checks.
     *
     * @param mixed $member Current member.
     * @return bool
     */
    public function memberHasAccess($member)
    {
        $this->memberChecks[] = $member;

        return $this->memberHasAccess;
    }
}

class FileFieldCollectionMock
{
    /** @var array<int, mixed> */
    private $items;

    /**
     * @param array<int, mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Build a dictionary keyed by the requested field.
     *
     * @param string $field
     * @return array<int, mixed>
     */
    public function indexBy($field)
    {
        $indexed = [];

        foreach ($this->items as $item) {
            $indexed[$item->$field] = $item;
        }

        return $indexed;
    }
}

class FileFieldModelQueryMock
{
    /** @var FileFieldModelServiceMock */
    private $service;

    /** @var string */
    private $modelName;

    /** @var mixed */
    private $id;

    /** @var array<int, string> */
    private $withRelations = [];

    /** @var array<int, array<string, mixed>> */
    private $filters = [];

    /**
     * @param FileFieldModelServiceMock $service
     * @param string $modelName
     * @param mixed $id
     */
    public function __construct(FileFieldModelServiceMock $service, $modelName, $id = null)
    {
        $this->service = $service;
        $this->modelName = $modelName;
        $this->id = $id;
    }

    /**
     * Record order clauses applied to query chains.
     *
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function order($field, $direction)
    {
        $this->service->orderCalls[] = [
            'field' => $field,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Record requested model relationship eager loads.
     *
     * @param string $relation
     * @return self
     */
    public function with($relation)
    {
        $this->withRelations[] = $relation;
        $this->service->withCalls[] = [
            'model' => $this->modelName,
            'id' => $this->id,
            'relation' => $relation,
        ];

        return $this;
    }

    /**
     * Record query filter constraints in normalized form.
     *
     * @param string $field
     * @param mixed $operatorOrValue
     * @param mixed $value
     * @return self
     */
    public function filter($field, $operatorOrValue, $value = null)
    {
        $operator = '=';
        $normalizedValue = $operatorOrValue;

        if (func_num_args() === 3) {
            $operator = (string) $operatorOrValue;
            $normalizedValue = $value;
        }

        $entry = [
            'field' => $field,
            'operator' => $operator,
            'value' => $normalizedValue,
        ];
        $this->filters[] = $entry;
        $this->service->filterCalls[] = [
            'model' => $this->modelName,
            'id' => $this->id,
            'field' => $field,
            'operator' => $operator,
            'value' => $normalizedValue,
        ];

        return $this;
    }

    /**
     * Return all configured upload destinations.
     *
     * @return FileFieldCollectionMock
     */
    public function all()
    {
        if ($this->modelName !== 'UploadDestination') {
            return new FileFieldCollectionMock([]);
        }

        return new FileFieldCollectionMock($this->service->uploadDestinationsAll);
    }

    /**
     * Return the configured upload destination for a specific id.
     *
     * @return mixed
     */
    public function first($asModel = null)
    {
        if ($this->modelName === 'UploadDestination') {
            if ($this->id === null) {
                return null;
            }

            if (! array_key_exists((int) $this->id, $this->service->uploadDestinationsById)) {
                return null;
            }

            return $this->service->uploadDestinationsById[(int) $this->id];
        }

        if ($this->modelName !== 'File') {
            return null;
        }

        $this->service->firstCalls[] = [
            'model' => $this->modelName,
            'id' => $this->id,
            'with' => $this->withRelations,
            'filters' => $this->filters,
            'as_model' => $asModel,
        ];

        if ($this->id !== null) {
            return $this->service->fileResultsById[(string) $this->id] ?? null;
        }

        $key = $this->service->buildFileFilterKey($this->filters);

        return $this->service->fileResultsByFilterKey[$key] ?? null;
    }
}

class FileFieldModelServiceMock
{
    /** @var array<int, mixed> */
    public $calls = [];

    /** @var array<int, mixed> */
    public $orderCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $withCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $filterCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $firstCalls = [];

    /** @var array<int, FileFieldUploadDestinationMock> */
    public $uploadDestinationsById = [];

    /** @var array<int, FileFieldUploadDestinationMock> */
    public $uploadDestinationsAll = [];

    /** @var array<string, mixed> */
    public $fileResultsById = [];

    /** @var array<string, mixed> */
    public $fileResultsByFilterKey = [];

    /**
     * Return a chainable query mock for model lookups.
     *
     * @param string $modelName
     * @param mixed $id
     * @return FileFieldModelQueryMock
     */
    public function get($modelName, $id = null)
    {
        $this->calls[] = [
            'model' => $modelName,
            'id' => $id,
        ];

        return new FileFieldModelQueryMock($this, $modelName, $id);
    }

    /**
     * Build deterministic key for lookup-based file model responses.
     *
     * @param array<int, array<string, mixed>> $filters
     * @return string
     */
    public function buildFileFilterKey(array $filters)
    {
        $normalized = [];

        foreach ($filters as $filter) {
            $normalized[] = [
                'field' => (string) $filter['field'],
                'operator' => (string) $filter['operator'],
                'value' => $filter['value'],
            ];
        }

        return json_encode($normalized);
    }
}

class FileFieldFilePickerLinkMock
{
    /** @var array<int, string> */
    public $valueTargets = [];

    /** @var array<int, string> */
    public $nameTargets = [];

    /** @var array<int, string> */
    public $imageTargets = [];

    /** @var int */
    public $asThumbsCalls = 0;

    /** @var int */
    public $asListCalls = 0;

    /** @var string */
    public $html = '';

    /** @var array<string, mixed> */
    public $attributes = [];

    /** @var array<int, mixed> */
    public $selected = [];

    /**
     * @param string $fieldName
     * @return self
     */
    public function withValueTarget($fieldName)
    {
        $this->valueTargets[] = $fieldName;

        return $this;
    }

    /**
     * @param string $fieldName
     * @return self
     */
    public function withNameTarget($fieldName)
    {
        $this->nameTargets[] = $fieldName;

        return $this;
    }

    /**
     * @param string $fieldName
     * @return self
     */
    public function withImage($fieldName)
    {
        $this->imageTargets[] = $fieldName;

        return $this;
    }

    /**
     * @return self
     */
    public function asThumbs()
    {
        $this->asThumbsCalls++;

        return $this;
    }

    /**
     * @return self
     */
    public function asList()
    {
        $this->asListCalls++;

        return $this;
    }

    /**
     * @param string $html
     * @return self
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param mixed $fileId
     * @return self
     */
    public function setSelected($fileId)
    {
        $this->selected[] = $fileId;

        return $this;
    }
}

class FileFieldFilePickerMock
{
    /** @var FileFieldFilePickerLinkMock */
    public $link;

    /** @var string */
    private $url;

    public function __construct($url = 'picker-url')
    {
        $this->url = $url;
        $this->link = new FileFieldFilePickerLinkMock();
    }

    /**
     * @return FileFieldFilePickerLinkMock
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}

class FileFieldFilePickerFactoryMock
{
    /** @var array<int, mixed> */
    public $allowedDirectoryCalls = [];

    /** @var FileFieldFilePickerMock */
    public $picker;

    public function __construct()
    {
        $this->picker = new FileFieldFilePickerMock();
    }

    /**
     * @param mixed $allowedDirectory
     * @return FileFieldFilePickerMock
     */
    public function make($allowedDirectory)
    {
        $this->allowedDirectoryCalls[] = $allowedDirectory;

        return $this->picker;
    }
}

class FileFieldCpMock
{
    /** @var array<int, array<string, mixed>> */
    public $scripts = [];

    /** @var array<int, string> */
    public $headItems = [];

    /** @var string */
    public $cp_theme_url = 'https://example.com/themes/cp/';

    /**
     * Capture JS script registrations in both legacy call signatures.
     *
     * @param mixed $scriptOrType
     * @param mixed $script
     * @return void
     */
    public function add_js_script($scriptOrType, $script = null)
    {
        if (func_num_args() === 1) {
            $this->scripts[] = $scriptOrType;

            return;
        }

        $this->scripts[] = [
            'type' => $scriptOrType,
            'script' => $script,
        ];
    }

    /**
     * Capture stylesheet link output added to the CP head region.
     *
     * @param string $headItem
     * @return void
     */
    public function add_to_head($headItem)
    {
        $this->headItems[] = $headItem;
    }
}

class FileFieldJavascriptMock
{
    /** @var array<int, array<string, mixed>> */
    public $globals = [];

    /** @var array<int, string> */
    public $readyCalls = [];

    /**
     * @param array<string, mixed> $globals
     * @return void
     */
    public function set_global(array $globals)
    {
        $this->globals[] = $globals;
    }

    /**
     * Capture ready handler registration payloads.
     *
     * @param string $javascript
     * @return void
     */
    public function ready($javascript)
    {
        $this->readyCalls[] = $javascript;
    }
}

class FileFieldLegacyViewMock
{
    /** @var array<int, string> */
    public $headLinks = [];

    /**
     * Return deterministic stylesheet markup for head registration tests.
     *
     * @param string $path
     * @return string
     */
    public function head_link($path)
    {
        $this->headLinks[] = $path;

        return '<head-link path="' . $path . '">';
    }
}

class FileFieldFunctionsMock
{
    /** @var array<int, bool> */
    public $fetchSiteIndexCalls = [];

    /**
     * Return deterministic site index prefixes for URL helper anchor generation.
     *
     * @param bool $includeDomain
     * @return string
     */
    public function fetch_site_index($includeDomain = false)
    {
        $this->fetchSiteIndexCalls[] = (bool) $includeDomain;

        return 'https://example.com/';
    }
}

class FileFieldCompiledUrlMock
{
    /** @var string */
    private $compiled;

    /** @var int */
    public $compileCalls = 0;

    public function __construct($compiled = 'compiled-create-url')
    {
        $this->compiled = $compiled;
    }

    /**
     * @return string
     */
    public function compile()
    {
        $this->compileCalls++;

        return $this->compiled;
    }
}

class FileFieldCpUrlFactoryMock
{
    /** @var array<int, string> */
    public $paths = [];

    /** @var FileFieldCompiledUrlMock */
    public $url;

    public function __construct($compiled = 'compiled-create-url')
    {
        $this->url = new FileFieldCompiledUrlMock($compiled);
    }

    /**
     * @param string $path
     * @return FileFieldCompiledUrlMock
     */
    public function make($path)
    {
        $this->paths[] = $path;

        return $this->url;
    }
}

class FileFieldServiceMock
{
    /** @var int */
    public $loadDragAndDropAssetsCalls = 0;

    /**
     * @return void
     */
    public function loadDragAndDropAssets()
    {
        $this->loadDragAndDropAssetsCalls++;
    }
}

class FileFieldViewFactoryMock
{
    /** @var array<int, string> */
    public $templates = [];

    /** @var array<int, array<string, mixed>> */
    public $renderVars = [];

    /** @var string */
    private $renderResult;

    public function __construct($renderResult = '<rendered>')
    {
        $this->renderResult = $renderResult;
    }

    /**
     * Return a mock view renderer for the requested template.
     *
     * @param string $template
     * @return object
     */
    public function make($template)
    {
        $this->templates[] = $template;

        return new class($this) {
            /** @var FileFieldViewFactoryMock */
            private $factory;

            public function __construct(FileFieldViewFactoryMock $factory)
            {
                $this->factory = $factory;
            }

            /**
             * Capture render vars and return configured marker output.
             *
             * @param array<string, mixed> $vars
             * @return string
             */
            public function render(array $vars)
            {
                $this->factory->renderVars[] = $vars;

                return $this->factory->renderResult();
            }
        };
    }

    /**
     * @return string
     */
    public function renderResult()
    {
        return $this->renderResult;
    }
}

class FileFieldResultRowsMock
{
    /** @var array<int, object> */
    private $rows;

    /**
     * @param array<int, object> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return array<int, object>
     */
    public function result()
    {
        return $this->rows;
    }
}

class FileFieldModelMock
{
    /** @var array<int, array<string, mixed>> */
    public $calls = [];

    /** @var array<string, mixed> */
    private $response;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Return deterministic file query results.
     *
     * @param mixed $directoryId
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function get_files($directoryId, array $options)
    {
        $this->calls[] = [
            'directory_id' => $directoryId,
            'options' => $options,
        ];

        return $this->response;
    }
}

class FileFieldFieldTest extends TestCase
{
    /** @var FileFieldFieldHarness */
    private $subject;

    /** @var FileFieldLoadMock */
    private $loadMock;

    /** @var FileFieldUploadPreferencesMock */
    private $uploadPreferencesMock;

    /** @var FileFieldManagerMock */
    private $fileManagerMock;

    /** @var FileFieldThumbnailMock */
    private $thumbnailMock;

    /** @var FileFieldViewFactoryMock */
    private $viewFactoryMock;

    /** @var FileFieldLegacyViewMock */
    private $legacyViewMock;

    /** @var FileFieldFunctionsMock */
    private $functionsMock;

    /** @var FileFieldModelMock */
    private $fileModelMock;

    /** @var FileFieldLangMock */
    private $langMock;

    /** @var FileFieldModelServiceMock */
    private $modelServiceMock;

    /** @var FileFieldFilePickerFactoryMock */
    private $filePickerFactoryMock;

    /** @var FileFieldCpMock */
    private $cpMock;

    /** @var FileFieldJavascriptMock */
    private $javascriptMock;

    /** @var FileFieldCpUrlFactoryMock */
    private $cpUrlFactoryMock;

    /** @var FileFieldServiceMock */
    private $fileFieldServiceMock;

    /**
     * Load legacy dependencies once for this test class.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (! defined('REQ')) {
            define('REQ', 'PAGE');
        }

        if (! function_exists('\bool_config_item')) {
            eval('
                function bool_config_item($item)
                {
                    $value = function_exists("ee") ? ee()->config->item($item) : false;

                    return $value === "y" || $value === true || $value === 1 || $value === "1";
                }
            ');
        }

        require_once SYSPATH . 'ee/legacy/helpers/form_helper.php';
        require_once SYSPATH . 'ee/legacy/helpers/url_helper.php';
        require_once SYSPATH . 'ee/legacy/helpers/html_helper.php';
    }

    /**
     * Install fresh mocks before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
        $_POST = [];

        $this->loadMock = new FileFieldLoadMock();
        ee()->setMock('load', $this->loadMock);

        $this->uploadPreferencesMock = new FileFieldUploadPreferencesMock([
            '' => 'directory',
            '9' => 'Assets',
        ]);
        ee()->setMock('file_upload_preferences_model', $this->uploadPreferencesMock);

        $this->fileManagerMock = new FileFieldManagerMock();
        ee()->setMock('filemanager', $this->fileManagerMock);

        $this->thumbnailMock = new FileFieldThumbnailMock('generated-thumb-tag');
        ee()->setMock('Thumbnail', $this->thumbnailMock);

        $this->viewFactoryMock = new FileFieldViewFactoryMock('<rendered-output>');
        ee()->setMock('View', $this->viewFactoryMock);

        $this->legacyViewMock = new FileFieldLegacyViewMock();
        ee()->setMock('view', $this->legacyViewMock);

        $this->functionsMock = new FileFieldFunctionsMock();
        ee()->setMock('functions', $this->functionsMock);

        $this->fileModelMock = new FileFieldModelMock([
            'results' => new FileFieldResultRowsMock([]),
        ]);
        ee()->setMock('file_model', $this->fileModelMock);

        $this->langMock = new FileFieldLangMock();
        ee()->setMock('lang', $this->langMock);

        $this->modelServiceMock = new FileFieldModelServiceMock();
        ee()->setMock('Model', $this->modelServiceMock);

        $this->filePickerFactoryMock = new FileFieldFilePickerFactoryMock();
        ee()->setMock('CP/FilePicker', $this->filePickerFactoryMock);

        $this->cpMock = new FileFieldCpMock();
        ee()->setMock('cp', $this->cpMock);

        $this->javascriptMock = new FileFieldJavascriptMock();
        ee()->setMock('javascript', $this->javascriptMock);

        $this->cpUrlFactoryMock = new FileFieldCpUrlFactoryMock('https://example.com/admin.php?/cp/files/uploads/create');
        ee()->setMock('CP/URL', $this->cpUrlFactoryMock);

        $this->fileFieldServiceMock = new FileFieldServiceMock();
        ee()->setMock('file_field', $this->fileFieldServiceMock);

        $this->subject = new FileFieldFieldHarness();
        $this->subject->fileModelReturn = (object) ['file_id' => 123];
    }

    /**
     * Reset singleton mocks after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    /**
     * Ensure parsed legacy data produces expected render vars and hide-state links.
     *
     * @return void
     */
    public function testFieldBuildsViewVariablesFromParsedDataAndExtension(): void
    {
        $this->subject->parseFieldReturn = [
            'filedir' => '{filedir_9}',
            'filename' => 'spec sheet',
            'extension' => 'pdf',
            'upload_location_id' => '9',
            'file_name' => '',
        ];

        $result = $this->subject->field('asset_file', '{filedir_9}spec%20sheet.pdf', 'all', 'image');

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame(['filemanager'], $this->loadMock->libraries);
        $this->assertSame([['html', 'form']], $this->loadMock->helpers);
        $this->assertSame([['file_model', 'file_upload_preferences_model']], $this->loadMock->models);
        $this->assertSame([PATH_THEMES . 'cp/default'], $this->loadMock->packagePaths);
        $this->assertSame(['_shared/file/field'], $this->viewFactoryMock->templates);
        $this->assertSame(
            [['filename' => 'spec sheet.pdf', 'directory_id' => '9']],
            $this->fileManagerMock->calls
        );
        $this->assertEquals([(object) ['file_id' => 123]], $this->thumbnailMock->calls);

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame('spec sheet.pdf', $vars['filename']);
        $this->assertSame('generated-thumb-tag', $vars['thumb']);
        $this->assertSame('spec sheet.pdf', $vars['alt']);
        $this->assertSame('', $vars['allowed_file_dirs']);
        $this->assertSame('', $vars['set_class']);
        $this->assertTrue($vars['filebrowser']);
        $this->assertNull($vars['existing_files']);
        $this->assertStringContainsString('choose_file js_hide', $vars['upload_link']);
        $this->assertStringContainsString('data-directory="all"', $vars['upload_link']);
        $this->assertStringContainsString('name="asset_file_hidden_file"', $vars['hidden']);
        $this->assertStringContainsString('value="spec sheet.pdf"', $vars['hidden']);
    }

    /**
     * Ensure existing-file dropdown uses IDs when compatibility mode is disabled.
     *
     * @return void
     */
    public function testFieldBuildsExistingFileDropdownWithIdsWhenCompatibilityModeOff(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'n');

        $this->uploadPreferencesMock = new FileFieldUploadPreferencesMock([
            '' => 'directory',
            '7' => 'Uploads',
        ]);
        ee()->setMock('file_upload_preferences_model', $this->uploadPreferencesMock);

        $this->fileModelMock = new FileFieldModelMock([
            'results' => new FileFieldResultRowsMock([
                (object) ['file_id' => 12, 'file_name' => 'alpha.jpg'],
                (object) ['file_id' => 13, 'file_name' => 'beta.jpg'],
            ]),
        ]);
        ee()->setMock('file_model', $this->fileModelMock);

        $result = $this->subject->field('feature_file', 'unparseable', 7, 'all', false, 2);

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame(['unparseable'], $this->subject->parseFieldCalls);
        $this->assertSame(
            [[
                'directory_id' => 7,
                'options' => [
                    'order' => ['file_name' => 'asc'],
                    'limit' => 2,
                ],
            ]],
            $this->fileModelMock->calls
        );

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame(7, $vars['upload_location_id']);
        $this->assertSame('js_hide', $vars['set_class']);
        $this->assertFalse($vars['filebrowser']);
        $this->assertStringContainsString('choose_file"', $vars['upload_link']);
        $this->assertStringNotContainsString('js_hide', $vars['upload_link']);
        $this->assertStringContainsString('name="feature_file_existing"', $vars['existing_files']);
        $this->assertStringContainsString('<option value="12">alpha.jpg</option>', $vars['existing_files']);
        $this->assertStringContainsString('<option value="13">beta.jpg</option>', $vars['existing_files']);
    }

    /**
     * Ensure compatibility mode keys by filename and legacy file_name fills empty filename.
     *
     * @return void
     */
    public function testFieldUsesFilenameKeysInCompatibilityModeAndLegacyFileNameFallback(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'y');

        $this->subject->parseFieldReturn = [
            'filedir' => '',
            'filename' => '',
            'upload_location_id' => '5',
            'file_name' => 'legacy name.png',
        ];

        $this->uploadPreferencesMock = new FileFieldUploadPreferencesMock([
            '' => 'directory',
        ]);
        ee()->setMock('file_upload_preferences_model', $this->uploadPreferencesMock);

        $this->fileModelMock = new FileFieldModelMock([
            'results' => new FileFieldResultRowsMock([
                (object) ['file_id' => 88, 'file_name' => 'legacy name.png'],
            ]),
        ]);
        ee()->setMock('file_model', $this->fileModelMock);

        $result = $this->subject->field('legacy_file', '{filedir_5}legacy%20name.png', 5, 'all', false, 0);

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame(
            [[
                'directory_id' => 5,
                'options' => [
                    'order' => ['file_name' => 'asc'],
                ],
            ]],
            $this->fileModelMock->calls
        );

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame('legacy name.png', $vars['filename']);
        $this->assertSame('directory_no_access', $vars['upload_link']);
        $this->assertStringContainsString('value="legacy name.png"', $vars['hidden']);
        $this->assertStringContainsString('name="legacy_file_existing"', $vars['existing_files']);
        $this->assertStringContainsString('<option value="legacy name.png">legacy name.png</option>', $vars['existing_files']);
        $this->assertStringNotContainsString('<option value="88">legacy name.png</option>', $vars['existing_files']);
    }

    /**
     * Ensure empty query results still return the default "select existing" option.
     *
     * @return void
     */
    public function testFieldBuildsExistingDropdownWithDefaultOptionWhenNoFilesReturned(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'n');

        $this->uploadPreferencesMock = new FileFieldUploadPreferencesMock([
            '' => 'directory',
            '4' => 'Media',
        ]);
        ee()->setMock('file_upload_preferences_model', $this->uploadPreferencesMock);

        $this->fileModelMock = new FileFieldModelMock([
            'results' => false,
        ]);
        ee()->setMock('file_model', $this->fileModelMock);

        $result = $this->subject->field('empty_existing', '', 4, 'all', false, 0);

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame([], $this->subject->parseFieldCalls);
        $this->assertSame(
            [[
                'directory_id' => 4,
                'options' => [
                    'order' => ['file_name' => 'asc'],
                ],
            ]],
            $this->fileModelMock->calls
        );

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertStringContainsString('name="empty_existing_existing"', $vars['existing_files']);
        $this->assertSame(1, substr_count($vars['existing_files'], '<option value='));
        $this->assertStringContainsString('<option value="">file_ft_select_existing</option>', $vars['existing_files']);
    }

    /**
     * Ensure empty field data short-circuits without touching model lookup.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataReturnsNullForEmptyData(): void
    {
        $subject = new \File_field();

        $this->assertNull($subject->getFileModelForFieldData(''));
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
    }

    /**
     * Ensure numeric file IDs use direct model fetch with UploadDestination eager loading.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataLoadsNumericFileId(): void
    {
        $subject = new \File_field();
        $expected = (object) ['file_id' => 42];
        $this->modelServiceMock->fileResultsById['42'] = $expected;

        $result = $subject->getFileModelForFieldData('42');

        $this->assertSame($expected, $result);
        $this->assertSame([['model' => 'File', 'id' => '42']], $this->modelServiceMock->calls);
        $this->assertSame([['model' => 'File', 'id' => '42', 'relation' => 'UploadDestination']], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => '42',
                'with' => ['UploadDestination'],
                'filters' => [],
                'as_model' => true,
            ]],
            $this->modelServiceMock->firstCalls
        );
    }

    /**
     * Ensure `{file:id:url}` payloads extract and query by embedded file id.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataLoadsFileTokenId(): void
    {
        $subject = new \File_field();
        $expected = (object) ['file_id' => 77];
        $this->modelServiceMock->fileResultsById['77'] = $expected;

        $result = $subject->getFileModelForFieldData('{file:77:url}');

        $this->assertSame($expected, $result);
        $this->assertSame([['model' => 'File', 'id' => '77']], $this->modelServiceMock->calls);
        $this->assertSame([['model' => 'File', 'id' => '77', 'relation' => 'UploadDestination']], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => '77',
                'with' => ['UploadDestination'],
                'filters' => [],
                'as_model' => true,
            ]],
            $this->modelServiceMock->firstCalls
        );
    }

    /**
     * Ensure filedir payloads query by filename, upload directory, and current site id.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataLoadsFiledirPayloadUsingSiteScopedFilters(): void
    {
        $subject = new \File_field();
        $expected = (object) ['file_name' => 'spec sheet.pdf'];
        ee()->config->setItem('site_id', 51);

        $filters = [
            ['field' => 'file_name', 'operator' => '=', 'value' => 'spec sheet.pdf'],
            ['field' => 'upload_location_id', 'operator' => '=', 'value' => '9'],
            ['field' => 'site_id', 'operator' => '=', 'value' => 51],
        ];
        $this->modelServiceMock->fileResultsByFilterKey[$this->modelServiceMock->buildFileFilterKey($filters)] = $expected;

        $result = $subject->getFileModelForFieldData('{filedir_9}spec sheet.pdf');

        $this->assertSame($expected, $result);
        $this->assertSame([['model' => 'File', 'id' => null]], $this->modelServiceMock->calls);
        $this->assertSame([['model' => 'File', 'id' => null, 'relation' => 'UploadDestination']], $this->modelServiceMock->withCalls);
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => '=',
                'value' => 'spec sheet.pdf',
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => '=',
                'value' => '9',
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'site_id',
                'operator' => '=',
                'value' => 51,
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => $filters,
                'as_model' => true,
            ]],
            $this->modelServiceMock->firstCalls
        );
    }

    /**
     * Ensure unsupported values return null and avoid accidental model lookups.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataReturnsNullForUnsupportedInput(): void
    {
        $subject = new \File_field();

        $this->assertNull($subject->getFileModelForFieldData('manual.pdf'));
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
    }

    /**
     * Ensure zero-like values follow the empty guard and avoid querying by id zero.
     *
     * @return void
     */
    public function testGetFileModelForFieldDataTreatsZeroStringAsEmpty(): void
    {
        $subject = new \File_field();

        $this->assertNull($subject->getFileModelForFieldData('0'));
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
    }

    /**
     * Ensure default browser initialization enables publish mode and loads shared assets.
     *
     * @return void
     */
    public function testBrowserWithDefaultConfigEnablesPublishModeAndLoadsAssets(): void
    {
        $this->subject->browser();

        $this->assertSame(['content'], $this->langMock->loaded);
        $this->assertSame(['css/file_browser.css'], $this->legacyViewMock->headLinks);
        $this->assertSame(['<head-link path="css/file_browser.css">'], $this->cpMock->headItems);
        $this->assertSame(
            [
                ['type' => 'plugin', 'script' => ['tmpl', 'ee_table']],
                [
                    'file' => ['vendor/underscore', 'files/publish_fields'],
                    'plugin' => ['ee_filebrowser', 'ee_fileuploader', 'tmpl'],
                ],
            ],
            $this->cpMock->scripts
        );
        $this->assertSame(['html'], $this->loadMock->helpers);
        $this->assertCount(2, $this->javascriptMock->globals);
        $this->assertSame(['filebrowser' => ['publish' => true]], $this->javascriptMock->globals[0]);
        $this->assertSame('addons/settings/filepicker/modal', $this->javascriptMock->globals[1]['filebrowser']['endpoint_url']);
        $this->assertSame([], $this->javascriptMock->readyCalls);
    }

    /**
     * Ensure trigger/callback browser config registers ready JS with optional field and settings arguments.
     *
     * @return void
     */
    public function testBrowserWithTriggerCallbackFieldAndSettingsRegistersReadyTrigger(): void
    {
        $this->subject->browser([
            'publish' => false,
            'trigger' => '.js-open-file-browser',
            'field_name' => 'hero_file',
            'settings' => '{"content_type":"image","directory":"7"}',
            'callback' => 'function(file, field) { window.fileBrowserHit = true; }',
        ], 'custom/filepicker/modal');

        $this->assertSame(['content'], $this->langMock->loaded);
        $this->assertCount(1, $this->javascriptMock->readyCalls);
        $this->assertStringContainsString(
            "$.ee_filebrowser.add_trigger('.js-open-file-browser', 'hero_file',{\"content_type\":\"image\",\"directory\":\"7\"}, function(file, field) { window.fileBrowserHit = true; });",
            $this->javascriptMock->readyCalls[0]
        );
        $this->assertCount(1, $this->javascriptMock->globals);
        $this->assertSame('custom/filepicker/modal', $this->javascriptMock->globals[0]['filebrowser']['endpoint_url']);
    }

    /**
     * Ensure trigger/callback mode omits optional arguments when field name and settings are not provided.
     *
     * @return void
     */
    public function testBrowserWithTriggerCallbackOmitsOptionalArgumentsWhenUnset(): void
    {
        $this->subject->browser([
            'trigger' => '#picker-trigger',
            'callback' => 'function(file, field) { return file; }',
        ]);

        $this->assertCount(1, $this->javascriptMock->readyCalls);
        $this->assertStringContainsString(
            "$.ee_filebrowser.add_trigger('#picker-trigger', function(file, field) { return file; });",
            $this->javascriptMock->readyCalls[0]
        );
        $this->assertCount(1, $this->javascriptMock->globals);
        $this->assertSame('addons/settings/filepicker/modal', $this->javascriptMock->globals[0]['filebrowser']['endpoint_url']);
    }

    /**
     * Ensure invalid config returns early without loading browser assets.
     *
     * @return void
     */
    public function testBrowserReturnsEarlyWhenConfigIsMissingRequiredTriggerOrCallback(): void
    {
        $this->subject->browser([
            'publish' => 1,
            'trigger' => '.missing-callback',
        ]);

        $this->assertSame(['content'], $this->langMock->loaded);
        $this->assertSame([], $this->javascriptMock->globals);
        $this->assertSame([], $this->javascriptMock->readyCalls);
        $this->assertSame([], $this->cpMock->headItems);
        $this->assertSame([], $this->cpMock->scripts);
        $this->assertSame([], $this->loadMock->helpers);
    }

    /**
     * Ensure callback-only config also returns early when trigger is missing.
     *
     * @return void
     */
    public function testBrowserReturnsEarlyWhenConfigIsMissingTrigger(): void
    {
        $this->subject->browser([
            'callback' => 'function(file, field) { return field; }',
        ]);

        $this->assertSame(['content'], $this->langMock->loaded);
        $this->assertSame([], $this->javascriptMock->globals);
        $this->assertSame([], $this->javascriptMock->readyCalls);
        $this->assertSame([], $this->cpMock->headItems);
        $this->assertSame([], $this->cpMock->scripts);
        $this->assertSame([], $this->loadMock->helpers);
    }

    /**
     * Ensure specific-directory mode uses model lookup, thumb modal view, and existing file metadata.
     *
     * @return void
     */
    public function testDragAndDropFieldUsesSpecificDirectoryThumbViewAndExistingFile(): void
    {
        $this->modelServiceMock->uploadDestinationsById[6] = new FileFieldUploadDestinationMock(6, 0, 'thumb', true);
        $this->subject->fileModelReturn = new class {
            /** @var int */
            public $file_id = 77;

            /** @var string */
            public $title = 'Hero Banner';

            /**
             * @return bool
             */
            public function isImage()
            {
                return true;
            }
        };

        $result = $this->subject->dragAndDropField('hero_image', '{filedir_6}banner.png', '6', 'image');

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame(['fieldtypes'], $this->langMock->loaded);
        $this->assertSame([['model' => 'UploadDestination', 'id' => '6']], $this->modelServiceMock->calls);
        $this->assertSame(['6'], $this->filePickerFactoryMock->allowedDirectoryCalls);
        $this->assertSame(['hero_image'], $this->filePickerFactoryMock->picker->link->valueTargets);
        $this->assertSame(['hero_image'], $this->filePickerFactoryMock->picker->link->nameTargets);
        $this->assertSame(['hero_image'], $this->filePickerFactoryMock->picker->link->imageTargets);
        $this->assertSame(1, $this->filePickerFactoryMock->picker->link->asThumbsCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asListCalls);
        $this->assertSame([77], $this->viewFactoryMock->renderVars[0]['fp_edit']->selected);
        $this->assertSame('file-field-filepicker button button--default', $this->viewFactoryMock->renderVars[0]['fp_edit']->attributes['class']);
        $this->assertSame(['files/uploads/create'], $this->cpUrlFactoryMock->paths);
        $this->assertSame(1, $this->cpUrlFactoryMock->url->compileCalls);
        $this->assertSame(1, $this->fileFieldServiceMock->loadDragAndDropAssetsCalls);
        $this->assertSame(
            [[
                'file' => [
                    'fields/file/control_panel',
                    'fields/file/file_field_drag_and_drop',
                    'cp/publish/entry-list',
                ],
            ]],
            $this->cpMock->scripts
        );
        $this->assertSame(
            [[
                'fileManager.fileDirectory.createUrl' => 'https://example.com/admin.php?/cp/files/uploads/create',
            ]],
            $this->javascriptMock->globals
        );

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame(['file:publish'], $this->viewFactoryMock->templates);
        $this->assertSame('hero_image', $vars['field_name']);
        $this->assertSame('{filedir_6}banner.png', $vars['value']);
        $this->assertSame('Hero Banner', $vars['title']);
        $this->assertSame('6', $vars['allowed_directory']);
        $this->assertSame([], $vars['role_allowed_dirs']);
        $this->assertSame('image', $vars['content_type']);
        $this->assertTrue($vars['is_image']);
        $this->assertSame('thumb-url', $vars['thumbnail']);
    }

    /**
     * Ensure all-directory mode narrows to one role-allowed directory and keeps role metadata.
     *
     * @return void
     */
    public function testDragAndDropFieldNarrowsAllToSingleRoleAllowedDirectory(): void
    {
        $disallowedByModule = new FileFieldUploadDestinationMock(2, 10, 'list', true);
        $disallowedByRole = new FileFieldUploadDestinationMock(4, 0, 'thumb', false);
        $onlyAllowed = new FileFieldUploadDestinationMock(9, 0, 'list', true);
        $this->modelServiceMock->uploadDestinationsAll = [$disallowedByModule, $disallowedByRole, $onlyAllowed];
        $this->subject->fileModelReturn = null;

        $result = $this->subject->dragAndDropField('asset_file', '{filedir_9}brochure.pdf', 'all', 'all');

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame([['model' => 'UploadDestination', 'id' => null]], $this->modelServiceMock->calls);
        $this->assertSame([['field' => 'name', 'direction' => 'asc']], $this->modelServiceMock->orderCalls);
        $this->assertSame([9], $this->filePickerFactoryMock->allowedDirectoryCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asThumbsCalls);
        $this->assertSame(1, $this->filePickerFactoryMock->picker->link->asListCalls);

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame('brochure.pdf', $vars['title']);
        $this->assertSame(9, $vars['allowed_directory']);
        $this->assertSame([9], $vars['role_allowed_dirs']);
        $this->assertFalse($vars['is_image']);
        $this->assertSame('thumb-url', $vars['thumbnail']);
    }

    /**
     * Ensure all-directory mode with multiple allowed directories keeps the picker on "all".
     *
     * @return void
     */
    public function testDragAndDropFieldKeepsAllDirectoryWhenRoleHasMultipleAllowedDirectories(): void
    {
        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(4, 0, 'thumb', true),
            new FileFieldUploadDestinationMock(5, 0, 'list', true),
            new FileFieldUploadDestinationMock(8, 2, 'list', true),
        ];
        $this->subject->fileModelReturn = null;

        $result = $this->subject->dragAndDropField('gallery_file', '{filedir_4}poster.jpg', 'all', 'all');

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame(['all'], $this->filePickerFactoryMock->allowedDirectoryCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asThumbsCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asListCalls);

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame('poster.jpg', $vars['title']);
        $this->assertSame('all', $vars['allowed_directory']);
        $this->assertSame([4, 5], $vars['role_allowed_dirs']);
    }

    /**
     * Ensure invalid specific directory ids gracefully fall back to "all" without a modal view hint.
     *
     * @return void
     */
    public function testDragAndDropFieldFallsBackToAllWhenSpecificDirectoryIsUnavailable(): void
    {
        $this->subject->fileModelReturn = null;

        $result = $this->subject->dragAndDropField('fallback_file', 'manual.pdf', '12', 'all');

        $this->assertSame('<rendered-output>', $result);
        $this->assertSame([['model' => 'UploadDestination', 'id' => '12']], $this->modelServiceMock->calls);
        $this->assertSame(['all'], $this->filePickerFactoryMock->allowedDirectoryCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asThumbsCalls);
        $this->assertSame(0, $this->filePickerFactoryMock->picker->link->asListCalls);

        $vars = $this->viewFactoryMock->renderVars[0];
        $this->assertSame('manual.pdf', $vars['title']);
        $this->assertSame('all', $vars['allowed_directory']);
        $this->assertSame([], $vars['role_allowed_dirs']);
    }
}
