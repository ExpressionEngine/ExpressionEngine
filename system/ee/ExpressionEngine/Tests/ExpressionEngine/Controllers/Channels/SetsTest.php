<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Channels {

use ExpressionEngine\Controller\Channels\Sets;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class SetsTest extends TestCase
{
    protected $controller;

    public static function setUpBeforeClass(): void
    {
        // Define constants if they don't exist
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(__DIR__ . '/../../../../../../') . '/');
        }
        if (!defined('APPPATH')) {
            define('APPPATH', SYSPATH . 'ee/ExpressionEngine/');
        }
        if (!defined('BASEPATH')) {
             define('BASEPATH', SYSPATH . 'ee/legacy/');
        }
        if (!defined('PATH_THIRD')) {
            define('PATH_THIRD', SYSPATH . 'user/addons/');
        }
        if (!defined('EE_APPPATH')) {
             define('EE_APPPATH', APPPATH);
        }

        // Include necessary files
        if (!class_exists('EE_Controller')) {
            require_once SYSPATH . 'ee/legacy/core/Controller.php';
        }
        require_once SYSPATH . 'ee/ExpressionEngine/Tests/eeObjectMock.php';
    }

    public function setUp(): void
    {
        ee()->resetMocks();
        
        $this->setupCoreMocks();
        
        // Instantiate the controller
        $this->controller = new Sets();
    }

    public function tearDown(): void
    {
        m::close();
        ee()->resetMocks();
    }

    protected function setupCoreMocks()
    {
        // Mock Core (Property)
        $core = m::mock('stdClass');
        $core->shouldReceive('bootstrap')->andReturn(true);
        $core->shouldReceive('run_ee')->andReturn(true);
        $core->shouldReceive('run_cp')->andReturn(true);
        ee()->setMock('core', $core);

        // Mock Extensions (Property)
        $extensions = m::mock('stdClass');
        $extensions->shouldReceive('active_hook')->andReturn(false);
        ee()->setMock('extensions', $extensions);

        // Mock Permission (Service - __call)
        $permission = $this->getMockBuilder('stdClass')
            ->addMethods(['has', 'can', 'hasAny', 'hasAll'])
            ->getMock();
        $permission->method('has')->willReturn(true);
        $permission->method('can')->willReturn(true);
        $permission->method('hasAny')->willReturn(true);
        $permission->method('hasAll')->willReturn(true);
        ee()->setMock('Permission', $permission);

        // Mock Router (Property)
        $router = m::mock('stdClass');
        $router->method = 'index';
        $router->class = 'channels';
        ee()->setMock('router', $router);

        // Mock Lang (Property)
        $lang = m::mock('stdClass');
        $lang->shouldReceive('loadfile');
        $lang->shouldReceive('line')->andReturnUsing(function ($key) {
            return $key;
        });
        ee()->setMock('lang', $lang);

        // Mock Load (Property)
        $load = m::mock('stdClass');
        $load->shouldReceive('library');
        $load->shouldReceive('helper');
        ee()->setMock('load', $load);

        // Mock View (Property)
        $view = new \stdClass();
        $view->header = [];
        $view->cp_breadcrumbs = [];
        $view->cp_page_title = '';
        ee()->setMock('view', $view);

        // Mock JavaScript (Property)
        $javascript = m::mock('stdClass');
        $javascript->shouldReceive('set_global');
        ee()->setMock('javascript', $javascript);

        // Mock CP (Property)
        $cp = m::mock('stdClass');
        $cp->shouldReceive('add_js_script');
        $cp->shouldReceive('render');
        ee()->setMock('cp', $cp);

        // Mock CP/URL (Service - __call)
        $url = $this->getMockBuilder('stdClass')
            ->addMethods(['make', 'compile', 'addQueryStringVariables'])
            ->getMock();
        $url->method('make')->willReturn($url);
        $url->method('compile')->willReturn('http://example.com/admin.php');
        $url->method('addQueryStringVariables')->willReturn($url);
        ee()->setMock('CP/URL', $url);

        // Mock Config (Property)
        ee()->config->setItem('site_id', 1);
        ee()->config->setItem('session_crypt_key', 'test_key');
        
        // Mock Session (Property)
        $session = m::mock('stdClass');
        $session->shouldReceive('set_flashdata');
        ee()->setMock('session', $session);

        // Mock Functions (Property)
        $functions = m::mock('stdClass');
        $functions->shouldReceive('redirect');
        ee()->setMock('functions', $functions);

        // Mock CP/Alert (Service - __call)
        $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeDeprecationNotice', 'now', 'makeInline', 'asIssue', 'withTitle', 'addToBody', 'defer', 'asSuccess'])
            ->getMock();
        $alert->method('makeDeprecationNotice')->willReturn($alert);
        $alert->method('now')->willReturn($alert);
        $alert->method('makeInline')->willReturn($alert);
        $alert->method('asIssue')->willReturn($alert);
        $alert->method('withTitle')->willReturn($alert);
        $alert->method('addToBody')->willReturn($alert);
        $alert->method('defer')->willReturn($alert);
        $alert->method('asSuccess')->willReturn($alert);
        ee()->setMock('CP/Alert', $alert);
    }

    public function testIndexRendersForm()
    {
        // Mock empty $_FILES
        $_FILES = [];

        // Expect render to be called
        $cp = m::mock('stdClass');
        $cp->shouldReceive('add_js_script');
        $cp->shouldReceive('render')->once()->with('settings/form', m::type('array'));
        ee()->setMock('cp', $cp);

        $this->controller->index();
        
        $this->assertTrue(true); // Assert something to avoid risky test warning
    }

    public function testIndexWithUploadError()
    {
        // Mock $_FILES
        $_FILES = ['set_file' => ['name' => 'test.txt']];

        // Mock Request (Service - __call)
        $request = $this->getMockBuilder('stdClass')
            ->addMethods(['file'])
            ->getMock();
        $request->method('file')->with('set_file')->willReturn(['name' => 'test.txt']);
        ee()->setMock('Request', $request);

        // Mock Validation (Service - __call)
        $result = m::mock('ExpressionEngine\Service\Validation\Result');
        $result->shouldReceive('isNotValid')->andReturn(true);

        $validator = $this->getMockBuilder('stdClass')
             ->addMethods(['validate'])
             ->getMock();
        $validator->method('validate')->willReturn($result);
        
        $validation = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        $validation->method('make')->willReturn($validator);
        ee()->setMock('Validation', $validation);

        // Mock Alert - Verify calls
        $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'now'])
            ->getMock();
        $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
        $alert->expects($this->once())->method('asIssue')->willReturnSelf();
        $alert->expects($this->once())->method('withTitle')->willReturnSelf();
        $alert->expects($this->once())->method('addToBody')->willReturnSelf();
        $alert->expects($this->once())->method('now');
        ee()->setMock('CP/Alert', $alert);

        $this->controller->index();
    }

    public function testIndexWithInvalidExtension()
    {
        // Mock $_FILES
        $_FILES = ['set_file' => ['name' => 'test.txt']];

        // Mock Request (Service - __call)
        $request = $this->getMockBuilder('stdClass')
            ->addMethods(['file'])
            ->getMock();
        $request->method('file')->with('set_file')->willReturn(['name' => 'test.txt']);
        ee()->setMock('Request', $request);

        // Mock Validation (Service - __call)
        $result = m::mock('ExpressionEngine\Service\Validation\Result');
        $result->shouldReceive('isNotValid')->andReturn(false);

        $validator = $this->getMockBuilder('stdClass')
             ->addMethods(['validate'])
             ->getMock();
        $validator->method('validate')->willReturn($result);
        
        $validation = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        $validation->method('make')->willReturn($validator);
        ee()->setMock('Validation', $validation);

        // Mock Alert - Verify calls
        $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'now'])
            ->getMock();
        $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
        $alert->expects($this->once())->method('asIssue')->willReturnSelf();
        $alert->expects($this->once())->method('withTitle')->willReturnSelf();
        $alert->expects($this->once())->method('addToBody')->willReturnSelf();
        $alert->expects($this->once())->method('now');
        ee()->setMock('CP/Alert', $alert);

        $this->controller->index();
    }

    public function testIndexWithValidUpload()
    {
        // Mock $_FILES
        $_FILES = ['set_file' => ['name' => 'test.zip']];

        // Mock Request
        $request = $this->getMockBuilder('stdClass')
            ->addMethods(['file'])
            ->getMock();
        $request->method('file')->with('set_file')->willReturn(['name' => 'test.zip']);
        ee()->setMock('Request', $request);

        // Mock Validation
        $result = m::mock('ExpressionEngine\Service\Validation\Result');
        $result->shouldReceive('isNotValid')->andReturn(false);

        $validator = $this->getMockBuilder('stdClass')
             ->addMethods(['validate'])
             ->getMock();
        $validator->method('validate')->willReturn($result);
        
        $validation = $this->getMockBuilder('stdClass')
            ->addMethods(['make'])
            ->getMock();
        $validation->method('make')->willReturn($validator);
        ee()->setMock('Validation', $validation);

        // Mock ChannelSet
        $set = m::mock('stdClass');
        $set->shouldReceive('getPath')->andReturn('/path/to/set');
        
        $channelSet = $this->getMockBuilder('stdClass')
            ->addMethods(['importUpload'])
            ->getMock();
        $channelSet->method('importUpload')->willReturn($set);
        ee()->setMock('ChannelSet', $channelSet);

        // Mock Encrypt
        $encrypt = $this->getMockBuilder('stdClass')
            ->addMethods(['encode'])
            ->getMock();
        $encrypt->method('encode')->willReturn('encoded_path');
        ee()->setMock('Encrypt', $encrypt);

        // Mock Redirect
        // Overwrite functions mock
        $functions = m::mock('stdClass');
        $functions->shouldReceive('redirect')->once();
        ee()->setMock('functions', $functions);

        $this->controller->index();
        
        $this->assertTrue(true); // Assert something to avoid risky test warning
    }

    public function testExportWithNoChannelId()
    {
        // Mock Alert
        $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'defer'])
            ->getMock();
        $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
        $alert->expects($this->once())->method('asIssue')->willReturnSelf();
        $alert->expects($this->once())->method('withTitle')->willReturnSelf();
        $alert->expects($this->once())->method('addToBody')->willReturnSelf();
        $alert->expects($this->once())->method('defer');
        ee()->setMock('CP/Alert', $alert);

        // Mock Redirect
        $functions = m::mock('stdClass');
        $functions->shouldReceive('redirect')->once()->andThrow(new \Exception('Redirect called'));
        ee()->setMock('functions', $functions);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called');

        $this->controller->export(null);
    }
    
    public function testExportWithValidChannelId()
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'ee_set_test_');
        file_put_contents($tempFile, 'test data');

        // Mock Channel Model retrieval
        $channel = m::mock('stdClass');
        
        // Mock Model service chaining
        $model = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'filter', 'first'])
            ->getMock();
        
        $model->method('get')->willReturn($model);
        $model->method('filter')->willReturn($model);
        $model->method('first')->willReturn($channel);
        
        ee()->setMock('Model', $model);

        // Mock ChannelSet export
        $channelSet = $this->getMockBuilder('stdClass')
            ->addMethods(['export'])
            ->getMock();
        $channelSet->method('export')->willReturn($tempFile);
        ee()->setMock('ChannelSet', $channelSet);

        // Mock Load Helper (redundant but explicit)
        ee()->load->shouldReceive('helper')->with('download');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Force download called');

        try {
            $this->controller->export(1);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testDoImportWithInvalidPath()
    {
        // Mock Request
        $request = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $request->method('get')->with('set_path')->willReturn('encoded_path');
        ee()->setMock('Request', $request);

        // Mock Encrypt
        $encrypt = $this->getMockBuilder('stdClass')
            ->addMethods(['decode'])
            ->getMock();
        $encrypt->method('decode')->willReturn(false); // return false or invalid path
        ee()->setMock('Encrypt', $encrypt);

        // Mock Alert
        $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'defer'])
            ->getMock();
        $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
        $alert->expects($this->once())->method('asIssue')->willReturnSelf();
        $alert->expects($this->once())->method('withTitle')->willReturnSelf();
        $alert->expects($this->once())->method('addToBody')->willReturnSelf();
        $alert->expects($this->once())->method('defer');
        ee()->setMock('CP/Alert', $alert);

        // Mock Redirect
        $functions = m::mock('stdClass');
        $functions->shouldReceive('redirect')->once()->andThrow(new \Exception('Redirect called'));
        ee()->setMock('functions', $functions);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Redirect called');

        $this->controller->doImport();
    }

    public function testDoImportValidatesAndSaves()
    {
         // Mock Request
         $request = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
         $request->method('get')->with('set_path')->willReturn('encoded_path');
         ee()->setMock('Request', $request);
 
         // Mock Encrypt
         $encrypt = $this->getMockBuilder('stdClass')
            ->addMethods(['decode', 'encode'])
            ->getMock();
         // We need a valid path that exists. Use __FILE__ as a placeholder existing file
         $encrypt->method('decode')->willReturn(__FILE__);
         $encrypt->method('encode')->willReturn('encoded_path');
         ee()->setMock('Encrypt', $encrypt);

         // Mock ChannelSet
         $set = m::mock('stdClass');
         $set->shouldReceive('setAliases');
         $set->shouldReceive('save');
         $set->shouldReceive('cleanUpSourceFiles');
         $set->shouldReceive('getIdsForElementType')->andReturn([]);
         $set->shouldReceive('getPath')->andReturn('/path/to/set');
         
         $channelSet = $this->getMockBuilder('stdClass')
            ->addMethods(['importDir'])
            ->getMock();
         $channelSet->method('importDir')->willReturn($set);
         ee()->setMock('ChannelSet', $channelSet);

         // Mock Validation Result
         $result = m::mock('ExpressionEngine\Service\ChannelSet\ImportResult');
         $result->shouldReceive('isValid')->andReturn(true);
         $set->shouldReceive('validate')->andReturn($result);

         // Mock Alert
         $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asSuccess', 'withTitle', 'addToBody', 'defer'])
            ->getMock();
         $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
         $alert->expects($this->once())->method('asSuccess')->willReturnSelf();
         $alert->expects($this->once())->method('withTitle')->willReturnSelf();
         $alert->expects($this->once())->method('addToBody')->willReturnSelf();
         $alert->expects($this->once())->method('defer');
         ee()->setMock('CP/Alert', $alert);

         // Mock Redirect
         $functions = m::mock('stdClass');
         $functions->shouldReceive('redirect')->once()->andThrow(new \Exception('Redirect called'));
         ee()->setMock('functions', $functions);

         $this->expectException(\Exception::class);
         $this->expectExceptionMessage('Redirect called');

         $this->controller->doImport();
    }
    
    public function testDoImportWithRecoverableErrors()
    {
         // Mock Request
         $request = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
         $request->method('get')->with('set_path')->willReturn('encoded_path');
         ee()->setMock('Request', $request);
 
         // Mock Encrypt
         $encrypt = $this->getMockBuilder('stdClass')
            ->addMethods(['decode', 'encode'])
            ->getMock();
         $encrypt->method('decode')->willReturn(__FILE__);
         $encrypt->method('encode')->willReturn('encoded_path');
         ee()->setMock('Encrypt', $encrypt);

         // Mock ChannelSet
         $set = m::mock('stdClass');
         $set->shouldReceive('setAliases');
         $set->shouldReceive('getPath')->andReturn('/path/to/set');
         
         $channelSet = $this->getMockBuilder('stdClass')
            ->addMethods(['importDir'])
            ->getMock();
         $channelSet->method('importDir')->willReturn($set);
         ee()->setMock('ChannelSet', $channelSet);

         // Mock Validation Result
         $result = m::mock('ExpressionEngine\Service\ChannelSet\ImportResult');
         $result->shouldReceive('isValid')->andReturn(false);
         $result->shouldReceive('isRecoverable')->andReturn(true);
         $result->shouldReceive('getRecoverableErrors')->andReturn([]);
         $set->shouldReceive('validate')->andReturn($result);

         // Mock Alert
         $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'now'])
            ->getMock();
         $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
         $alert->expects($this->once())->method('asIssue')->willReturnSelf();
         $alert->expects($this->once())->method('withTitle')->willReturnSelf();
         $alert->expects($this->once())->method('addToBody')->willReturnSelf();
         $alert->expects($this->once())->method('now');
         ee()->setMock('CP/Alert', $alert);
         
         // Should NOT redirect
         // Expect render
         $cp = m::mock('stdClass');
         $cp->shouldReceive('add_js_script');
         $cp->shouldReceive('render')->once()->with('settings/form', m::type('array'));
         ee()->setMock('cp', $cp);
         
         // Mock Format Service (used in createAliasForm)
         $format = $this->getMockBuilder('stdClass')
             ->addMethods(['make'])
             ->getMock();
         $textFormatter = m::mock('stdClass');
         $textFormatter->shouldReceive('convertToEntities')->andReturnSelf();
         $textFormatter->shouldReceive('compile')->andReturn('');
         $format->method('make')->willReturn($textFormatter);
         ee()->setMock('Format', $format);

         $this->controller->doImport();
    }
    
    public function testDoImportWithUnrecoverableErrors()
    {
         // Mock Request
         $request = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
         $request->method('get')->with('set_path')->willReturn('encoded_path');
         ee()->setMock('Request', $request);
 
         // Mock Encrypt
         $encrypt = $this->getMockBuilder('stdClass')
            ->addMethods(['decode', 'encode'])
            ->getMock();
         $encrypt->method('decode')->willReturn(__FILE__);
         ee()->setMock('Encrypt', $encrypt);

         // Mock ChannelSet
         $set = m::mock('stdClass');
         $set->shouldReceive('setAliases');
         $set->shouldReceive('cleanUpSourceFiles');
         $set->shouldReceive('getPath')->andReturn('/path/to/set');
         
         $channelSet = $this->getMockBuilder('stdClass')
            ->addMethods(['importDir'])
            ->getMock();
         $channelSet->method('importDir')->willReturn($set);
         ee()->setMock('ChannelSet', $channelSet);

         // Mock Validation Result
         $result = m::mock('ExpressionEngine\Service\ChannelSet\ImportResult');
         $result->shouldReceive('isValid')->andReturn(false);
         $result->shouldReceive('isRecoverable')->andReturn(false);
         $result->shouldReceive('getErrors')->andReturn(['Error 1']);
         $result->shouldReceive('getModelErrors')->andReturn([]);
         $set->shouldReceive('validate')->andReturn($result);

         // Mock Alert
         $alert = $this->getMockBuilder('stdClass')
            ->addMethods(['makeInline', 'asIssue', 'withTitle', 'addToBody', 'defer'])
            ->getMock();
         $alert->expects($this->once())->method('makeInline')->with('shared-form')->willReturnSelf();
         $alert->expects($this->once())->method('asIssue')->willReturnSelf();
         $alert->expects($this->once())->method('withTitle')->willReturnSelf();
         $alert->expects($this->once())->method('addToBody')->willReturnSelf();
         $alert->expects($this->once())->method('defer');
         ee()->setMock('CP/Alert', $alert);
         
         // Mock Redirect
         $functions = m::mock('stdClass');
         $functions->shouldReceive('redirect')->once()->andThrow(new \Exception('Redirect called'));
         ee()->setMock('functions', $functions);

         $this->expectException(\Exception::class);
         $this->expectExceptionMessage('Redirect called');

         $this->controller->doImport();
    }
}

} // End namespace ExpressionEngine\Tests\Controllers\Channels

namespace {
    // Global functions
    if (!function_exists('lang')) {
        function lang($key) {
            return $key;
        }
    }

    if (!function_exists('show_error')) {
        function show_error($message, $code = 500) {
            throw new \Exception($message, $code);
        }
    }

    if (!function_exists('force_download')) {
        function force_download($filename = '', $data = '') {
            // echo "Downloaded: $filename"; // Debug
            throw new \Exception('Force download called');
        }
    }
}
