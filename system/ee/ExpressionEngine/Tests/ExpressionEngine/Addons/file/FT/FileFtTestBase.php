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
            public static $constructCount = 0;

            /** @var array<string, mixed> */
            public $settings = [];

            /** @var int */
            public $content_id = 0;

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
        }
    }

    require_once dirname(__DIR__, 5) . '/Addons/file/ft.file.php';

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

        /** @var array<int, array<string, mixed>> */
        public $dragAndDropCalls = [];

        /** @var array<int, array<string, mixed>> */
        public $fieldCalls = [];

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

        /**
         * Reset the EE mock container and seed the shared File_ft doubles.
         *
         * @return void
         */
        protected function setUp(): void
        {
            parent::setUp();

            ee()->resetMocks();

            EE_Fieldtype::$constructCount = 0;
            $this->loadRecorder = new FileFtLoadRecorder();
            $this->sessionMock = new FileFtSessionStub();
            $this->fileFieldMock = new FileFtFileFieldStub();
            $this->modelService = new FileFtModelServiceStub();
            $this->javascriptMock = new FileFtJavascriptStub();
            $this->cpMock = new FileFtCpStub();
            $this->cpUrlFactory = new FileFtCpUrlFactoryStub();

            ee()->setMock('load', $this->loadRecorder);
            ee()->setMock('session', $this->sessionMock);
            ee()->setMock('file_field', $this->fileFieldMock);
            ee()->setMock('Model', $this->modelService);
            ee()->setMock('javascript', $this->javascriptMock);
            ee()->setMock('cp', $this->cpMock);
            ee()->setMock('CP/URL', $this->cpUrlFactory);
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
    }
}
