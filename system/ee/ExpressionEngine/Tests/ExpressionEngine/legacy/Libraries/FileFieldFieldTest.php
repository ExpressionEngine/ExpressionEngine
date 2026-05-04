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

    public function __construct($tag = 'thumb-tag')
    {
        $this->tag = $tag;
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

        return (object) ['tag' => $this->tag];
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

    /** @var FileFieldModelMock */
    private $fileModelMock;

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

        $this->fileModelMock = new FileFieldModelMock([
            'results' => new FileFieldResultRowsMock([]),
        ]);
        ee()->setMock('file_model', $this->fileModelMock);

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
}
