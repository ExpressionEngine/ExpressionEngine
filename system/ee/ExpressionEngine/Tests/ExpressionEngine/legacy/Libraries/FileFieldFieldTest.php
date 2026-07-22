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

class FileFieldParseFieldHarness extends \File_field
{
    /** @var mixed */
    public $getFileReturn = null;

    /** @var array<int, array<string, mixed>> */
    public $getFileCalls = [];

    /**
     * Return deterministic file records for parse_field branch coverage.
     *
     * @param mixed $file_reference
     * @param mixed $dir_id
     * @return mixed
     */
    public function get_file($file_reference = null, $dir_id = null)
    {
        $this->getFileCalls[] = [
            'file_reference' => $file_reference,
            'dir_id' => $dir_id,
        ];

        if (is_callable($this->getFileReturn)) {
            return call_user_func($this->getFileReturn, $file_reference, $dir_id);
        }

        return $this->getFileReturn;
    }
}

class FileFieldParseFilesystemMock
{
    /** @var array<int, mixed> */
    public $urlCalls = [];

    /** @var array<int, string> */
    public $existsCalls = [];

    /** @var array<int, string> */
    public $sizeCalls = [];

    /** @var array<string, bool> */
    public $existingPaths = [];

    /** @var array<string, int> */
    public $sizesByPath = [];

    /** @var array<string, bool> */
    public $throwOnPath = [];

    /** @var string */
    private $baseUrl;

    /**
     * @param string $baseUrl
     */
    public function __construct($baseUrl = 'https://cdn.example.com/uploads/')
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Return a base URL or file URL for parse_field path generation.
     *
     * @param string|null $path
     * @return string
     */
    public function getUrl($path = null)
    {
        $this->urlCalls[] = $path;
        $normalizedPath = is_null($path) ? '__base__' : (string) $path;

        if (! empty($this->throwOnPath[$normalizedPath])) {
            throw new \RuntimeException('URL resolution failed for ' . $normalizedPath);
        }

        if (is_null($path)) {
            return $this->baseUrl;
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim((string) $path, '/');
    }

    /**
     * Return configured existence checks for manipulation absolute paths.
     *
     * @param string $path
     * @return bool
     */
    public function exists($path)
    {
        $this->existsCalls[] = $path;

        return ! empty($this->existingPaths[(string) $path]);
    }

    /**
     * Return deterministic size values for manipulated outputs.
     *
     * @param string $path
     * @return int
     */
    public function getSize($path)
    {
        $this->sizeCalls[] = $path;

        if (isset($this->sizesByPath[(string) $path])) {
            return $this->sizesByPath[(string) $path];
        }

        return 0;
    }
}

class FileFieldParseUploadDestinationMock
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $pre_format = 'pre-format';

    /** @var string */
    public $post_format = 'post-format';

    /** @var string */
    public $file_pre_format = 'file-pre-format';

    /** @var string */
    public $file_post_format = 'file-post-format';

    /** @var array<int, string> */
    public $properties = ['resize', 'crop'];

    /** @var array<int, string> */
    public $file_properties = ['download'];

    /** @var FileFieldParseFilesystemMock */
    private $filesystem;

    /**
     * @param int $id
     * @param string $name
     * @param FileFieldParseFilesystemMock $filesystem
     */
    public function __construct($id, $name, FileFieldParseFilesystemMock $filesystem)
    {
        $this->id = $id;
        $this->name = $name;
        $this->filesystem = $filesystem;
    }

    /**
     * Return filesystem implementation used by parse_field.
     *
     * @return FileFieldParseFilesystemMock
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

class FileFieldParseModelObjectMock
{
    /** @var array<int, string> */
    public $absoluteManipulationPathCalls = [];

    /** @var array<string, string> */
    private $manipulationPaths;

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $absoluteUrl;

    /** @var string */
    private $absolutePath;

    /**
     * @param string $baseUrl
     * @param string $absoluteUrl
     * @param string $absolutePath
     * @param array<string, string> $manipulationPaths
     */
    public function __construct($baseUrl, $absoluteUrl, $absolutePath, array $manipulationPaths = [])
    {
        $this->baseUrl = $baseUrl;
        $this->absoluteUrl = $absoluteUrl;
        $this->absolutePath = $absolutePath;
        $this->manipulationPaths = $manipulationPaths;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getAbsoluteURL()
    {
        return $this->absoluteUrl;
    }

    /**
     * @param string $shortName
     * @return string
     */
    public function getAbsoluteManipulationPath($shortName)
    {
        $this->absoluteManipulationPathCalls[] = $shortName;

        if (isset($this->manipulationPaths[$shortName])) {
            return $this->manipulationPaths[$shortName];
        }

        return $this->absolutePath;
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }
}

class FileFieldParseManipulationMock
{
    /** @var string */
    public $short_name;

    /** @var int */
    public $width;

    /** @var int */
    public $height;

    /** @var array<string, int>|null */
    private $dimensions;

    /** @var array<int, mixed> */
    public $calls = [];

    /**
     * @param string $shortName
     * @param int $width
     * @param int $height
     * @param array<string, int>|null $dimensions
     */
    public function __construct($shortName, $width, $height, $dimensions = null)
    {
        $this->short_name = $shortName;
        $this->width = $width;
        $this->height = $height;
        $this->dimensions = $dimensions;
    }

    /**
     * Return configured manipulated dimensions for the provided file model.
     *
     * @param mixed $fileModel
     * @return array<string, int>|null
     */
    public function getNewDimensionsOfFile($fileModel)
    {
        $this->calls[] = $fileModel;

        return $this->dimensions;
    }
}

class FileFieldFormatNumberMock
{
    /** @var int */
    private $value;

    /** @var array<int, bool> */
    public $bytesCalls = [];

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return deterministic byte formatting for parse_field assertions.
     *
     * @param bool $short
     * @return string
     */
    public function bytes($short = true)
    {
        $this->bytesCalls[] = (bool) $short;

        if ($short) {
            return (string) $this->value . 'B';
        }

        return (string) $this->value . ' bytes';
    }
}

class FileFieldFormatFactoryMock
{
    /** @var array<int, array<string, mixed>> */
    public $calls = [];

    /**
     * Create deterministic formatter instances for parse_field size output.
     *
     * @param string $format
     * @param int $value
     * @return FileFieldFormatNumberMock
     */
    public function make($format, $value)
    {
        $this->calls[] = [
            'format' => $format,
            'value' => $value,
        ];

        return new FileFieldFormatNumberMock((int) $value);
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

    /** @var array<int, array<string, mixed>> */
    public $uploadFileCalls = [];

    /** @var array<string, mixed> */
    public $uploadFileResponse = [];

    /** @var int */
    public $validatePostDataCalls = 0;

    /** @var bool */
    public $validatePostDataReturn = true;

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

    /**
     * Return configurable upload payload for upload branch coverage.
     *
     * @param mixed $directoryId
     * @param string $fieldName
     * @return array<string, mixed>
     */
    public function upload_file($directoryId, $fieldName)
    {
        $this->uploadFileCalls[] = [
            'directory_id' => $directoryId,
            'field_name' => $fieldName,
        ];

        return $this->uploadFileResponse;
    }

    /**
     * Return configurable post validation state for overflow checks.
     *
     * @return bool
     */
    public function validate_post_data()
    {
        $this->validatePostDataCalls++;

        return $this->validatePostDataReturn;
    }
}

class FileFieldInputMock
{
    /** @var array<string, mixed> */
    public $postValues = [];

    /** @var array<int, string> */
    public $postCalls = [];

    /**
     * Return configured POST values by key.
     *
     * @param string $key
     * @return mixed
     */
    public function post($key)
    {
        $this->postCalls[] = $key;

        if (array_key_exists($key, $this->postValues)) {
            return $this->postValues[$key];
        }

        return null;
    }
}

class FileFieldDbMock extends \eeDbArMock
{
    /** @var array<int, string> */
    public $selectCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $whereCalls = [];

    /** @var array<int, mixed> */
    public $getCalls = [];

    /**
     * Track selected columns for legacy validation queries.
     *
     * @param mixed $field
     * @return $this
     */
    public function select($field = null)
    {
        $this->selectCalls[] = $field;

        return parent::select();
    }

    /**
     * Track where filters used for entry/grid validation lookups.
     *
     * @param mixed $field
     * @param mixed $value
     * @return $this
     */
    public function where($field = null, $value = null)
    {
        if (is_array($field)) {
            foreach ($field as $column => $columnValue) {
                $this->whereCalls[] = [
                    'field' => $column,
                    'value' => $columnValue,
                ];
            }
        }

        if (! is_array($field)) {
            $this->whereCalls[] = [
                'field' => $field,
                'value' => $value,
            ];
        }

        return parent::where($field, $value);
    }

    /**
     * Track table names used for entry/grid fallback queries.
     *
     * @param mixed $table
     * @return \eeDbResultMock
     */
    public function get($table = null)
    {
        $this->getCalls[] = $table;

        return parent::get();
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
     * Track requested language loads.
     *
     * @param string $file Language file alias.
     * @return void
     */
    public function load($file)
    {
        $this->loaded[] = $file;
    }

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
     * Return configured query results for `all()` calls.
     *
     * @return mixed
     */
    public function all()
    {
        if ($this->modelName === 'UploadDestination') {
            return new FileFieldCollectionMock($this->service->uploadDestinationsAll);
        }

        if ($this->modelName !== 'File') {
            return [];
        }

        $this->service->allCalls[] = [
            'model' => $this->modelName,
            'id' => $this->id,
            'with' => $this->withRelations,
            'filters' => $this->filters,
        ];
        $key = $this->service->buildFileFilterKey($this->filters);

        if (! array_key_exists($key, $this->service->fileResultsAllByFilterKey)) {
            return [];
        }

        return $this->service->fileResultsAllByFilterKey[$key];
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

    /** @var array<int, array<string, mixed>> */
    public $allCalls = [];

    /** @var array<int, FileFieldUploadDestinationMock> */
    public $uploadDestinationsById = [];

    /** @var array<int, FileFieldUploadDestinationMock> */
    public $uploadDestinationsAll = [];

    /** @var array<string, mixed> */
    public $fileResultsById = [];

    /** @var array<string, mixed> */
    public $fileResultsByFilterKey = [];

    /** @var array<string, array<int, mixed>> */
    public $fileResultsAllByFilterKey = [];

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

class FileFieldCachedModelRecordMock
{
    /** @var array<string, mixed> */
    private $attributes;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Return normalized model attributes for File_field cache merging.
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return $this->attributes;
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

    /** @var FileFieldInputMock */
    private $inputMock;

    /** @var FileFieldDbMock */
    private $dbMock;

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

    /** @var FileFieldFormatFactoryMock */
    private $formatFactoryMock;

    /**
     * Load legacy dependencies once for this test class.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (! defined('REQ')) {
            define('REQ', 'CP');
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
        $_FILES = [];

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
        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(9),
        ];

        $this->inputMock = new FileFieldInputMock();
        ee()->setMock('input', $this->inputMock);

        $this->dbMock = new FileFieldDbMock();
        ee()->setMock('db', $this->dbMock);

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

        $this->formatFactoryMock = new FileFieldFormatFactoryMock();
        ee()->setMock('Format', $this->formatFactoryMock);

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
     * Configure default POST payload for validate branch tests.
     *
     * @param string $fieldName
     * @param array<string, mixed> $overrides
     * @return void
     */
    private function setValidatePostValues($fieldName, array $overrides = []): void
    {
        $_FILES = [];

        $defaults = [
            'entry_id' => 0,
            $fieldName . '_directory' => '9',
            $fieldName . '_existing' => '',
            $fieldName . '_hidden_file' => '',
            $fieldName . '_hidden_dir' => '9',
        ];
        $this->inputMock->postValues = array_merge($defaults, $overrides);
    }

    /**
     * Install deterministic directory path lookups for parse_string coverage tests.
     *
     * @param array<string|int, string> $paths
     * @return void
     */
    private function setParseStringUploadPaths(array $paths): void
    {
        ee()->setMock('file_upload_preferences_model', new class($paths) {
            /** @var array<string|int, string> */
            private $paths;

            /**
             * @param array<string|int, string> $paths
             */
            public function __construct(array $paths)
            {
                $this->paths = $paths;
            }

            /**
             * @return array<string|int, string>
             */
            public function get_paths()
            {
                return $this->paths;
            }
        });
    }

    /**
     * Build lightweight file model objects used by parse_string file token tests.
     *
     * @param int $fileId
     * @param array<string, mixed> $fieldValues
     * @param array<int, string> $fields
     * @param string $absoluteUrl
     * @return object
     */
    private function makeParseStringFileModel(int $fileId, array $fieldValues, array $fields, string $absoluteUrl): object
    {
        return new class($fileId, $fieldValues, $fields, $absoluteUrl) {
            /** @var int */
            public $file_id;

            /** @var array<string, mixed> */
            private $fieldValues;

            /** @var array<int, string> */
            private $fields;

            /** @var string */
            private $absoluteUrl;

            /**
             * @param int $fileId
             * @param array<string, mixed> $fieldValues
             * @param array<int, string> $fields
             * @param string $absoluteUrl
             */
            public function __construct(int $fileId, array $fieldValues, array $fields, string $absoluteUrl)
            {
                $this->file_id = $fileId;
                $this->fieldValues = $fieldValues;
                $this->fields = $fields;
                $this->absoluteUrl = $absoluteUrl;
            }

            /**
             * @return array<int, string>
             */
            public function getFields(): array
            {
                return $this->fields;
            }

            /**
             * @return string
             */
            public function getAbsoluteURL(): string
            {
                return $this->absoluteUrl;
            }

            /**
             * @param string $name
             * @return mixed
             */
            public function __get(string $name)
            {
                return $this->fieldValues[$name] ?? null;
            }
        };
    }

    /**
     * Seed upload preference cache to avoid unrelated model calls in targeted tests.
     *
     * @param mixed $uploadPreferences
     * @return void
     */
    private function setUploadPreferenceCache($uploadPreferences): void
    {
        $uploadPrefsProperty = new \ReflectionProperty(\File_field::class, '_upload_prefs');
        \TestReflectionHelper::makeAccessible($uploadPrefsProperty);
        $uploadPrefsProperty->setValue($this->subject, $uploadPreferences);
    }

    /**
     * Build upload destination doubles used by loadDragAndDropAssets() branch tests.
     *
     * @param int $id
     * @param int $siteId
     * @param int $moduleId
     * @param string $name
     * @param array<string|int, string> $dropdown
     * @return object
     */
    private function makeDragAndDropUploadPreference(
        int $id,
        int $siteId,
        int $moduleId,
        string $name,
        array $dropdown
    ): object {
        return new class($id, $siteId, $moduleId, $name, $dropdown) {
            /** @var int */
            public $id;

            /** @var int */
            public $site_id;

            /** @var int */
            public $module_id;

            /** @var string */
            public $name;

            /** @var int */
            public $getDirectoriesDropdownCalls = 0;

            /** @var array<string|int, string> */
            private $dropdown;

            /**
             * @param int $id
             * @param int $siteId
             * @param int $moduleId
             * @param string $name
             * @param array<string|int, string> $dropdown
             */
            public function __construct(int $id, int $siteId, int $moduleId, string $name, array $dropdown)
            {
                $this->id = $id;
                $this->site_id = $siteId;
                $this->module_id = $moduleId;
                $this->name = $name;
                $this->dropdown = $dropdown;
            }

            /**
             * @return array<string|int, string>
             */
            public function getDirectoriesDropdown(): array
            {
                $this->getDirectoriesDropdownCalls++;

                return $this->dropdown;
            }
        };
    }

    /**
     * Override collaborators required by loadDragAndDropAssets() endpoint/global setup.
     *
     * @param bool $canAccessFiles
     * @return array<string, mixed>
     */
    private function setLoadDragAndDropCollaborators(bool $canAccessFiles): array
    {
        $permission = new class($canAccessFiles) {
            /** @var array<int, string> */
            public $hasCalls = [];

            /** @var bool */
            private $canAccessFiles;

            public function __construct(bool $canAccessFiles)
            {
                $this->canAccessFiles = $canAccessFiles;
            }

            /**
             * @param string $permission
             * @return bool
             */
            public function has(string $permission): bool
            {
                $this->hasCalls[] = $permission;

                return $this->canAccessFiles;
            }
        };
        ee()->setMock('Permission', $permission);

        $viewHelpers = new class {
            /** @var array<int, array<int|string, mixed>> */
            public $normalizedChoicesCalls = [];

            /**
             * @param array<int|string, mixed> $choices
             * @return array<int|string, mixed>
             */
            public function normalizedChoices(array $choices): array
            {
                $this->normalizedChoicesCalls[] = $choices;

                return $choices;
            }
        };
        ee()->setMock('View/Helpers', $viewHelpers);

        $cpUrlFactory = new class {
            /** @var array<int, string> */
            public $paths = [];

            /** @var array<int, string> */
            public $compilePaths = [];

            /**
             * @param string $path
             * @return object
             */
            public function make(string $path): object
            {
                $this->paths[] = $path;

                return new class($path, $this) {
                    /** @var string */
                    private $path;

                    /** @var object */
                    private $factory;

                    public function __construct(string $path, $factory)
                    {
                        $this->path = $path;
                        $this->factory = $factory;
                    }

                    /**
                     * @return string
                     */
                    public function compile(): string
                    {
                        $this->factory->compilePaths[] = $this->path;

                        return 'compiled://' . $this->path;
                    }
                };
            }
        };
        ee()->setMock('CP/URL', $cpUrlFactory);

        $filePickerFactory = new class {
            /** @var array<int, string> */
            public $allowedDirectoryCalls = [];

            /** @var int */
            public $compileCalls = 0;

            /**
             * @param string $allowedDirectory
             * @return object
             */
            public function make(string $allowedDirectory): object
            {
                $this->allowedDirectoryCalls[] = $allowedDirectory;

                return new class($this) {
                    /** @var object */
                    private $factory;

                    public function __construct($factory)
                    {
                        $this->factory = $factory;
                    }

                    /**
                     * @return object
                     */
                    public function getUrl(): object
                    {
                        return new class($this->factory) {
                            /** @var object */
                            private $factory;

                            public function __construct($factory)
                            {
                                $this->factory = $factory;
                            }

                            /**
                             * @return string
                             */
                            public function compile(): string
                            {
                                $this->factory->compileCalls++;

                                return 'compiled://filepicker/all';
                            }
                        };
                    }
                };
            }
        };
        ee()->setMock('CP/FilePicker', $filePickerFactory);

        return [
            'permission' => $permission,
            'view_helpers' => $viewHelpers,
            'cp_url_factory' => $cpUrlFactory,
            'file_picker_factory' => $filePickerFactory,
        ];
    }

    /**
     * Ensure numeric file identifiers are normalized to `{file:id:url}` tokens when compatibility mode is disabled.
     *
     * @param string $fileId
     * @dataProvider formatDataNumericFileIdProvider
     * @return void
     */
    public function testFormatDataReturnsFileIdTokenWhenCompatibilityModeIsDisabled(string $fileId): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'n');

        $result = $this->subject->format_data($fileId);

        $this->assertSame('{file:' . $fileId . ':url}', $result);
    }

    /**
     * Provide numeric IDs that should preserve token formatting, including zero-value boundaries.
     *
     * @return array<string, array<int, string>>
     */
    public function formatDataNumericFileIdProvider(): array
    {
        return [
            'standard numeric id' => ['42'],
            'zero numeric id' => ['0'],
        ];
    }

    /**
     * Ensure an explicit directory ID produces legacy `{filedir_n}` payloads.
     *
     * @return void
     */
    public function testFormatDataReturnsFiledirPayloadWhenDirectoryIsProvided(): void
    {
        $result = $this->subject->format_data('brochure.pdf', '9');

        $this->assertSame('{filedir_9}brochure.pdf', $result);
    }

    /**
     * Ensure compatibility mode keeps numeric values unchanged when no directory context is available.
     *
     * @return void
     */
    public function testFormatDataReturnsRawFileNameWhenCompatibilityModeIsEnabledAndDirectoryIsMissing(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'y');

        $result = $this->subject->format_data('42', 0);

        $this->assertSame('42', $result);
    }

    /**
     * Ensure empty file names keep the legacy null return contract.
     *
     * @return void
     */
    public function testFormatDataReturnsNullWhenFileNameIsEmpty(): void
    {
        $result = $this->subject->format_data('');

        $this->assertNull($result);
    }

    /**
     * Ensure cache_data returns false for empty payloads and skips dependency loading.
     *
     * @return void
     */
    public function testCacheDataReturnsFalseForEmptyPayload(): void
    {
        $result = $this->subject->cache_data([]);

        $this->assertFalse($result);
        $this->assertSame([], $this->loadMock->models);
        $this->assertSame([], $this->subject->_file_names);
        $this->assertSame([], $this->subject->_file_ids);
        $this->assertSame([], $this->subject->_files);
    }

    /**
     * Ensure cache_data parses mixed file formats, de-duplicates cached IDs/names, and merges query results.
     *
     * @return void
     */
    public function testCacheDataCachesMixedFormatsAndMergesModelResults(): void
    {
        $this->subject->_files = [
            ['file_id' => 999, 'file_name' => 'existing.png'],
        ];
        $this->subject->_file_names = ['already-cached.jpg'];
        $this->subject->_file_ids = ['12'];

        $nameFilters = [
            ['field' => 'file_name', 'operator' => 'IN', 'value' => ['diagram.jpg']],
            ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['3']],
        ];
        $idFilters = [
            ['field' => 'file_id', 'operator' => 'IN', 'value' => ['44']],
        ];

        $this->modelServiceMock->fileResultsAllByFilterKey[$this->modelServiceMock->buildFileFilterKey($nameFilters)] = [
            new FileFieldCachedModelRecordMock([
                'file_id' => 301,
                'file_name' => 'diagram.jpg',
                'upload_location_id' => 3,
            ]),
        ];
        $this->modelServiceMock->fileResultsAllByFilterKey[$this->modelServiceMock->buildFileFilterKey($idFilters)] = [
            new FileFieldCachedModelRecordMock([
                'file_id' => 44,
                'file_name' => 'manual.pdf',
                'upload_location_id' => 0,
            ]),
        ];

        $result = $this->subject->cache_data([
            '{filedir_3}diagram.jpg',
            '44',
            '{file:12:url}',
            '{filedir_3}diagram.jpg',
            'manual-text',
            '',
            '0',
        ]);

        $this->assertNull($result);
        $this->assertSame(['file_model'], $this->loadMock->models);
        $this->assertSame(['already-cached.jpg', 'diagram.jpg'], $this->subject->_file_names);
        $this->assertSame(['12', '44'], $this->subject->_file_ids);
        $this->assertCount(3, $this->subject->_files);
        $this->assertSame(999, $this->subject->_files[0]['file_id']);
        $this->assertSame(301, $this->subject->_files[1]['file_id']);
        $this->assertSame(44, $this->subject->_files[2]['file_id']);
        $this->assertArrayHasKey('model_object', $this->subject->_files[1]);
        $this->assertArrayHasKey('model_object', $this->subject->_files[2]);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => 'IN',
                'value' => ['diagram.jpg'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => 'IN',
                'value' => ['3'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'file_id',
                'operator' => 'IN',
                'value' => ['44'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => $nameFilters,
            ], [
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => $idFilters,
            ]],
            $this->modelServiceMock->allCalls
        );
    }

    /**
     * Ensure cache_data keeps state stable when ID lookup returns no file models.
     *
     * @return void
     */
    public function testCacheDataHandlesEmptyModelResultForCollectedFileIds(): void
    {
        $result = $this->subject->cache_data([
            '77',
        ]);

        $this->assertNull($result);
        $this->assertSame(['file_model'], $this->loadMock->models);
        $this->assertSame([], $this->subject->_file_names);
        $this->assertSame(['77'], $this->subject->_file_ids);
        $this->assertSame([], $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_id',
                'operator' => 'IN',
                'value' => ['77'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => [
                    ['field' => 'file_id', 'operator' => 'IN', 'value' => ['77']],
                ],
            ]],
            $this->modelServiceMock->allCalls
        );
    }

    /**
     * Ensure cache_data skips model queries when parsed candidates are already cached or invalid.
     *
     * @return void
     */
    public function testCacheDataSkipsQueriesWhenOnlyCachedOrInvalidValuesRemain(): void
    {
        $this->subject->_files = [
            ['file_id' => 44, 'file_name' => 'diagram.jpg'],
        ];
        $this->subject->_file_names = ['diagram.jpg'];
        $this->subject->_file_ids = ['44'];

        $result = $this->subject->cache_data([
            '{filedir_3}diagram.jpg',
            '44',
            '',
            'manual-entry',
            '0',
        ]);

        $this->assertNull($result);
        $this->assertSame(['file_model'], $this->loadMock->models);
        $this->assertSame(['diagram.jpg'], $this->subject->_file_names);
        $this->assertSame(['44'], $this->subject->_file_ids);
        $this->assertSame([['file_id' => 44, 'file_name' => 'diagram.jpg']], $this->subject->_files);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure get_files_by_name returns false when either required input is empty.
     *
     * @param mixed $fileNames
     * @param mixed $dirIds
     * @dataProvider getFilesByNameEmptyInputProvider
     * @return void
     * @throws \ReflectionException
     */
    public function testGetFilesByNameReturnsFalseWhenRequiredInputsAreEmpty($fileNames, $dirIds): void
    {
        $method = new \ReflectionMethod(\File_field::class, 'get_files_by_name');
        \TestReflectionHelper::makeAccessible($method);

        $result = $method->invoke($this->subject, $fileNames, $dirIds);

        $this->assertFalse($result);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Provide empty-input combinations for private get_files_by_name guard coverage.
     *
     * @return array<string, array<int, mixed>>
     */
    public function getFilesByNameEmptyInputProvider(): array
    {
        return [
            'empty file_names array' => [[], ['9']],
            'empty dir_ids array' => [['brochure.pdf'], []],
            'scalar empty dir id' => ['brochure.pdf', null],
        ];
    }

    /**
     * Ensure get_file normalizes scalar file-name inputs and returns merged model data.
     *
     * @return void
     */
    public function testGetFileQueriesByNameWithScalarInputsAndCachesResult(): void
    {
        $this->subject->_files = [];

        $filters = [
            ['field' => 'file_name', 'operator' => 'IN', 'value' => ['brochure.pdf']],
            ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['9']],
        ];
        $this->modelServiceMock->fileResultsAllByFilterKey[$this->modelServiceMock->buildFileFilterKey($filters)] = [
            new FileFieldCachedModelRecordMock([
                'file_id' => 77,
                'file_name' => 'brochure.pdf',
                'upload_location_id' => 9,
            ]),
        ];

        $result = $this->subject->get_file('brochure.pdf', '9');

        $this->assertSame(77, $result['file_id']);
        $this->assertSame('brochure.pdf', $result['file_name']);
        $this->assertSame(9, $result['upload_location_id']);
        $this->assertArrayHasKey('model_object', $result);
        $this->assertCount(1, $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => 'IN',
                'value' => ['brochure.pdf'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => 'IN',
                'value' => ['9'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => $filters,
            ]],
            $this->modelServiceMock->allCalls
        );
    }

    /**
     * Ensure get_file returns false for null references without mutating cache state.
     *
     * @return void
     */
    public function testGetFileReturnsFalseWhenReferenceIsNull(): void
    {
        $this->subject->_files = [
            ['file_id' => 999, 'file_name' => 'existing.pdf', 'upload_location_id' => 3],
        ];

        $result = $this->subject->get_file(null);

        $this->assertFalse($result);
        $this->assertSame(
            [
                ['file_id' => 999, 'file_name' => 'existing.pdf', 'upload_location_id' => 3],
            ],
            $this->subject->_files
        );
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure numeric cache lookups return cached files and skip model queries.
     *
     * @return void
     */
    public function testGetFileReturnsCachedNumericReferenceWithoutQuery(): void
    {
        $this->subject->_files = [
            ['file_id' => 21, 'file_name' => 'older.pdf', 'upload_location_id' => 2],
            ['file_name' => 'no-id.pdf', 'upload_location_id' => 5],
            ['file_id' => 55, 'file_name' => 'cached.pdf', 'upload_location_id' => 7],
        ];

        $result = $this->subject->get_file('55');

        $this->assertSame(55, $result['file_id']);
        $this->assertSame('cached.pdf', $result['file_name']);
        $this->assertSame(7, $result['upload_location_id']);
        $this->assertCount(3, $this->subject->_files);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure URL-encoded file names resolve from cache using matching directory IDs.
     *
     * @return void
     */
    public function testGetFileReturnsCachedUrlDecodedNameFromMatchingDirectory(): void
    {
        $this->subject->_files = [
            ['file_name' => 'different.pdf', 'upload_location_id' => 9],
            ['file_name' => 'brochure guide.pdf', 'upload_location_id' => 3],
            ['file_name' => 'brochure guide.pdf', 'upload_location_id' => 9],
        ];

        $result = $this->subject->get_file('brochure%20guide.pdf', '9');

        $this->assertSame('brochure guide.pdf', $result['file_name']);
        $this->assertSame(9, $result['upload_location_id']);
        $this->assertCount(3, $this->subject->_files);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->modelServiceMock->withCalls);
        $this->assertSame([], $this->modelServiceMock->filterCalls);
        $this->assertSame([], $this->modelServiceMock->firstCalls);
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure numeric lookups apply optional directory filters and cache merged model data.
     *
     * @return void
     */
    public function testGetFileQueriesByNumericIdWithDirectoryAndCachesMergedModelData(): void
    {
        $filters = [
            ['field' => 'file_id', 'operator' => '=', 'value' => '88'],
            ['field' => 'upload_location_id', 'operator' => '=', 'value' => '9'],
        ];
        $this->modelServiceMock->fileResultsByFilterKey[$this->modelServiceMock->buildFileFilterKey($filters)] = new FileFieldCachedModelRecordMock([
            'file_id' => 88,
            'file_name' => 'manual.pdf',
            'upload_location_id' => 9,
        ]);

        $result = $this->subject->get_file('88', '9');

        $this->assertSame(88, $result['file_id']);
        $this->assertSame('manual.pdf', $result['file_name']);
        $this->assertSame(9, $result['upload_location_id']);
        $this->assertArrayHasKey('model_object', $result);
        $this->assertCount(1, $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_id',
                'operator' => '=',
                'value' => '88',
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => '=',
                'value' => '9',
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => $filters,
                'as_model' => null,
            ]],
            $this->modelServiceMock->firstCalls
        );
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure numeric lookups cache null results when no model record is found.
     *
     * @return void
     */
    public function testGetFileCachesNullForMissingNumericLookup(): void
    {
        $result = $this->subject->get_file('91', 0);

        $this->assertNull($result);
        $this->assertSame([null], $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_id',
                'operator' => '=',
                'value' => '91',
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => [
                    ['field' => 'file_id', 'operator' => '=', 'value' => '91'],
                ],
                'as_model' => null,
            ]],
            $this->modelServiceMock->firstCalls
        );
        $this->assertSame([], $this->modelServiceMock->allCalls);
    }

    /**
     * Ensure file-name lookups cache null when model queries return no rows.
     *
     * @return void
     */
    public function testGetFileCachesNullForMissingFileNameLookup(): void
    {
        $result = $this->subject->get_file('missing.pdf', '9');

        $this->assertNull($result);
        $this->assertSame([null], $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => 'IN',
                'value' => ['missing.pdf'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => 'IN',
                'value' => ['9'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => [
                    ['field' => 'file_name', 'operator' => 'IN', 'value' => ['missing.pdf']],
                    ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['9']],
                ],
            ]],
            $this->modelServiceMock->allCalls
        );
        $this->assertSame([], $this->modelServiceMock->firstCalls);
    }

    /**
     * Ensure get_file still performs name lookups when the legacy cache container is null.
     *
     * @return void
     */
    public function testGetFileHandlesNullCacheContainerDuringNameLookup(): void
    {
        $warningCount = 0;
        $this->subject->_files = null;

        set_error_handler(function ($severity) use (&$warningCount) {
            if ($severity === E_WARNING) {
                $warningCount++;

                return true;
            }

            return false;
        });

        try {
            $result = $this->subject->get_file('missing.pdf', '9');
        } finally {
            restore_error_handler();
        }

        $this->assertNull($result);
        $this->assertGreaterThanOrEqual(1, $warningCount);
        $this->assertSame([null], $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => 'IN',
                'value' => ['missing.pdf'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => 'IN',
                'value' => ['9'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => [
                    ['field' => 'file_name', 'operator' => 'IN', 'value' => ['missing.pdf']],
                    ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['9']],
                ],
            ]],
            $this->modelServiceMock->allCalls
        );
        $this->assertSame([], $this->modelServiceMock->firstCalls);
    }

    /**
     * Ensure cache_data keeps cached files unchanged when name lookup returns no results.
     *
     * @return void
     */
    public function testCacheDataHandlesEmptyModelResultsForNameLookup(): void
    {
        $result = $this->subject->cache_data([
            '{filedir_3}missing-image.png',
        ]);

        $this->assertNull($result);
        $this->assertSame(['file_model'], $this->loadMock->models);
        $this->assertSame(['missing-image.png'], $this->subject->_file_names);
        $this->assertSame([], $this->subject->_file_ids);
        $this->assertSame([], $this->subject->_files);
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null],
            ],
            $this->modelServiceMock->calls
        );
        $this->assertSame(
            [
                ['model' => 'File', 'id' => null, 'relation' => 'UploadDestination'],
            ],
            $this->modelServiceMock->withCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'field' => 'file_name',
                'operator' => 'IN',
                'value' => ['missing-image.png'],
            ], [
                'model' => 'File',
                'id' => null,
                'field' => 'upload_location_id',
                'operator' => 'IN',
                'value' => ['3'],
            ]],
            $this->modelServiceMock->filterCalls
        );
        $this->assertSame(
            [[
                'model' => 'File',
                'id' => null,
                'with' => ['UploadDestination'],
                'filters' => [
                    ['field' => 'file_name', 'operator' => 'IN', 'value' => ['missing-image.png']],
                    ['field' => 'upload_location_id', 'operator' => 'IN', 'value' => ['3']],
                ],
            ]],
            $this->modelServiceMock->allCalls
        );
    }

    /**
     * Ensure parse_field short-circuits false for empty payloads.
     *
     * @return void
     */
    public function testParseFieldReturnsFalseForEmptyPayload(): void
    {
        $subject = new FileFieldParseFieldHarness();

        $result = $subject->parse_field('');

        $this->assertFalse($result);
        $this->assertSame([], $subject->getFileCalls);
    }

    /**
     * Ensure parse_field preserves unknown string values as legacy URL fallback output.
     *
     * @return void
     */
    public function testParseFieldReturnsFallbackArrayForUnknownStringValue(): void
    {
        $subject = new FileFieldParseFieldHarness();

        $result = $subject->parse_field('https://legacy.example.com/images/category/photo.jpg');

        $this->assertSame('https://legacy.example.com/images/category/photo.jpg', $result['url']);
        $this->assertSame('https://legacy.example.com/images/category/photo.jpg', $result['file_name']);
        $this->assertSame('', $result['filename']);
        $this->assertSame('', $result['extension']);
        $this->assertSame('', $result['upload_location_id']);
        $this->assertSame('', $result['file_hw_original']);
        $this->assertSame([], $subject->getFileCalls);
    }

    /**
     * Ensure parse_field returns false when file lookup resolves but upload directory is unavailable.
     *
     * @return void
     */
    public function testParseFieldReturnsFalseWhenUploadDirectoryIsMissing(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $subject->getFileReturn = [
            'file_name' => 'missing.jpg',
            'upload_location_id' => 99,
            'directory_id' => 99,
            'file_hw_original' => '10 10',
            'file_size' => 42,
            'mime_type' => 'image/jpeg',
        ];
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', new FileFieldParseFilesystemMock()),
        ];

        $result = $subject->parse_field('22');

        $this->assertFalse($result);
        $this->assertSame([['file_reference' => '22', 'dir_id' => null]], $subject->getFileCalls);
    }

    /**
     * Ensure parse_field parses `{file:id:url}` values and returns normalized metadata.
     *
     * @return void
     */
    public function testParseFieldParsesFileTokenAndBuildsFilesystemMetadata(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->getFileReturn = [
            'file_name' => 'brochure image.png',
            'upload_location_id' => 9,
            'directory_id' => 22,
            'file_hw_original' => '480 640',
            'file_size' => 1024,
            'mime_type' => 'image/png',
        ];

        $result = $subject->parse_field('{file:42:url}');

        $this->assertSame([['file_reference' => '42', 'dir_id' => null]], $subject->getFileCalls);
        $this->assertSame('brochure%20image.png', $result['file_name']);
        $this->assertSame('png', $result['extension']);
        $this->assertSame('brochure%20image', $result['filename']);
        $this->assertSame('{file:42:url}', $result['raw_output']);
        $this->assertSame('{file:42:url}', $result['raw_content']);
        $this->assertSame('640', $result['width']);
        $this->assertSame('480', $result['height']);
        $this->assertSame('https://cdn.example.com/uploads/', $result['path']);
        $this->assertSame('https://cdn.example.com/uploads/brochure%20image.png', $result['url']);
        $this->assertSame(22, $result['folder_id']);
        $this->assertSame(9, $result['directory_id']);
        $this->assertSame('Assets', $result['directory_title']);
        $this->assertSame('1024B', $result['file_size:human']);
        $this->assertSame('1024 bytes', $result['file_size:human_long']);
    }

    /**
     * Ensure parse_field extracts directory-scoped file names from `{filedir_n}` payloads.
     *
     * @return void
     */
    public function testParseFieldParsesFiledirPayloadAndPassesDirectoryIdToLookup(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->getFileReturn = [
            'file_name' => 'manual.pdf',
            'upload_location_id' => 9,
            'directory_id' => 9,
            'file_hw_original' => '',
            'file_size' => 88,
            'mime_type' => 'application/pdf',
        ];

        $result = $subject->parse_field('{filedir_9}manual.pdf');

        $this->assertSame([['file_reference' => 'manual.pdf', 'dir_id' => '9']], $subject->getFileCalls);
        $this->assertSame('manual.pdf', $result['file_name']);
        $this->assertSame('pdf', $result['extension']);
        $this->assertSame('manual', $result['filename']);
        $this->assertSame('{filedir_9}manual.pdf', $result['raw_output']);
    }

    /**
     * Ensure parse_field can recover file metadata directly from array payloads.
     *
     * @return void
     */
    public function testParseFieldUsesArrayFallbackWhenLookupInputIsArray(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', new FileFieldParseFilesystemMock()),
        ];
        $input = [
            'file_name' => 'array-input.jpg',
            'upload_location_id' => 9,
            'directory_id' => 9,
            'file_hw_original' => '',
            'file_size' => 5,
            'mime_type' => 'image/jpeg',
        ];

        $result = $subject->parse_field($input);

        $this->assertSame([], $subject->getFileCalls);
        $this->assertSame('array-input.jpg', $result['file_name']);
        $this->assertSame('jpg', $result['extension']);
        $this->assertSame('array-input', $result['filename']);
        $this->assertSame($input, $result['raw_output']);
        $this->assertSame($input, $result['raw_content']);
    }

    /**
     * Ensure parse_field falls back to encoded filenames when filesystem URL lookups fail.
     *
     * @return void
     */
    public function testParseFieldFallsBackToEncodedFilenameWhenFilesystemThrows(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $filesystem->throwOnPath['error%20doc.pdf'] = true;
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->getFileReturn = [
            'file_name' => 'error doc.pdf',
            'upload_location_id' => 9,
            'directory_id' => 9,
            'file_hw_original' => '',
            'file_size' => 3,
            'mime_type' => 'application/pdf',
        ];

        $result = $subject->parse_field('77');

        $this->assertSame('', $result['path']);
        $this->assertSame('error%20doc.pdf', $result['url']);
    }

    /**
     * Ensure parse_field builds manipulation metadata and preserves legacy aliases.
     *
     * @return void
     */
    public function testParseFieldBuildsManipulationMetadataForImageFiles(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $filesystem->sizesByPath['_thumb/sample photo.jpg'] = 256;
        $modelObject = new FileFieldParseModelObjectMock(
            'https://files.example.com/base/',
            'https://files.example.com/base/sample%20photo.jpg',
            '/var/www/files/sample photo.jpg',
            ['thumb' => '/var/www/files/_thumb/sample photo.jpg']
        );
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->_manipulations[9] = [
            new FileFieldParseManipulationMock('thumb', 150, 120, ['width' => 90, 'height' => 72]),
        ];
        $subject->getFileReturn = [
            'file_name' => 'sample photo.jpg',
            'upload_location_id' => 9,
            'directory_id' => 44,
            'file_hw_original' => '100 200',
            'file_size' => 64,
            'mime_type' => 'image/jpeg',
            'model_object' => $modelObject,
        ];

        $result = $subject->parse_field('55');

        $this->assertSame('https://files.example.com/base/', $result['path']);
        $this->assertSame('https://files.example.com/base/sample%20photo.jpg', $result['url']);
        $this->assertSame('https://files.example.com/base/_thumb/sample%20photo.jpg', $result['url:thumb']);
        $this->assertSame('/var/www/files/sample photo.jpg', $result['path:thumb']);
        $this->assertSame(90, $result['width:thumb']);
        $this->assertSame(72, $result['height:thumb']);
        $this->assertSame(256, $result['file_size:thumb']);
        $this->assertSame('256B', $result['file_size:thumb:human']);
        $this->assertSame('256 bytes', $result['file_size:thumb:human_long']);
        $this->assertSame(256, $result['thumb_size']);
        $this->assertSame(72, $result['thumb_height']);
        $this->assertSame(90, $result['thumb_width']);
        $this->assertSame('https://files.example.com/base/_thumb/sample%20photo.jpg', $result['thumb_file_url']);
    }

    /**
     * Ensure parse_field uses absolute manipulation paths when generated files exist.
     *
     * @return void
     */
    public function testParseFieldUsesManipulationAbsolutePathWhenGeneratedFileExists(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $filesystem->existingPaths['/var/www/files/_thumb/has-path.jpg'] = true;
        $filesystem->sizesByPath['_thumb/has-path.jpg'] = 321;
        $modelObject = new FileFieldParseModelObjectMock(
            'https://files.example.com/base/',
            'https://files.example.com/base/has-path.jpg',
            '/var/www/files/has-path.jpg',
            ['thumb' => '/var/www/files/_thumb/has-path.jpg']
        );
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->_manipulations[9] = [
            new FileFieldParseManipulationMock('thumb', 151, 121),
        ];
        $subject->getFileReturn = [
            'file_name' => 'has-path.jpg',
            'upload_location_id' => 9,
            'directory_id' => 44,
            'file_hw_original' => '100 200',
            'file_size' => 12,
            'mime_type' => 'image/jpeg',
            'model_object' => $modelObject,
        ];

        $result = $subject->parse_field('56');

        $this->assertSame('/var/www/files/_thumb/has-path.jpg', $result['path:thumb']);
        $this->assertSame(321, $result['file_size:thumb']);
        $this->assertSame('321B', $result['file_size:thumb:human']);
    }

    /**
     * Ensure parse_field omits manipulation directories for SVG image MIME types.
     *
     * @return void
     */
    public function testParseFieldSkipsManipulationDirectoryPrefixForSvgMimeType(): void
    {
        $subject = new FileFieldParseFieldHarness();
        $filesystem = new FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
        $filesystem->sizesByPath['vector.svg'] = 19;
        $subject->_upload_prefs = [
            9 => new FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
        ];
        $subject->_manipulations[9] = [
            new FileFieldParseManipulationMock('thumb', 10, 11),
        ];
        $subject->getFileReturn = [
            'file_name' => 'vector.svg',
            'upload_location_id' => 9,
            'directory_id' => 9,
            'file_hw_original' => '',
            'file_size' => 10,
            'mime_type' => 'image/svg+xml',
            'model_object' => null,
        ];

        $result = $subject->parse_field('19');

        $this->assertSame('https://cdn.example.com/uploads/vector.svg', $result['url:thumb']);
        $this->assertSame('', $result['path:thumb']);
        $this->assertSame(10, $result['width:thumb']);
        $this->assertSame(11, $result['height:thumb']);
        $this->assertSame(['vector.svg'], $filesystem->sizeCalls);
    }

    /**
     * Ensure parse_string short-circuits to an empty string for empty payloads.
     *
     * @return void
     */
    public function testParseStringReturnsEmptyStringForEmptyPayload(): void
    {
        $result = $this->subject->parse_string('');

        $this->assertSame('', $result);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->loadMock->models);
    }

    /**
     * Ensure parse_string replaces file metadata tokens and known filedir placeholders.
     *
     * @return void
     */
    public function testParseStringReplacesFileTokensAndRawFiledirTags(): void
    {
        $this->setParseStringUploadPaths([
            7 => 'https://assets.example.com/uploads/',
        ]);
        $fileOne = $this->makeParseStringFileModel(
            12,
            [
                'width' => 1200,
                'height' => 800,
                'title' => 'Hero Banner',
                'credit' => 'Staff',
            ],
            ['title', 'credit'],
            'https://files.example.com/hero-banner.jpg'
        );
        $fileTwo = $this->makeParseStringFileModel(
            45,
            [
                'width' => 640,
                'height' => 480,
                'title' => 'Detail Shot',
                'credit' => 'Designer',
            ],
            ['title', 'credit'],
            'https://files.example.com/detail-shot.jpg'
        );
        $this->modelServiceMock->fileResultsAllByFilterKey[$this->modelServiceMock->buildFileFilterKey([])] = [
            $fileOne,
            $fileTwo,
        ];

        $result = $this->subject->parse_string(
            'T={file:12:title} C={file:45:credit} W={file:12:width} H={file:45:height} U={file:12:url} P={filedir_7}brochure.pdf M={filedir_99}skip.pdf'
        );

        $this->assertSame(
            'T=Hero Banner C=Designer W=1200 H=480 U=https://files.example.com/hero-banner.jpg P=https://assets.example.com/uploads/brochure.pdf M={filedir_99}skip.pdf',
            $result
        );
        $this->assertSame([['model' => 'File', 'id' => ['12', '45', '12', '45', '12']]], $this->modelServiceMock->calls);
        $this->assertSame([['model' => 'File', 'id' => ['12', '45', '12', '45', '12'], 'relation' => 'UploadDestination']], $this->modelServiceMock->withCalls);
        $this->assertSame(['file_upload_preferences_model'], $this->loadMock->models);
    }

    /**
     * Ensure parse_string parses encoded filedir tags when encoded parsing is enabled.
     *
     * @return void
     */
    public function testParseStringReplacesEncodedFiledirTagsWhenEnabled(): void
    {
        $this->setParseStringUploadPaths([
            3 => 'https://cdn.example.com/site-images/',
        ]);

        $result = $this->subject->parse_string('Before &#123;filedir_3&#125;photo.jpg After', true);

        $this->assertSame('Before https://cdn.example.com/site-images/photo.jpg After', $result);
        $this->assertSame(['file_upload_preferences_model'], $this->loadMock->models);
    }

    /**
     * Ensure parse_string keeps file tokens unchanged when file lookup returns no records.
     *
     * @return void
     */
    public function testParseStringLeavesFileTokensWhenModelReturnsNoFiles(): void
    {
        $result = $this->subject->parse_string('T={file:99:title} U={file:99:url}');

        $this->assertSame('T={file:99:title} U={file:99:url}', $result);
        $this->assertSame([['model' => 'File', 'id' => ['99', '99']]], $this->modelServiceMock->calls);
        $this->assertSame(
            [['model' => 'File', 'id' => ['99', '99'], 'relation' => 'UploadDestination']],
            $this->modelServiceMock->withCalls
        );
    }

    /**
     * Ensure parse_string keeps encoded filedir tags unchanged when encoded parsing is disabled.
     *
     * @return void
     */
    public function testParseStringLeavesEncodedFiledirTagsWhenParseEncodedIsDisabled(): void
    {
        $this->setParseStringUploadPaths([
            3 => 'https://cdn.example.com/site-images/',
        ]);

        $result = $this->subject->parse_string('Before &#123;filedir_3&#125;photo.jpg After', false);

        $this->assertSame('Before &#123;filedir_3&#125;photo.jpg After', $result);
        $this->assertSame([], $this->loadMock->models);
    }

    /**
     * Ensure parse_string skips model and directory lookups when tokens do not match supported formats.
     *
     * @return void
     */
    public function testParseStringLeavesUnmatchedTokensUnchanged(): void
    {
        $result = $this->subject->parse_string('bad={file:abc:url} also={filedir_invalid} and plain file: marker');

        $this->assertSame('bad={file:abc:url} also={filedir_invalid} and plain file: marker', $result);
        $this->assertSame([], $this->modelServiceMock->calls);
        $this->assertSame([], $this->loadMock->models);
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

        $result = $this->subject->field('asset_file', '{filedir_9}spec%20sheet.pdf', 'all', 'image', true, null, 'asset_file');

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
        $this->assertStringContainsString('id="asset_file"', $vars['upload']);
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
     * Ensure browser javascript globals include expected language and pager markup invariants.
     *
     * @return void
     */
    public function testBrowserJavascriptGlobalsIncludeLanguageAndPagerMarkup(): void
    {
        $this->cpMock->cp_theme_url = 'https://cdn.example.com/themes/cp/';

        $this->subject->browser([], 'custom/modal/path');

        $globals = $this->javascriptMock->globals[1];
        $filebrowser = $globals['filebrowser'];
        $fileUploader = $globals['fileuploader'];

        $this->assertSame(
            [
                'resize_image' => 'resize_image',
                'or' => 'or',
                'return_to_publish' => 'return_to_publish',
            ],
            $globals['lang']
        );
        $this->assertSame('custom/modal/path', $filebrowser['endpoint_url']);
        $this->assertSame('file_manager', $filebrowser['window_title']);
        $this->assertStringContainsString('class="next"', $filebrowser['next']);
        $this->assertStringContainsString('images/pagination_next_button.gif', $filebrowser['next']);
        $this->assertStringContainsString('alt=""', $filebrowser['next']);
        $this->assertStringContainsString('class="previous"', $filebrowser['previous']);
        $this->assertStringContainsString('images/pagination_prev_button.gif', $filebrowser['previous']);
        $this->assertStringContainsString('alt=""', $filebrowser['previous']);
        $this->assertSame('file_upload', $fileUploader['window_title']);
        $this->assertSame('C=content_files&M=delete_files', $fileUploader['delete_url']);
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
     * Ensure existing picker selections take priority over hidden fallback values.
     *
     * @return void
     */
    public function testValidateUsesExistingSelectionBeforeHiddenFallback(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_existing' => 'existing.pdf',
            'asset_file_hidden_file' => 'hidden.pdf',
            'asset_file_hidden_dir' => '9',
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{filedir_9}existing.pdf'], $result);
        $this->assertSame([], $this->fileManagerMock->uploadFileCalls);
        $this->assertSame(0, $this->fileManagerMock->validatePostDataCalls);
    }

    /**
     * Ensure hidden fallback values are used when existing picker value is absent.
     *
     * @return void
     */
    public function testValidateUsesHiddenFallbackWhenExistingSelectionIsMissing(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_hidden_file' => 'legacy.png',
            'asset_file_hidden_dir' => '9',
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{filedir_9}legacy.png'], $result);
        $this->assertSame([], $this->fileManagerMock->uploadFileCalls);
    }

    /**
     * Ensure uploads in compatibility mode store the uploaded filename token.
     *
     * @return void
     */
    public function testValidateUsesUploadedFilenameInCompatibilityMode(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'y');
        $this->setValidatePostValues('asset_file', [
            'asset_file_hidden_dir' => '9',
        ]);
        $_FILES['asset_file'] = ['name' => 'legacy.jpg'];
        $this->fileManagerMock->uploadFileResponse = [
            'file_id' => 33,
            'file_name' => 'legacy.jpg',
        ];

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{filedir_9}legacy.jpg'], $result);
        $this->assertSame(
            [[
                'directory_id' => '9',
                'field_name' => 'asset_file',
            ]],
            $this->fileManagerMock->uploadFileCalls
        );
        $this->assertSame(0, $this->fileManagerMock->validatePostDataCalls);
    }

    /**
     * Ensure uploads outside compatibility mode store numeric file IDs.
     *
     * @return void
     */
    public function testValidateUsesUploadedFileIdWhenCompatibilityModeIsDisabled(): void
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'n');
        $this->setValidatePostValues('asset_file', [
            'asset_file_hidden_dir' => '9',
        ]);
        $_FILES['asset_file'] = ['name' => 'hero.jpg'];
        $this->fileManagerMock->uploadFileResponse = [
            'file_id' => 77,
            'file_name' => 'hero.jpg',
        ];

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{file:77:url}'], $result);
        $this->assertSame(1, count($this->fileManagerMock->uploadFileCalls));
    }

    /**
     * Ensure upload errors are returned directly from the filemanager payload.
     *
     * @return void
     */
    public function testValidateReturnsUploadErrorPayloadFromFilemanager(): void
    {
        $this->setValidatePostValues('asset_file');
        $_FILES['asset_file'] = ['name' => 'failing.jpg'];
        $this->fileManagerMock->uploadFileResponse = [
            'error' => [
                'value' => '',
                'error' => 'invalid_upload',
            ],
        ];

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'invalid_upload',
            ],
            $result
        );
    }

    /**
     * Ensure post_max_size overflows map to the upload limit language key.
     *
     * @return void
     */
    public function testValidateReturnsLimitErrorWhenPostDataValidationFails(): void
    {
        $this->setValidatePostValues('asset_file');
        $this->fileManagerMock->validatePostDataReturn = false;

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'upload_file_exceeds_limit',
            ],
            $result
        );
        $this->assertSame(['upload'], $this->langMock->loaded);
        $this->assertSame(1, $this->fileManagerMock->validatePostDataCalls);
    }

    /**
     * Ensure required file fields reject empty values after passing post validation.
     *
     * @return void
     */
    public function testValidateReturnsRequiredErrorWhenNoFileIsResolved(): void
    {
        $this->setValidatePostValues('asset_file');
        $this->fileManagerMock->validatePostDataReturn = true;

        $result = $this->subject->validate('', 'asset_file', 'y');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'required',
            ],
            $result
        );
    }

    /**
     * Ensure inaccessible directory values with no selected directory preserve legacy values.
     *
     * @return void
     */
    public function testValidatePreservesLegacyValueWhenDirectoryIsBlankAndInaccessible(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '',
            'asset_file_hidden_dir' => '',
            'asset_file_existing' => 'legacy-image.jpg',
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => 'legacy-image.jpg'], $result);
    }

    /**
     * Ensure inaccessible directory values fail on new entries without entry IDs.
     *
     * @return void
     */
    public function testValidateReturnsNoAccessForNewEntriesWhenDirectoryIsInaccessible(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '11',
            'asset_file_hidden_dir' => '11',
            'asset_file_existing' => 'legacy-image.jpg',
            'entry_id' => 0,
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'directory_no_access',
            ],
            $result
        );
    }

    /**
     * Ensure edit submissions fail when the existing entry has no stored field value row.
     *
     * @return void
     */
    public function testValidateReturnsNoAccessWhenExistingEntryRowIsMissing(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '11',
            'asset_file_hidden_dir' => '11',
            'asset_file_existing' => 'legacy-image.jpg',
            'entry_id' => 15,
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'directory_no_access',
            ],
            $result
        );
        $this->assertSame(['asset_file'], $this->dbMock->selectCalls);
        $this->assertSame([['field' => 'entry_id', 'value' => 15]], $this->dbMock->whereCalls);
        $this->assertSame(['channel_data'], $this->dbMock->getCalls);
    }

    /**
     * Ensure edits fail when stored entry data differs from the submitted legacy filename.
     *
     * @return void
     */
    public function testValidateReturnsNoAccessWhenSubmittedValueDiffersFromStoredLegacyValue(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '11',
            'asset_file_hidden_dir' => '11',
            'asset_file_existing' => 'legacy-image.jpg',
            'entry_id' => 22,
        ]);
        $this->dbMock->setRows([
            ['entry_id' => 22, 'asset_file' => '{filedir_11}different.jpg'],
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'directory_no_access',
            ],
            $result
        );
    }

    /**
     * Ensure numeric values still enter the numeric-token validation branch on edits.
     *
     * @return void
     */
    public function testValidateChecksNumericTokenBranchForInaccessibleDirectoryEdits(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '11',
            'asset_file_hidden_dir' => '11',
            'asset_file_existing' => '77',
            'entry_id' => 31,
        ]);
        $this->dbMock->setRows([
            ['entry_id' => 31, 'asset_file' => '{filedir_11}77'],
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(
            [
                'value' => '',
                'error' => 'directory_no_access',
            ],
            $result
        );
    }

    /**
     * Ensure unchanged legacy values are accepted on edits without directory access.
     *
     * @return void
     */
    public function testValidateAllowsUnchangedLegacyValueForInaccessibleDirectoryEdit(): void
    {
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '11',
            'asset_file_hidden_dir' => '11',
            'asset_file_existing' => 'legacy-image.jpg',
            'entry_id' => 44,
        ]);
        $this->dbMock->setRows([
            ['entry_id' => 44, 'asset_file' => '{filedir_11}legacy-image.jpg'],
        ]);

        $result = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{filedir_11}legacy-image.jpg'], $result);
    }

    /**
     * Ensure grid field edits use grid table lookups before no-access validation checks.
     *
     * @return void
     */
    public function testValidateUsesGridRowLookupForInaccessibleDirectoryEditChecks(): void
    {
        $this->setValidatePostValues('grid_asset', [
            'grid_asset_directory' => '11',
            'grid_asset_hidden_dir' => '11',
            'grid_asset_existing' => 'grid-file.pdf',
            'entry_id' => 51,
        ]);
        $this->dbMock->setRows([
            ['row_id' => 7, 'grid_asset' => '{filedir_11}grid-file.pdf'],
        ]);

        $result = $this->subject->validate('', 'grid_asset', 'n', [
            'grid_row_id' => 7,
            'grid_field_id' => 3,
        ]);

        $this->assertSame(['value' => '{filedir_11}grid-file.pdf'], $result);
        $this->assertSame(
            [['field' => 'row_id', 'value' => 7]],
            $this->dbMock->whereCalls
        );
        $this->assertSame(['grid_field_3'], $this->dbMock->getCalls);
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

    /**
     * Ensure upload preferences are cached across drag-and-drop calls and reused on subsequent "all" lookups.
     *
     * @return void
     */
    public function testDragAndDropFieldReusesCachedUploadPreferencesAcrossCalls(): void
    {
        $this->subject->fileModelReturn = null;
        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(9, 0, 'list', true),
        ];

        $first = $this->subject->dragAndDropField('asset_file', '{filedir_9}first.pdf', 'all', 'all');

        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(5, 0, 'list', true),
        ];
        $second = $this->subject->dragAndDropField('asset_file', '{filedir_5}second.pdf', 'all', 'all');

        $this->assertSame('<rendered-output>', $first);
        $this->assertSame('<rendered-output>', $second);
        $this->assertSame([9], $this->viewFactoryMock->renderVars[0]['role_allowed_dirs']);
        $this->assertSame([9], $this->viewFactoryMock->renderVars[1]['role_allowed_dirs']);
        $this->assertSame(9, $this->viewFactoryMock->renderVars[0]['allowed_directory']);
        $this->assertSame(9, $this->viewFactoryMock->renderVars[1]['allowed_directory']);
        $this->assertSame([9, 9], $this->filePickerFactoryMock->allowedDirectoryCalls);
        $this->assertSame([['model' => 'UploadDestination', 'id' => null]], $this->modelServiceMock->calls);
        $this->assertSame([['field' => 'name', 'direction' => 'asc']], $this->modelServiceMock->orderCalls);
    }

    /**
     * Ensure validate() reuses cached upload preferences and does not query destinations twice.
     *
     * @return void
     */
    public function testValidateReusesCachedUploadPreferencesAcrossCalls(): void
    {
        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(9, 0, 'list', true),
        ];

        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '9',
            'asset_file_hidden_dir' => '9',
            'asset_file_existing' => 'first.pdf',
        ]);
        $first = $this->subject->validate('', 'asset_file');

        $this->modelServiceMock->uploadDestinationsAll = [
            new FileFieldUploadDestinationMock(5, 0, 'list', true),
        ];
        $this->setValidatePostValues('asset_file', [
            'asset_file_directory' => '5',
            'asset_file_hidden_dir' => '5',
            'asset_file_existing' => 'second.pdf',
        ]);
        $second = $this->subject->validate('', 'asset_file');

        $this->assertSame(['value' => '{filedir_9}first.pdf'], $first);
        $this->assertSame(
            [
                'value' => '',
                'error' => 'directory_no_access',
            ],
            $second
        );
        $this->assertSame([['model' => 'UploadDestination', 'id' => null]], $this->modelServiceMock->calls);
        $this->assertSame([['field' => 'name', 'direction' => 'asc']], $this->modelServiceMock->orderCalls);
    }

    /**
     * Ensure CP drag-and-drop globals include only allowed destinations and endpoint invariants.
     *
     * @return void
     */
    public function testLoadDragAndDropAssetsBuildsGlobalsForAllowedCpDirectories(): void
    {
        ee()->config->setItem('site_id', 7);
        ee()->config->setItem('file_manager_compatibility_mode', 'n');

        $globalPref = $this->makeDragAndDropUploadPreference(1, 0, 0, 'Global Assets', [11 => 'Global Child']);
        $currentSitePref = $this->makeDragAndDropUploadPreference(2, 7, 0, 'Site Assets', [12 => 'Site Child']);
        $otherSitePref = $this->makeDragAndDropUploadPreference(3, 9, 0, 'Other Site', [13 => 'Other Child']);
        $moduleOwnedPref = $this->makeDragAndDropUploadPreference(4, 7, 9, 'Module Files', [14 => 'Module Child']);
        $this->setUploadPreferenceCache([$globalPref, $currentSitePref, $otherSitePref, $moduleOwnedPref]);

        $collaborators = $this->setLoadDragAndDropCollaborators(true);

        $this->subject->loadDragAndDropAssets();

        $this->assertSame(['can_access_files', 'can_access_files'], $collaborators['permission']->hasCalls);
        $this->assertSame(1, $globalPref->getDirectoriesDropdownCalls);
        $this->assertSame(1, $currentSitePref->getDirectoriesDropdownCalls);
        $this->assertSame(0, $otherSitePref->getDirectoriesDropdownCalls);
        $this->assertSame(0, $moduleOwnedPref->getDirectoriesDropdownCalls);

        $expectedDestinations = [
            1 => [
                'label' => 'Global Assets',
                'path' => '',
                'upload_location_id' => 1,
                'children' => [11 => 'Global Child'],
            ],
            2 => [
                'label' => 'Site Assets',
                'path' => '',
                'upload_location_id' => 2,
                'children' => [12 => 'Site Child'],
            ],
        ];
        $this->assertSame([$expectedDestinations], $collaborators['view_helpers']->normalizedChoicesCalls);
        $this->assertCount(1, $this->javascriptMock->globals);
        $globals = $this->javascriptMock->globals[0];
        $this->assertSame('file_dnd_no_directories_desc', $globals['lang.file_dnd_no_directories_desc']);
        $this->assertSame($expectedDestinations, $globals['dragAndDrop.uploadDesinations']);
        $this->assertSame('compiled://addons/settings/filepicker/ajax-upload', $globals['dragAndDrop.endpoint']);
        $this->assertSame(
            'compiled://addons/settings/filepicker/ajax-overwrite-or-rename',
            $globals['dragAndDrop.resolveConflictEndpoint']
        );
        $this->assertSame('compiled://filepicker/all', $globals['dragAndDrop.filepickerEndpoint']);
        $this->assertSame('compiled://addons/settings/filepicker/upload', $globals['dragAndDrop.filepickerUploadEndpoint']);
        $this->assertSame(
            [
                'addons/settings/filepicker/ajax-upload',
                'addons/settings/filepicker/ajax-overwrite-or-rename',
                'addons/settings/filepicker/upload',
            ],
            $collaborators['cp_url_factory']->paths
        );
        $this->assertSame(['all'], $collaborators['file_picker_factory']->allowedDirectoryCalls);
        $this->assertSame(1, $collaborators['file_picker_factory']->compileCalls);
        $this->assertSame(
            [[
                'file' => [
                    'fields/file/file_field_drag_and_drop',
                    'fields/file/concurrency_queue',
                    'fields/file/file_upload_progress_table',
                    'fields/file/drag_and_drop_upload',
                    'fields/grid/file_grid',
                ],
            ]],
            $this->cpMock->scripts
        );
    }

    /**
     * Ensure destination globals are emptied and permissions copy reflects access denial.
     *
     * @return void
     */
    public function testLoadDragAndDropAssetsClearsDestinationsWhenFileAccessIsDenied(): void
    {
        ee()->config->setItem('site_id', 7);
        ee()->config->setItem('file_manager_compatibility_mode', 'n');

        $allowedPref = $this->makeDragAndDropUploadPreference(8, 7, 0, 'Member Files', [30 => 'Uploads']);
        $this->setUploadPreferenceCache([$allowedPref]);

        $collaborators = $this->setLoadDragAndDropCollaborators(false);

        $this->subject->loadDragAndDropAssets();

        $this->assertSame(['can_access_files', 'can_access_files'], $collaborators['permission']->hasCalls);
        $this->assertSame(1, $allowedPref->getDirectoriesDropdownCalls);
        $this->assertSame([[]], $collaborators['view_helpers']->normalizedChoicesCalls);
        $this->assertCount(1, $this->javascriptMock->globals);
        $globals = $this->javascriptMock->globals[0];
        $this->assertSame('file_dnd_no_directory_permissions', $globals['lang.file_dnd_no_directories_desc']);
        $this->assertSame([], $globals['dragAndDrop.uploadDesinations']);
    }

    /**
     * Ensure compatibility mode bypasses child-directory lookups in upload destination payloads.
     *
     * @return void
     */
    public function testLoadDragAndDropAssetsOmitsChildrenInCompatibilityMode(): void
    {
        ee()->config->setItem('site_id', 7);
        ee()->config->setItem('file_manager_compatibility_mode', 'y');

        $pref = $this->makeDragAndDropUploadPreference(10, 7, 0, 'Compatibility Files', [41 => 'Should Not Load']);
        $this->setUploadPreferenceCache([$pref]);

        $collaborators = $this->setLoadDragAndDropCollaborators(true);

        $this->subject->loadDragAndDropAssets();

        $this->assertSame(0, $pref->getDirectoriesDropdownCalls);
        $this->assertSame(
            [[
                10 => [
                    'label' => 'Compatibility Files',
                    'path' => '',
                    'upload_location_id' => 10,
                    'children' => [],
                ],
            ]],
            $collaborators['view_helpers']->normalizedChoicesCalls
        );
    }

    /**
     * Ensure empty upload preference caches still register CP globals without destination payloads.
     *
     * @return void
     */
    public function testLoadDragAndDropAssetsHandlesEmptyUploadPreferences(): void
    {
        ee()->config->setItem('site_id', 7);
        ee()->config->setItem('file_manager_compatibility_mode', 'n');
        $this->setUploadPreferenceCache(new \ArrayObject([]));

        $collaborators = $this->setLoadDragAndDropCollaborators(true);

        $this->subject->loadDragAndDropAssets();

        $this->assertSame([[]], $collaborators['view_helpers']->normalizedChoicesCalls);
        $this->assertCount(1, $this->javascriptMock->globals);
        $this->assertSame([], $this->javascriptMock->globals[0]['dragAndDrop.uploadDesinations']);
    }
}
