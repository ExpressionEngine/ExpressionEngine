<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager {
    if (!interface_exists(ColumnInterface::class, false)) {
        interface ColumnInterface
        {
        }
    }
}

namespace {
    use PHPUnit\Framework\TestCase;

    if (!class_exists('EE_Fieldtype')) {
        abstract class EE_Fieldtype
        {
            use ExpressionEngine\Service\Template\Variables\ModifiableTrait;

            public static $constructCount = 0;

            /** @var array<string, mixed> */
            public $settings = [];

            /** @var int */
            public $content_id = 0;

            /** @var int|null */
            public $id = null;

            /** @var int|null */
            public $field_id = null;

            /** @var string */
            public $name = 'file_field';

            /** @var string */
            public $field_name = 'file_field';

            /**
             * Track parent constructor calls for lightweight fieldtype tests.
             *
             * @return void
             */
            public function __construct()
            {
                self::$constructCount++;
            }

            /**
             * Mirror the fieldtype API used by File_ft::validate().
             *
             * @return string
             */
            public function name()
            {
                return $this->field_name;
            }

            /**
             * Mirror the canonical field id getter used by fieldtypes.
             *
             * @return int|null
             */
            public function id()
            {
                return $this->id;
            }

            /**
             * Mirror the new-field check used by fieldtype settings.
             *
             * @return bool
             */
            public function isNew()
            {
                return is_null($this->id);
            }

            /**
             * Mirror the content id getter used by fieldtypes.
             *
             * @return int|null
             */
            public function content_id()
            {
                return $this->content_id;
            }

            /**
             * Mirror EE_Fieldtype::_init() for cross-suite compatibility.
             *
             * @param array $config
             * @return void
             */
            public function _init($config = [])
            {
                foreach ($config as $key => $value) {
                    $this->$key = $value;
                }

                if (isset($config['name'])) {
                    $this->name = $config['name'];
                    $this->field_name = $config['name'];
                }

                if (isset($config['field_name'])) {
                    $this->name = $config['field_name'];
                    $this->field_name = $config['field_name'];
                }

                if (isset($config['id'])) {
                    $this->field_id = $config['id'];
                }

                if (isset($config['field_id'])) {
                    $this->id = $config['field_id'];
                    $this->field_id = $config['field_id'];
                }
            }
        }
    }

    require_once dirname(__DIR__, 5) . '/Addons/file/ft.file.php';

    if (!function_exists('get_bool_from_string')) {
        /**
         * Convert common yes/no strings into booleans for isolated fieldtype tests.
         *
         * @param mixed $value
         * @return bool|null
         */
        function get_bool_from_string($value)
        {
            if (is_bool($value) || is_null($value)) {
                return $value;
            }

            $normalized = strtolower((string) $value);

            if (in_array($normalized, ['true', 'yes', 'y', 'on', '1'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', 'no', 'n', 'off', '0'], true)) {
                return false;
            }

            return null;
        }
    }

    class FileFtLoadRecorder
    {
        /** @var array<int, string> */
        public $libraries = [];

        /** @var array<int, string> */
        public $models = [];

        /** @var array<int, string> */
        public $packagePaths = [];

        /**
         * Record requested libraries.
         *
         * @param string $name
         * @return void
         */
        public function library($name)
        {
            $this->libraries[] = $name;
        }

        /**
         * Record requested models.
         *
         * @param string $name
         * @return void
         */
        public function model($name)
        {
            $this->models[] = $name;
        }

        /**
         * Record package-path additions.
         *
         * @param string $path
         * @return void
         */
        public function add_package_path($path)
        {
            $this->packagePaths[] = $path;
        }

        /**
         * Record package-path removals.
         *
         * @param string $path
         * @return void
         */
        public function remove_package_path($path)
        {
            $this->packagePaths[] = 'remove:' . $path;
        }
    }

    class FileFtSessionStub
    {
        /** @var object|null */
        public $member;

        /**
         * Seed the session member returned by getMember().
         *
         * @param object|null $member
         * @return void
         */
        public function __construct($member = null)
        {
            $this->member = $member;
        }

        /**
         * Return the configured session member.
         *
         * @return object|null
         */
        public function getMember()
        {
            return $this->member;
        }
    }

    class FileFtFileFieldStub
    {
        /** @var mixed */
        public $fileModel;

        /** @var array<int, mixed> */
        public $calls = [];

        /** @var array<int, mixed> */
        public $parseFieldCalls = [];

        /** @var array<int, mixed> */
        public $cacheDataCalls = [];

        /** @var array<int, array<string, mixed>> */
        public $dragAndDropCalls = [];

        /** @var array<int, array<string, mixed>> */
        public $fieldCalls = [];

        /** @var mixed */
        public $parseFieldReturn;

        /** @var array<int, string> */
        public $parseStringCalls = [];

        /** @var string */
        public $parseStringReturn = '';

        /** @var string */
        public $dragAndDropReturn = 'cp-display-field';

        /** @var string */
        public $fieldReturn = 'frontend-display-field';

        /**
         * Return the configured file model for the incoming field data.
         *
         * @param mixed $data
         * @return mixed
         */
        public function getFileModelForFieldData($data)
        {
            $this->calls[] = $data;

            return $this->fileModel;
        }

        /**
         * Capture pre_process() parser calls and return the configured value.
         *
         * @param mixed $data
         * @return mixed
         */
        public function parse_field($data)
        {
            $this->parseFieldCalls[] = $data;

            return $this->parseFieldReturn;
        }

        /**
         * Capture replace_tag() string parsing calls and return the configured URL.
         *
         * @param string $data
         * @return string
         */
        public function parse_string($data)
        {
            $this->parseStringCalls[] = $data;

            return $this->parseStringReturn;
        }

        /**
         * Capture pre_loop() cache requests.
         *
         * @param mixed $data
         * @return void
         */
        public function cache_data($data)
        {
            $this->cacheDataCalls[] = $data;
        }

        /**
         * Capture the control-panel drag-and-drop field arguments.
         *
         * @param string $fieldName
         * @param mixed $data
         * @param mixed $allowedFileDirs
         * @param string $contentType
         * @return string
         */
        public function dragAndDropField($fieldName, $data, $allowedFileDirs, $contentType)
        {
            $this->dragAndDropCalls[] = [
                'field_name' => $fieldName,
                'data' => $data,
                'allowed_file_dirs' => $allowedFileDirs,
                'content_type' => $contentType,
            ];

            return $this->dragAndDropReturn;
        }

        /**
         * Capture the front-end field arguments.
         *
         * @param string $fieldName
         * @param mixed $data
         * @param mixed $allowedFileDirs
         * @param string $contentType
         * @param bool $filebrowser
         * @param int|null $existingLimit
         * @return string
         */
        public function field($fieldName, $data, $allowedFileDirs, $contentType, $filebrowser, $existingLimit)
        {
            $this->fieldCalls[] = [
                'field_name' => $fieldName,
                'data' => $data,
                'allowed_file_dirs' => $allowedFileDirs,
                'content_type' => $contentType,
                'filebrowser' => $filebrowser,
                'existing_limit' => $existingLimit,
            ];

            return $this->fieldReturn;
        }
    }

    class FileFtJavascriptStub
    {
        /** @var array<int, array<string, string>> */
        public $globals = [];

        /**
         * Capture global JavaScript payloads.
         *
         * @param array<string, string> $data
         * @return void
         */
        public function set_global(array $data)
        {
            $this->globals[] = $data;
        }
    }

    class FileFtCpStub
    {
        /** @var array<int, array<string, array<int, string>>> */
        public $scripts = [];

        /**
         * Capture queued control-panel JavaScript bundles.
         *
         * @param array<string, array<int, string>> $script
         * @return void
         */
        public function add_js_script(array $script)
        {
            $this->scripts[] = $script;
        }
    }

    class FileFtCompiledUrlStub
    {
        /** @var string */
        public $compiledUrl;

        /** @var int */
        public $compileCalls = 0;

        /**
         * Seed the compiled control-panel URL returned to display_field().
         *
         * @param string $compiledUrl
         * @return void
         */
        public function __construct($compiledUrl)
        {
            $this->compiledUrl = $compiledUrl;
        }

        /**
         * Return the configured compiled URL and record the call.
         *
         * @return string
         */
        public function compile()
        {
            $this->compileCalls++;

            return $this->compiledUrl;
        }
    }

    class FileFtCpUrlFactoryStub
    {
        /** @var string */
        public $compiledUrl;

        /** @var array<int, array<string, mixed>> */
        public $makeCalls = [];

        /** @var FileFtCompiledUrlStub|null */
        public $lastCompiledUrl;

        /**
         * Seed the URL generated for the publish-file modal.
         *
         * @param string $compiledUrl
         * @return void
         */
        public function __construct($compiledUrl = 'https://example.com/admin.php?/cp/files/file/view/###&modal_form=y')
        {
            $this->compiledUrl = $compiledUrl;
        }

        /**
         * Capture control-panel URL generation requests.
         *
         * @param string $path
         * @param array<string, string> $params
         * @return FileFtCompiledUrlStub
         */
        public function make($path, array $params = [])
        {
            $this->makeCalls[] = [
                'path' => $path,
                'params' => $params,
            ];
            $this->lastCompiledUrl = new FileFtCompiledUrlStub($this->compiledUrl);

            return $this->lastCompiledUrl;
        }
    }

    class FileFtFileModelStub
    {
        /** @var bool */
        public $memberHasAccessResult;

        /** @var array<int, object> */
        public $memberChecks = [];

        /**
         * Seed the configured access result.
         *
         * @param bool $memberHasAccessResult
         * @return void
         */
        public function __construct($memberHasAccessResult = true)
        {
            $this->memberHasAccessResult = $memberHasAccessResult;
        }

        /**
         * Return the configured member access decision.
         *
         * @param object $member
         * @return bool
         */
        public function memberHasAccess($member)
        {
            $this->memberChecks[] = $member;

            return $this->memberHasAccessResult;
        }
    }

    class FileFtGridModelStub
    {
        /** @var array<mixed> */
        public $rows;

        /** @var array<int, array<string, mixed>> */
        public $calls = [];

        /**
         * Seed the grid rows returned during validation.
         *
         * @param array<mixed> $rows
         * @return void
         */
        public function __construct(array $rows)
        {
            $this->rows = $rows;
        }

        /**
         * Return the configured entry rows and capture the lookup arguments.
         *
         * @param int $contentId
         * @param int $fieldId
         * @param string $contentType
         * @param array<mixed> $filters
         * @param bool $includeDeleted
         * @param int $fluidFieldDataId
         * @return array<mixed>
         */
        public function get_entry_rows($contentId, $fieldId, $contentType, array $filters, $includeDeleted, $fluidFieldDataId)
        {
            $this->calls[] = [
                'content_id' => $contentId,
                'field_id' => $fieldId,
                'content_type' => $contentType,
                'filters' => $filters,
                'include_deleted' => $includeDeleted,
                'fluid_field_data_id' => $fluidFieldDataId,
            ];

            return $this->rows;
        }
    }

    class FileFtModelQueryStub
    {
        /** @var mixed */
        private $result;

        /**
         * Seed the model query result returned by first().
         *
         * @param mixed $result
         * @return void
         */
        public function __construct($result)
        {
            $this->result = $result;
        }

        /**
         * Return the configured first model result.
         *
         * @return mixed
         */
        public function first()
        {
            return $this->result;
        }
    }

    class FileFtModelServiceStub
    {
        /** @var array<string, mixed> */
        private $results = [];

        /** @var array<int, array<string, mixed>> */
        public $queries = [];

        /**
         * Register the result returned for a model/entity lookup pair.
         *
         * @param string $model
         * @param int $id
         * @param mixed $result
         * @return void
         */
        public function setFirstResult($model, $id, $result)
        {
            $this->results[$this->getKey($model, $id)] = $result;
        }

        /**
         * Return the configured query stub for the requested model lookup.
         *
         * @param string $model
         * @param int $id
         * @return FileFtModelQueryStub
         */
        public function get($model, $id)
        {
            $this->queries[] = [
                'model' => $model,
                'id' => $id,
            ];

            return new FileFtModelQueryStub($this->results[$this->getKey($model, $id)] ?? null);
        }

        /**
         * Build a stable lookup key for model results.
         *
         * @param string $model
         * @param int $id
         * @return string
         */
        private function getKey($model, $id)
        {
            return $model . ':' . $id;
        }
    }

    class FileFtChannelFormLibStub
    {
        /** @var int */
        public $logged_out_member_id;

        /** @var int */
        public $fetchCalls = 0;

        /**
         * Seed the logged-out member selected by the fallback lookup.
         *
         * @param int $loggedOutMemberId
         * @return void
         */
        public function __construct($loggedOutMemberId = 0)
        {
            $this->logged_out_member_id = $loggedOutMemberId;
        }

        /**
         * Record the logged-out member fetch attempt.
         *
         * @return void
         */
        public function fetch_logged_out_member()
        {
            $this->fetchCalls++;
        }
    }

    class FileFtValidationValidatorStub
    {
        /** @var array<string, string> */
        public $rules = [];

        /** @var array<int, array{name: string, callback: callable}> */
        public $defineRuleCalls = [];

        /** @var array<int, mixed> */
        public $validateCalls = [];

        /** @var mixed */
        public $validateReturn;

        /**
         * Seed the validator return value used by validate_settings().
         *
         * @param mixed $validateReturn
         * @return void
         */
        public function __construct($validateReturn = null)
        {
            $this->validateReturn = $validateReturn;
        }

        /**
         * Record custom validation rule registration.
         *
         * @param string $name
         * @param callable $callback
         * @return self
         */
        public function defineRule($name, $callback)
        {
            $this->defineRuleCalls[] = [
                'name' => $name,
                'callback' => $callback,
            ];

            return $this;
        }

        /**
         * Record validation payloads and return the configured result.
         *
         * @param mixed $settings
         * @return mixed
         */
        public function validate($settings)
        {
            $this->validateCalls[] = $settings;

            return $this->validateReturn;
        }
    }

    class FileFtValidationServiceStub
    {
        /** @var array<int, array<string, string>> */
        public $makeCalls = [];

        /** @var FileFtValidationValidatorStub */
        public $validator;

        /**
         * Seed the validator returned by ee('Validation')->make().
         *
         * @param FileFtValidationValidatorStub|null $validator
         * @return void
         */
        public function __construct($validator = null)
        {
            $this->validator = $validator ?: new FileFtValidationValidatorStub();
        }

        /**
         * Record requested rules and return the configured validator.
         *
         * @param array<string, string> $rules
         * @return FileFtValidationValidatorStub
         */
        public function make(array $rules)
        {
            $this->makeCalls[] = $rules;
            $this->validator->rules = $rules;

            return $this->validator;
        }
    }

    class FileFtTemplateStub
    {
        /** @var array<string, mixed> */
        public $fetchParamMap = [];

        /** @var array<int, array<string, mixed>> */
        public $fetchParamCalls = [];

        /** @var array<int, array<string, mixed>> */
        public $parseVariablesCalls = [];

        /** @var string */
        public $parseVariablesReturn = 'parsed-template';

        /** @var int */
        public $noResultsCalls = 0;

        /** @var string */
        public $noResultsReturn = 'NO_RESULTS';

        /**
         * Return the configured template parameter value.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed
         */
        public function fetch_param($key, $default = null)
        {
            $this->fetchParamCalls[] = [
                'key' => $key,
                'default' => $default,
            ];

            if (array_key_exists($key, $this->fetchParamMap)) {
                return $this->fetchParamMap[$key];
            }

            return $default;
        }

        /**
         * Capture template variable parsing requests and return the configured result.
         *
         * @param string $tagdata
         * @param array<int, array<string, mixed>> $variables
         * @return string
         */
        public function parse_variables($tagdata, $variables)
        {
            $this->parseVariablesCalls[] = [
                'tagdata' => $tagdata,
                'variables' => $variables,
            ];

            return $this->parseVariablesReturn;
        }

        /**
         * Capture no-results fallbacks and return the configured payload.
         *
         * @return string
         */
        public function no_results()
        {
            $this->noResultsCalls++;

            return $this->noResultsReturn;
        }
    }

    class FileFtConfigStub
    {
        /** @var array<string, mixed> */
        public $items = [
            'image_resize_protocol' => 'gd2',
            'image_library_path' => '/usr/local/lib',
            'image_manipulation_quality' => null,
            'debug' => 0,
        ];

        /**
         * Return the configured config value for the requested key.
         *
         * @param string $key
         * @return mixed
         */
        public function item($key)
        {
            if (array_key_exists($key, $this->items)) {
                return $this->items[$key];
            }

            return null;
        }
    }

    class FileFtPermissionStub
    {
        /** @var bool */
        public $isSuperAdmin = false;

        /**
         * Return the configured super-admin flag.
         *
         * @return bool
         */
        public function isSuperAdmin()
        {
            return $this->isSuperAdmin;
        }
    }

    class FileFtRequestStub
    {
        /** @var array<string, mixed> */
        public $postValues = [];

        /** @var array<int, array{key: string, default: mixed}> */
        public $postCalls = [];

        /**
         * Seed the POST values returned by the request double.
         *
         * @param array<string, mixed> $postValues
         * @return void
         */
        public function __construct(array $postValues = [])
        {
            $this->postValues = $postValues;
        }

        /**
         * Return a configured POST value or the provided default.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed
         */
        public function post($key, $default = null)
        {
            $this->postCalls[] = [
                'key' => $key,
                'default' => $default,
            ];

            if (array_key_exists($key, $this->postValues)) {
                return $this->postValues[$key];
            }

            return $default;
        }
    }

    class FileFtImageLibStub
    {
        /** @var array<int, string> */
        public $explodeNameCalls = [];

        /** @var int */
        public $clearCalls = 0;

        /** @var array<int, array<string, mixed>> */
        public $initializeCalls = [];

        /** @var array<int, array<int, mixed>> */
        public $getImagePropertiesCalls = [];

        /** @var array<int, string> */
        public $actionCalls = [];

        /** @var array<string, bool> */
        public $actionResults = [
            'resize' => true,
            'crop' => true,
            'rotate' => true,
            'webp' => true,
            'avif' => true,
        ];

        /** @var array<string, array<string, int>> */
        public $imagePropertiesByPath = [];

        /** @var string */
        public $displayErrorsReturn = 'display-errors';

        /** @var string */
        public $width = 'sentinel-width';

        /** @var string */
        public $height = 'sentinel-height';

        /**
         * Return split filename parts that mirror Image_lib::explode_name().
         *
         * @param string $sourceImage
         * @return array<string, string>
         */
        public function explode_name($sourceImage)
        {
            $this->explodeNameCalls[] = $sourceImage;
            $ext = strrchr($sourceImage, '.');
            $name = ($ext === false) ? $sourceImage : substr($sourceImage, 0, -strlen($ext));

            return ['ext' => $ext, 'name' => $name];
        }

        /**
         * Record that the image library state was cleared.
         *
         * @return void
         */
        public function clear()
        {
            $this->clearCalls++;
        }

        /**
         * Record the config used to initialize the image library.
         *
         * @param array<string, mixed> $config
         * @return void
         */
        public function initialize($config)
        {
            $this->initializeCalls[] = $config;
        }

        /**
         * Return the configured image properties for the requested path.
         *
         * @param string $path
         * @param bool $cached
         * @return array<string, int>
         */
        public function get_image_properties($path, $cached)
        {
            $this->getImagePropertiesCalls[] = [$path, $cached];

            if (isset($this->imagePropertiesByPath[$path])) {
                return $this->imagePropertiesByPath[$path];
            }

            return ['width' => 200, 'height' => 100];
        }

        /**
         * Return the configured image-library error payload.
         *
         * @return string
         */
        public function display_errors()
        {
            return $this->displayErrorsReturn;
        }

        /**
         * Handle resize/crop/rotate/webp/avif calls through one stubbed gateway.
         *
         * @param string $name
         * @param array<int, mixed> $arguments
         * @return bool
         */
        public function __call($name, $arguments)
        {
            $this->actionCalls[] = $name;

            if (array_key_exists($name, $this->actionResults)) {
                return $this->actionResults[$name];
            }

            throw new \BadMethodCallException('Unsupported image action: ' . $name);
        }
    }

    class FileFtProcessImageFilesystemStub
    {
        /** @var array<string, bool> */
        public $directories = [];

        /** @var array<string, bool> */
        public $writableDirectories = [];

        /** @var array<string, bool> */
        public $existingPaths = [];

        /** @var array<string, string> */
        public $copyToTempFileExceptions = [];

        /** @var array<string, string> */
        public $copyToTempFileContents = [];

        /** @var array<int, string> */
        public $isDirCalls = [];

        /** @var array<int, string> */
        public $mkdirCalls = [];

        /** @var array<int, string> */
        public $addIndexHtmlCalls = [];

        /** @var array<int, string> */
        public $isWritableCalls = [];

        /** @var array<int, string> */
        public $existsCalls = [];

        /** @var array<int, string> */
        public $copyToTempFileCalls = [];

        /** @var int */
        public $createTempFileCalls = 0;

        /** @var array<int, array<string, mixed>> */
        public $writeStreamCalls = [];

        /** @var array<int, string> */
        public $ensureCorrectAccessModeCalls = [];

        /** @var array<int, string> */
        private $temporaryPaths = [];

        /**
         * Return whether the target path is a known directory.
         *
         * @param string $path
         * @return bool
         */
        public function isDir($path)
        {
            $this->isDirCalls[] = $path;

            return $this->directories[$path] ?? false;
        }

        /**
         * Record directory creation and mark the path as available.
         *
         * @param string $path
         * @return void
         */
        public function mkdir($path)
        {
            $this->mkdirCalls[] = $path;
            $this->directories[$path] = true;
        }

        /**
         * Record index-file creation for the requested directory.
         *
         * @param string $path
         * @return void
         */
        public function addIndexHtml($path)
        {
            $this->addIndexHtmlCalls[] = $path;
        }

        /**
         * Return whether the requested directory is writable.
         *
         * @param string $path
         * @return bool
         */
        public function isWritable($path)
        {
            $this->isWritableCalls[] = $path;

            if (array_key_exists($path, $this->writableDirectories)) {
                return $this->writableDirectories[$path];
            }

            return true;
        }

        /**
         * Return whether the requested destination already exists.
         *
         * @param string $path
         * @return bool
         */
        public function exists($path)
        {
            $this->existsCalls[] = $path;

            return $this->existingPaths[$path] ?? false;
        }

        /**
         * Return a local temp-file copy or throw the configured exception.
         *
         * @param string $path
         * @return array<string, mixed>
         * @throws \ExpressionEngine\Library\Filesystem\FilesystemException
         */
        public function copyToTempFile($path)
        {
            $this->copyToTempFileCalls[] = $path;

            if (array_key_exists($path, $this->copyToTempFileExceptions)) {
                throw new \ExpressionEngine\Library\Filesystem\FilesystemException(
                    $this->copyToTempFileExceptions[$path]
                );
            }

            $contents = $this->copyToTempFileContents[$path] ?? 'temp-image-data';

            return $this->makeTempFile($contents);
        }

        /**
         * Create a writable temp file for the transformed image.
         *
         * @return array<string, mixed>
         */
        public function createTempFile()
        {
            $this->createTempFileCalls++;

            return $this->makeTempFile('generated-image-data');
        }

        /**
         * Record transformed-file writes and mark the destination as existing.
         *
         * @param string $path
         * @param resource $stream
         * @return void
         */
        public function writeStream($path, $stream)
        {
            $metadata = stream_get_meta_data($stream);
            $this->writeStreamCalls[] = [
                'path' => $path,
                'stream_uri' => $metadata['uri'] ?? null,
            ];
            $this->existingPaths[$path] = true;
        }

        /**
         * Record access-mode normalization requests.
         *
         * @param string $path
         * @return void
         */
        public function ensureCorrectAccessMode($path)
        {
            $this->ensureCorrectAccessModeCalls[] = $path;
        }

        /**
         * Clean up any temporary files created for the test.
         *
         * @return void
         */
        public function __destruct()
        {
            foreach ($this->temporaryPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        /**
         * Create and open a temporary file seeded with the provided contents.
         *
         * @param string $contents
         * @return array<string, mixed>
         */
        private function makeTempFile($contents)
        {
            $path = tempnam(sys_get_temp_dir(), 'file-ft-');
            file_put_contents($path, $contents);
            $file = fopen($path, 'r+');
            $this->temporaryPaths[] = $path;

            return [
                'path' => $path,
                'file' => $file,
            ];
        }
    }

    class FileFtProcessImageUploadDestinationStub
    {
        /** @var mixed */
        private $filesystem;

        /**
         * Seed the filesystem returned by the upload destination.
         *
         * @param mixed $filesystem
         * @return void
         */
        public function __construct($filesystem)
        {
            $this->filesystem = $filesystem;
        }

        /**
         * Return the configured filesystem object.
         *
         * @return mixed
         */
        public function getFilesystem()
        {
            return $this->filesystem;
        }
    }

    class FileFtProcessImageModelObjectStub
    {
        /** @var string */
        public $file_name;

        /** @var FileFtProcessImageUploadDestinationStub */
        public $UploadDestination;

        /** @var bool */
        private $isImage;

        /** @var bool */
        private $isEditableImage;

        /** @var string */
        private $absolutePath;

        /** @var string */
        private $baseServerPath;

        /** @var string */
        private $subfoldersPath;

        /** @var string */
        private $absoluteUrl;

        /** @var string */
        private $manipulationBaseUrl;

        /** @var array<int, string> */
        public $manipulationUrlCalls = [];

        /**
         * Seed the file model consumed by editable-image processing tests.
         *
         * @param array<string, mixed> $attributes
         * @return void
         */
        public function __construct(array $attributes = [])
        {
            $this->file_name = $attributes['file_name'] ?? 'hero.jpg';
            $filesystem = $attributes['filesystem'] ?? new FileFtProcessImageFilesystemStub();
            $this->UploadDestination = new FileFtProcessImageUploadDestinationStub($filesystem);
            $this->isImage = $attributes['isImage'] ?? true;
            $this->isEditableImage = $attributes['isEditableImage'] ?? true;
            $this->absolutePath = $attributes['absolutePath'] ?? '/srv/uploads/gallery/hero.jpg';
            $this->baseServerPath = $attributes['baseServerPath'] ?? '/srv/uploads';
            $this->subfoldersPath = $attributes['subfoldersPath'] ?? '/gallery';
            $this->absoluteUrl = $attributes['absoluteUrl'] ?? 'https://example.com/uploads/gallery/hero.jpg';
            $this->manipulationBaseUrl = $attributes['manipulationBaseUrl'] ?? 'https://example.com/uploads/gallery';
        }

        /**
         * Return whether the model should be treated as an image.
         *
         * @return bool
         */
        public function isImage()
        {
            return $this->isImage;
        }

        /**
         * Return whether the image is editable by the manipulation pipeline.
         *
         * @return bool
         */
        public function isEditableImage()
        {
            return $this->isEditableImage;
        }

        /**
         * Return the absolute file path used as the source image.
         *
         * @return string
         */
        public function getAbsolutePath()
        {
            return $this->absolutePath;
        }

        /**
         * Return the filesystem root for generated manipulation directories.
         *
         * @return string
         */
        public function getBaseServerPath()
        {
            return $this->baseServerPath;
        }

        /**
         * Return the model subfolder suffix for generated manipulations.
         *
         * @return string
         */
        public function getSubfoldersPath()
        {
            return $this->subfoldersPath;
        }

        /**
         * Return the original file URL when transformation setup fails.
         *
         * @return string
         */
        public function getAbsoluteURL()
        {
            return $this->absoluteUrl;
        }

        /**
         * Return the generated manipulation URL for the current file_name.
         *
         * @param string $function
         * @return string
         */
        public function getAbsoluteManipulationURL($function)
        {
            $this->manipulationUrlCalls[] = $function;

            return rtrim($this->manipulationBaseUrl, '/') . '/_' . $function . '/' . $this->file_name;
        }
    }

    abstract class FileFtTestBase extends TestCase
    {
        /** @var FileFtLoadRecorder */
        protected $loadRecorder;

        /** @var FileFtSessionStub */
        protected $sessionMock;

        /** @var FileFtFileFieldStub */
        protected $fileFieldMock;

        /** @var FileFtModelServiceStub */
        protected $modelService;

        /** @var FileFtJavascriptStub */
        protected $javascriptMock;

        /** @var FileFtCpStub */
        protected $cpMock;

        /** @var FileFtCpUrlFactoryStub */
        protected $cpUrlFactory;

        /** @var FileFtTemplateStub */
        protected $templateMock;

        /** @var FileFtConfigStub */
        protected $configMock;

        /** @var FileFtRequestStub */
        protected $requestMock;

        /** @var FileFtPermissionStub */
        protected $permissionMock;

        /** @var FileFtImageLibStub */
        protected $imageLibMock;

        /** @var FileFtValidationServiceStub|null */
        protected $validationService;

        /**
         * Reset the EE mock container and seed the shared File_ft doubles.
         *
         * @return void
         */
        protected function setUp(): void
        {
            parent::setUp();

            ee()->resetMocks();

            if (property_exists('EE_Fieldtype', 'constructCount')) {
                EE_Fieldtype::$constructCount = 0;
            }
            $this->loadRecorder = new FileFtLoadRecorder();
            $this->sessionMock = new FileFtSessionStub();
            $this->fileFieldMock = new FileFtFileFieldStub();
            $this->modelService = new FileFtModelServiceStub();
            $this->javascriptMock = new FileFtJavascriptStub();
            $this->cpMock = new FileFtCpStub();
            $this->cpUrlFactory = new FileFtCpUrlFactoryStub();
            $this->templateMock = new FileFtTemplateStub();
            $this->configMock = new FileFtConfigStub();
            $this->requestMock = new FileFtRequestStub();
            $this->permissionMock = new FileFtPermissionStub();
            $this->imageLibMock = new FileFtImageLibStub();

            ee()->setMock('load', $this->loadRecorder);
            ee()->setMock('session', $this->sessionMock);
            ee()->setMock('file_field', $this->fileFieldMock);
            ee()->setMock('Model', $this->modelService);
            ee()->setMock('javascript', $this->javascriptMock);
            ee()->setMock('cp', $this->cpMock);
            ee()->setMock('CP/URL', $this->cpUrlFactory);
            ee()->setMock('TMPL', $this->templateMock);
            ee()->setMock('config', $this->configMock);
            ee()->setMock('Request', $this->requestMock);
            ee()->setMock('Permission', $this->permissionMock);
            ee()->setMock('image_lib', $this->imageLibMock);
        }

        /**
         * Clear the EE mock container after each test.
         *
         * @return void
         */
        protected function tearDown(): void
        {
            ee()->resetMocks();

            parent::tearDown();
        }

        /**
         * Build a File_ft instance with the requested field context.
         *
         * @param array<string, mixed> $settings
         * @param int $contentId
         * @param string $fieldName
         * @param class-string<File_ft> $fieldtypeClass
         * @return File_ft
         */
        protected function makeFieldtype(array $settings = [], $contentId = 0, $fieldName = 'file_field', $fieldtypeClass = File_ft::class)
        {
            $fieldtype = new $fieldtypeClass();
            $fieldtype->settings = array_merge([
                'field_required' => 'n',
            ], $settings);
            $fieldtype->content_id = $contentId;
            $fieldtype->field_name = $fieldName;

            return $fieldtype;
        }

        /**
         * Seed the session member used by permission checks.
         *
         * @param object|null $member
         * @return void
         */
        protected function setSessionMember($member)
        {
            $this->sessionMock->member = $member;
        }

        /**
         * Register the file model returned for the selected field data.
         *
         * @param mixed $fileModel
         * @return void
         */
        protected function setFileModel($fileModel)
        {
            $this->fileFieldMock->fileModel = $fileModel;
        }

        /**
         * Register the entry model returned for a ChannelEntry lookup.
         *
         * @param int $entryId
         * @param mixed $entry
         * @return void
         */
        protected function setChannelEntry($entryId, $entry)
        {
            $this->modelService->setFirstResult('ChannelEntry', $entryId, $entry);
        }

        /**
         * Register the member returned for the logged-out channel form path.
         *
         * @param int $memberId
         * @param mixed $member
         * @return void
         */
        protected function setMemberModel($memberId, $member)
        {
            $this->modelService->setFirstResult('Member', $memberId, $member);
        }

        /**
         * Override one or more config values used by image-processing tests.
         *
         * @param array<string, mixed> $items
         * @return void
         */
        protected function setConfigItems(array $items)
        {
            $this->configMock->items = array_merge($this->configMock->items, $items);
        }

        /**
         * Seed the request POST payload used by save_settings() tests.
         *
         * @param array<string, mixed> $postValues
         * @return FileFtRequestStub
         */
        protected function setRequestPostValues(array $postValues)
        {
            $this->requestMock = new FileFtRequestStub($postValues);
            ee()->setMock('Request', $this->requestMock);

            return $this->requestMock;
        }

        /**
         * Swap in a custom image-lib double for the current test.
         *
         * @param FileFtImageLibStub $imageLib
         * @return void
         */
        protected function setImageLib(FileFtImageLibStub $imageLib)
        {
            $this->imageLibMock = $imageLib;
            ee()->setMock('image_lib', $imageLib);
        }

        /**
         * Configure the permission service used by debug-image failure paths.
         *
         * @param bool $isSuperAdmin
         * @return void
         */
        protected function setPermissionIsSuperAdmin($isSuperAdmin)
        {
            $this->permissionMock->isSuperAdmin = $isSuperAdmin;
        }

        /**
         * Register the grid model rows used by validation.
         *
         * @param array<mixed> $rows
         * @return FileFtGridModelStub
         */
        protected function setGridRows(array $rows)
        {
            $gridModel = new FileFtGridModelStub($rows);
            ee()->setMock('grid_model', $gridModel);

            return $gridModel;
        }

        /**
         * Enable the channel form logged-out member fallback path.
         *
         * @param int $loggedOutMemberId
         * @return FileFtChannelFormLibStub
         */
        protected function enableChannelFormFallback($loggedOutMemberId = 0)
        {
            $channelFormLib = new FileFtChannelFormLibStub($loggedOutMemberId);
            ee()->setMock('channel_form', (object) []);
            ee()->setMock('channel_form_lib', $channelFormLib);

            return $channelFormLib;
        }

        /**
         * Register the validation service used by validate_settings().
         *
         * @param FileFtValidationValidatorStub|null $validator
         * @return FileFtValidationServiceStub
         */
        protected function setValidationService($validator = null)
        {
            $this->validationService = new FileFtValidationServiceStub($validator);
            ee()->setMock('Validation', $this->validationService);

            return $this->validationService;
        }
    }
}
