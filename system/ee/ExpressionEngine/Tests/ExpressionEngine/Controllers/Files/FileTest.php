<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Files {

    /**
     * Thrown when the controller triggers show_error().
     */
    class FileShowErrorException extends \RuntimeException
    {
    }

    /**
     * Thrown when the controller triggers show_404().
     */
    class FileShow404Exception extends \RuntimeException
    {
    }

    /**
     * Captures force_download() calls without terminating the test run.
     */
    class FileDownloadRecorder
    {
        /** @var array<int, array<string, string>> */
        public static $calls = [];

        /**
         * Clear recorded downloads between tests.
         *
         * @return void
         */
        public static function reset(): void
        {
            self::$calls = [];
        }

        /**
         * Store the download payload for assertions.
         *
         * @param string $filename
         * @param string $data
         * @return void
         */
        public static function record($filename, $data): void
        {
            self::$calls[] = [
                'filename' => (string) $filename,
                'data' => (string) $data,
            ];
        }
    }

    if (! function_exists(__NAMESPACE__ . '\lang')) {
        /**
         * Return the language key for predictable test assertions.
         *
         * @param string $key
         * @return string
         */
        function lang($key)
        {
            return $key;
        }
    }

    if (! function_exists(__NAMESPACE__ . '\show_error')) {
        /**
         * Replace the controller show_error() path with an exception.
         *
         * @param string $message
         * @param int $status
         * @return void
         *
         * @throws FileShowErrorException
         */
        function show_error($message, $status = 500)
        {
            throw new FileShowErrorException((string) $message, $status);
        }
    }

    if (! function_exists(__NAMESPACE__ . '\show_404')) {
        /**
         * Replace the controller show_404() path with an exception.
         *
         * @param string $page
         * @param bool $log_error
         * @return void
         *
         * @throws FileShow404Exception
         */
        function show_404($page = '', $log_error = true)
        {
            throw new FileShow404Exception('show_404');
        }
    }

    if (! function_exists(__NAMESPACE__ . '\force_download')) {
        /**
         * Record the requested download instead of sending a response.
         *
         * @param string $filename
         * @param string $data
         * @return void
         */
        function force_download($filename, $data)
        {
            FileDownloadRecorder::record($filename, $data);
        }
    }

    if (! function_exists(__NAMESPACE__ . '\bool_config_item')) {
        /**
         * Read boolean config values from the test ee() container.
         *
         * @param string $item
         * @return bool
         */
        function bool_config_item($item)
        {
            if (! function_exists('\ee')) {
                return false;
            }

            $value = \ee()->config->item($item);

            if (is_bool($value)) {
                return $value;
            }

            if ($value === null) {
                return false;
            }

            $normalized = strtolower((string) $value);

            if (in_array($normalized, ['true', 'yes', 'y', 'on', '1'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', 'no', 'n', 'off', '0', ''], true)) {
                return false;
            }

            return (bool) $value;
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Files {

use ExpressionEngine\Controller\Files\FileShow404Exception;
use ExpressionEngine\Controller\Files\FileDownloadRecorder;
use ExpressionEngine\Controller\Files\FileShowErrorException;
use ExpressionEngine\Model\File\File as FileModel;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use PHPUnit\Framework\TestCase;

if (! defined('AJAX_REQUEST')) {
    define('AJAX_REQUEST', false);
}

require_once APPPATH . 'core/Controller.php';

/**
 * Behavioral coverage for Files\File controller actions under test.
 */
class FileTest extends TestCase
{
    /** @var TestableFileController */
    private $controller;

    /** @var AlertRecorder */
    private $alerts;

    /** @var CpRecorder */
    private $cp;

    /** @var UploadRecorder */
    private $upload;

    /** @var LoadRecorder */
    private $load;

    /** @var FormValidationRecorder */
    private $formValidation;

    /**
     * Reset EE mocks and create a fresh controller double.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
        ee()->config->setItem('site_id', 1);
        ee()->config->setItem('file_manager_compatibility_mode', 'n');
        FileDownloadRecorder::reset();

        $this->controller = (new \ReflectionClass(TestableFileController::class))
            ->newInstanceWithoutConstructor();

        $this->alerts = new AlertRecorder();
        $this->cp = new CpRecorder();
        $this->upload = new UploadRecorder();
        $this->load = new LoadRecorder();
        $this->formValidation = new FormValidationRecorder();

        ee()->setMock('Permission', new PermissionRecorder(['edit_files' => true]));
        ee()->setMock('session', new SessionRecorder((object) ['member_id' => 7]));
        ee()->setMock('Request', new RequestRecorder());
        ee()->setMock('CP/Alert', $this->alerts);
        ee()->setMock('cp', $this->cp);
        ee()->setMock('view', (object) [
            'cp_page_title' => null,
            'header' => [],
            'cp_breadcrumbs' => [],
        ]);
        ee()->setMock('CP/URL', new UrlFactoryRecorder());
        ee()->setMock('Format', new FormatRecorder());
        ee()->setMock('File', new FileServiceRecorder($this->upload));
        ee()->setMock('load', $this->load);
        ee()->setMock('image_lib', new ImageLibRecorder());
        ee()->setMock('form_validation', $this->formValidation);
    }

    /**
     * Clear static EE mock state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        FileDownloadRecorder::reset();
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    /**
     * Assert edit_files permission is enforced before model access.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testViewShowsUnauthorizedErrorWhenEditFilesPermissionIsMissing()
    {
        ee()->setMock('Permission', new PermissionRecorder(['edit_files' => false]));

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('unauthorized_access');

        $this->controller->view(10);
    }

    /**
     * Assert the missing model result exits through the no_file error path.
     *
     * @return void
     */
    public function testViewShowsNoFileErrorWhenModelLookupReturnsNothing()
    {
        $this->bindFileToModel(null);

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionMessage('no_file');

        $this->controller->view(15);
    }

    /**
     * Assert member access is enforced after the file is loaded.
     *
     * @return void
     */
    public function testViewShowsUnauthorizedErrorWhenMemberCannotAccessFile()
    {
        $file = new TestFileModel([
            'memberHasAccess' => false,
        ]);
        $this->bindFileToModel($file);

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('unauthorized_access');

        $this->controller->view(20);
    }

    /**
     * Assert the missing file path raises a 404 and adds directory guidance.
     *
     * @return void
     */
    public function testViewShowsNotFoundAlertWhenFileAndDirectoryAreMissing()
    {
        $file = new TestFileModel([
            'exists' => false,
            'UploadDestination' => new UploadDestinationStub([
                'exists' => false,
                'id' => 44,
                'server_path' => '/missing/uploads',
            ]),
        ]);
        $this->bindFileToModel($file);

        $this->expectException(FileShow404Exception::class);

        try {
            $this->controller->view(21);
        } finally {
            $this->assertCount(1, $this->alerts->records);
            $this->assertSame('standard', $this->alerts->records[0]->type);
            $this->assertSame('issue', $this->alerts->records[0]->state);
            $this->assertSame('file_not_found', $this->alerts->records[0]->title);
            $this->assertCount(3, $this->alerts->records[0]->body);
            $this->assertTrue($this->alerts->records[0]->finalized);
        }
    }

    /**
     * Assert the modal path warns about unwritable files and omits save_and_close.
     *
     * @return void
     */
    public function testViewRendersModalFormWithInlineAlertForUnwritableFiles()
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'y');
        ee()->setMock('Request', new RequestRecorder([], ['modal_form' => 'y']));

        $file = new TestFileModel([
            'isWritable' => false,
            'isEditableImage' => true,
        ]);
        $this->bindFileToModel($file);

        $this->controller->view(22);

        $this->assertCount(1, $this->alerts->records);
        $this->assertSame('inline', $this->alerts->records[0]->type);
        $this->assertSame('shared-form', $this->alerts->records[0]->name);
        $this->assertSame('file_not_writable', $this->alerts->records[0]->title);

        $this->assertCount(1, $this->cp->renderCalls);
        $this->assertSame('files/edit', $this->cp->renderCalls[0]['view']);
        $this->assertTrue($this->cp->renderCalls[0]['vars']['modal_form']);
        $this->assertCount(1, $this->cp->renderCalls[0]['vars']['buttons']);
        $this->assertArrayNotHasKey('usage', $this->cp->renderCalls[0]['vars']['tabs']);
        $this->assertArrayNotHasKey('crop', $this->cp->renderCalls[0]['vars']['tabs']);
    }

    /**
     * Assert the non-modal editable image path renders image and usage tabs.
     *
     * @return void
     */
    public function testViewRendersImageTabsManipulationsAndUsageWhenImageIsEditable()
    {
        $file = new TestFileModel([
            'isEditableImage' => true,
            'UploadDestination' => new UploadDestinationStub([
                'dimensionsCount' => 2,
            ]),
        ]);
        $this->bindFileToModel($file);
        $invalid = $this->makeInvalidValidationResult();
        $this->upload->validationResult = $invalid;

        $this->controller->view(23);

        $this->assertSame([
            ['UploadDestination', 'UploadAuthor', 'ModifyAuthor'],
        ], $this->lastModelQuery()->withCalls);
        $this->assertSame([
            ['site_id', 'IN', [1, 0]],
        ], $this->lastModelQuery()->filterCalls);

        $tabs = $this->cp->renderCalls[0]['vars']['tabs'];

        $this->assertArrayHasKey('file_data', $tabs);
        $this->assertArrayHasKey('categories', $tabs);
        $this->assertArrayHasKey('crop', $tabs);
        $this->assertArrayHasKey('rotate', $tabs);
        $this->assertArrayHasKey('resize', $tabs);
        $this->assertArrayHasKey('manipulations', $tabs);
        $this->assertArrayHasKey('usage', $tabs);
        $this->assertSame($invalid, $tabs['file_data']['errors']);
        $this->assertSame($invalid, $tabs['categories']['errors']);
        $this->assertCount(2, $this->cp->renderCalls[0]['vars']['buttons']);
        $this->assertSame('btn_edit_file_meta', $this->cp->renderCalls[0]['vars']['save_btn_text']);
        $this->assertSame('compiled:files/file/view/23', (string) $this->cp->renderCalls[0]['vars']['base_url']);
        $this->assertSame('compiled:files/file/download/99', (string) $this->cp->renderCalls[0]['vars']['download_url']);
        $this->assertSame('edit_file_metadata', ee()->view->cp_page_title);
        $this->assertSame('edit_file', ee()->view->header['title']);
        $this->assertSame('Gallery', array_values(ee()->view->cp_breadcrumbs)[1]);
    }

    /**
     * Assert successful validation delegates to saveFileAndRedirect().
     *
     * @return void
     */
    public function testViewCallsSaveFileAndRedirectWhenValidationSucceeds()
    {
        $file = new TestFileModel();
        $this->bindFileToModel($file);
        $this->upload->validationResult = new ValidationResult();

        $this->expectException(FileRedirectInterceptedException::class);

        try {
            $this->controller->view(24);
        } finally {
            $this->assertCount(1, $this->controller->savedFiles);
            $this->assertSame($file, $this->controller->savedFiles[0]);
        }
    }

    /**
     * Assert resize actions route through modify() before the normal render path.
     *
     * @return void
     */
    public function testViewInvokesModifyForRecognizedActions()
    {
        ee()->config->setItem('file_manager_compatibility_mode', 'y');
        ee()->setMock('Request', new RequestRecorder(['action' => 'resize'], ['modal_form' => 'y']));

        $file = new TestFileModel([
            'isEditableImage' => false,
        ]);
        $this->bindFileToModel($file);

        $this->controller->view(25);

        $this->assertSame(['image_lib', 'form_validation'], $this->load->libraries);
        $this->assertCount(2, $this->formValidation->rules);
        $this->assertSame('resize_width', $this->formValidation->rules[0][0]);
        $this->assertSame('resize_height', $this->formValidation->rules[1][0]);
        $this->assertCount(1, $this->cp->renderCalls);
    }

    /**
     * Assert download() exits through the missing-file error branch.
     *
     * @return void
     */
    public function testDownloadShowsNoFileErrorWhenModelLookupReturnsNothing()
    {
        $this->bindFileToModel(null);

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionMessage('no_file');

        try {
            $this->controller->download(26);
        } finally {
            $this->assertSame([
                ['UploadDestination'],
            ], $this->lastModelQuery()->withCalls);
            $this->assertSame([
                ['site_id', 'IN', [1, 0]],
            ], $this->lastModelQuery()->filterCalls);
            $this->assertSame([], $this->load->helpers);
            $this->assertSame([], FileDownloadRecorder::$calls);
        }
    }

    /**
     * Assert download() rejects members without file access.
     *
     * @return void
     */
    public function testDownloadShowsUnauthorizedErrorWhenMemberCannotAccessFile()
    {
        $filesystem = new FilesystemRecorder('restricted-bytes');
        $file = new TestFileModel([
            'memberHasAccess' => false,
            'UploadDestination' => new UploadDestinationStub([
                'filesystem' => $filesystem,
            ]),
        ]);
        $this->bindFileToModel($file);

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('unauthorized_access');

        try {
            $this->controller->download(27);
        } finally {
            $this->assertCount(1, $file->memberAccessChecks);
            $this->assertSame(ee()->session->getMember(), $file->memberAccessChecks[0]);
            $this->assertSame([], $this->load->helpers);
            $this->assertSame([], $filesystem->reads);
            $this->assertSame([], FileDownloadRecorder::$calls);
        }
    }

    /**
     * Assert download() loads the helper, reads the file, and forwards bytes.
     *
     * @return void
     */
    public function testDownloadForcesTheResolvedFilesystemBytes()
    {
        $filesystem = new FilesystemRecorder('banner-bytes');
        $file = new TestFileModel([
            'file_name' => 'hero.jpg',
            'absolutePath' => '/var/www/html/hero.jpg',
            'UploadDestination' => new UploadDestinationStub([
                'filesystem' => $filesystem,
            ]),
        ]);
        $this->bindFileToModel($file);

        $this->controller->download(28);

        $this->assertSame([
            ['UploadDestination'],
        ], $this->lastModelQuery()->withCalls);
        $this->assertSame([
            ['site_id', 'IN', [1, 0]],
        ], $this->lastModelQuery()->filterCalls);
        $this->assertCount(1, $file->memberAccessChecks);
        $this->assertSame(ee()->session->getMember(), $file->memberAccessChecks[0]);
        $this->assertSame(['download'], $this->load->helpers);
        $this->assertSame(['/var/www/html/hero.jpg'], $filesystem->reads);
        $this->assertSame([
            [
                'filename' => 'hero.jpg',
                'data' => 'banner-bytes',
            ],
        ], FileDownloadRecorder::$calls);
    }

    /**
     * Attach a model query stub for the provided file lookup result.
     *
     * @param TestFileModel|null $file
     * @return void
     */
    private function bindFileToModel($file): void
    {
        $query = new ModelQueryRecorder($file);
        ee()->setMock('Model', new ModelServiceRecorder($query));
    }

    /**
     * Return the most recent file query recorder.
     *
     * @return ModelQueryRecorder
     */
    private function lastModelQuery(): ModelQueryRecorder
    {
        /** @var ModelServiceRecorder $model */
        $model = ee('Model');

        return $model->lastQuery;
    }

    /**
     * Create an invalid validation result object for form rendering tests.
     *
     * @return ValidationResult
     */
    private function makeInvalidValidationResult(): ValidationResult
    {
        $result = new ValidationResult();
        $result->addFailed('title', new class {
            /**
             * Return a predictable rule name for the validation result.
             *
             * @return string
             */
            public function getName()
            {
                return 'required';
            }
        });

        return $result;
    }
}

/**
 * Test double for Files\File protected collaborators.
 */
class TestableFileController extends \ExpressionEngine\Controller\Files\File
{
    /** @var array<int, FileModel> */
    public $savedFiles = [];

    /**
     * Intercept saveFileAndRedirect() so the test can assert delegation.
     *
     * @param FileModel $file
     * @param bool $is_new
     * @param mixed $sub_alert
     * @return void
     *
     * @throws FileRedirectInterceptedException
     */
    protected function saveFileAndRedirect(FileModel $file, $is_new = false, $sub_alert = null)
    {
        $this->savedFiles[] = $file;

        throw new FileRedirectInterceptedException('save intercepted');
    }

    /**
     * Return a predictable usage form payload.
     *
     * @param mixed $file
     * @return array<string, mixed>
     */
    protected function renderUsageForm($file)
    {
        return ['name' => 'usage', 'file_id' => $file->file_id];
    }

    /**
     * Return a predictable manipulations payload.
     *
     * @param mixed $file
     * @return array<string, mixed>
     */
    protected function renderManipulationsForm($file)
    {
        return ['name' => 'manipulations', 'file_id' => $file->file_id];
    }

    /**
     * Return a predictable crop payload.
     *
     * @param mixed $file
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    protected function renderCropForm($file, $info)
    {
        return ['name' => 'crop', 'info' => $info];
    }

    /**
     * Return a predictable rotate payload.
     *
     * @param mixed $file
     * @return array<string, mixed>
     */
    protected function renderRotateForm($file)
    {
        return ['name' => 'rotate', 'file_id' => $file->file_id];
    }

    /**
     * Return a predictable resize payload.
     *
     * @param mixed $file
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    protected function renderResizeForm($file, $info)
    {
        return ['name' => 'resize', 'info' => $info];
    }
}

/**
 * Raised when saveFileAndRedirect() is intentionally intercepted.
 */
class FileRedirectInterceptedException extends \RuntimeException
{
}

/**
 * Records permission checks for the controller.
 */
class PermissionRecorder
{
    /** @var array<string, bool> */
    private $permissions;

    /**
     * Store permission answers by key.
     *
     * @param array<string, bool> $permissions
     * @return void
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Return the configured permission flag.
     *
     * @param string $permission
     * @return bool
     */
    public function can($permission)
    {
        return $this->permissions[$permission] ?? true;
    }
}

/**
 * Provides a fixed session member.
 */
class SessionRecorder
{
    /** @var object */
    private $member;

    /**
     * Store the member returned by getMember().
     *
     * @param object $member
     * @return void
     */
    public function __construct($member)
    {
        $this->member = $member;
    }

    /**
     * Return the configured member.
     *
     * @return object
     */
    public function getMember()
    {
        return $this->member;
    }
}

/**
 * Stores request GET and POST values.
 */
class RequestRecorder
{
    /** @var array<string, mixed> */
    private $post;

    /** @var array<string, mixed> */
    private $get;

    /**
     * Capture request payload state.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $get
     * @return void
     */
    public function __construct(array $post = [], array $get = [])
    {
        $this->post = $post;
        $this->get = $get;
    }

    /**
     * Return a configured POST value.
     *
     * @param string $key
     * @return mixed
     */
    public function post($key)
    {
        return $this->post[$key] ?? null;
    }

    /**
     * Return a configured GET value.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->get[$key] ?? null;
    }
}

/**
 * Supplies model queries for file lookups.
 */
class ModelServiceRecorder
{
    /** @var ModelQueryRecorder */
    public $lastQuery;

    /**
     * Store the query object returned by get().
     *
     * @param ModelQueryRecorder $query
     * @return void
     */
    public function __construct(ModelQueryRecorder $query)
    {
        $this->lastQuery = $query;
    }

    /**
     * Return the configured query recorder.
     *
     * @param string $model
     * @param int $id
     * @return ModelQueryRecorder
     */
    public function get($model, $id)
    {
        return $this->lastQuery;
    }
}

/**
 * Records file model query chaining.
 */
class ModelQueryRecorder
{
    /** @var TestFileModel|null */
    private $file;

    /** @var array<int, array<int, string>> */
    public $withCalls = [];

    /** @var array<int, array<int, mixed>> */
    public $filterCalls = [];

    /**
     * Store the first() return value.
     *
     * @param TestFileModel|null $file
     * @return void
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Record eager-loaded relations.
     *
     * @param string ...$relations
     * @return self
     */
    public function with(...$relations)
    {
        $this->withCalls[] = $relations;

        return $this;
    }

    /**
     * Record a filter constraint.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function filter($field, $operator, $value)
    {
        $this->filterCalls[] = [$field, $operator, $value];

        return $this;
    }

    /**
     * Keep the fluent chain alive.
     *
     * @return self
     */
    public function all()
    {
        return $this;
    }

    /**
     * Return the configured file lookup result.
     *
     * @return TestFileModel|null
     */
    public function first()
    {
        return $this->file;
    }
}

/**
 * Provides makeUpload() for ee('File').
 */
class FileServiceRecorder
{
    /** @var UploadRecorder */
    private $upload;

    /**
     * Store the upload recorder.
     *
     * @param UploadRecorder $upload
     * @return void
     */
    public function __construct(UploadRecorder $upload)
    {
        $this->upload = $upload;
    }

    /**
     * Return the shared upload recorder.
     *
     * @return UploadRecorder
     */
    public function makeUpload()
    {
        return $this->upload;
    }
}

/**
 * Records upload service interactions.
 */
class UploadRecorder
{
    /** @var mixed */
    public $validationResult = null;

    /**
     * Return the configured validation result.
     *
     * @param mixed $file
     * @return mixed
     */
    public function validateFile($file)
    {
        return $this->validationResult;
    }

    /**
     * Return predictable file data form payload.
     *
     * @param mixed $file
     * @param mixed $errors
     * @return array<string, mixed>
     */
    public function getFileDataForm($file, $errors)
    {
        return ['name' => 'file_data', 'errors' => $errors];
    }

    /**
     * Return predictable category form payload.
     *
     * @param mixed $file
     * @param mixed $errors
     * @return array<string, mixed>
     */
    public function getCategoryForm($file, $errors)
    {
        return ['name' => 'categories', 'errors' => $errors];
    }
}

/**
 * Captures alert builders and their final state.
 */
class AlertRecorder
{
    /** @var array<int, AlertRecord> */
    public $records = [];

    /**
     * Start a standard alert chain.
     *
     * @return AlertRecord
     */
    public function makeStandard()
    {
        $record = new AlertRecord('standard');
        $this->records[] = $record;

        return $record;
    }

    /**
     * Start an inline alert chain.
     *
     * @param string $name
     * @return AlertRecord
     */
    public function makeInline($name)
    {
        $record = new AlertRecord('inline', $name);
        $this->records[] = $record;

        return $record;
    }
}

/**
 * Mutable alert chain object.
 */
class AlertRecord
{
    /** @var string */
    public $type;

    /** @var string|null */
    public $name;

    /** @var string|null */
    public $state;

    /** @var string|null */
    public $title;

    /** @var array<int, string> */
    public $body = [];

    /** @var bool */
    public $finalized = false;

    /**
     * Seed a new alert record.
     *
     * @param string $type
     * @param string|null $name
     * @return void
     */
    public function __construct($type, $name = null)
    {
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * Mark the alert as an issue.
     *
     * @return self
     */
    public function asIssue()
    {
        $this->state = 'issue';

        return $this;
    }

    /**
     * Mark the alert as a success.
     *
     * @return self
     */
    public function asSuccess()
    {
        $this->state = 'success';

        return $this;
    }

    /**
     * Store the alert title.
     *
     * @param string $title
     * @return self
     */
    public function withTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Append a body line.
     *
     * @param string $body
     * @return self
     */
    public function addToBody($body)
    {
        $this->body[] = $body;

        return $this;
    }

    /**
     * Mark the alert as shown immediately.
     *
     * @return self
     */
    public function now()
    {
        $this->finalized = true;

        return $this;
    }

    /**
     * Mark the alert as deferred.
     *
     * @return self
     */
    public function defer()
    {
        $this->finalized = true;

        return $this;
    }
}

/**
 * Captures render and JS registration calls.
 */
class CpRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $renderCalls = [];

    /** @var array<int, array<string, mixed>> */
    public $scripts = [];

    /**
     * Record a CP JavaScript registration.
     *
     * @param array<string, mixed> $script
     * @return void
     */
    public function add_js_script($script)
    {
        $this->scripts[] = $script;
    }

    /**
     * Record a CP render invocation.
     *
     * @param string $view
     * @param array<string, mixed> $vars
     * @return void
     */
    public function render($view, $vars)
    {
        $this->renderCalls[] = [
            'view' => $view,
            'vars' => $vars,
        ];
    }
}

/**
 * Records requested libraries.
 */
class LoadRecorder
{
    /** @var array<int, string> */
    public $libraries = [];

    /** @var array<int, string> */
    public $helpers = [];

    /**
     * Record the requested library name.
     *
     * @param string $library
     * @return void
     */
    public function library($library)
    {
        $this->libraries[] = $library;
    }

    /**
     * Record the requested helper name.
     *
     * @param string $helper
     * @return void
     */
    public function helper($helper)
    {
        $this->helpers[] = $helper;
    }
}

/**
 * Builds URL value objects for the controller.
 */
class UrlFactoryRecorder
{
    /**
     * Return a predictable URL value object.
     *
     * @param string $path
     * @return UrlValue
     */
    public function make($path)
    {
        return new UrlValue($path);
    }
}

/**
 * Stringable URL value used by CP/URL.
 */
class UrlValue
{
    /** @var string */
    private $path;

    /**
     * Store the requested CP path.
     *
     * @param string $path
     * @return void
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Return the compiled URL string.
     *
     * @return string
     */
    public function compile()
    {
        return 'compiled:' . $this->path;
    }

    /**
     * Return the compiled URL string when cast.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->compile();
    }
}

/**
 * Returns a predictable byte string for file sizes.
 */
class FormatRecorder
{
    /**
     * Return a formatter for the provided value.
     *
     * @param string $type
     * @param mixed $value
     * @return ByteValue
     */
    public function make($type, $value)
    {
        return new ByteValue($value);
    }
}

/**
 * Formats file sizes for assertions.
 */
class ByteValue
{
    /** @var mixed */
    private $value;

    /**
     * Store the numeric byte value.
     *
     * @param mixed $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return a stringified byte value.
     *
     * @return string
     */
    public function bytes()
    {
        return (string) $this->value . ' bytes';
    }
}

/**
 * Records image property lookups.
 */
class ImageLibRecorder
{
    /** @var array<int, array<int, mixed>> */
    public $calls = [];

    /** @var array<int, mixed> */
    public $error_msg = ['stale-error'];

    /**
     * Return predictable image dimensions.
     *
     * @param string $path
     * @param bool $cached
     * @return array<string, int>
     */
    public function get_image_properties($path, $cached)
    {
        $this->calls[] = [$path, $cached];

        return ['height' => 100, 'width' => 200];
    }
}

/**
 * Records form validation rule setup.
 */
class FormValidationRecorder
{
    /** @var array<int, array<int, string>> */
    public $rules = [];

    /**
     * Capture a validation rule.
     *
     * @param string $field
     * @param string $label
     * @param string $rules
     * @return void
     */
    public function set_rules($field, $label, $rules)
    {
        $this->rules[] = [$field, $label, $rules];
    }

    /**
     * Return false so modify() stays on the render path.
     *
     * @return bool
     */
    public function run()
    {
        return false;
    }

    /**
     * Return false so modify() does not emit an alert.
     *
     * @return bool
     */
    public function errors_exist()
    {
        return false;
    }

    /**
     * Satisfy the AJAX branch contract if it is ever reached.
     *
     * @return void
     */
    public function run_ajax()
    {
    }
}

/**
 * File model double for controller view tests.
 */
class TestFileModel extends FileModel
{
    /** @var bool */
    private $memberHasAccess;

    /** @var bool */
    private $existsValue;

    /** @var bool */
    private $isWritableValue;

    /** @var bool */
    private $isEditableImageValue;

    /** @var bool */
    private $isImageValue;

    /** @var string */
    private $absolutePath;

    /** @var string */
    private $filesystem;

    /** @var UploadDestinationStub */
    public $UploadDestination;

    /** @var array<int, object> */
    public $memberAccessChecks = [];

    /** @var int */
    public $file_id = 99;

    /** @var int */
    public $file_size = 2048;

    /** @var int */
    public $total_records = 12;

    /** @var string */
    public $title = 'Banner';

    /** @var string */
    public $file_name = 'banner.png';

    /** @var string */
    public $file_hw_original = '100 200';

    /**
     * Seed the file double with behavior flags.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->memberHasAccess = $attributes['memberHasAccess'] ?? true;
        $this->existsValue = $attributes['exists'] ?? true;
        $this->isWritableValue = $attributes['isWritable'] ?? true;
        $this->isEditableImageValue = $attributes['isEditableImage'] ?? false;
        $this->isImageValue = $attributes['isImage'] ?? true;
        $this->absolutePath = $attributes['absolutePath'] ?? '/var/www/html/banner.png';
        $this->filesystem = $attributes['filesystem'] ?? 'local';
        $this->UploadDestination = $attributes['UploadDestination'] ?? new UploadDestinationStub();

        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Return the configured member access value.
     *
     * @param object $member
     * @return bool
     */
    public function memberHasAccess($member)
    {
        $this->memberAccessChecks[] = $member;

        return $this->memberHasAccess;
    }

    /**
     * Return the configured existence flag.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->existsValue;
    }

    /**
     * Return the configured writability flag.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->isWritableValue;
    }

    /**
     * Return the configured editable-image flag.
     *
     * @return bool
     */
    public function isEditableImage()
    {
        return $this->isEditableImageValue;
    }

    /**
     * Return the configured image flag.
     *
     * @return bool
     */
    public function isImage()
    {
        return $this->isImageValue;
    }

    /**
     * Return the absolute file path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    /**
     * Return the file upload destination.
     *
     * @return UploadDestinationStub
     */
    public function getUploadDestination()
    {
        return $this->UploadDestination;
    }

    /**
     * Execute the callback against the configured path.
     *
     * @param callable $callback
     * @return mixed
     */
    public function actLocally(callable $callback)
    {
        return $callback($this->absolutePath);
    }

    /**
     * Return the filesystem label used by modify().
     *
     * @return string
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

/**
 * Upload destination double for breadcrumbs and alert context.
 */
class UploadDestinationStub
{
    /** @var int */
    public $id = 7;

    /** @var string */
    public $name = 'Gallery';

    /** @var string */
    public $server_path = '/var/www/html/images';

    /** @var FileDimensionsStub */
    public $FileDimensions;

    /** @var bool */
    private $existsValue;

    /** @var FilesystemRecorder */
    private $filesystem;

    /**
     * Seed upload destination state.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->existsValue = $attributes['exists'] ?? true;
        $this->filesystem = $attributes['filesystem'] ?? new FilesystemRecorder('file-bytes');
        $dimensionsCount = $attributes['dimensionsCount'] ?? 0;
        $this->FileDimensions = new FileDimensionsStub($dimensionsCount);

        foreach ($attributes as $key => $value) {
            if ($key === 'exists' || $key === 'dimensionsCount') {
                continue;
            }

            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Return the configured existence flag.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->existsValue;
    }

    /**
     * Return the upload destination identifier.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the configured file dimensions collection.
     *
     * @return FileDimensionsStub
     */
    public function getFileDimensions()
    {
        return $this->FileDimensions;
    }

    /**
     * Return the configured filesystem double.
     *
     * @return FilesystemRecorder
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

/**
 * Records filesystem reads for download assertions.
 */
class FilesystemRecorder
{
    /** @var array<int, string> */
    public $reads = [];

    /** @var string */
    private $contents;

    /**
     * Store the bytes returned for any read() call.
     *
     * @param string $contents
     * @return void
     */
    public function __construct($contents)
    {
        $this->contents = $contents;
    }

    /**
     * Record the requested path and return the configured bytes.
     *
     * @param string $path
     * @return string
     */
    public function read($path)
    {
        $this->reads[] = $path;

        return $this->contents;
    }
}

/**
 * Minimal dimensions collection for FileDimensions access.
 */
class FileDimensionsStub
{
    /** @var int */
    private $countValue;

    /**
     * Store the collection size.
     *
     * @param int $countValue
     * @return void
     */
    public function __construct($countValue)
    {
        $this->countValue = $countValue;
    }

    /**
     * Return the number of file dimensions.
     *
     * @return int
     */
    public function count()
    {
        return $this->countValue;
    }

    /**
     * Return a predictable array representation.
     *
     * @return array<int, array<string, int>>
     */
    public function asArray()
    {
        return array_fill(0, $this->countValue, ['id' => 1]);
    }
}

}
