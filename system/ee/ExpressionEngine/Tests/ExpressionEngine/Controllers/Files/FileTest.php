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
        $_POST = [];
        $_GET = [];
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
        $_POST = [];
        $_GET = [];
        FileDownloadRecorder::reset();
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    /**
     * Ensure image metadata is considered usable only when dimensions are readable.
     *
     * @return void
     */
    public function testUsableImagePropertiesRequiresReadableDimensions()
    {
        $this->assertTrue($this->hasUsableImageProperties(['width' => 120, 'height' => 80]));
        $this->assertTrue($this->hasUsableImageProperties(['width' => '120', 'height' => '80']));

        $this->assertFalse($this->hasUsableImageProperties(false));
        $this->assertFalse($this->hasUsableImageProperties([]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 120]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 120, 'height' => 0]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 'bad', 'height' => 80]));
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
     * Assert modify() rejects actions that view() cannot route to directly.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testModifyShowsUnauthorizedErrorForUnknownAction()
    {
        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('unauthorized_access');

        $this->invokeModify(new TestFileModel(), 'flip');
    }

    /**
     * Assert modify() rejects non-image files before any image work happens.
     *
     * @return void
     */
    public function testViewShowsNotAnImageErrorWhenModifyTargetsANonImage()
    {
        $this->setModifyRequest('rotate');
        $file = new TestFileModel([
            'isImage' => false,
        ]);
        $this->bindFileToModel($file);

        $this->expectException(FileShowErrorException::class);
        $this->expectExceptionMessage('not_an_image');

        try {
            $this->controller->view(29);
        } finally {
            $this->assertSame([], $this->load->libraries);
            $this->assertSame([], $this->alerts->records);
        }
    }

    /**
     * Assert modify() raises a 404 and includes missing-directory guidance.
     *
     * @return void
     */
    public function testViewShowsModifyNotFoundAlertWhenFileAndDirectoryAreMissing()
    {
        $this->setModifyRequest('crop');
        $file = new TestFileModel([
            'exists' => false,
            'UploadDestination' => new UploadDestinationStub([
                'exists' => false,
                'id' => 55,
                'server_path' => '/missing/modify',
            ]),
        ]);
        $this->bindFileToModel($file);

        $this->expectException(FileShow404Exception::class);

        try {
            $this->controller->view(30);
        } finally {
            $this->assertCount(1, $this->alerts->records);
            $this->assertSame('standard', $this->alerts->records[0]->type);
            $this->assertSame('file_not_found', $this->alerts->records[0]->title);
            $this->assertCount(3, $this->alerts->records[0]->body);
            $this->assertSame([], $this->cp->renderCalls);
        }
    }

    /**
     * Assert crop validation failures surface the inline modify alert.
     *
     * @return void
     */
    public function testViewShowsModifyAlertWhenCropValidationFails()
    {
        $this->setModifyRequest('crop');
        $this->formValidation->errorsExist = true;
        $file = new TestFileModel();
        $this->bindFileToModel($file);

        $this->controller->view(31);

        $this->assertSame(['image_lib', 'form_validation'], $this->load->libraries);
        $this->assertSame([
            ['crop_width', 'lang:width', 'trim|is_natural_no_zero|required'],
            ['crop_height', 'lang:height', 'trim|is_natural_no_zero|required'],
            ['crop_x', 'lang:x_axis', 'trim|numeric|required'],
            ['crop_y', 'lang:y_axis', 'trim|numeric|required'],
        ], $this->formValidation->rules);
        $this->assertCount(1, $this->alerts->records);
        $this->assertSame('inline', $this->alerts->records[0]->type);
        $this->assertSame('file-modify', $this->alerts->records[0]->name);
        $this->assertSame('issue', $this->alerts->records[0]->state);
        $this->assertSame('crop_file_error', $this->alerts->records[0]->title);
        $this->assertSame(['crop_file_error_desc'], $this->alerts->records[0]->body);
    }

    /**
     * Assert crop failures from the legacy filemanager stay inline.
     *
     * @return void
     */
    public function testViewShowsModifyAlertWhenCropFilemanagerFails()
    {
        $this->setModifyRequest('crop', [
            'crop_width' => 25,
            'crop_height' => 15,
            'crop_x' => 3,
            'crop_y' => 4,
        ]);
        $this->formValidation->runResult = true;
        $filemanager = new FileManagerRecorder();
        $filemanager->cropResponse = ['errors' => 'crop failed'];
        ee()->setMock('filemanager', $filemanager);
        $file = new TestFileModel();
        $this->bindFileToModel($file);

        $this->controller->view(32);

        $this->assertSame(['/var/www/html/banner.png', 'local'], $filemanager->cropCalls[0]);
        $this->assertCount(1, $this->alerts->records);
        $this->assertSame('issue', $this->alerts->records[0]->state);
        $this->assertSame('crop_file_error', $this->alerts->records[0]->title);
        $this->assertSame(['crop failed'], $this->alerts->records[0]->body);
    }

    /**
     * Assert rotate failures surface the correct inline alert and delegate.
     *
     * @return void
     */
    public function testViewShowsModifyAlertWhenRotateFilemanagerFails()
    {
        $this->setModifyRequest('rotate', [
            'rotate' => '90_r',
        ]);
        $this->formValidation->runResult = true;
        $filemanager = new FileManagerRecorder();
        $filemanager->rotateResponse = ['errors' => 'rotate failed'];
        ee()->setMock('filemanager', $filemanager);
        $file = new TestFileModel();
        $this->bindFileToModel($file);

        $this->controller->view(33);

        $this->assertSame([
            ['rotate', 'lang:rotate', 'required'],
        ], $this->formValidation->rules);
        $this->assertSame(['/var/www/html/banner.png', 'local'], $filemanager->rotateCalls[0]);
        $this->assertCount(1, $this->alerts->records);
        $this->assertSame('issue', $this->alerts->records[0]->state);
        $this->assertSame('crop_file_error', $this->alerts->records[0]->title);
        $this->assertSame(['rotate failed'], $this->alerts->records[0]->body);
    }

    /**
     * Assert resize fills a missing width, saves metadata, and regenerates thumbs.
     *
     * @return void
     */
    public function testViewResizesAndRegeneratesThumbnailsWhenWidthIsMissing()
    {
        $this->setModifyRequest('resize', [
            'resize_width' => '',
            'resize_height' => 50,
        ]);
        $this->formValidation->runResult = true;
        $filemanager = new FileManagerRecorder();
        $filemanager->resizeResponse = [
            'dimensions' => ['height' => 50, 'width' => 100],
            'file_info' => ['size' => 4096],
        ];
        ee()->setMock('filemanager', $filemanager);
        $file = new TestFileModel([
            'isWritable' => false,
            'UploadDestination' => new UploadDestinationStub([
                'dimensionsCount' => 2,
            ]),
        ]);
        $this->bindFileToModel($file);

        $this->controller->view(34);

        $this->assertSame(100.0, (float) $_POST['resize_width']);
        $this->assertSame(['/var/www/html/banner.png', 'local'], $filemanager->resizeCalls[0]);
        $this->assertSame('50 100', $file->file_hw_original);
        $this->assertSame(4096, $file->file_size);
        $this->assertSame(1, $file->saveCalls);
        $this->assertCount(1, $filemanager->createThumbCalls);
        $this->assertSame('/var/www/html/banner.png', $filemanager->createThumbCalls[0][0]);
        $this->assertSame('/var/www/html/images', $filemanager->createThumbCalls[0][1]['server_path']);
        $this->assertCount(2, $filemanager->createThumbCalls[0][1]['dimensions']);
        $this->assertTrue($filemanager->createThumbCalls[0][2]);
        $this->assertFalse($filemanager->createThumbCalls[0][3]);
        $this->assertCount(3, $this->alerts->records);
        $this->assertSame('file_not_writable', $this->alerts->records[0]->title);
        $this->assertSame('crop_file_success', $this->alerts->records[1]->title);
        $this->assertSame(['crop_file_success_desc'], $this->alerts->records[1]->body);
        $this->assertSame('shared-form', $this->alerts->records[2]->name);
    }

    /**
     * Assert resize fills a missing height before delegating to filemanager.
     *
     * @return void
     */
    public function testViewResizesWhenHeightIsMissing()
    {
        $this->setModifyRequest('resize', [
            'resize_width' => 60,
            'resize_height' => '',
        ]);
        $this->formValidation->runResult = true;
        $filemanager = new FileManagerRecorder();
        $filemanager->resizeResponse = [
            'dimensions' => ['height' => 30, 'width' => 60],
            'file_info' => ['size' => 5120],
        ];
        ee()->setMock('filemanager', $filemanager);
        $file = new TestFileModel();
        $this->bindFileToModel($file);

        $this->controller->view(35);

        $this->assertSame(30.0, (float) $_POST['resize_height']);
        $this->assertSame(['/var/www/html/banner.png', 'local'], $filemanager->resizeCalls[0]);
        $this->assertSame('30 60', $file->file_hw_original);
        $this->assertSame(5120, $file->file_size);
        $this->assertSame(1, $file->saveCalls);
        $this->assertCount(1, $this->alerts->records);
        $this->assertSame('success', $this->alerts->records[0]->state);
        $this->assertSame('crop_file_success', $this->alerts->records[0]->title);
    }

    /**
     * Assert the AJAX validation path delegates to run_ajax().
     *
     * @return void
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testViewRunsAjaxValidationDuringModify()
    {
        define('ExpressionEngine\\Controller\\Files\\AJAX_REQUEST', true);

        $this->setModifyRequest('rotate', [
            'rotate' => '180',
        ]);
        $this->formValidation->runAjaxThrows = true;
        $file = new TestFileModel();
        $this->bindFileToModel($file);

        $this->expectException(FileModifyAjaxInterceptedException::class);
        $this->expectExceptionMessage('ajax');

        try {
            $this->controller->view(36);
        } finally {
            $this->assertSame(1, $this->formValidation->ajaxCalls);
            $this->assertSame([
                ['rotate', 'lang:rotate', 'required'],
            ], $this->formValidation->rules);
        }
    }

    /**
     * Assert renderUsageForm() builds the usage payload with edit-other links and status fallbacks.
     *
     * @return void
     */
    public function testRenderUsageFormBuildsUsagePayloadWithEditOtherLinksAndFallbacks()
    {
        $permission = new PermissionRecorder([
            'edit_other_entries_channel_id_5' => true,
            'edit_categories' => false,
        ]);
        $view = new ViewFactoryRecorder();
        $tables = new TableServiceRecorder();

        ee()->setMock('Permission', $permission);
        ee()->setMock('View', $view);
        ee()->setMock('CP/Table', $tables);

        $file = $this->makeUsageFile(
            [
                new UsageEntryStub([
                    'title' => 'Allowed & linked',
                    'site_id' => 1,
                    'channel_id' => 5,
                    'author_id' => 99,
                    'entry_id' => 101,
                    'channel_title' => 'News',
                    'status' => 'open',
                    'status_tag' => new UsageStatusStub('<span class="status">Open</span>'),
                ]),
                new UsageEntryStub([
                    'title' => 'Remote & plain',
                    'site_id' => 2,
                    'channel_id' => 8,
                    'author_id' => 7,
                    'entry_id' => 102,
                    'channel_title' => 'Pages',
                    'status' => 'closed',
                ]),
            ],
            [
                new UsageCategoryStub([
                    'cat_name' => 'General & plain',
                    'cat_id' => 11,
                    'group_id' => 4,
                    'group_name' => 'Topics',
                    'can_edit_categories' => 'writer|',
                ]),
            ]
        );

        $output = $this->makeUsageController()->renderUsageFormForTest($file);

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(2, $tables->tables);
        $this->assertSame(\ExpressionEngine\Library\CP\Table::COL_STATUS, $tables->tables[0]['columns']['status']['type']);
        $this->assertStringContainsString('compiled:publish/edit/entry/101', $tables->tables[0]['data'][0]['columns'][0]);
        $this->assertStringContainsString('Allowed &amp; linked', $tables->tables[0]['data'][0]['columns'][0]);
        $this->assertSame('<span class="status">Open</span>', $tables->tables[0]['data'][0]['columns'][2]);
        $this->assertStringNotContainsString('<a href=', $tables->tables[0]['data'][1]['columns'][0]);
        $this->assertStringContainsString('Remote &amp; plain', $tables->tables[0]['data'][1]['columns'][0]);
        $this->assertSame('closed', $tables->tables[0]['data'][1]['columns'][2]);
        $this->assertStringNotContainsString('<a href=', $tables->tables[1]['data'][0]['columns'][0]);
        $this->assertStringContainsString('General &amp; plain', $tables->tables[1]['data'][0]['columns'][0]);
        $this->assertCount(2, $view->renders);
        $this->assertSame('ee:_shared/file/usage-tab', $view->renders[0]['view']);
        $this->assertSame($tables->tables[0], $view->renders[0]['vars']['entries']);
        $this->assertSame($tables->tables[1], $view->renders[0]['vars']['categories']);
        $this->assertSame('_shared/form/section', $view->renders[1]['view']);
        $this->assertSame('rendered:ee:_shared/file/usage-tab', $view->renders[1]['vars']['settings'][0]['fields']['usage_tables']['content']);
    }

    /**
     * Assert renderUsageForm() links self-editable entries and role-matched categories only.
     *
     * @return void
     */
    public function testRenderUsageFormBuildsSelfEditAndRoleMatchedLinks()
    {
        $permission = new PermissionRecorder([
            'edit_other_entries_channel_id_7' => false,
            'edit_self_entries_channel_id_7' => true,
            'edit_categories' => true,
        ], ['writer']);
        $tables = new TableServiceRecorder();

        ee()->setMock('Permission', $permission);
        ee()->setMock('View', new ViewFactoryRecorder());
        ee()->setMock('CP/Table', $tables);

        $file = $this->makeUsageFile(
            [
                new UsageEntryStub([
                    'title' => 'Own entry',
                    'site_id' => 1,
                    'channel_id' => 7,
                    'author_id' => 7,
                    'entry_id' => 201,
                    'channel_title' => 'Articles',
                    'status' => 'draft',
                ]),
                new UsageEntryStub([
                    'title' => 'Not mine',
                    'site_id' => 1,
                    'channel_id' => 7,
                    'author_id' => 8,
                    'entry_id' => 202,
                    'channel_title' => 'Articles',
                    'status' => 'draft',
                ]),
            ],
            [
                new UsageCategoryStub([
                    'cat_name' => 'Writer category',
                    'cat_id' => 21,
                    'group_id' => 9,
                    'group_name' => 'Assignable',
                    'can_edit_categories' => 'writer|admin|',
                ]),
                new UsageCategoryStub([
                    'cat_name' => 'Locked category',
                    'cat_id' => 22,
                    'group_id' => 9,
                    'group_name' => 'Assignable',
                    'can_edit_categories' => 'publisher|',
                ]),
            ]
        );

        $this->makeUsageController()->renderUsageFormForTest($file);

        $this->assertStringContainsString('compiled:publish/edit/entry/201', $tables->tables[0]['data'][0]['columns'][0]);
        $this->assertStringNotContainsString('<a href=', $tables->tables[0]['data'][1]['columns'][0]);
        $this->assertStringContainsString('compiled:categories/edit/9/21', $tables->tables[1]['data'][0]['columns'][0]);
        $this->assertStringNotContainsString('<a href=', $tables->tables[1]['data'][1]['columns'][0]);
        $this->assertSame(['writer', 'admin'], $permission->roleChecks[0]);
        $this->assertSame(['publisher'], $permission->roleChecks[1]);
    }

    /**
     * Assert renderUsageForm() lets super admins edit categories without role checks.
     *
     * @return void
     */
    public function testRenderUsageFormLetsSuperAdminsEditCategoriesWithoutRoleChecks()
    {
        $permission = new PermissionRecorder([
            'edit_categories' => false,
        ], [], true);
        $tables = new TableServiceRecorder();

        ee()->setMock('Permission', $permission);
        ee()->setMock('View', new ViewFactoryRecorder());
        ee()->setMock('CP/Table', $tables);

        $file = $this->makeUsageFile([], [
            new UsageCategoryStub([
                'cat_name' => 'Super admin category',
                'cat_id' => 31,
                'group_id' => 12,
                'group_name' => 'Protected',
                'can_edit_categories' => '',
            ]),
        ]);

        $this->makeUsageController()->renderUsageFormForTest($file);

        $this->assertStringContainsString('compiled:categories/edit/12/31', $tables->tables[1]['data'][0]['columns'][0]);
        $this->assertSame([], $permission->roleChecks);
        $this->assertSame([], array_values(array_filter($permission->canCalls, function ($permissionName) {
            return $permissionName === 'edit_categories';
        })));
    }

    /**
     * Assert renderManipulationsForm() builds manipulation rows, watermark labels, and view links.
     *
     * @return void
     */
    public function testRenderManipulationsFormBuildsManipulationRowsWithWatermarksAndViewLinks()
    {
        $view = new ViewFactoryRecorder();
        $tables = new TableServiceRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('CP/Table', $tables);

        $file = $this->makeManipulationFile(
            [
                new ManipulationStub([
                    'short_name' => 'hero_small',
                    'resize_type' => 'constrain',
                    'width' => 320,
                    'height' => 240,
                    'watermark_id' => 5,
                    'watermark_name' => 'Brand mark',
                ]),
                new ManipulationStub([
                    'short_name' => 'hero_square',
                    'resize_type' => 'crop',
                    'width' => 150,
                    'height' => 150,
                    'watermark_id' => 0,
                ]),
            ],
            [
                'hero_small' => 'https://example.com/manipulations/hero_small',
                'hero_square' => 'https://example.com/manipulations/hero_square',
            ]
        );

        $output = $this->makeManipulationController()->renderManipulationsFormForTest($file);

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(1, $tables->tables);
        $this->assertSame([
            'short_name' => [
                'encode' => false,
                'attrs' => [
                    'width' => '40%',
                ],
            ],
            'type',
            'watermark',
            'view' => [
                'encode' => false,
            ],
        ], $tables->tables[0]['columns']);
        $this->assertSame([
            [
                'attrs' => [],
                'columns' => [
                    'hero_small',
                    'constrain, 320px by 240px',
                    'Brand mark',
                    '<a href="https://example.com/manipulations/hero_small" target="_blank"><i class="fal fa-eye"></i></a>',
                ],
            ],
            [
                'attrs' => [],
                'columns' => [
                    'hero_square',
                    'crop, 150px by 150px',
                    '',
                    '<a href="https://example.com/manipulations/hero_square" target="_blank"><i class="fal fa-eye"></i></a>',
                ],
            ],
        ], $tables->tables[0]['data']);
        $this->assertSame(['hero_small', 'hero_square'], $file->manipulationUrlCalls);
        $this->assertCount(2, $view->renders);
        $this->assertSame('ee:_shared/table', $view->renders[0]['view']);
        $this->assertSame($tables->tables[0], $view->renders[0]['vars']);
        $this->assertSame('_shared/form/section', $view->renders[1]['view']);
        $this->assertSame('existing_file_manipulations_desc', $view->renders[1]['vars']['settings'][0]['desc']);
        $this->assertSame('rendered:ee:_shared/table', $view->renders[1]['vars']['settings'][0]['fields']['usage_tables']['content']);
    }

    /**
     * Assert renderManipulationsForm() still renders an empty table section with no manipulations.
     *
     * @return void
     */
    public function testRenderManipulationsFormRendersEmptyTableWhenNoManipulationsExist()
    {
        $view = new ViewFactoryRecorder();
        $tables = new TableServiceRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('CP/Table', $tables);

        $file = $this->makeManipulationFile([]);

        $output = $this->makeManipulationController()->renderManipulationsFormForTest($file);

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(1, $tables->tables);
        $this->assertSame([], $tables->tables[0]['data']);
        $this->assertSame([], $file->manipulationUrlCalls);
        $this->assertCount(2, $view->renders);
        $this->assertSame('ee:_shared/table', $view->renders[0]['view']);
        $this->assertSame($tables->tables[0], $view->renders[0]['vars']);
        $this->assertSame('_shared/form/section', $view->renders[1]['view']);
        $this->assertSame('rendered:ee:_shared/table', $view->renders[1]['vars']['settings'][0]['fields']['usage_tables']['content']);
    }

    /**
     * Assert renderCropForm() falls back to image dimensions and zero coordinates.
     *
     * @return void
     */
    public function testRenderCropFormUsesImageInfoDefaultsWhenRequestValuesAreMissing()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder());

        $output = $this->makeCropController()->renderCropFormForTest(
            new \stdClass(),
            ['width' => 640, 'height' => 480]
        );

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(1, $view->renders);
        $this->assertSame('_shared/form/section', $view->renders[0]['view']);
        $this->assertSame([
            [
                'title' => 'constraints',
                'desc' => 'crop_constraints_desc',
                'fields' => [
                    'crop_width' => [
                        'type' => 'short-text',
                        'label' => 'crop_width',
                        'value' => 640,
                    ],
                    'crop_height' => [
                        'type' => 'short-text',
                        'label' => 'crop_height',
                        'value' => 480,
                    ],
                ],
            ],
            [
                'title' => 'coordinates',
                'desc' => 'coordiantes_desc',
                'fields' => [
                    'crop_x' => [
                        'type' => 'short-text',
                        'label' => 'x_axis',
                        'value' => 0,
                    ],
                    'crop_y' => [
                        'type' => 'short-text',
                        'label' => 'y_axis',
                        'value' => 0,
                    ],
                ],
            ],
        ], $view->renders[0]['vars']['settings']);
        $this->assertNull($view->renders[0]['vars']['name']);
    }

    /**
     * Assert renderCropForm() prefers posted crop values over image defaults.
     *
     * @return void
     */
    public function testRenderCropFormUsesPostedConstraintAndCoordinateValues()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder([
            'crop_width' => '320',
            'crop_height' => '180',
            'crop_x' => '14',
            'crop_y' => '28',
        ]));

        $this->makeCropController()->renderCropFormForTest(
            new \stdClass(),
            ['width' => 640, 'height' => 480]
        );

        $settings = $view->renders[0]['vars']['settings'];

        $this->assertSame('320', $settings[0]['fields']['crop_width']['value']);
        $this->assertSame('180', $settings[0]['fields']['crop_height']['value']);
        $this->assertSame('14', $settings[1]['fields']['crop_x']['value']);
        $this->assertSame('28', $settings[1]['fields']['crop_y']['value']);
    }

    /**
     * Assert renderRotateForm() renders all rotation choices with no default selection.
     *
     * @return void
     */
    public function testRenderRotateFormBuildsRotationChoicesWithoutPostedValue()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder());

        $output = $this->makeRotateController()->renderRotateFormForTest(new \stdClass());

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(1, $view->renders);
        $this->assertSame('_shared/form/section', $view->renders[0]['view']);
        $this->assertSame([
            [
                'title' => 'rotation',
                'desc' => 'rotation_desc',
                'fields' => [
                    'rotate' => [
                        'type' => 'radio',
                        'choices' => [
                            '270' => '90_degrees_right',
                            '90' => '90_degrees_left',
                            'vrt' => 'flip_vertically',
                            'hor' => 'flip_horizontally',
                        ],
                        'value' => null,
                    ],
                ],
            ],
        ], $view->renders[0]['vars']['settings']);
        $this->assertNull($view->renders[0]['vars']['name']);
    }

    /**
     * Assert renderRotateForm() preserves the posted rotation value in the radio field.
     *
     * @return void
     */
    public function testRenderRotateFormUsesPostedRotationValue()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder([
            'rotate' => 'vrt',
        ]));

        $this->makeRotateController()->renderRotateFormForTest(new \stdClass());

        $settings = $view->renders[0]['vars']['settings'];

        $this->assertSame('vrt', $settings[0]['fields']['rotate']['value']);
        $this->assertSame([
            '270' => '90_degrees_right',
            '90' => '90_degrees_left',
            'vrt' => 'flip_vertically',
            'hor' => 'flip_horizontally',
        ], $settings[0]['fields']['rotate']['choices']);
    }

    /**
     * Assert renderResizeForm() falls back to the file dimensions when nothing is posted.
     *
     * @return void
     */
    public function testRenderResizeFormBuildsConstraintFieldsFromFileDimensions()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder());

        $output = $this->makeResizeController()->renderResizeFormForTest(
            new \stdClass(),
            ['width' => 1920, 'height' => 1080]
        );

        $this->assertSame('rendered:_shared/form/section', $output);
        $this->assertCount(1, $view->renders);
        $this->assertSame('_shared/form/section', $view->renders[0]['view']);
        $this->assertSame([
            [
                'title' => 'constraints',
                'desc' => 'crop_constraints_desc',
                'fields' => [
                    'resize_width' => [
                        'type' => 'short-text',
                        'label' => 'resize_width',
                        'value' => 1920,
                    ],
                    'resize_height' => [
                        'type' => 'short-text',
                        'label' => 'resize_height',
                        'value' => 1080,
                    ],
                ],
            ],
        ], $view->renders[0]['vars']['settings']);
        $this->assertNull($view->renders[0]['vars']['name']);
    }

    /**
     * Assert renderResizeForm() preserves posted width and height values.
     *
     * @return void
     */
    public function testRenderResizeFormUsesPostedConstraintValues()
    {
        $view = new ViewFactoryRecorder();

        ee()->setMock('View', $view);
        ee()->setMock('Request', new RequestRecorder([
            'resize_width' => '640',
            'resize_height' => '360',
        ]));

        $this->makeResizeController()->renderResizeFormForTest(
            new \stdClass(),
            ['width' => 1920, 'height' => 1080]
        );

        $settings = $view->renders[0]['vars']['settings'];

        $this->assertSame('640', $settings[0]['fields']['resize_width']['value']);
        $this->assertSame('360', $settings[0]['fields']['resize_height']['value']);
        $this->assertSame('short-text', $settings[0]['fields']['resize_width']['type']);
        $this->assertSame('short-text', $settings[0]['fields']['resize_height']['type']);
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

    /**
     * Route a modify action through the request double and $_POST.
     *
     * @param string $action
     * @param array<string, mixed> $post
     * @param array<string, mixed> $get
     * @return void
     */
    private function setModifyRequest(string $action, array $post = [], array $get = []): void
    {
        $_POST = array_merge(['action' => $action], $post);
        $_GET = $get;
        ee()->setMock('Request', new RequestRecorder($_POST, $_GET));
    }

    /**
     * Invoke the private modify() method for unreachable guard coverage.
     *
     * @param TestFileModel $file
     * @param string $action
     * @return void
     *
     * @throws \ReflectionException
     */
    private function invokeModify(TestFileModel $file, string $action): void
    {
        $method = new \ReflectionMethod(\ExpressionEngine\Controller\Files\File::class, 'modify');
        \TestReflectionHelper::makeAccessible($method);
        $method->invoke($this->controller, $file, $action);
    }

    /**
     * Invoke the controller image-dimension guard.
     *
     * @param mixed $info
     * @return bool
     *
     * @throws \ReflectionException
     */
    private function hasUsableImageProperties($info)
    {
        $reflection = new \ReflectionClass('ExpressionEngine\Controller\Files\File');
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('hasUsableImageProperties');
        \TestReflectionHelper::makeAccessible($method);

        return $method->invoke($controller, $info);
    }

    /**
     * Create a controller double that exposes the real renderUsageForm() implementation.
     *
     * @return UsageFormFileController
     */
    private function makeUsageController(): UsageFormFileController
    {
        return (new \ReflectionClass(UsageFormFileController::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Create a controller double that exposes the real renderManipulationsForm() implementation.
     *
     * @return ManipulationFormFileController
     */
    private function makeManipulationController(): ManipulationFormFileController
    {
        return (new \ReflectionClass(ManipulationFormFileController::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Create a controller double that exposes the real renderCropForm() implementation.
     *
     * @return CropFormFileController
     */
    private function makeCropController(): CropFormFileController
    {
        return (new \ReflectionClass(CropFormFileController::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Create a controller double that exposes the real renderRotateForm() implementation.
     *
     * @return RotateFormFileController
     */
    private function makeRotateController(): RotateFormFileController
    {
        return (new \ReflectionClass(RotateFormFileController::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Create a controller double that exposes the real renderResizeForm() implementation.
     *
     * @return ResizeFormFileController
     */
    private function makeResizeController(): ResizeFormFileController
    {
        return (new \ReflectionClass(ResizeFormFileController::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Build a file stub for renderManipulationsForm() data.
     *
     * @param array<int, ManipulationStub> $manipulations
     * @param array<string, string> $manipulationUrls
     * @return ManipulationFileStub
     */
    private function makeManipulationFile(array $manipulations, array $manipulationUrls = []): ManipulationFileStub
    {
        return new ManipulationFileStub(
            new UploadDestinationStub([
                'FileDimensions' => new ManipulationCollectionStub($manipulations),
            ]),
            $manipulationUrls
        );
    }

    /**
     * Build a file stub for renderUsageForm() entry and category data.
     *
     * @param array<int, UsageEntryStub> $entries
     * @param array<int, UsageCategoryStub> $categories
     * @return object
     */
    private function makeUsageFile(array $entries, array $categories): object
    {
        return new class($entries, $categories) {
            /** @var array<int, UsageEntryStub> */
            public $FileEntries;

            /** @var array<int, UsageCategoryStub> */
            public $FileCategories;

            /**
             * Store the usage data collections.
             *
             * @param array<int, UsageEntryStub> $entries
             * @param array<int, UsageCategoryStub> $categories
             * @return void
             */
            public function __construct(array $entries, array $categories)
            {
                $this->FileEntries = $entries;
                $this->FileCategories = $categories;
            }
        };
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
 * Exposes the real renderUsageForm() implementation for targeted tests.
 */
class UsageFormFileController extends \ExpressionEngine\Controller\Files\File
{
    /**
     * Call the parent implementation through a public test seam.
     *
     * @param mixed $file
     * @return string
     */
    public function renderUsageFormForTest($file)
    {
        return parent::renderUsageForm($file);
    }
}

/**
 * Exposes the real renderManipulationsForm() implementation for targeted tests.
 */
class ManipulationFormFileController extends \ExpressionEngine\Controller\Files\File
{
    /**
     * Call the parent implementation through a public test seam.
     *
     * @param mixed $file
     * @return string
     */
    public function renderManipulationsFormForTest($file)
    {
        return parent::renderManipulationsForm($file);
    }
}

/**
 * Exposes the real renderCropForm() implementation for targeted tests.
 */
class CropFormFileController extends \ExpressionEngine\Controller\Files\File
{
    /**
     * Call the parent implementation through a public test seam.
     *
     * @param mixed $file
     * @param array<string, mixed> $info
     * @return string
     */
    public function renderCropFormForTest($file, array $info)
    {
        return parent::renderCropForm($file, $info);
    }
}

/**
 * Exposes the real renderRotateForm() implementation for targeted tests.
 */
class RotateFormFileController extends \ExpressionEngine\Controller\Files\File
{
    /**
     * Call the parent implementation through a public test seam.
     *
     * @param mixed $file
     * @return string
     */
    public function renderRotateFormForTest($file)
    {
        return parent::renderRotateForm($file);
    }
}

/**
 * Exposes the real renderResizeForm() implementation for targeted tests.
 */
class ResizeFormFileController extends \ExpressionEngine\Controller\Files\File
{
    /**
     * Call the parent implementation through a public test seam.
     *
     * @param mixed $file
     * @param array<string, mixed> $info
     * @return string
     */
    public function renderResizeFormForTest($file, array $info)
    {
        return parent::renderResizeForm($file, $info);
    }
}

/**
 * Raised when saveFileAndRedirect() is intentionally intercepted.
 */
class FileRedirectInterceptedException extends \RuntimeException
{
}

/**
 * Raised when the AJAX validation branch is intentionally intercepted.
 */
class FileModifyAjaxInterceptedException extends \RuntimeException
{
}

/**
 * Records permission checks for the controller.
 */
class PermissionRecorder
{
    /** @var array<string, bool> */
    private $permissions;

    /** @var array<int, string> */
    private $roles;

    /** @var bool */
    private $superAdmin;

    /** @var array<int, string> */
    public $canCalls = [];

    /** @var array<int, array<int, string>> */
    public $roleChecks = [];

    /**
     * Store permission answers by key.
     *
     * @param array<string, bool> $permissions
     * @param array<int, string> $roles
     * @param bool $superAdmin
     * @return void
     */
    public function __construct(array $permissions, array $roles = [], $superAdmin = false)
    {
        $this->permissions = $permissions;
        $this->roles = $roles;
        $this->superAdmin = (bool) $superAdmin;
    }

    /**
     * Return the configured permission flag.
     *
     * @param string $permission
     * @return bool
     */
    public function can($permission)
    {
        $this->canCalls[] = $permission;

        return $this->permissions[$permission] ?? true;
    }

    /**
     * Return the configured super admin state.
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->superAdmin;
    }

    /**
     * Return whether any requested role is assigned.
     *
     * @param array<int, string> $roles
     * @return bool
     */
    public function hasAnyRole(array $roles)
    {
        $this->roleChecks[] = $roles;

        return count(array_intersect($roles, $this->roles)) > 0;
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

    /**
     * Return member userdata values used by renderUsageForm().
     *
     * @param string $key
     * @return mixed
     */
    public function userdata($key)
    {
        return $this->member->$key ?? null;
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
     * @param mixed $default
     * @return mixed
     */
    public function post($key, $default = null)
    {
        return $this->post[$key] ?? $default;
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
 * Returns predictable formatters for the requested value type.
 */
class FormatRecorder
{
    /**
     * Return a formatter for the provided value.
     *
     * @param string $type
     * @param mixed $value
     * @return ByteValue|TextValue
     */
    public function make($type, $value)
    {
        if ($type === 'Text') {
            return new TextValue($value);
        }

        return new ByteValue($value);
    }
}

/**
 * Formats plain text for HTML output assertions.
 */
class TextValue
{
    /** @var mixed */
    private $value;

    /**
     * Store the original text value.
     *
     * @param mixed $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return the text with HTML entities encoded.
     *
     * @return string
     */
    public function convertToEntities()
    {
        return htmlspecialchars((string) $this->value, ENT_QUOTES, 'UTF-8');
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

    /** @var bool */
    public $runResult = false;

    /** @var bool */
    public $errorsExist = false;

    /** @var int */
    public $ajaxCalls = 0;

    /** @var bool */
    public $runAjaxThrows = false;

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
        return $this->runResult;
    }

    /**
     * Return false so modify() does not emit an alert.
     *
     * @return bool
     */
    public function errors_exist()
    {
        return $this->errorsExist;
    }

    /**
     * Satisfy the AJAX branch contract if it is ever reached.
     *
     * @return void
     */
    public function run_ajax()
    {
        $this->ajaxCalls++;

        if ($this->runAjaxThrows) {
            throw new FileModifyAjaxInterceptedException('ajax');
        }
    }
}

/**
 * Records legacy filemanager manipulation calls.
 */
class FileManagerRecorder
{
    /** @var array<int, array<int, string>> */
    public $cropCalls = [];

    /** @var array<int, array<int, string>> */
    public $rotateCalls = [];

    /** @var array<int, array<int, string>> */
    public $resizeCalls = [];

    /** @var array<int, array<int, mixed>> */
    public $createThumbCalls = [];

    /** @var array<string, mixed> */
    public $cropResponse = [];

    /** @var array<string, mixed> */
    public $rotateResponse = [];

    /** @var array<string, mixed> */
    public $resizeResponse = [];

    /**
     * Record crop arguments and return the configured response.
     *
     * @param string $path
     * @param string $filesystem
     * @return array<string, mixed>
     */
    public function _do_crop($path, $filesystem)
    {
        $this->cropCalls[] = [$path, $filesystem];

        return $this->cropResponse;
    }

    /**
     * Record rotate arguments and return the configured response.
     *
     * @param string $path
     * @param string $filesystem
     * @return array<string, mixed>
     */
    public function _do_rotate($path, $filesystem)
    {
        $this->rotateCalls[] = [$path, $filesystem];

        return $this->rotateResponse;
    }

    /**
     * Record resize arguments and return the configured response.
     *
     * @param string $path
     * @param string $filesystem
     * @return array<string, mixed>
     */
    public function _do_resize($path, $filesystem)
    {
        $this->resizeCalls[] = [$path, $filesystem];

        return $this->resizeResponse;
    }

    /**
     * Record thumbnail regeneration arguments.
     *
     * @param string $path
     * @param array<string, mixed> $config
     * @param bool $regenerate
     * @param bool $all
     * @return void
     */
    public function create_thumb($path, array $config, $regenerate, $all)
    {
        $this->createThumbCalls[] = [$path, $config, (bool) $regenerate, (bool) $all];
    }
}

/**
 * Records CP table definitions and sequential view payload requests.
 */
class TableServiceRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $tables = [];

    /** @var int */
    private $tableIndex = -1;

    /** @var int */
    private $viewIndex = 0;

    /**
     * Start a new table definition and store the requested columns.
     *
     * @param array<string, mixed> $columns
     * @return self
     */
    public function setColumns($columns)
    {
        $this->tableIndex++;
        $this->tables[$this->tableIndex] = [
            'columns' => $columns,
            'data' => [],
        ];

        return $this;
    }

    /**
     * Store table rows for the current table.
     *
     * @param array<int, array<string, mixed>> $data
     * @return self
     */
    public function setData($data)
    {
        $this->tables[$this->tableIndex]['data'] = $data;

        return $this;
    }

    /**
     * Return the next stored table payload.
     *
     * @return array<string, mixed>
     */
    public function viewData()
    {
        $table = $this->tables[$this->viewIndex] ?? [
            'columns' => [],
            'data' => [],
        ];
        $this->viewIndex++;

        return $table;
    }
}

/**
 * Records view renders and returns predictable template output.
 */
class ViewFactoryRecorder
{
    /** @var array<int, array<string, mixed>> */
    public $renders = [];

    /**
     * Return a template recorder for the requested view.
     *
     * @param string $view
     * @return ViewTemplateRecorder
     */
    public function make($view)
    {
        return new ViewTemplateRecorder($this, $view);
    }
}

/**
 * Captures view render arguments for assertions.
 */
class ViewTemplateRecorder
{
    /** @var ViewFactoryRecorder */
    private $factory;

    /** @var string */
    private $view;

    /**
     * Store the owning factory and view name.
     *
     * @param ViewFactoryRecorder $factory
     * @param string $view
     * @return void
     */
    public function __construct(ViewFactoryRecorder $factory, $view)
    {
        $this->factory = $factory;
        $this->view = $view;
    }

    /**
     * Record the render arguments and return a deterministic string.
     *
     * @param array<string, mixed> $vars
     * @return string
     */
    public function render($vars = [])
    {
        $this->factory->renders[] = [
            'view' => $this->view,
            'vars' => $vars,
        ];

        return 'rendered:' . $this->view;
    }
}

/**
 * Entry stub for renderUsageForm() coverage.
 */
class UsageEntryStub
{
    /** @var string */
    public $title;

    /** @var int */
    public $site_id;

    /** @var int */
    public $channel_id;

    /** @var int */
    public $author_id;

    /** @var int */
    public $entry_id;

    /** @var string */
    public $status;

    /** @var object */
    public $Channel;

    /** @var UsageStatusStub|null */
    private $statusTag;

    /**
     * Store entry attributes used by the controller.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->title = $attributes['title'];
        $this->site_id = $attributes['site_id'];
        $this->channel_id = $attributes['channel_id'];
        $this->author_id = $attributes['author_id'];
        $this->entry_id = $attributes['entry_id'];
        $this->status = $attributes['status'];
        $this->statusTag = $attributes['status_tag'] ?? null;
        $this->Channel = (object) [
            'channel_title' => $attributes['channel_title'],
        ];
    }

    /**
     * Return the configured status object.
     *
     * @return UsageStatusStub|null
     */
    public function getStatus()
    {
        return $this->statusTag;
    }
}

/**
 * Status stub for renderUsageForm() tag rendering.
 */
class UsageStatusStub
{
    /** @var string */
    private $tag;

    /**
     * Store the rendered tag.
     *
     * @param string $tag
     * @return void
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Return the configured status tag markup.
     *
     * @return string
     */
    public function renderTag()
    {
        return $this->tag;
    }
}

/**
 * Category stub for renderUsageForm() coverage.
 */
class UsageCategoryStub
{
    /** @var string */
    public $cat_name;

    /** @var int */
    public $cat_id;

    /** @var int */
    public $group_id;

    /** @var object */
    public $CategoryGroup;

    /**
     * Store category attributes used by the controller.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->cat_name = $attributes['cat_name'];
        $this->cat_id = $attributes['cat_id'];
        $this->group_id = $attributes['group_id'];
        $this->CategoryGroup = (object) [
            'group_name' => $attributes['group_name'],
            'can_edit_categories' => $attributes['can_edit_categories'],
        ];
    }
}

/**
 * File stub for renderManipulationsForm() coverage.
 */
class ManipulationFileStub
{
    /** @var UploadDestinationStub */
    public $UploadDestination;

    /** @var array<int, string> */
    public $manipulationUrlCalls = [];

    /** @var array<string, string> */
    private $manipulationUrls;

    /**
     * Store the upload destination and per-manipulation URLs.
     *
     * @param UploadDestinationStub $uploadDestination
     * @param array<string, string> $manipulationUrls
     * @return void
     */
    public function __construct(UploadDestinationStub $uploadDestination, array $manipulationUrls = [])
    {
        $this->UploadDestination = $uploadDestination;
        $this->manipulationUrls = $manipulationUrls;
    }

    /**
     * Record the short name lookup and return a predictable URL.
     *
     * @param string $shortName
     * @return string
     */
    public function getAbsoluteManipulationURL($shortName)
    {
        $shortName = (string) $shortName;
        $this->manipulationUrlCalls[] = $shortName;

        return $this->manipulationUrls[$shortName] ?? 'compiled:files/manipulation/' . $shortName;
    }
}

/**
 * Manipulation stub for renderManipulationsForm() coverage.
 */
class ManipulationStub
{
    /** @var string */
    public $short_name;

    /** @var string */
    public $resize_type;

    /** @var int */
    public $width;

    /** @var int */
    public $height;

    /** @var int */
    public $watermark_id;

    /** @var object */
    public $Watermark;

    /**
     * Store manipulation attributes used by the controller.
     *
     * @param array<string, mixed> $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->short_name = $attributes['short_name'];
        $this->resize_type = $attributes['resize_type'];
        $this->width = $attributes['width'];
        $this->height = $attributes['height'];
        $this->watermark_id = $attributes['watermark_id'] ?? 0;
        $this->Watermark = (object) [
            'wm_name' => $attributes['watermark_name'] ?? '',
        ];
    }
}

/**
 * Iterable manipulation collection for renderManipulationsForm() coverage.
 */
class ManipulationCollectionStub implements \IteratorAggregate
{
    /** @var array<int, ManipulationStub> */
    private $items;

    /**
     * Store the configured manipulation rows.
     *
     * @param array<int, ManipulationStub> $items
     * @return void
     */
    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Return the configured manipulation iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
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

    /** @var int */
    public $saveCalls = 0;

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

    /**
     * Record save() calls without touching persistence.
     *
     * @return bool
     */
    public function save()
    {
        $this->saveCalls++;

        return true;
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
